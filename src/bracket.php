<?php
/**
 * Llaves de eliminacion simple con byes, siembra estandar y lucha por el bronce.
 */

/**
 * Gi vs NoGi (src/bracket.php): en gi las divisiones van por cinturon exacto,
 * como siempre. En nogi, infantiles/juveniles se agrupan solo por edad+peso
 * (el cinturon no importa) y adultos/masters se agrupan por NIVEL
 * (amateur/semipro/pro), derivado del cinturon real del inscripto via un
 * mapeo configurable (general en /admin/settings, override por torneo).
 */
function nogi_tier_default(): array {
    return ['white' => 'amateur', 'blue' => 'amateur', 'purple' => 'semipro', 'brown' => 'pro', 'black' => 'pro'];
}

function nogi_tier_labels(): array {
    return ['amateur' => t('nogi_amateur'), 'semipro' => t('nogi_semipro'), 'pro' => t('nogi_pro')];
}

function nogi_tiers_sanitize($map): array {
    $out = [];
    foreach (nogi_tier_default() as $belt => $def) {
        $tier = is_array($map) ? ($map[$belt] ?? '') : '';
        $out[$belt] = in_array($tier, ['amateur', 'semipro', 'pro'], true) ? $tier : $def;
    }
    return $out;
}

function nogi_tiers_global(): array {
    return nogi_tiers_sanitize(setting('nogi_tiers', null));
}

function nogi_tiers_for(?array $tournament): array {
    if ($tournament && !empty($tournament['nogi_tiers'])) {
        $decoded = json_decode((string)$tournament['nogi_tiers'], true);
        if (is_array($decoded)) return nogi_tiers_sanitize($decoded);
    }
    return nogi_tiers_global();
}

/** Infantiles/juveniles nunca usan nivel/cinturon en nogi; adultos/masters si */
function nogi_uses_tier(array $age): bool {
    return !$age['is_kids'] && $age['code'] !== 'juvenil';
}

/**
 * Reglas de elegibilidad para "Absoluto": nunca infantiles ni juveniles (en
 * ninguna disciplina); en gi nunca cinturon blanco; en nogi nunca nivel
 * amateur (el nivel sale del mapeo cinturon->nivel configurado del torneo,
 * asi respeta si el organizador reconfiguro que cinturon cae en cada nivel).
 */
function can_compete_absolute(int $beltId, int $ageDivisionId, ?array $tournament): bool {
    $age = row('SELECT * FROM age_divisions WHERE id = ?', [$ageDivisionId]);
    if (!$age || $age['is_kids'] || $age['code'] === 'juvenil') return false;
    $belt = row('SELECT * FROM belts WHERE id = ?', [$beltId]);
    if (!$belt) return false;
    if (($tournament['discipline'] ?? 'gi') === 'nogi') {
        return (nogi_tiers_for($tournament)[$belt['code']] ?? 'amateur') !== 'amateur';
    }
    return $belt['code'] !== 'white';
}

/**
 * En que "kinds" de division entra un inscripto segun competes_in: solo su
 * categoria, solo el absoluto, o ambos (compite en las dos llaves a la vez).
 */
function registrant_division_kinds(array $r): array {
    $c = $r['competes_in'] ?? 'category';
    if ($c === 'both') return ['category', 'absolute'];
    return [$c === 'absolute' ? 'absolute' : 'category'];
}

/**
 * Calcula a que "clave" de division corresponde un inscripto (gender/belt_id/
 * tier/kind/age_division_id/weight_class_id + duracion sugerida) para un
 * $kind puntual ('category' o 'absolute' — no lee competes_in directo, porque
 * con "both" un mismo inscripto necesita las DOS claves). La usan tanto
 * ensure_divisions() (para crearlas) como el ranking (para encontrar los
 * podios reales de cada inscripto) — asi las dos coinciden siempre.
 */
