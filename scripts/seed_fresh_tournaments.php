<?php
/**
 * Seeder de limpieza: borra TODOS los torneos existentes y crea 3 torneos
 * nuevos, a cero (sin ninguna lucha jugada), cada uno con inscriptos en
 * todos los cinturones (infantiles/juveniles, negro, marrón, violeta, azul,
 * blanco) para poder verificar el orden de corrida en cualquier pantalla.
 *
 *   docker compose exec app php scripts/seed_fresh_tournaments.php
 */
require_once dirname(__DIR__) . '/src/bootstrap.php';

echo "== Seeder: limpieza y 3 torneos nuevos ==\n";

$deleted = (int)scalar('SELECT COUNT(*) FROM tournaments');
q('DELETE FROM tournaments');
echo "Torneos anteriores eliminados: $deleted\n";

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

/** @param array $specs [gender, belt_code, age_code, wc_code, cantidad][] */
function seed_tournament(string $name, string $slug, int $orgId, array $academyNames, array $specs, int $eventDaysOffset): void {
    global $firstM, $firstF, $last, $belts, $ages, $wcs;

    q('INSERT INTO tournaments (user_id, name, slug, type, event_date, max_participants, default_duration_sec, status)
       VALUES (?,?,?,?,?,?,?,?)',
        [$orgId, $name, $slug, 'open', date('Y-m-d', strtotime("$eventDaysOffset days")), 200, 300, 'open']);
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

    echo "'$name' ($slug): $n inscriptos, " . count($divs) . " divisiones, 0 luchas jugadas.\n";
}

// Cada torneo cubre los 6 grupos del orden: infantiles/juveniles, negro, marrón, violeta, azul, blanco.
seed_tournament('Copa Primavera 2026', 'primavera2026', $orgId,
    ['Gracie Barra', 'Alliance', 'Atos'],
    [
        ['M','k_yellow','inf_c','k_33',3], ['F','k_grey','inf_b','k_27',2], ['M','white','juvenil','m_pluma',2],
        ['M','black','adulto','m_medio',2], ['M','brown','adulto','m_pesado',2], ['M','purple','adulto','m_leve',3],
        ['M','blue','adulto','m_medio',4], ['F','blue','adulto','f_leve',3],
        ['M','white','adulto','m_pluma',4], ['F','white','adulto','f_medio',3],
    ],
    30);

seed_tournament('Copa Verano 2026', 'verano2026', $orgId,
    ['Checkmat', 'Alliance', 'Gracie Barra'],
    [
        ['F','k_orange','inf_d','k_40',2], ['M','k_green','inf_d','k_44',3], ['M','white','juvenil','m_pluma',2],
        ['M','black','master1','m_medio',2], ['M','brown','master2','m_pesado',2], ['F','purple','adulto','f_leve',2],
        ['M','purple','adulto','m_medio',3], ['M','blue','adulto','m_leve',4], ['F','blue','adulto','f_medio',3],
        ['M','white','adulto','m_medio',4], ['M','white','master3','m_leve',3],
    ],
    45);

seed_tournament('Copa Invierno 2026', 'invierno2026', $orgId,
    ['Atos', 'Checkmat', 'Alliance'],
    [
        ['M','k_grey','inf_b','k_27',2], ['F','k_yellow','inf_c','k_30',2], ['F','white','juvenil','f_pena',2],
        ['M','black','adulto','m_pesado',2], ['M','brown','master1','m_medio',2], ['M','purple','master2','m_pesado',2],
        ['F','purple','adulto','f_medio',2], ['M','blue','adulto','m_superpesado',3], ['M','blue','master4','m_medio',3],
        ['F','blue','adulto','f_leve',3], ['M','white','adulto','m_leve',4], ['F','white','adulto','f_pena',3],
    ],
    60);

$r = recompute_rankings();
echo "Ranking recalculado: $r filas\n";
echo "\nListo. Total de torneos en la base: " . scalar('SELECT COUNT(*) FROM tournaments') . "\n";
