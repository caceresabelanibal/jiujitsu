<?php
/**
 * API del marcador: GET estado / POST acciones del operador.
 * El timer vive en el servidor: timer_remaining + timer_started_at.
 */
$mid = (int)$params[0];
$m = row('SELECT * FROM matches WHERE id = ?', [$mid]);
if (!$m) json_out(['error' => 'not_found'], 404);

function match_state(array $m): array {
    $remaining = (int)$m['timer_remaining'];
    if ($m['timer_running'] && $m['timer_started_at']) {
        $remaining = max(0, $remaining - (time() - strtotime($m['timer_started_at'])));
    }
    $winnerName = $m['winner_reg_id'] ? scalar('SELECT name FROM registrations WHERE id = ?', [$m['winner_reg_id']]) : null;
    $methodLabels = ['points' => t('by_points'), 'advantages' => t('by_advantages'), 'submission' => t('submission'),
                     'decision' => t('decision'), 'dq' => t('dq'), 'wo' => t('walkover')];
    return [
        'status' => $m['status'],
        'red_points' => (int)$m['red_points'], 'blue_points' => (int)$m['blue_points'],
        'red_adv' => (int)$m['red_adv'], 'blue_adv' => (int)$m['blue_adv'],
        'red_pen' => (int)$m['red_pen'], 'blue_pen' => (int)$m['blue_pen'],
        'timer_running' => (bool)$m['timer_running'],
        'timer_remaining' => $remaining,
        'duration_sec' => (int)$m['duration_sec'],
        'winner_name' => $winnerName,
        'method_label' => $m['method'] ? ($methodLabels[$m['method']] ?? $m['method']) : null,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_out(match_state($m));
}

// POST: solo dueno del torneo o admin
$t = require_tournament_owner((int)$m['tournament_id']);
if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? null)) json_out(['error' => 'csrf'], 419);
$action = $_POST['action'] ?? '';
if ($m['status'] === 'done' && $action !== 'reopen') json_out(match_state($m));

$side = ($_POST['side'] ?? '') === 'blue' ? 'blue' : 'red';
$scoring = setting('scoring', ['takedown' => 2, 'sweep' => 2, 'knee_on_belly' => 2, 'guard_pass' => 3, 'mount' => 4, 'back_control' => 4]);

/** Congela el tiempo restante actual en la fila */
function freeze_timer(array $m): int {
    $remaining = (int)$m['timer_remaining'];
    if ($m['timer_running'] && $m['timer_started_at']) {
        $remaining = max(0, $remaining - (time() - strtotime($m['timer_started_at'])));
    }
    return $remaining;
}

