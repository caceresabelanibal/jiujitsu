<?php
/**
 * Seeder de 4 torneos adicionales de demostracion:
 *   docker compose exec app php scripts/seed_more_tournaments.php
 *
 * Crea (re-ejecutable, borra los anteriores por slug):
 *   - 2 torneos SIN COMENZAR (llaves generadas, 0 luchas jugadas), 20-30 inscriptos.
 *   - 2 torneos EMPEZADOS POR LA MITAD (~50% de las luchas reales ya jugadas).
 * Junto con "Copa Demo BJJ 2026" (seed_demo.php) quedan 5 torneos en total.
 */
require_once dirname(__DIR__) . '/src/bootstrap.php';

echo "== Seeder: 4 torneos adicionales ==\n";

function ensure_org(): int {
    $u = row('SELECT id FROM users WHERE email = ?', ['organizador@demo.local']);
    if ($u) return (int)$u['id'];
    q('INSERT INTO users (name, email, pass_hash, role, verified_at) VALUES (?,?,?,?,NOW())',
        ['Organizador Demo', 'organizador@demo.local', password_hash('demo123', PASSWORD_DEFAULT), 'user']);
    return (int)db()->lastInsertId();
}
$orgId = ensure_org();

$firstM = ['Juan','Pedro','Lucas','Mateo','Diego','Bruno','Rafael','Thiago','Facundo','Nicolás','Gonzalo','Martín','Franco','Emiliano','Joaquín','Agustín','Ramiro','Santiago','Iván','Tomás','Leandro','Pablo','Hernán','Ezequiel'];
$firstF = ['María','Sofía','Valentina','Camila','Lucía','Julieta','Carla','Paula','Florencia','Agustina','Milagros','Rocío','Ana','Victoria','Josefina','Brenda','Daniela','Micaela'];
$last   = ['García','Rodríguez','Fernández','López','Martínez','González','Pérez','Sánchez','Romero','Torres','Álvarez','Ruiz','Silva','Moreno','Muñoz','Rojas','Molina','Castro','Vargas','Ríos','Acosta','Benítez','Medina','Herrera'];

$belts = []; foreach (rows('SELECT * FROM belts') as $b) $belts[$b['code']] = $b;
$ages  = []; foreach (rows('SELECT * FROM age_divisions') as $a) $ages[$a['code']] = $a;
$wcs   = []; foreach (rows('SELECT * FROM weight_classes') as $w) $wcs[$w['gender']][$w['code']] = $w;

/**
 * @param array $specs [gender, belt_code, age_code, wc_code, cantidad][]
 * @param float $simulateFraction 0 = nada jugado, 0.5 = ~mitad de las luchas reales
 */
