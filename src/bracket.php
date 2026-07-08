<?php
/**
 * Llaves de eliminacion simple con byes, siembra estandar y lucha por el bronce.
 */

/** Crea/actualiza divisiones segun los inscriptos verificados del torneo */
function ensure_divisions(int $tournamentId): int {
    $combos = rows('SELECT DISTINCT gender, belt_id, age_division_id, weight_class_id
                    FROM registrations WHERE tournament_id = ? AND verified = 1', [$tournamentId]);
    $created = 0;
    foreach ($combos as $c) {
        $exists = row('SELECT id FROM divisions WHERE tournament_id=? AND gender=? AND belt_id=? AND age_division_id=? AND weight_class_id=?',
            [$tournamentId, $c['gender'], $c['belt_id'], $c['age_division_id'], $c['weight_class_id']]);
        if (!$exists) {
            $belt = row('SELECT * FROM belts WHERE id = ?', [$c['belt_id']]);
            q('INSERT INTO divisions (tournament_id, gender, belt_id, age_division_id, weight_class_id, duration_sec)
               VALUES (?,?,?,?,?,?)',
                [$tournamentId, $c['gender'], $c['belt_id'], $c['age_division_id'], $c['weight_class_id'],
                 (int)($belt['default_duration_sec'] ?? 300)]);
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
        q('UPDATE divisions SET status = ? WHERE id = ?', [$pending === 0 ? 'done' : 'bracketed', $divisionId]);
    }
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
