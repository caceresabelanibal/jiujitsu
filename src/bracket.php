<?php
/**
 * Llaves de eliminacion simple con byes, siembra estandar y lucha por el bronce.
 */

/** Crea/actualiza divisiones segun los inscriptos verificados del torneo */
function ensure_divisions(int $tournamentId): int {
    $tournament = row('SELECT * FROM tournaments WHERE id = ?', [$tournamentId]);
    $durations = belt_durations_for($tournament);
    $combos = rows('SELECT DISTINCT gender, belt_id, age_division_id, weight_class_id
                    FROM registrations WHERE tournament_id = ? AND verified = 1', [$tournamentId]);
    $created = 0;
    foreach ($combos as $c) {
        $exists = row('SELECT id FROM divisions WHERE tournament_id=? AND gender=? AND belt_id=? AND age_division_id=? AND weight_class_id=?',
            [$tournamentId, $c['gender'], $c['belt_id'], $c['age_division_id'], $c['weight_class_id']]);
        if (!$exists) {
            $belt = row('SELECT * FROM belts WHERE id = ?', [$c['belt_id']]);
            $age = row('SELECT * FROM age_divisions WHERE id = ?', [$c['age_division_id']]);
            $bucket = belt_duration_bucket($belt['code'], (bool)$age['is_kids'], $age['code']);
            $sec = $durations[$bucket] ?? (int)($belt['default_duration_sec'] ?? 300);
            q('INSERT INTO divisions (tournament_id, gender, belt_id, age_division_id, weight_class_id, duration_sec)
               VALUES (?,?,?,?,?,?)',
                [$tournamentId, $c['gender'], $c['belt_id'], $c['age_division_id'], $c['weight_class_id'], $sec]);
            $created++;
        }
    }
    return $created;
}

function division_registrations(int $divisionId): array {
    $d = row('SELECT * FROM divisions WHERE id = ?', [$divisionId]);
    if (!$d) return [];
    return rows('SELECT r.*, a.name AS academy_name FROM registrations r
                 LEFT JOIN tournament_academies a ON a.id = r.academy_id
                 WHERE r.tournament_id=? AND r.verified=1 AND r.gender=? AND r.belt_id=? AND r.age_division_id=? AND r.weight_class_id=?
                 ORDER BY r.name',
        [$d['tournament_id'], $d['gender'], $d['belt_id'], $d['age_division_id'], $d['weight_class_id']]);
}

/** Posiciones de siembra estandar para un bracket de $size (potencia de 2). Devuelve seeds 1-based por slot. */
function seed_positions(int $size): array {
    $pos = [1];
    for ($n = 2; $n <= $size; $n *= 2) {
        $new = [];
        foreach ($pos as $p) {
            $new[] = $p;
            $new[] = $n + 1 - $p;
        }
        $pos = $new;
    }
    return $pos;
}

/**
 * Genera la llave completa de una division.
 * $order: ids de registrations en el orden de siembra (1..N). Con $shuffle se mezclan (boton "aleatorio").
 */
function generate_bracket(int $divisionId, array $order = [], bool $shuffle = false): void {
    $d = row('SELECT * FROM divisions WHERE id = ?', [$divisionId]);
    if (!$d) throw new RuntimeException('Division not found');

    if (!$order) $order = array_column(division_registrations($divisionId), 'id');
    if ($shuffle) shuffle($order);
    $n = count($order);
    if ($n < 2) throw new RuntimeException('need_two_competitors');

    q('DELETE FROM matches WHERE division_id = ?', [$divisionId]);

    $size = 2;
    while ($size < $n) $size *= 2;
    $roundsTotal = (int)log($size, 2);
    $dur = (int)$d['duration_sec'];

    // Crear todos los partidos por ronda
    $ids = []; // [round][slot] => match_id
    for ($r = 1; $r <= $roundsTotal; $r++) {
        $count = (int)($size / (2 ** $r));
        for ($s = 0; $s < $count; $s++) {
            q('INSERT INTO matches (tournament_id, division_id, round, slot, duration_sec, timer_remaining) VALUES (?,?,?,?,?,?)',
                [$d['tournament_id'], $divisionId, $r, $s, $dur, $dur]);
            $ids[$r][$s] = (int)db()->lastInsertId();
        }
    }

    // Lucha por el bronce (si hay semifinales, es decir >= 4 competidores)
    $bronzeId = null;
    if ($roundsTotal >= 2) {
        q('INSERT INTO matches (tournament_id, division_id, round, slot, is_bronze, duration_sec, timer_remaining) VALUES (?,?,?,?,1,?,?)',
            [$d['tournament_id'], $divisionId, $roundsTotal, 1, $dur, $dur]);
        $bronzeId = (int)db()->lastInsertId();
    }

    // Encadenar rondas
    for ($r = 1; $r < $roundsTotal; $r++) {
        foreach ($ids[$r] as $s => $mid) {
            $next = $ids[$r + 1][intdiv($s, 2)];
            $side = ($s % 2 === 0) ? 'red' : 'blue';
            q('UPDATE matches SET next_match_id=?, next_slot=? WHERE id=?', [$next, $side, $mid]);
        }
    }
    // Perdedores de semifinal -> bronce
    if ($bronzeId) {
        $semiRound = $roundsTotal - 1;
        foreach ($ids[$semiRound] as $s => $mid) {
            $side = ($s % 2 === 0) ? 'red' : 'blue';
            q('UPDATE matches SET bronze_match_id=?, bronze_slot=? WHERE id=?', [$bronzeId, $side, $mid]);
        }
    }

    // Sembrar ronda 1 con byes distribuidos
    $slots = seed_positions($size); // seed 1-based por posicion del bracket
    foreach ($ids[1] as $s => $mid) {
        $seedRed = $slots[$s * 2] - 1;
        $seedBlue = $slots[$s * 2 + 1] - 1;
        $red = $order[$seedRed] ?? null;
        $blue = $order[$seedBlue] ?? null;
        q('UPDATE matches SET red_reg_id=?, blue_reg_id=? WHERE id=?', [$red, $blue, $mid]);
    }

    q('UPDATE divisions SET status = "bracketed" WHERE id = ?', [$divisionId]);
    propagate_byes($divisionId);
}

/** Avanza automaticamente los partidos con un solo competidor (bye) */
function propagate_byes(int $divisionId): void {
    $changed = true;
    while ($changed) {
        $changed = false;
        $ms = rows('SELECT * FROM matches WHERE division_id=? AND status="pending"', [$divisionId]);
        foreach ($ms as $m) {
            $red = $m['red_reg_id'];
            $blue = $m['blue_reg_id'];
            if ($red && $blue) continue;
            $solo = $red ?: $blue;
            if (!$solo) continue;
            // Puede llegar todavia un rival de un partido anterior?
            $feeders = (int)scalar('SELECT COUNT(*) FROM matches WHERE division_id=? AND status!="done"
                                    AND ((next_match_id=? ) OR (bronze_match_id=?))',
                [$divisionId, $m['id'], $m['id']]);
            if ($feeders > 0) continue;
            q('UPDATE matches SET winner_reg_id=?, status="done", method="wo" WHERE id=?', [$solo, $m['id']]);
            advance_winner((int)$m['id']);
            $changed = true;
        }
    }
    check_division_done($divisionId);
}

/** Propaga ganador al siguiente partido y perdedor al bronce */
function advance_winner(int $matchId): void {
    $m = row('SELECT * FROM matches WHERE id = ?', [$matchId]);
    if (!$m || !$m['winner_reg_id']) return;
    $loser = ($m['winner_reg_id'] == $m['red_reg_id']) ? $m['blue_reg_id'] : $m['red_reg_id'];

    if ($m['next_match_id']) {
        $col = $m['next_slot'] === 'red' ? 'red_reg_id' : 'blue_reg_id';
        q("UPDATE matches SET $col = ? WHERE id = ?", [$m['winner_reg_id'], $m['next_match_id']]);
    }
    if ($m['bronze_match_id'] && $loser) {
        $col = $m['bronze_slot'] === 'red' ? 'red_reg_id' : 'blue_reg_id';
        q("UPDATE matches SET $col = ? WHERE id = ?", [$loser, $m['bronze_match_id']]);
    }
    check_division_done((int)$m['division_id']);
}

function check_division_done(int $divisionId): void {
    $pending = (int)scalar('SELECT COUNT(*) FROM matches WHERE division_id=? AND status!="done"', [$divisionId]);
    $total = (int)scalar('SELECT COUNT(*) FROM matches WHERE division_id=?', [$divisionId]);
    if ($total > 0) {
        $status = $pending === 0 ? 'done' : 'bracketed';
        q('UPDATE divisions SET status = ? WHERE id = ?', [$status, $divisionId]);
        if ($status === 'done') {
            $tid = scalar('SELECT tournament_id FROM divisions WHERE id = ?', [$divisionId]);
            if ($tid) check_tournament_done((int)$tid);
        }
    }
}

/**
 * Si ya no queda ninguna division con llave por terminar, marca el torneo
 * como finalizado y le manda un mail de agradecimiento al organizador. Se
 * llama automaticamente al cerrar cualquier lucha (via check_division_done())
 * y tambien desde el cron `tournament_status` como red de seguridad.
 */
function check_tournament_done(int $tournamentId): bool {
    $t = row('SELECT * FROM tournaments WHERE id = ?', [$tournamentId]);
    if (!$t || $t['status'] !== 'running') return false;
    $withBracket = (int)scalar('SELECT COUNT(DISTINCT d.id) FROM divisions d JOIN matches m ON m.division_id = d.id WHERE d.tournament_id = ?', [$tournamentId]);
    if ($withBracket === 0) return false;
    $open = (int)scalar("SELECT COUNT(*) FROM divisions d
                         WHERE d.tournament_id = ? AND d.status != 'done'
                         AND EXISTS (SELECT 1 FROM matches m WHERE m.division_id = d.id)", [$tournamentId]);
    if ($open > 0) return false;

    q("UPDATE tournaments SET status = 'finished' WHERE id = ?", [$tournamentId]);
    $owner = row('SELECT * FROM users WHERE id = ?', [$t['user_id']]);
    if ($owner) {
        queue_mail($owner['email'], $owner['name'], t('mail_tournament_done_subject'),
            mail_layout(t('mail_tournament_done_subject'),
                '<p>' . sprintf(t('mail_tournament_done_body1'), e($t['name'])) . '</p>' .
                '<p>' . t('mail_tournament_done_body2') . '</p>' .
                '<p style="text-align:center"><a href="' . APP_URL . '/tournaments/create" style="background:#30a46c;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold">' . t('mail_tournament_done_button') . '</a></p>'));
    }
    return true;
}

/** Podio de una division: [gold, silver, bronze] (reg ids o null) */
function division_podium(int $divisionId): array {
    $final = row('SELECT * FROM matches WHERE division_id=? AND is_bronze=0 ORDER BY round DESC, slot ASC LIMIT 1', [$divisionId]);
    $bronzeM = row('SELECT * FROM matches WHERE division_id=? AND is_bronze=1 LIMIT 1', [$divisionId]);
    $gold = $silver = $bronze = null;
    if ($final && $final['status'] === 'done' && $final['winner_reg_id']) {
        $gold = (int)$final['winner_reg_id'];
        $silver = ($final['winner_reg_id'] == $final['red_reg_id']) ? $final['blue_reg_id'] : $final['red_reg_id'];
        $silver = $silver ? (int)$silver : null;
    }
    if ($bronzeM && $bronzeM['status'] === 'done' && $bronzeM['winner_reg_id']) {
        $bronze = (int)$bronzeM['winner_reg_id'];
    }
    return [$gold, $silver, $bronze];
}

/** Cantidad de luchas reales (sin byes) de un inscripto */
function fights_count(int $regId): int {
    return (int)scalar('SELECT COUNT(*) FROM matches WHERE status="done" AND red_reg_id IS NOT NULL AND blue_reg_id IS NOT NULL
                        AND (red_reg_id=? OR blue_reg_id=?)', [$regId, $regId]);
}

/** Determina el ganador segun puntos > ventajas > menos penalizaciones; null = decision del arbitro */
function infer_winner(array $m): ?string {
    if ($m['red_points'] != $m['blue_points']) return $m['red_points'] > $m['blue_points'] ? 'red' : 'blue';
    if ($m['red_adv'] != $m['blue_adv']) return $m['red_adv'] > $m['blue_adv'] ? 'red' : 'blue';
    if ($m['red_pen'] != $m['blue_pen']) return $m['red_pen'] < $m['blue_pen'] ? 'red' : 'blue';
    return null;
}

/**
 * Orden habitual de las luchas en un torneo de Jiu-Jitsu: primero infantiles y
 * juveniles (todas las categorias/pesos juntos), despues los adultos/masters
 * por cinturon de negro a blanco. Configurable en /admin/settings (general) y
 * por torneo en /tournament/{id}/settings (sobreescribe el general si se guarda).
 */
function division_order_default(): array {
    return ['kids_juvenile', 'black', 'brown', 'purple', 'blue', 'white'];
}

function division_order_labels(): array {
    return [
        'kids_juvenile' => t('div_order_kids_juvenile'),
        'black' => t('div_order_black'),
        'brown' => t('div_order_brown'),
        'purple' => t('div_order_purple'),
        'blue' => t('div_order_blue'),
        'white' => t('div_order_white'),
    ];
}

/** Valida que sea una permutacion de las 6 claves conocidas; si no, usa el default */
function division_order_sanitize($order): array {
    $keys = division_order_default();
    $order = is_array($order) ? array_values(array_intersect(array_unique($order), $keys)) : [];
    foreach ($keys as $k) {
        if (!in_array($k, $order, true)) $order[] = $k;
    }
    return $order;
}

function division_order_global(): array {
    return division_order_sanitize(setting('division_order', null));
}

/** Orden efectivo para un torneo: su propio override si guardo uno, si no el general */
function division_order_for(?array $tournament): array {
    if ($tournament && !empty($tournament['division_order'])) {
        $decoded = json_decode((string)$tournament['division_order'], true);
        if (is_array($decoded)) return division_order_sanitize($decoded);
    }
    return division_order_global();
}

/** Expresion SQL (CASE) que traduce el orden configurado a una posicion numerica para ORDER BY */
function division_order_case_sql(array $order, string $adAlias = 'ad', string $beltAlias = 'b'): string {
    $pos = array_flip(division_order_sanitize($order));
    $sql = "CASE WHEN $adAlias.is_kids=1 OR $adAlias.code='juvenil' THEN " . (int)$pos['kids_juvenile'] . "\n";
    foreach (['black', 'brown', 'purple', 'blue', 'white'] as $belt) {
        $sql .= " WHEN $beltAlias.code='$belt' THEN " . (int)$pos[$belt] . "\n";
    }
    return $sql . ' ELSE 99 END';
}

/**
 * Duracion de lucha por defecto segun categoria: infantiles/juveniles comparten
 * un unico valor, los adultos/masters uno por cinturon (mismos 6 grupos que el
 * orden de corrida). Configurable en /admin/settings (general) y por torneo en
 * /tournament/{id}/settings (sobreescribe el general si se guarda) o al crear
 * el torneo. Los valores de fabrica coinciden con los que ya traía cada
 * cinturon en `belts.default_duration_sec`.
 */
function belt_duration_defaults(): array {
    return ['kids_juvenile' => 240, 'black' => 600, 'brown' => 480, 'purple' => 420, 'blue' => 360, 'white' => 300];
}

/** A que grupo de duracion pertenece una division segun su cinturon y categoria de edad */
function belt_duration_bucket(string $beltCode, bool $ageIsKids, string $ageCode): string {
    if ($ageIsKids || $ageCode === 'juvenil') return 'kids_juvenile';
    return in_array($beltCode, ['black', 'brown', 'purple', 'blue', 'white'], true) ? $beltCode : 'white';
}

/** Valida que tenga las 6 claves conocidas con segundos razonables; si no, usa el default */
function belt_duration_sanitize($durations): array {
    $defaults = belt_duration_defaults();
    $out = [];
    foreach ($defaults as $k => $def) {
        $sec = is_array($durations) ? (int)($durations[$k] ?? 0) : 0;
        $out[$k] = $sec >= 60 && $sec <= 1800 ? $sec : $def;
    }
    return $out;
}

function belt_durations_global(): array {
    return belt_duration_sanitize(setting('belt_durations', null));
}

/** Duraciones efectivas para un torneo: su propio override si guardo uno, si no el general */
function belt_durations_for(?array $tournament): array {
    if ($tournament && !empty($tournament['belt_durations'])) {
        $decoded = json_decode((string)$tournament['belt_durations'], true);
        if (is_array($decoded)) return belt_duration_sanitize($decoded);
    }
    return belt_durations_global();
}

/** Aplica un mapa de duraciones a todas las divisiones existentes del torneo (y sus luchas pendientes) */
function apply_belt_durations(int $tournamentId, array $durations): void {
    $divs = rows('SELECT d.id, b.code belt_code, ad.is_kids, ad.code age_code
                  FROM divisions d JOIN belts b ON b.id=d.belt_id JOIN age_divisions ad ON ad.id=d.age_division_id
                  WHERE d.tournament_id = ?', [$tournamentId]);
    foreach ($divs as $d) {
        $bucket = belt_duration_bucket($d['belt_code'], (bool)$d['is_kids'], $d['age_code']);
        $sec = $durations[$bucket] ?? null;
        if ($sec === null) continue;
        q('UPDATE divisions SET duration_sec = ? WHERE id = ?', [$sec, $d['id']]);
        q('UPDATE matches SET duration_sec = ?, timer_remaining = ? WHERE division_id = ? AND status = "pending"', [$sec, $sec, $d['id']]);
    }
}
