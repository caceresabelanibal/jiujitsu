<?php
/**
 * Crea dos torneos demo (uno Gi y uno NoGi) asignados al admin, con 15
 * competidores ficticios cada uno (30 en total), academias reales y la mitad
 * de las luchas ya jugadas. Idempotente por slug: si el torneo demo ya existe,
 * no hace nada (no duplica). Para regenerarlos, borralos desde la UI y volvé a correr.
 *
 *   docker compose exec app php scripts/seed_demo_tournaments.php
 *
 * El entrypoint lo corre solo si la variable de entorno SEED_DEMO=1.
 */
require_once dirname(__DIR__) . '/src/bootstrap.php';

$adminEmail = strtolower(trim(getenv('ADMIN_EMAIL') ?: 'admin@taninzu.com'));
$owner = row('SELECT id FROM users WHERE email = ? AND role = "admin"', [$adminEmail])
      ?: row('SELECT id FROM users WHERE role = "admin" ORDER BY id LIMIT 1');
if (!$owner) { echo "No hay usuario admin todavía; corré seed_admin.php primero.\n"; exit; }
$ownerId = (int)$owner['id'];

$maleFirst = ['Lucas','Mateo','Santiago','Bruno','Rodrigo','Facundo','Tomás','Nicolás','Joaquín','Ignacio',
              'Gabriel','Martín','Emiliano','Federico','Ramiro','Agustín','Diego','Franco','Julián','Maximiliano'];
$femaleFirst = ['Sofía','Valentina','Camila','Martina','Luana','Julieta','Carolina','Florencia','Micaela','Antonella'];
$lastNames = ['Fernández','González','Rodríguez','López','Martínez','Pereira','Silva','Souza','Oliveira','Almeida',
              'Gómez','Díaz','Torres','Ramírez','Ferreira','Castro','Romano','Núñez','Ortiz','Medina','Acosta','Sosa'];
$academyNames = ['Gracie Barra', 'Alliance', 'Atos', 'Checkmat', 'Ns Brotherhood', 'GF Team'];

$beltId = [];
foreach (rows("SELECT id, code FROM belts WHERE is_kids=0") as $b) $beltId[$b['code']] = (int)$b['id'];
$ageAdulto = (int)scalar("SELECT id FROM age_divisions WHERE code='adulto'");
$wcByCode = [];
foreach (rows("SELECT id, code, max_kg FROM weight_classes WHERE is_absolute=0") as $w) $wcByCode[$w['code']] = $w;

// Buckets concentrados (genero, cinturon, peso, cupo) que suman 15, para que
// se armen divisiones con 2-5 personas cada una.
$bucketsByDisc = [
    'gi' => [
        ['M','blue','m_pena',3], ['M','blue','m_medio',3], ['M','purple','m_leve',3],
        ['M','brown','m_medio',2], ['M','black','m_medio',2], ['F','blue','f_leve',2],
    ],
    'nogi' => [
        ['M','blue','m_pena',3], ['M','white','m_pena',2], ['M','purple','m_medio',3],
        ['M','brown','m_medio',3], ['M','black','m_leve',2], ['F','blue','f_leve',2],
    ],
];

