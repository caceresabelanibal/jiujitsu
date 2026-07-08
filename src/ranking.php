<?php
/**
 * Ranking global por combinacion genero + cinturon + edad + peso.
 * Puntaje configurable desde el panel admin (setting "ranking").
 */
function recompute_rankings(): int {
    $cfg = setting('ranking', ['gold' => 9, 'silver' => 3, 'bronze' => 1, 'win' => 2, 'submission_bonus' => 1]);
    db()->exec('DELETE FROM ranking_points');

    $regs = rows('SELECT r.* FROM registrations r WHERE r.verified = 1');
    $count = 0;
    foreach ($regs as $r) {
        $wins = (int)scalar('SELECT COUNT(*) FROM matches WHERE status="done" AND winner_reg_id=?
                             AND red_reg_id IS NOT NULL AND blue_reg_id IS NOT NULL', [$r['id']]);
        $subs = (int)scalar('SELECT COUNT(*) FROM matches WHERE status="done" AND winner_reg_id=? AND method="submission"', [$r['id']]);

        // Podios en la division correspondiente
        $div = row('SELECT id FROM divisions WHERE tournament_id=? AND gender=? AND belt_id=? AND age_division_id=? AND weight_class_id=?',
            [$r['tournament_id'], $r['gender'], $r['belt_id'], $r['age_division_id'], $r['weight_class_id']]);
        $golds = $silvers = $bronzes = 0;
        if ($div) {
            [$g, $s, $b] = division_podium((int)$div['id']);
            if ($g === (int)$r['id']) $golds = 1;
            if ($s === (int)$r['id']) $silvers = 1;
            if ($b === (int)$r['id']) $bronzes = 1;
        }
        if ($wins + $golds + $silvers + $bronzes === 0) continue;

        $points = $golds * (int)$cfg['gold'] + $silvers * (int)$cfg['silver'] + $bronzes * (int)$cfg['bronze']
                + $wins * (int)$cfg['win'] + $subs * (int)$cfg['submission_bonus'];

        q('INSERT INTO ranking_points (email, name, gender, belt_id, age_division_id, weight_class_id, points, golds, silvers, bronzes, wins, submissions)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
           ON DUPLICATE KEY UPDATE points = points + VALUES(points), golds = golds + VALUES(golds),
             silvers = silvers + VALUES(silvers), bronzes = bronzes + VALUES(bronzes),
             wins = wins + VALUES(wins), submissions = submissions + VALUES(submissions), name = VALUES(name)',
            [strtolower($r['email']), $r['name'], $r['gender'], $r['belt_id'], $r['age_division_id'], $r['weight_class_id'],
             $points, $golds, $silvers, $bronzes, $wins, $subs]);
        $count++;
    }
    return $count;
}
