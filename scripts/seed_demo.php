<?php
/**
 * Seeder de demostracion:
 *   docker compose exec app php scripts/seed_demo.php
 *
 * Crea admin + organizador, torneo "Copa Demo BJJ" (open) con 4 academias,
 * ~130 inscriptos en todas las categorias/cinturones, llaves aleatorias,
 * simula la mayoria de las luchas y recalcula el ranking.
 */
require_once dirname(__DIR__) . '/src/bootstrap.php';

const SLUG = 'demo2026';

echo "== BJJ Tournament Manager - Seeder demo ==\n";

// --- Usuarios base -----------------------------------------------------
function ensure_user(string $name, string $email, string $pass, string $role): int {
    $u = row('SELECT id FROM users WHERE email = ?', [$email]);
    if ($u) return (int)$u['id'];
    q('INSERT INTO users (name, email, pass_hash, role, verified_at) VALUES (?,?,?,?,NOW())',
        [$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
    return (int)db()->lastInsertId();
}
$adminId = ensure_user('Admin', 'admin@demo.local', 'admin123', 'admin');
$orgId   = ensure_user('Organizador Demo', 'organizador@demo.local', 'demo123', 'user');
echo "Usuarios: admin@demo.local/admin123 · organizador@demo.local/demo123\n";

// --- Torneo (re-ejecutable: borra el demo anterior) --------------------
$old = row('SELECT id FROM tournaments WHERE slug = ?', [SLUG]);
if ($old) {
    q('DELETE FROM tournaments WHERE id = ?', [$old['id']]);
    echo "Torneo demo anterior eliminado.\n";
}
q('INSERT INTO tournaments (user_id, name, slug, type, event_date, max_participants, default_duration_sec, status)
   VALUES (?,?,?,?,?,?,?,?)',
    [$orgId, 'Copa Demo BJJ 2026', SLUG, 'open', date('Y-m-d', strtotime('+10 days')), 300, 300, 'open']);
$tid = (int)db()->lastInsertId();

// --- Academias y profesores --------------------------------------------
$academyDefs = [
    'Gracie Barra Demo' => [['Carlos Pereira', 'Sede Centro'], ['Julia Mendes', 'Sede Norte']],
    'Alliance Demo'     => [['Marcos Silva', 'Sede Única']],
    'Atos Demo'         => [['Andre Costa', 'Sede Sur'], ['Lucas Ribeiro', 'Sede Este']],
    'Checkmat Demo'     => [['Fernanda Lima', 'Sede Central']],
];
$academyIds = [];
foreach ($academyDefs as $aname => $profs) {
    q('INSERT INTO tournament_academies (tournament_id, name) VALUES (?,?)', [$tid, $aname]);
    $aid = (int)db()->lastInsertId();
    $academyIds[] = $aid;
    foreach ($profs as [$pname, $sede]) {
        q('INSERT INTO tournament_professors (tournament_id, academy_id, name, sede) VALUES (?,?,?,?)', [$tid, $aid, $pname, $sede]);
    }
}
echo "Academias: " . count($academyIds) . "\n";

// --- Inscriptos ---------------------------------------------------------
$firstM = ['Juan','Pedro','Lucas','Mateo','Diego','Bruno','Rafael','Thiago','Facundo','Nicolás','Gonzalo','Martín','Franco','Emiliano','Joaquín','Agustín','Ramiro','Santiago','Iván','Tomás','Leandro','Pablo','Hernán','Ezequiel'];
$firstF = ['María','Sofía','Valentina','Camila','Lucía','Julieta','Carla','Paula','Florencia','Agustina','Milagros','Rocío','Ana','Victoria','Josefina','Brenda','Daniela','Micaela'];
$last   = ['García','Rodríguez','Fernández','López','Martínez','González','Pérez','Sánchez','Romero','Torres','Álvarez','Ruiz','Silva','Moreno','Muñoz','Rojas','Molina','Castro','Vargas','Ríos','Acosta','Benítez','Medina','Herrera'];

$belts   = [];
foreach (rows('SELECT * FROM belts') as $b) $belts[$b['code']] = $b;
$ages    = [];
foreach (rows('SELECT * FROM age_divisions') as $a) $ages[$a['code']] = $a;
$wcs     = [];
foreach (rows('SELECT * FROM weight_classes') as $w) $wcs[$w['gender']][$w['code']] = $w;

/** specs: [gender, belt_code, age_code, wc_code, cantidad] */
$specs = [
    // Adultos masculino: todos los cinturones
    ['M','white','adulto','m_pluma',6], ['M','white','adulto','m_leve',8], ['M','white','adulto','m_medio',7], ['M','white','adulto','m_pesado',5],
    ['M','blue','adulto','m_pluma',5], ['M','blue','adulto','m_leve',6], ['M','blue','adulto','m_medio',6], ['M','blue','adulto','m_superpesado',4],
    ['M','purple','adulto','m_leve',4], ['M','purple','adulto','m_medio',5],
    ['M','brown','adulto','m_medio',4], ['M','black','adulto','m_medio',3],
    // Adultos femenino
    ['F','white','adulto','f_pena',5], ['F','white','adulto','f_leve',6], ['F','white','adulto','f_medio',4],
    ['F','blue','adulto','f_leve',4], ['F','blue','adulto','f_medio',4], ['F','purple','adulto','f_leve',3],
    // Masters 1-6
    ['M','white','master1','m_medio',5], ['M','blue','master1','m_pesado',4],
    ['M','blue','master2','m_medio',5], ['M','purple','master2','m_pesado',4],
    ['M','blue','master3','m_medio',4], ['M','purple','master4','m_pesado',3],
    ['M','brown','master5','m_medio',3], ['M','black','master6','m_pesado',2],
    ['F','blue','master1','f_medio',3], ['F','purple','master2','f_leve',3],
    // Juveniles
    ['M','white','juvenil','m_pluma',4], ['F','white','juvenil','f_pena',3],
    // Infantiles (cinturones infantiles + pesos infantiles)
    ['M','k_grey','inf_b','k_27',4], ['M','k_yellow','inf_c','k_33',5], ['M','k_green','inf_d','k_44',4],
    ['F','k_grey','inf_b','k_27',3], ['F','k_yellow','inf_c','k_30',4], ['F','k_orange','inf_d','k_40',3],
];

$profRows = rows('SELECT * FROM tournament_professors WHERE tournament_id = ?', [$tid]);
$profsByAcademy = [];
foreach ($profRows as $p) $profsByAcademy[$p['academy_id']][] = (int)$p['id'];

$n = 0;
$usedNames = [];
foreach ($specs as [$g, $beltCode, $ageCode, $wcCode, $count]) {
    $belt = $belts[$beltCode];
    $age  = $ages[$ageCode];
    $wc   = $wcs[$g][$wcCode] ?? $wcs['A'][$wcCode];
    for ($i = 0; $i < $count; $i++) {
        // Nombre unico
        do {
            $fn = $g === 'M' ? $firstM[array_rand($firstM)] : $firstF[array_rand($firstF)];
            $name = $fn . ' ' . $last[array_rand($last)];
        } while (isset($usedNames[$name]));
        $usedNames[$name] = true;

        // Edad dentro de la division (al 31/12)
        $minA = (int)$age['min_age'];
        $maxA = $age['max_age'] !== null ? (int)$age['max_age'] : $minA + 6;
        $ageYears = rand($minA, $maxA);
        $birthdate = (date('Y') - $ageYears) . '-' . str_pad((string)rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad((string)rand(1, 28), 2, '0', STR_PAD_LEFT);

        // Peso dentro de la categoria
        if ($wc['max_kg'] !== null) {
            $max = (float)$wc['max_kg'];
            $weight = round($max - (mt_rand(5, 60) / 10), 1);
        } else {
            $weight = round(($g === 'F' ? 80 : 101) + (mt_rand(0, 120) / 10), 1);
        }

        $n++;
        $email = 'competidor' . $n . '@demo.local';
        $aid = $academyIds[array_rand($academyIds)];
        $pid = $profsByAcademy[$aid][array_rand($profsByAcademy[$aid])];
        q('INSERT INTO registrations (tournament_id, name, email, gender, birthdate, weight_kg, belt_id, age_division_id, weight_class_id, academy_id, professor_id, verified)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,1)',
            [$tid, $name, $email, $g, $birthdate, $weight, $belt['id'], $age['id'], $wc['id'], $aid, $pid]);
    }
}
echo "Inscriptos: $n\n";

// --- Divisiones y llaves -------------------------------------------------
ensure_divisions($tid);
$divs = rows('SELECT * FROM divisions WHERE tournament_id = ?', [$tid]);
foreach ($divs as $d) {
    if (count(division_registrations((int)$d['id'])) >= 2) {
        generate_bracket((int)$d['id'], [], true);
    }
}
echo "Divisiones: " . count($divs) . " (llaves aleatorias generadas)\n";

// --- Simular luchas (dejamos 3 divisiones sin luchar para probar en vivo) --
$skipDivs = array_slice(array_column($divs, 'id'), 0, 3);
$methodsPool = ['points', 'points', 'points', 'submission', 'submission', 'advantages', 'decision'];

$simulated = 0;
do {
    $m = row('SELECT * FROM matches WHERE tournament_id = ? AND status = "pending"
              AND red_reg_id IS NOT NULL AND blue_reg_id IS NOT NULL
              AND division_id NOT IN (' . implode(',', array_map('intval', $skipDivs)) . ')
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
} while (true);
echo "Luchas simuladas: $simulated (3 divisiones quedaron pendientes para probar el marcador)\n";

// --- Ranking --------------------------------------------------------------
$r = recompute_rankings();
echo "Ranking recalculado: $r filas\n";

$done = (int)scalar('SELECT COUNT(*) FROM divisions WHERE tournament_id=? AND status="done"', [$tid]);
echo "\nListo ✅  Torneo: Copa Demo BJJ 2026\n";
echo "  Link inscripción: " . APP_URL . "/t/" . SLUG . "\n";
echo "  Divisiones completas: $done/" . count($divs) . "\n";
echo "  Login organizador: organizador@demo.local / demo123\n";
echo "  Login admin:       admin@demo.local / admin123\n";