switch ($action) {
    case 'start':
        if (!$m['timer_running']) {
            q('UPDATE matches SET timer_running = 1, timer_started_at = NOW(), status = "live" WHERE id = ?', [$mid]);
            q('INSERT INTO match_events (match_id, type) VALUES (?, "timer_start")', [$mid]);
        }
        break;

    case 'pause':
        if ($m['timer_running']) {
            q('UPDATE matches SET timer_running = 0, timer_remaining = ?, timer_started_at = NULL WHERE id = ?', [freeze_timer($m), $mid]);
            q('INSERT INTO match_events (match_id, type) VALUES (?, "timer_pause")', [$mid]);
        }
        break;

    case 'reset':
        q('UPDATE matches SET timer_running = 0, timer_remaining = duration_sec, timer_started_at = NULL WHERE id = ?', [$mid]);
        break;

    case 'score':
        // No se puede puntuar antes de arrancar el cronometro por primera vez
        // (evita clicks accidentales pre-inicio; el boton ya queda grisado en el front).
        if ($m['status'] === 'pending') json_out(['error' => 'not_started', 'state' => match_state($m)], 422);
        $type = $_POST['type'] ?? '';
        if (isset($scoring[$type])) {
            $col = $side . '_points';
            q("UPDATE matches SET $col = $col + ? WHERE id = ?", [(int)$scoring[$type], $mid]);
            q('INSERT INTO match_events (match_id, side, type, value) VALUES (?,?,?,?)', [$mid, $side, $type, (int)$scoring[$type]]);
        } elseif ($type === 'advantage') {
            q("UPDATE matches SET {$side}_adv = {$side}_adv + 1 WHERE id = ?", [$mid]);
            q('INSERT INTO match_events (match_id, side, type, value) VALUES (?,?,"advantage",1)', [$mid, $side]);
        } elseif ($type === 'penalty') {
            q("UPDATE matches SET {$side}_pen = {$side}_pen + 1 WHERE id = ?", [$mid]);
            q('INSERT INTO match_events (match_id, side, type, value) VALUES (?,?,"penalty",1)', [$mid, $side]);
        }
        break;

    case 'undo':
        $last = row('SELECT * FROM match_events WHERE match_id = ? AND type NOT LIKE "timer%" ORDER BY id DESC LIMIT 1', [$mid]);
        if ($last) {
            if ($last['type'] === 'advantage') {
                q("UPDATE matches SET {$last['side']}_adv = GREATEST(0, {$last['side']}_adv - 1) WHERE id = ?", [$mid]);
            } elseif ($last['type'] === 'penalty') {
                q("UPDATE matches SET {$last['side']}_pen = GREATEST(0, {$last['side']}_pen - 1) WHERE id = ?", [$mid]);
            } else {
                $col = $last['side'] . '_points';
                q("UPDATE matches SET $col = GREATEST(0, $col - ?) WHERE id = ?", [(int)$last['value'], $mid]);
            }
            q('DELETE FROM match_events WHERE id = ?', [$last['id']]);
        }
        break;

    case 'end':
        $method = $_POST['type'] ?? 'points';
        if (!in_array($method, ['points', 'advantages', 'submission', 'decision', 'dq', 'wo'])) $method = 'points';
        $fresh = row('SELECT * FROM matches WHERE id = ?', [$mid]);
        $remaining = freeze_timer($fresh);
        $elapsed = max(0, (int)$fresh['duration_sec'] - $remaining);

        // Ganador: elegido por el operador, o inferido por puntos > ventajas > penalizaciones
        $winnerSide = in_array($_POST['side'] ?? '', ['red', 'blue']) ? $_POST['side'] : infer_winner($fresh);
        if (!$winnerSide) json_out(['error' => 'need_winner', 'state' => match_state($fresh)], 422);
        if ($method === 'points' && $fresh['red_points'] === $fresh['blue_points']) {
            $method = $fresh['red_adv'] !== $fresh['blue_adv'] ? 'advantages' : 'decision';
        }
        $winnerReg = $winnerSide === 'red' ? $fresh['red_reg_id'] : $fresh['blue_reg_id'];
        q('UPDATE matches SET status = "done", winner_reg_id = ?, method = ?, timer_running = 0,
                              timer_remaining = ?, timer_started_at = NULL, elapsed_sec = ? WHERE id = ?',
            [$winnerReg, $method, $remaining, $elapsed, $mid]);
        advance_winner($mid);
        propagate_byes((int)$m['division_id']);
        $tournamentJustFinished = $t['status'] !== 'finished'
            && scalar('SELECT status FROM tournaments WHERE id = ?', [$t['id']]) === 'finished';
        break;

    case 'reopen':
        if ($m['status'] !== 'done') break;
        // Seguridad: no reabrir si el ganador (o el perdedor, via bronce) ya
        // avanzo a una lucha que empezo o termino - ahi habria que corregir
        // esa lucha primero.
        $nextBusy = $m['next_match_id']
            ? (scalar('SELECT status FROM matches WHERE id = ?', [$m['next_match_id']]) !== 'pending') : false;
        $bronzeBusy = $m['bronze_match_id']
            ? (scalar('SELECT status FROM matches WHERE id = ?', [$m['bronze_match_id']]) !== 'pending') : false;
        if ($nextBusy || $bronzeBusy) {
            json_out(['error' => 'downstream_started', 'state' => match_state($m)], 422);
        }
        // Limpia el avance previo; se vuelve a completar al re-cerrar la lucha.
        if ($m['next_match_id']) {
            $col = $m['next_slot'] === 'red' ? 'red_reg_id' : 'blue_reg_id';
            q("UPDATE matches SET $col = NULL WHERE id = ?", [$m['next_match_id']]);
        }
        if ($m['bronze_match_id']) {
            $col = $m['bronze_slot'] === 'red' ? 'red_reg_id' : 'blue_reg_id';
            q("UPDATE matches SET $col = NULL WHERE id = ?", [$m['bronze_match_id']]);
        }
        q('UPDATE matches SET status = "live", winner_reg_id = NULL, method = NULL WHERE id = ?', [$mid]);
        q('INSERT INTO match_events (match_id, type) VALUES (?, "reopen")', [$mid]);
        check_division_done((int)$m['division_id']);
        break;
}

$outState = match_state(row('SELECT * FROM matches WHERE id = ?', [$mid]));
if (!empty($tournamentJustFinished)) {
    $outState['tournament_finished'] = true;
    $outState['tournament_name'] = $t['name'];
}
json_out($outState);
