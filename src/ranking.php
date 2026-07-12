<?php
/**
 * Ranking global por combinacion genero + cinturon + edad + peso + disciplina.
 * Gi y NoGi se calculan y muestran por separado (dos rankings): un mismo
 * competidor puede tener una fila para sus resultados en torneos gi y otra
 * para sus resultados en nogi (misma identidad real de cinturon/edad/peso,
 * discipline distinta), segun la disciplina del torneo de cada inscripcion.
 * Puntaje configurable desde el panel admin (setting "ranking").
 */
function recompute_rankings(): int {
    $cfg = setting('ranking', ['gold' => 9, 'silver' => 3, 'bronze' => 1, 'win' => 2, 'submission_bonus' => 1]);
    db()->exec('DELETE FROM ranking_points');

    $regs = rows('SELECT r.*, b.code belt_code, ad.is_kids age_is_kids, ad.code age_code
                  FROM registrations r
                  JOIN belts b ON b.id = r.belt_id
                  JOIN age_divisions ad ON ad.id = r.age_division_id
                  WHERE r.verified = 1');
    $tournamentsById = [];
    $count = 0;
    foreach ($regs as $r) {
        $wins = (int)scalar('SELECT COUNT(*) FROM matches WHERE status="done" AND winner_reg_id=?
                             AND red_reg_id IS NOT NULL AND blue_reg_id IS NOT NULL', [$r['id']]);
        $subs = (int)scalar('SELECT COUNT(*) FROM matches WHERE status="done" AND winner_reg_id=? AND method="submission"', [$r['id']]);

        // Podio en la(s) division(es) real(es) de este inscripto (respeta gi/nogi/absoluto;
        // si eligio "both" cuenta el podio de las dos llaves donde compite)
        $tid = (int)$r['tournament_id'];
        if (!array_key_exists($tid, $tournamentsById)) {
            $tournamentsById[$tid] = row('SELECT * FROM tournaments WHERE id = ?', [$tid]);
        }
        $golds = $silvers = $bronzes = 0;
        foreach (find_registrant_divisions($r, $tournamentsById[$tid]) as $div) {
            [$g, $s, $b] = division_podium((int)$div['id']);
            if ($g === (int)$r['id']) $golds++;
            if ($s === (int)$r['id']) $silvers++;
            if ($b === (int)$r['id']) $bronzes++;
        }
        if ($wins + $golds + $silvers + $bronzes === 0) continue;

        $points = $golds * (int)$cfg['gold'] + $silvers * (int)$cfg['silver'] + $bronzes * (int)$cfg['bronze']
                + $wins * (int)$cfg['win'] + $subs * (int)$cfg['submission_bonus'];

        // La foto mas reciente de este email entre todas sus inscripciones (puede
        // haberla subido en otro torneo o en otra categoria de este mismo)
        $photo = scalar('SELECT photo FROM registrations WHERE email=? AND photo IS NOT NULL ORDER BY created_at DESC LIMIT 1', [$r['email']]);

        // El ranking nogi no va por cinturon sino por categoria: infantiles/
        // juveniles o el nivel (amateur/semipro/pro) segun el mapeo del torneo
        // donde se ganaron los puntos. En gi el tier queda '' (por que '' y no
        // NULL: un NULL en la unique key uq_rank rompe el ON DUPLICATE KEY).
        $discipline = $tournamentsById[$tid]['discipline'] ?? 'gi';
        $tier = '';
        if ($discipline === 'nogi') {
            $tier = ((bool)$r['age_is_kids'] || $r['age_code'] === 'juvenil')
                ? 'kids_juvenile'
                : (nogi_tiers_for($tournamentsById[$tid])[$r['belt_code']] ?? 'amateur');
        }
        q('INSERT INTO ranking_points (email, name, photo, gender, discipline, tier, belt_id, age_division_id, weight_class_id, points, golds, silvers, bronzes, wins, submissions)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
           ON DUPLICATE KEY UPDATE points = points + VALUES(points), golds = golds + VALUES(golds),
             silvers = silvers + VALUES(silvers), bronzes = bronzes + VALUES(bronzes),
             wins = wins + VALUES(wins), submissions = submissions + VALUES(submissions), name = VALUES(name), photo = VALUES(photo)',
            [strtolower($r['email']), $r['name'], $photo ?: null, $r['gender'], $discipline, $tier, $r['belt_id'], $r['age_division_id'], $r['weight_class_id'],
             $points, $golds, $silvers, $bronzes, $wins, $subs]);
        $count++;
    }
    return $count;
}
