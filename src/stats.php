<?php
/**
 * Dashboard del torneo: estadisticas agregadas.
 */
function tournament_stats(int $tid): array {
    $s = [];

    // Tabla de medallas por academia (define academia ganadora)
    $medals = []; // academy_id => [name, gold, silver, bronze]
    $divs = rows('SELECT id FROM divisions WHERE tournament_id = ?', [$tid]);
    foreach ($divs as $d) {
        [$g, $sv, $b] = division_podium((int)$d['id']);
        foreach ([['gold', $g], ['silver', $sv], ['bronze', $b]] as [$type, $regId]) {
            if (!$regId) continue;
            $reg = row('SELECT r.academy_id, a.name FROM registrations r LEFT JOIN tournament_academies a ON a.id=r.academy_id WHERE r.id=?', [$regId]);
            $aid = $reg['academy_id'] ?? 0;
            if (!isset($medals[$aid])) $medals[$aid] = ['name' => $reg['name'] ?? '—', 'gold' => 0, 'silver' => 0, 'bronze' => 0];
            $medals[$aid][$type]++;
        }
    }
    usort($medals, fn($a, $b) => [$b['gold'], $b['silver'], $b['bronze']] <=> [$a['gold'], $a['silver'], $a['bronze']]);
    $s['medals_by_academy'] = $medals;
    $s['winning_academy'] = $medals[0] ?? null;

    $realFight = 'm.status="done" AND m.red_reg_id IS NOT NULL AND m.blue_reg_id IS NOT NULL';

    // Luchador con mas luchas
    $s['most_fights'] = row("SELECT r.name, r.email, COUNT(*) c FROM matches m
        JOIN registrations r ON r.id IN (m.red_reg_id, m.blue_reg_id)
        WHERE m.tournament_id=? AND $realFight GROUP BY r.id ORDER BY c DESC LIMIT 1", [$tid]);

    // Mas minutos en tatami
    $s['most_mat_time'] = row("SELECT r.name, SUM(m.elapsed_sec) sec FROM matches m
        JOIN registrations r ON r.id IN (m.red_reg_id, m.blue_reg_id)
        WHERE m.tournament_id=? AND $realFight GROUP BY r.id ORDER BY sec DESC LIMIT 1", [$tid]);

    // Mas finalizador (victorias por finalizacion)
    $s['most_submissions'] = row("SELECT r.name, COUNT(*) c FROM matches m
        JOIN registrations r ON r.id = m.winner_reg_id
        WHERE m.tournament_id=? AND m.status='done' AND m.method='submission' GROUP BY r.id ORDER BY c DESC LIMIT 1", [$tid]);

    // Quien mas gano por puntos
    $s['most_wins_points'] = row("SELECT r.name, COUNT(*) c FROM matches m
        JOIN registrations r ON r.id = m.winner_reg_id
        WHERE m.tournament_id=? AND m.status='done' AND m.method='points' GROUP BY r.id ORDER BY c DESC LIMIT 1", [$tid]);

    // Mas derrotas
    $s['most_losses'] = row("SELECT r.name, COUNT(*) c FROM matches m
        JOIN registrations r ON (r.id IN (m.red_reg_id, m.blue_reg_id) AND r.id != m.winner_reg_id)
        WHERE m.tournament_id=? AND $realFight AND m.winner_reg_id IS NOT NULL
        GROUP BY r.id ORDER BY c DESC LIMIT 1", [$tid]);

    // Finalizacion mas rapida
    $s['fastest_submission'] = row("SELECT r.name, m.elapsed_sec sec FROM matches m
        JOIN registrations r ON r.id = m.winner_reg_id
        WHERE m.tournament_id=? AND m.status='done' AND m.method='submission' AND m.elapsed_sec > 0
        ORDER BY m.elapsed_sec ASC LIMIT 1", [$tid]);

    // Mas puntos anotados en total
    $s['most_points_scored'] = row("SELECT r.name, SUM(IF(m.red_reg_id=r.id, m.red_points, m.blue_points)) pts FROM matches m
        JOIN registrations r ON r.id IN (m.red_reg_id, m.blue_reg_id)
        WHERE m.tournament_id=? AND $realFight GROUP BY r.id ORDER BY pts DESC LIMIT 1", [$tid]);

    // Mas ventajas
    $s['most_advantages'] = row("SELECT r.name, SUM(IF(m.red_reg_id=r.id, m.red_adv, m.blue_adv)) adv FROM matches m
        JOIN registrations r ON r.id IN (m.red_reg_id, m.blue_reg_id)
        WHERE m.tournament_id=? AND $realFight GROUP BY r.id ORDER BY adv DESC LIMIT 1", [$tid]);

    // Totales generales
    $s['totals'] = row("SELECT COUNT(*) fights, COALESCE(SUM(m.elapsed_sec),0) mat_seconds,
            SUM(m.method='submission') submissions, SUM(m.method='points') by_points,
            SUM(m.method='decision') by_decision, SUM(m.method='advantages') by_advantages
        FROM matches m WHERE m.tournament_id=? AND $realFight", [$tid]);

    $s['participants'] = (int)scalar('SELECT COUNT(*) FROM registrations WHERE tournament_id=? AND verified=1', [$tid]);
    $s['divisions_total'] = count($divs);
    $s['divisions_done'] = (int)scalar('SELECT COUNT(*) FROM divisions WHERE tournament_id=? AND status="done"', [$tid]);

    return $s;
}