function division_key_for(array $r, ?array $tournament, string $kind): ?array {
    $discipline = $tournament['discipline'] ?? 'gi';
    $tierMap = nogi_tiers_for($tournament);
    $belt = row('SELECT * FROM belts WHERE id = ?', [$r['belt_id']]);
    $age = row('SELECT * FROM age_divisions WHERE id = ?', [$r['age_division_id']]);
    if (!$belt || !$age) return null;

    $isNogiTier = $discipline === 'nogi' && nogi_uses_tier($age);
    $isNogiAgeOnly = $discipline === 'nogi' && !$isNogiTier;
    $beltIdForDiv = ($isNogiTier || $isNogiAgeOnly) ? null : (int)$r['belt_id'];
    $tierForDiv = $isNogiTier ? ($tierMap[$belt['code']] ?? 'amateur') : null;
    $isAbsolute = $kind === 'absolute';

    if ($discipline === 'nogi') {
        $bucket = nogi_tier_duration_bucket((bool)$age['is_kids'], $age['code'], $tierForDiv);
        $durationSec = nogi_tier_durations_for($tournament)[$bucket] ?? 300;
    } else {
        $bucket = belt_duration_bucket($belt['code'], (bool)$age['is_kids'], $age['code']);
        $durationSec = belt_durations_for($tournament)[$bucket] ?? (int)($belt['default_duration_sec'] ?? 300);
    }

    return [
        'gender' => $r['gender'], 'belt_id' => $beltIdForDiv, 'tier' => $tierForDiv,
        'kind' => $isAbsolute ? 'absolute' : 'standard',
        'age_division_id' => $isAbsolute ? null : (int)$r['age_division_id'],
        'weight_class_id' => $isAbsolute ? null : (int)$r['weight_class_id'],
        'duration_sec' => $durationSec,
    ];
}