function seed_tournament(string $name, string $slug, int $orgId, array $academyNames, array $specs,
                          string $status, int $eventDaysOffset, float $simulateFraction): void {
    global $firstM, $firstF, $last, $belts, $ages, $wcs;

    $old = row('SELECT id FROM tournaments WHERE slug = ?', [$slug]);
    if ($old) {
        q('DELETE FROM tournaments WHERE id = ?', [$old['id']]);
        echo "  (torneo anterior '$slug' eliminado)\n";
    }
    q('INSERT INTO tournaments (user_id, name, slug, type, event_date, max_participants, default_duration_sec, status)
       VALUES (?,?,?,?,?,?,?,?)',
        [$orgId, $name, $slug, 'open', date('Y-m-d', strtotime("$eventDaysOffset days")), 200, 300, $status]);
    $tid = (int)db()->lastInsertId();

    $academyIds = [];
    foreach ($academyNames as $aname) {
        q('INSERT INTO tournament_academies (tournament_id, name) VALUES (?,?)', [$tid, $aname]);
        $aid = (int)db()->lastInsertId();
        $academyIds[] = $aid;
        q('INSERT INTO tournament_professors (tournament_id, academy_id, name, sede) VALUES (?,?,?,?)',
            [$tid, $aid, 'Profesor ' . $aname, 'Sede Única']);
    }
    $profsByAcademy = [];
    foreach (rows('SELECT * FROM tournament_professors WHERE tournament_id = ?', [$tid]) as $p) {
        $profsByAcademy[$p['academy_id']][] = (int)$p['id'];
    }

    $n = 0;
    $usedNames = [];
    foreach ($specs as [$g, $beltCode, $ageCode, $wcCode, $count]) {
        $belt = $belts[$beltCode];
        $age  = $ages[$ageCode];
        $wc   = $wcs[$g][$wcCode] ?? $wcs['A'][$wcCode];
        for ($i = 0; $i < $count; $i++) {
            do {
                $fn = $g === 'M' ? $firstM[array_rand($firstM)] : $firstF[array_rand($firstF)];
                $name2 = $fn . ' ' . $last[array_rand($last)];
            } while (isset($usedNames[$name2]));
            $usedNames[$name2] = true;

            $minA = (int)$age['min_age'];
            $maxA = $age['max_age'] !== null ? (int)$age['max_age'] : $minA + 6;
            $ageYears = rand($minA, $maxA);
            $birthdate = (date('Y') - $ageYears) . '-' . str_pad((string)rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad((string)rand(1, 28), 2, '0', STR_PAD_LEFT);

            if ($wc['max_kg'] !== null) {
                $weight = round((float)$wc['max_kg'] - (mt_rand(5, 60) / 10), 1);
            } else {
                $weight = round(($g === 'F' ? 80 : 101) + (mt_rand(0, 120) / 10), 1);
            }

            $n++;
            $email = 'p' . $tid . '_' . $n . '@demo.local';
            $aid = $academyIds[array_rand($academyIds)];
            $pid = $profsByAcademy[$aid][array_rand($profsByAcademy[$aid])];
            q('INSERT INTO registrations (tournament_id, name, email, gender, birthdate, weight_kg, belt_id, age_division_id, weight_class_id, academy_id, professor_id, verified)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,1)',
                [$tid, $name2, $email, $g, $birthdate, $weight, $belt['id'], $age['id'], $wc['id'], $aid, $pid]);
        }
    }

    ensure_divisions($tid);
    $divs = rows('SELECT * FROM divisions WHERE tournament_id = ?', [$tid]);
    foreach ($divs as $d) {
        if (count(division_registrations((int)$d['id'])) >= 2) {
            generate_bracket((int)$d['id'], [], true);
        }
    }

    $simulated = 0;
    if ($simulateFraction > 0) {
        $totalReal = (int)scalar('SELECT COUNT(*) FROM matches WHERE tournament_id=? AND red_reg_id IS NOT NULL AND blue_reg_id IS NOT NULL', [$tid]);
        $target = (int)round($totalReal * $simulateFraction);
        $methodsPool = ['points', 'points', 'points', 'submission', 'submission', 'advantages', 'decision'];
        while ($simulated < $target) {
            $m = row('SELECT * FROM matches WHERE tournament_id = ? AND status = "pending"
                      AND red_reg_id IS NOT NULL AND blue_reg_id IS NOT NULL
                      ORDER BY division_id, round, slot LIMIT 1', [$tid]);
            if (!$m) break;
            $method = $methodsPool[array_rand($methodsPool)];
            $winner = rand(0, 1) ? 'red' : 'blue';
            $loser = $winner === 'red' ? 'blue' : 'red';
            $pw = [0, 2, 3, 4, 5, 6, 7, 8, 9, 12][rand(2, 9)];
            $pl = max(0, $pw - [2, 3, 4, 5][rand(0, 3)]);
            $aw = rand(0, 3); $al = rand(0, 2);
            if ($method === 'advantages') { $pl = $pw; $al = max(0, $aw - 1); if ($aw === $al) $aw++; }
            if ($method === 'decision')   { $pl = $pw; $al = $aw; }
            $dur = (int)$m['duration_sec'];
            $elapsed = $method === 'submission' ? rand(25, max(30, $dur - 10)) : $dur;
            q("UPDATE matches SET status='done', winner_reg_id = {$winner}_reg_id, method=?,
                        {$winner}_points=?, {$loser}_points=?, {$winner}_adv=?, {$loser}_adv=?,
                        {$winner}_pen=?, {$loser}_pen=?, elapsed_sec=?, timer_remaining=? WHERE id=?",
                [$method, $pw, $pl, $aw, $al, rand(0, 1), rand(0, 2), $elapsed, max(0, $dur - $elapsed), $m['id']]);
            advance_winner((int)$m['id']);
            propagate_byes((int)$m['division_id']);
            $simulated++;
        }
    }

    $done = (int)scalar('SELECT COUNT(*) FROM divisions WHERE tournament_id=? AND status="done"', [$tid]);
    echo "'$name' ($slug): $n inscriptos, " . count($divs) . " divisiones, $simulated luchas jugadas, $done divisiones completas.\n";
}

// --- 2 torneos SIN COMENZAR (20-30 inscriptos, 0 luchas) --------------------
seed_tournament('Copa Norte 2026', 'copanorte2026', $orgId,
    ['Gracie Barra Norte', 'Alliance Norte'],
    [
        ['M','white','adulto','m_leve',4], ['M','blue','adulto','m_medio',4], ['M','purple','adulto','m_medio',3],
        ['F','white','adulto','f_leve',3], ['F','blue','adulto','f_medio',3],
        ['M','white','juvenil','m_pluma',3], ['M','k_yellow','inf_c','k_33',3], ['F','k_grey','inf_b','k_27',2],
    ],
    'open', 20, 0.0);

seed_tournament('Copa Sur 2026', 'copasur2026', $orgId,
    ['Atos Sur', 'Checkmat Sur', 'Alliance Sur'],
    [
        ['M','white','adulto','m_pena',3], ['M','blue','adulto','m_leve',4], ['M','black','adulto','m_medio',2],
        ['F','white','adulto','f_medio',4], ['F','purple','adulto','f_leve',2],
        ['M','blue','master1','m_medio',3], ['M','brown','master2','m_pesado',2],
        ['M','white','juvenil','m_pluma',3], ['F','k_orange','inf_d','k_40',3],
    ],
    'open', 25, 0.0);

// --- 2 torneos EMPEZADOS POR LA MITAD ---------------------------------------
seed_tournament('Copa Este 2026', 'copaeste2026', $orgId,
    ['Gracie Barra Este', 'Atos Este', 'Checkmat Este'],
    [
        ['M','white','adulto','m_pluma',4], ['M','white','adulto','m_leve',4], ['M','blue','adulto','m_medio',4],
        ['M','purple','adulto','m_pesado',3], ['M','black','adulto','m_medio',2],
        ['F','white','adulto','f_pena',3], ['F','blue','adulto','f_leve',3],
        ['M','blue','master2','m_medio',3], ['M','brown','master5','m_medio',2],
        ['M','k_green','inf_d','k_44',2],
    ],
    'running', -3, 0.5);

seed_tournament('Copa Oeste 2026', 'copaoeste2026', $orgId,
    ['Alliance Oeste', 'Gracie Barra Oeste'],
    [
        ['M','white','adulto','m_leve',4], ['M','blue','adulto','m_pluma',3], ['M','blue','adulto','m_superpesado',3],
        ['M','purple','adulto','m_medio',3],
        ['F','white','adulto','f_leve',3], ['F','blue','adulto','f_medio',3],
        ['M','white','master1','m_medio',3], ['M','purple','master4','m_pesado',2],
        ['F','k_yellow','inf_c','k_30',2], ['M','k_grey','inf_b','k_27',2],
    ],
    'running', -1, 0.5);

$r = recompute_rankings();
echo "Ranking recalculado: $r filas\n";
echo "\nListo. Total de torneos en la base: " . scalar('SELECT COUNT(*) FROM tournaments') . "\n";