function seed_one(string $discipline, string $name, string $slug, int $ownerId, array $buckets, array $ctx): void {
    if (row('SELECT id FROM tournaments WHERE slug = ?', [$slug])) {
        echo "Demo '$name' ya existe (slug $slug) — sin cambios.\n";
        return;
    }
    ['maleFirst'=>$maleFirst,'femaleFirst'=>$femaleFirst,'lastNames'=>$lastNames,
     'academyNames'=>$academyNames,'beltId'=>$beltId,'ageAdulto'=>$ageAdulto,'wcByCode'=>$wcByCode] = $ctx;

    q("INSERT INTO tournaments (user_id, name, slug, type, discipline, status, event_date, default_duration_sec, max_participants)
       VALUES (?,?,?,'open',?,'running', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 300, 60)",
       [$ownerId, $name, $slug, $discipline]);
    $tid = (int)db()->lastInsertId();

    $acadIds = [];
    foreach ($academyNames as $an) {
        q('INSERT INTO tournament_academies (tournament_id, name) VALUES (?,?)', [$tid, $an]);
        $aid = (int)db()->lastInsertId();
        $acadIds[] = $aid;
        q('INSERT INTO tournament_professors (tournament_id, academy_id, name) VALUES (?,?,?)',
            [$tid, $aid, 'Prof. ' . $lastNames[array_rand($lastNames)]]);
    }

    $used = [];
    $tourRow = row('SELECT * FROM tournaments WHERE id=?', [$tid]);
    $i = 0;
    foreach ($buckets as [$gender, $beltCode, $wcCode, $cupo]) {
        $wc = $wcByCode[$wcCode];
        for ($k = 0; $k < $cupo; $k++) {
            $i++;
            $firsts = $gender === 'M' ? $maleFirst : $femaleFirst;
            do { $nm = $firsts[array_rand($firsts)] . ' ' . $lastNames[array_rand($lastNames)]; } while (isset($used[$nm]));
            $used[$nm] = true;
            $hi = $wc['max_kg'] !== null ? (float)$wc['max_kg'] : 90.0;
            $weight = round($hi - mt_rand(5, 40) / 10, 1);
            $birthdate = sprintf('%04d-%02d-%02d', 2026 - mt_rand(19, 29), mt_rand(1, 12), mt_rand(1, 28));
            $competesIn = ($beltCode !== 'white' && mt_rand(1, 100) <= 22) ? (mt_rand(1, 100) <= 60 ? 'both' : 'absolute') : 'category';
            $aid = $acadIds[array_rand($acadIds)];
            $prof = rows('SELECT id FROM tournament_professors WHERE academy_id=?', [$aid]);
            $profId = $prof ? (int)$prof[array_rand($prof)]['id'] : null;

            q("INSERT INTO registrations (tournament_id, academy_id, professor_id, name, email, gender, birthdate, weight_kg, belt_id, age_division_id, weight_class_id, competes_in, verified)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1)",
               [$tid, $aid, $profId, $nm, 'demo' . $i . '_t' . $tid . '@demo.local', $gender, $birthdate, $weight,
                $beltId[$beltCode], $ageAdulto, (int)$wc['id'], $competesIn]);
            $regId = (int)db()->lastInsertId();
            if ($competesIn !== 'category' && !can_compete_absolute($beltId[$beltCode], $ageAdulto, $tourRow)) {
                q("UPDATE registrations SET competes_in='category' WHERE id=?", [$regId]);
            }
        }
    }

    ensure_divisions($tid);
    $withBracket = [];
    foreach (rows('SELECT id FROM divisions WHERE tournament_id=?', [$tid]) as $d) {
        if (count(division_registrations((int)$d['id'])) < 2) continue;
        generate_bracket((int)$d['id'], [], true);
        $withBracket[] = (int)$d['id'];
    }

    // completar la mitad de las luchas reales, en orden de corrida
    $t = row('SELECT * FROM tournaments WHERE id=?', [$tid]);
    $divOrder = $discipline === 'nogi'
        ? nogi_division_order_case_sql(nogi_division_order_for($t))
        : division_order_case_sql(division_order_for($t));
    $ageOrder = age_order_case_sql(age_order_for($t));
    $weightOrder = weight_order_case_sql(weight_order_for($t));
    $ph = implode(',', array_fill(0, count($withBracket), '?'));
    $orderedDivs = $withBracket ? array_column(rows(
        "SELECT d.id FROM divisions d
         LEFT JOIN belts b ON b.id=d.belt_id LEFT JOIN age_divisions ad ON ad.id=d.age_division_id LEFT JOIN weight_classes wc ON wc.id=d.weight_class_id
         WHERE d.tournament_id=? AND d.id IN ($ph)
         ORDER BY $divOrder, d.gender, $ageOrder, b.sort, $weightOrder",
        array_merge([$tid], $withBracket)), 'id') : [];

    $totalReal = (int)scalar('SELECT COUNT(*) FROM matches WHERE tournament_id=? AND red_reg_id IS NOT NULL AND blue_reg_id IS NOT NULL', [$tid]);
    $target = intdiv($totalReal, 2);
    $methods = ['points','points','points','advantages','submission','decision'];
    $completed = 0;
    foreach ($orderedDivs as $did) {
        if ($completed >= $target) break;
        $maxRound = (int)scalar('SELECT MAX(round) FROM matches WHERE division_id=?', [$did]);
        for ($round = 1; $round <= $maxRound; $round++) {
            if ($completed >= $target) break;
            foreach (rows('SELECT * FROM matches WHERE division_id=? AND round=? AND red_reg_id IS NOT NULL AND blue_reg_id IS NOT NULL AND status="pending"', [$did, $round]) as $m) {
                if ($completed >= $target) break;
                $side = mt_rand(0, 1) ? 'red' : 'blue';
                $winnerId = $side === 'red' ? $m['red_reg_id'] : $m['blue_reg_id'];
                $method = $methods[array_rand($methods)];
                $rp = $side === 'red' ? mt_rand(2, 12) : mt_rand(0, 4);
                $bp = $side === 'blue' ? mt_rand(2, 12) : mt_rand(0, 4);
                if ($method === 'submission') { if ($side === 'red') $bp = 0; else $rp = 0; }
                q('UPDATE matches SET status="done", winner_reg_id=?, method=?, red_points=?, blue_points=? WHERE id=?', [$winnerId, $method, $rp, $bp, $m['id']]);
                advance_winner((int)$m['id']);
                $completed++;
            }
        }
        $pending = (int)scalar('SELECT COUNT(*) FROM matches WHERE division_id=? AND status!="done"', [$did]);
        $tot = (int)scalar('SELECT COUNT(*) FROM matches WHERE division_id=?', [$did]);
        if ($tot > 0) q('UPDATE divisions SET status=? WHERE id=?', [$pending === 0 ? 'done' : 'bracketed', $did]);
    }
    echo "Demo '$name' creado: 15 inscriptos, " . count($withBracket) . " llaves, $completed/$totalReal luchas jugadas.\n";
}

$ctx = compact('maleFirst','femaleFirst','lastNames','academyNames','beltId','ageAdulto','wcByCode');
seed_one('gi',   'Copa Gi Demo 2026',       'demo-gi-2026',   $ownerId, $bucketsByDisc['gi'],   $ctx);
seed_one('nogi', 'Grappling NoGi Demo 2026','demo-nogi-2026', $ownerId, $bucketsByDisc['nogi'], $ctx);
recompute_rankings();
echo "Rankings recalculados.\n";