/** Crea/actualiza divisiones segun los inscriptos verificados del torneo (categoria y absoluto) */
function ensure_divisions(int $tournamentId): int {
    $tournament = row('SELECT * FROM tournaments WHERE id = ?', [$tournamentId]);
    $combos = rows('SELECT DISTINCT gender, belt_id, age_division_id, weight_class_id, competes_in
                    FROM registrations WHERE tournament_id = ? AND verified = 1', [$tournamentId]);
    $created = 0;
    foreach ($combos as $c) {
        foreach (registrant_division_kinds($c) as $kind) {
            $key = division_key_for($c, $tournament, $kind);
            if (!$key) continue;
            $exists = row('SELECT id FROM divisions WHERE tournament_id=? AND kind=? AND gender=?
                           AND belt_id <=> ? AND tier <=> ? AND age_division_id <=> ? AND weight_class_id <=> ?',
                [$tournamentId, $key['kind'], $key['gender'], $key['belt_id'], $key['tier'], $key['age_division_id'], $key['weight_class_id']]);
            if (!$exists) {
                q('INSERT INTO divisions (tournament_id, gender, belt_id, tier, kind, age_division_id, weight_class_id, duration_sec)
                   VALUES (?,?,?,?,?,?,?,?)',
                    [$tournamentId, $key['gender'], $key['belt_id'], $key['tier'], $key['kind'], $key['age_division_id'], $key['weight_class_id'], $key['duration_sec']]);
                $created++;
            }
        }
    }
    return $created;
}

/**
 * Al cambiar el mapeo cinturon->nivel de un torneo NoGi, las divisiones ya
 * creadas quedan "congeladas" en el tier que tenian al momento de crearse
 * (guardan tier, no belt_id — no hay forma de recalcularlas in-place). Si
 * despues el organizador cambia el mapeo, division_registrations() (que SI
 * usa el mapeo actual) puede dejar una division existente sin ningun
 * inscripto real, mientras que esos inscriptos ahora necesitan una division
 * con el tier nuevo. Se llama al guardar/resetear el mapeo (save_tiers /
 * reset_tiers en tournament_settings.php): genera las divisiones que hagan
 * falta con el mapeo nuevo y borra las que hayan quedado vacias Y sin
 * ninguna lucha cargada (si ya tiene luchas, se deja — el organizador decide
 * a mano con el boton de eliminar categoria, para no perder resultados).
 */
function reconcile_nogi_tier_divisions(int $tournamentId): void {
    ensure_divisions($tournamentId);
    foreach (rows('SELECT id FROM divisions WHERE tournament_id=? AND tier IS NOT NULL', [$tournamentId]) as $d) {
        $did = (int)$d['id'];
        $hasMembers = count(division_registrations($did)) > 0;
        $hasMatches = (int)scalar('SELECT COUNT(*) FROM matches WHERE division_id=?', [$did]) > 0;
        if (!$hasMembers && !$hasMatches) {
            q('DELETE FROM divisions WHERE id=?', [$did]);
        }
    }
}

/** Divisiones reales donde compite este inscripto (1 o 2 si eligio "both") — para podios/ranking */
function find_registrant_divisions(array $r, ?array $tournament): array {
    $out = [];
    foreach (registrant_division_kinds($r) as $kind) {
        $key = division_key_for($r, $tournament, $kind);
        if (!$key) continue;
        $div = row('SELECT * FROM divisions WHERE tournament_id=? AND kind=? AND gender=?
                    AND belt_id <=> ? AND tier <=> ? AND age_division_id <=> ? AND weight_class_id <=> ?',
            [$r['tournament_id'], $key['kind'], $key['gender'], $key['belt_id'], $key['tier'], $key['age_division_id'], $key['weight_class_id']]);
        if ($div) $out[] = $div;
    }
    return $out;
}

function division_registrations(int $divisionId): array {
    $d = row('SELECT * FROM divisions WHERE id = ?', [$divisionId]);
    if (!$d) return [];

    if ($d['kind'] === 'special') {
        return rows('SELECT r.*, a.name AS academy_name FROM division_members dm
                     JOIN registrations r ON r.id = dm.registration_id
                     LEFT JOIN tournament_academies a ON a.id = r.academy_id
                     WHERE dm.division_id = ? ORDER BY r.name', [$divisionId]);
    }

    $where = 'r.tournament_id=? AND r.verified=1 AND r.gender=?';
    $params = [$d['tournament_id'], $d['gender']];

    if ($d['kind'] === 'absolute') {
        $where .= " AND r.competes_in IN ('absolute','both')";
    } else {
        $where .= " AND r.competes_in IN ('category','both') AND r.age_division_id <=> ? AND r.weight_class_id <=> ?";
        $params[] = $d['age_division_id'];
        $params[] = $d['weight_class_id'];
    }

    if ($d['tier'] !== null) {
        $tour = row('SELECT * FROM tournaments WHERE id = ?', [$d['tournament_id']]);
        $tierMap = nogi_tiers_for($tour);
        $beltCodes = array_keys(array_filter($tierMap, fn($tier) => $tier === $d['tier']));
        if (!$beltCodes) return [];
        $placeholders = implode(',', array_fill(0, count($beltCodes), '?'));
        $where .= " AND r.belt_id IN (SELECT id FROM belts WHERE code IN ($placeholders))";
        array_push($params, ...$beltCodes);
    } elseif ($d['belt_id'] !== null) {
        $where .= ' AND r.belt_id = ?';
        $params[] = $d['belt_id'];
    }
    // si belt_id y tier son ambos NULL (nogi infantil/juvenil): sin filtro de cinturon

    return rows("SELECT r.*, a.name AS academy_name FROM registrations r
                 LEFT JOIN tournament_academies a ON a.id = r.academy_id
                 WHERE $where ORDER BY r.name", $params);
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
            $tid = (int)scalar('SELECT tournament_id FROM divisions WHERE id = ?', [$divisionId]);
            if ($tid) {
                // Genera/manda ya mismo el certificado de podio de esta division
                // (y de participacion para quien todavia no lo tenga) sin esperar
                // a que termine el resto del torneo.
                certificates_send_all($tid, true, true, 25);
                check_tournament_done($tid);
            }
        }
    }
}

/**
 * Si ya no queda ninguna division con llave por terminar, marca el torneo
 * como finalizado, recalcula el ranking global (asi no hay que esperar al
 * cron horario para verlo actualizado), genera y encola los certificados de
 * podio + participacion (el primer lote sale ya mismo, el resto lo termina
 * el cron `certificates` si el torneo es grande) y le manda un mail de
 * agradecimiento al organizador. Se llama automaticamente al cerrar
 * cualquier lucha (via check_division_done()) y tambien desde el cron
 * `tournament_status` como red de seguridad.
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
    recompute_rankings();
    certificates_send_all($tournamentId, true, true, 25);
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

/** Expresion SQL (CASE) que traduce el orden configurado a una posicion numerica para ORDER BY (gi: por cinturon exacto) */
function division_order_case_sql(array $order, string $adAlias = 'ad', string $beltAlias = 'b'): string {
    $pos = array_flip(division_order_sanitize($order));
    $sql = "CASE WHEN $adAlias.is_kids=1 OR $adAlias.code='juvenil' THEN " . (int)$pos['kids_juvenile'] . "\n";
    foreach (['black', 'brown', 'purple', 'blue', 'white'] as $belt) {
        $sql .= " WHEN $beltAlias.code='$belt' THEN " . (int)$pos[$belt] . "\n";
    }
    return $sql . ' ELSE 99 END';
}

/**
 * Orden de corrida propio de NoGi — NO se deriva del orden de cinturones de
 * gi. El organizador aclaro que NoGi corre al reves de gi por default:
 * primero infantiles/juveniles, despues Amateur, despues Semi Pro, y Pro al
 * final (gi en cambio arranca por Negro). Mismo esquema general+override
 * (settings.nogi_division_order / tournaments.nogi_division_order) que el
 * resto de los ordenes configurables.
 */
function nogi_division_order_default(): array {
    return ['kids_juvenile', 'amateur', 'semipro', 'pro'];
}

function nogi_division_order_labels(): array {
    return ['kids_juvenile' => t('div_order_kids_juvenile')] + nogi_tier_labels();
}

function nogi_division_order_sanitize($order): array {
    $keys = nogi_division_order_default();
    $order = is_array($order) ? array_values(array_intersect(array_unique($order), $keys)) : [];
    foreach ($keys as $k) {
        if (!in_array($k, $order, true)) $order[] = $k;
    }
    return $order;
}

function nogi_division_order_global(): array {
    return nogi_division_order_sanitize(setting('nogi_division_order', null));
}

function nogi_division_order_for(?array $tournament): array {
    if ($tournament && !empty($tournament['nogi_division_order'])) {
        $decoded = json_decode((string)$tournament['nogi_division_order'], true);
        if (is_array($decoded)) return nogi_division_order_sanitize($decoded);
    }
    return nogi_division_order_global();
}

/** Expresion SQL (CASE) equivalente a division_order_case_sql() pero por tier (nogi) en vez de cinturon exacto */
function nogi_division_order_case_sql(array $order, string $adAlias = 'ad', string $divAlias = 'd'): string {
    $pos = array_flip(nogi_division_order_sanitize($order));
    $sql = "CASE WHEN $adAlias.is_kids=1 OR $adAlias.code='juvenil' THEN " . (int)$pos['kids_juvenile'] . "\n";
    foreach (['amateur', 'semipro', 'pro'] as $tier) {
        $sql .= " WHEN $divAlias.tier='$tier' THEN " . (int)$pos[$tier] . "\n";
    }
    return $sql . ' ELSE 99 END';
}

/**
 * Variante para ordenar INSCRIPTOS nogi (tabla registrations, sin JOIN a
 * divisions): el inscripto no tiene columna tier — su categoria se deriva del
 * cinturon real via el mapeo del torneo, asi que el CASE va por b.code, con
 * cada cinturon tomando la posicion de su nivel en nogi_division_order.
 */
function nogi_registrant_order_case_sql(array $order, array $tierMap, string $adAlias = 'ad', string $beltAlias = 'b'): string {
    $pos = array_flip(nogi_division_order_sanitize($order));
    $sql = "CASE WHEN $adAlias.is_kids=1 OR $adAlias.code='juvenil' THEN " . (int)$pos['kids_juvenile'] . "\n";
    foreach (['black', 'brown', 'purple', 'blue', 'white'] as $belt) {
        $tier = $tierMap[$belt] ?? 'amateur';
        $sql .= " WHEN $beltAlias.code='$belt' THEN " . (int)$pos[$tier] . "\n";
    }
    return $sql . ' ELSE 99 END';
}

/**
 * Orden (drag-and-drop) de categorias de EDAD y de PESO dentro de cada
 * cinturon — el cinturon sigue siendo el grupo principal (division_order_*
 * arriba); esto es el sub-orden secundario/terciario configurable en vez del
 * ascendente fijo que habia antes. Mismo esquema general+override que el
 * resto: settings.age_order/weight_order (general) y
 * tournaments.age_order/weight_order (por torneo, NULL = usa el general).
 */
function age_order_default(): array {
    return ['inf_a', 'inf_b', 'inf_c', 'inf_d', 'juvenil', 'adulto', 'master1', 'master2', 'master3', 'master4', 'master5', 'master6'];
}

function age_order_labels(): array {
    $out = [];
    foreach (rows('SELECT code, name_es, name_en FROM age_divisions ORDER BY sort') as $a) {
        $out[$a['code']] = loc_name($a);
    }
    return $out;
}

function age_order_sanitize($order): array {
    $keys = age_order_default();
    $order = is_array($order) ? array_values(array_intersect(array_unique($order), $keys)) : [];
    foreach ($keys as $k) {
        if (!in_array($k, $order, true)) $order[] = $k;
    }
    return $order;
}

function age_order_global(): array {
    return age_order_sanitize(setting('age_order', null));
}

function age_order_for(?array $tournament): array {
    if ($tournament && !empty($tournament['age_order'])) {
        $decoded = json_decode((string)$tournament['age_order'], true);
        if (is_array($decoded)) return age_order_sanitize($decoded);
    }
    return age_order_global();
}

function age_order_case_sql(array $order, string $adAlias = 'ad'): string {
    $pos = array_flip(age_order_sanitize($order));
    $sql = "CASE $adAlias.code\n";
    foreach ($pos as $code => $p) {
        $sql .= " WHEN '$code' THEN $p\n";
    }
    return $sql . ' ELSE 99 END';
}

/**
 * El peso usa el rank (wc.sort: 10, 20, ... 100) en vez del codigo, porque
 * los codigos difieren por genero (m_galo/f_galo/k_24) pero el rank (mas
 * liviano -> mas pesado) es el mismo concepto para M/F/infantiles. Los
 * nombres de referencia para el drag-and-drop salen del listado masculino
 * (el mas completo: 10 categorias, incluye "Pesadisimo" que Femenino no tiene).
 */
function weight_order_default(): array {
    return ['w10', 'w20', 'w30', 'w40', 'w50', 'w60', 'w70', 'w80', 'w90', 'w100'];
}

function weight_order_labels(): array {
    $out = [];
    foreach (rows("SELECT sort, name_es, name_en FROM weight_classes WHERE gender = 'M' ORDER BY sort") as $w) {
        $out['w' . (int)$w['sort']] = loc_name($w);
    }
    return $out;
}

function weight_order_sanitize($order): array {
    $keys = weight_order_default();
    $order = is_array($order) ? array_values(array_intersect(array_unique($order), $keys)) : [];
    foreach ($keys as $k) {
        if (!in_array($k, $order, true)) $order[] = $k;
    }
    return $order;
}

function weight_order_global(): array {
    return weight_order_sanitize(setting('weight_order', null));
}

function weight_order_for(?array $tournament): array {
    if ($tournament && !empty($tournament['weight_order'])) {
        $decoded = json_decode((string)$tournament['weight_order'], true);
        if (is_array($decoded)) return weight_order_sanitize($decoded);
    }
    return weight_order_global();
}

function weight_order_case_sql(array $order, string $wcAlias = 'wc'): string {
    $pos = array_flip(weight_order_sanitize($order));
    $sql = "CASE $wcAlias.sort\n";
    foreach ($pos as $key => $p) {
        $sortVal = (int)substr($key, 1);
        $sql .= " WHEN $sortVal THEN $p\n";
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

/**
 * Aplica un mapa de duraciones a todas las divisiones existentes del torneo (y
 * sus luchas pendientes). LEFT JOIN en age_divisions (no INNER): las
 * divisiones "absolute" tienen age_division_id NULL (edad colapsada), y con
 * INNER JOIN quedaban afuera y nunca recibian la duracion actualizada.
 */
function apply_belt_durations(int $tournamentId, array $durations): void {
    $divs = rows('SELECT d.id, b.code belt_code, ad.is_kids, ad.code age_code
                  FROM divisions d JOIN belts b ON b.id=d.belt_id LEFT JOIN age_divisions ad ON ad.id=d.age_division_id
                  WHERE d.tournament_id = ?', [$tournamentId]);
    foreach ($divs as $d) {
        $bucket = belt_duration_bucket($d['belt_code'], (bool)$d['is_kids'], (string)$d['age_code']);
        $sec = $durations[$bucket] ?? null;
        if ($sec === null) continue;
        q('UPDATE divisions SET duration_sec = ? WHERE id = ?', [$sec, $d['id']]);
        q('UPDATE matches SET duration_sec = ?, timer_remaining = ? WHERE division_id = ? AND status = "pending"', [$sec, $sec, $d['id']]);
    }
}

/**
 * Duracion de lucha para NoGi, independiente de la de gi (por las mismas 4
 * categorias que el orden de corrida: infantiles/juveniles + los 3 niveles)
 * en vez de por cinturon exacto — el organizador senalo que el cinturon no
 * es un parametro real en NoGi, asi que no tiene sentido pedirle duracion
 * "por cinturon" ahi. Mismo esquema general+override que el resto.
 */
function nogi_tier_duration_defaults(): array {
    return ['kids_juvenile' => 240, 'amateur' => 300, 'semipro' => 420, 'pro' => 600];
}

function nogi_tier_duration_sanitize($durations): array {
    $defaults = nogi_tier_duration_defaults();
    $out = [];
    foreach ($defaults as $k => $def) {
        $sec = is_array($durations) ? (int)($durations[$k] ?? 0) : 0;
        $out[$k] = $sec >= 60 && $sec <= 1800 ? $sec : $def;
    }
    return $out;
}

function nogi_tier_durations_global(): array {
    return nogi_tier_duration_sanitize(setting('nogi_tier_durations', null));
}

function nogi_tier_durations_for(?array $tournament): array {
    if ($tournament && !empty($tournament['nogi_tier_durations'])) {
        $decoded = json_decode((string)$tournament['nogi_tier_durations'], true);
        if (is_array($decoded)) return nogi_tier_duration_sanitize($decoded);
    }
    return nogi_tier_durations_global();
}

/** A que bucket de duracion NoGi pertenece una division, segun edad y tier (bucket propio, no cinturon) */
function nogi_tier_duration_bucket(bool $ageIsKids, string $ageCode, ?string $tier): string {
    if ($ageIsKids || $ageCode === 'juvenil') return 'kids_juvenile';
    return in_array($tier, ['amateur', 'semipro', 'pro'], true) ? $tier : 'amateur';
}

/** Analogo a apply_belt_durations() pero para NoGi (bucket por tier, no por cinturon) */
function apply_nogi_tier_durations(int $tournamentId, array $durations): void {
    $divs = rows('SELECT d.id, d.tier, ad.is_kids, ad.code age_code
                  FROM divisions d LEFT JOIN age_divisions ad ON ad.id=d.age_division_id
                  WHERE d.tournament_id = ?', [$tournamentId]);
    foreach ($divs as $d) {
        $bucket = nogi_tier_duration_bucket((bool)$d['is_kids'], (string)$d['age_code'], $d['tier']);
        $sec = $durations[$bucket] ?? null;
        if ($sec === null) continue;
        q('UPDATE divisions SET duration_sec = ? WHERE id = ?', [$sec, $d['id']]);
        q('UPDATE matches SET duration_sec = ?, timer_remaining = ? WHERE division_id = ? AND status = "pending"', [$sec, $sec, $d['id']]);
    }
}
