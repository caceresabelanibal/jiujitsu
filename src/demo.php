<?php
/**
 * Torneos de muestra ("demo"): dos torneos (uno Gi, uno NoGi) que TODOS los
 * usuarios pueden ver y operar, para mostrar la plataforma andando sin tener
 * que cargar gente cada vez. Solo el admin puede resetearlos al "momento cero"
 * (mitad de las luchas jugadas, certificados generados) — por boton en el
 * centro de operacion o por el cron "reset_demo".
 *
 * Toda la logica de armado vive aca (la comparten el seeder de CLI, el boton
 * de reset y el cron) para que el estado demo se regenere igual siempre.
 */

/** Definicion de los dos torneos demo, indexada por slug. */
function demo_definitions(): array {
    return [
        'demo-gi-2026' => [
            'discipline' => 'gi',
            'name' => 'Copa Gi Demo 2026',
            'buckets' => [
                ['M','blue','m_pena',3], ['M','blue','m_medio',3], ['M','purple','m_leve',3],
                ['M','brown','m_medio',2], ['M','black','m_medio',2], ['F','blue','f_leve',2],
            ],
        ],
        'demo-nogi-2026' => [
            'discipline' => 'nogi',
            'name' => 'Grappling NoGi Demo 2026',
            'buckets' => [
                ['M','blue','m_pena',3], ['M','white','m_pena',2], ['M','purple','m_medio',3],
                ['M','brown','m_medio',3], ['M','black','m_leve',2], ['F','blue','f_leve',2],
            ],
        ],
    ];
}

/** Contexto compartido (datos de referencia + pools de nombres/academias). */
function demo_context(): array {
    $beltId = [];
    foreach (rows("SELECT id, code FROM belts WHERE is_kids=0") as $b) $beltId[$b['code']] = (int)$b['id'];
    $wcByCode = [];
    foreach (rows("SELECT id, code, max_kg FROM weight_classes WHERE is_absolute=0") as $w) $wcByCode[$w['code']] = $w;
    return [
        'maleFirst' => ['Lucas','Mateo','Santiago','Bruno','Rodrigo','Facundo','Tomás','Nicolás','Joaquín','Ignacio',
                        'Gabriel','Martín','Emiliano','Federico','Ramiro','Agustín','Diego','Franco','Julián','Maximiliano'],
        'femaleFirst' => ['Sofía','Valentina','Camila','Martina','Luana','Julieta','Carolina','Florencia','Micaela','Antonella'],
        'lastNames' => ['Fernández','González','Rodríguez','López','Martínez','Pereira','Silva','Souza','Oliveira','Almeida',
                        'Gómez','Díaz','Torres','Ramírez','Ferreira','Castro','Romano','Núñez','Ortiz','Medina','Acosta','Sosa'],
        'academyNames' => ['Gracie Barra', 'Alliance', 'Atos', 'Checkmat', 'Ns Brotherhood', 'GF Team'],
        'beltId' => $beltId,
        'ageAdulto' => (int)scalar("SELECT id FROM age_divisions WHERE code='adulto'"),
        'wcByCode' => $wcByCode,
    ];
}

/**
 * Puebla un torneo demo YA CREADO y vacio: academias, 15 inscriptos, divisiones,
 * llaves, la mitad de las luchas jugadas y los certificados generados.
 */
function demo_populate(int $tid, string $discipline, array $buckets, array $ctx): int {
    ['maleFirst'=>$maleFirst,'femaleFirst'=>$femaleFirst,'lastNames'=>$lastNames,
     'academyNames'=>$academyNames,'beltId'=>$beltId,'ageAdulto'=>$ageAdulto,'wcByCode'=>$wcByCode] = $ctx;

    $tourRow = row('SELECT * FROM tournaments WHERE id=?', [$tid]);

    $acadIds = [];
    foreach ($academyNames as $an) {
        q('INSERT INTO tournament_academies (tournament_id, name) VALUES (?,?)', [$tid, $an]);
        $aid = (int)db()->lastInsertId();
        $acadIds[] = $aid;
        q('INSERT INTO tournament_professors (tournament_id, academy_id, name) VALUES (?,?,?)',
            [$tid, $aid, 'Prof. ' . $lastNames[array_rand($lastNames)]]);
    }

    $used = [];
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

    demo_generate_certificates($tid);
    return $completed;
}

/**
 * Genera (sin enviar por mail) los certificados del torneo demo: participacion
 * para todos los verificados + podios (oro/plata/bronce) de las divisiones ya
 * terminadas. Crea las filas en `certificates` y los PDFs, para que la pestana
 * de certificados y las descargas se puedan explorar. No encola mails (no queremos
 * spamear direcciones @demo.local).
 */
function demo_generate_certificates(int $tid): void {
    $queue = []; // reg_id => type (el podio pisa participacion)
    foreach (rows('SELECT id FROM registrations WHERE tournament_id=? AND verified=1', [$tid]) as $r) {
        $queue[(int)$r['id']] = 'participation';
    }
    foreach (rows('SELECT id FROM divisions WHERE tournament_id=? AND status="done"', [$tid]) as $d) {
        [$g, $s, $b] = division_podium((int)$d['id']);
        if ($g) $queue[$g] = 'gold';
        if ($s) $queue[$s] = 'silver';
        if ($b) $queue[$b] = 'bronze';
    }
    foreach ($queue as $regId => $type) {
        try { certificate_generate($regId, $type); } catch (Throwable $e) { /* best-effort en demo */ }
    }
}

/** Borra todo el contenido (inscriptos/divisiones/luchas/certificados/academias) de un torneo, sin borrar el torneo. */
function demo_wipe_content(int $tid): void {
    // Orden hijo-a-padre: matches referencia registrations sin cascade, asi que primero las luchas.
    q('DELETE FROM certificates WHERE tournament_id=?', [$tid]);
    q('DELETE FROM matches WHERE tournament_id=?', [$tid]);        // cascade: match_events
    q('DELETE FROM divisions WHERE tournament_id=?', [$tid]);      // cascade: division_members
    q('DELETE FROM registrations WHERE tournament_id=?', [$tid]);
    q('DELETE FROM tournament_academies WHERE tournament_id=?', [$tid]); // cascade: tournament_professors
    // borrar los PDFs viejos de este torneo del disco
    foreach (glob(BASE_PATH . "/storage/certificates/cert-$tid-*.pdf") ?: [] as $f) @unlink($f);
}

/**
 * Resetea un torneo demo al "momento cero": borra su contenido y lo vuelve a
 * armar (mitad de luchas jugadas + certificados). Mantiene el mismo id/slug/dueno
 * (asi los links no cambian). Devuelve la cantidad de luchas completadas.
 */
function reset_demo_tournament(int $tid): int {
    $t = row('SELECT * FROM tournaments WHERE id=? AND is_demo=1', [$tid]);
    if (!$t) return 0;
    $def = demo_definitions()[$t['slug']] ?? null;
    if (!$def) return 0;

    demo_wipe_content($tid);
    q("UPDATE tournaments SET status='running', event_date=DATE_SUB(CURDATE(), INTERVAL 1 DAY), certs_requested=0 WHERE id=?", [$tid]);
    $completed = demo_populate($tid, $def['discipline'], $def['buckets'], demo_context());
    recompute_rankings();
    return $completed;
}

/** Resetea los dos torneos demo (lo usa el cron "reset_demo"). Devuelve un resumen por torneo. */
function reset_all_demo_tournaments(): array {
    $out = [];
    foreach (rows('SELECT id, name FROM tournaments WHERE is_demo=1 ORDER BY id') as $t) {
        $n = reset_demo_tournament((int)$t['id']);
        $out[] = "{$t['name']}: $n luchas";
    }
    return $out;
}

/**
 * Crea los dos torneos demo si todavia no existen (idempotente por slug). Lo usa
 * el seeder de CLI (entrypoint con SEED_DEMO=1). No toca los que ya existen.
 */
function seed_demo_tournaments(int $ownerId): array {
    $ctx = demo_context();
    $out = [];
    foreach (demo_definitions() as $slug => $def) {
        if (row('SELECT id FROM tournaments WHERE slug = ?', [$slug])) {
            $out[] = "Demo '{$def['name']}' ya existe (slug $slug) — sin cambios.";
            continue;
        }
        q("INSERT INTO tournaments (user_id, name, slug, type, discipline, status, event_date, default_duration_sec, max_participants, is_demo)
           VALUES (?,?,?,'open',?,'running', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 300, 60, 1)",
           [$ownerId, $def['name'], $slug, $def['discipline']]);
        $tid = (int)db()->lastInsertId();
        $completed = demo_populate($tid, $def['discipline'], $def['buckets'], $ctx);
        $out[] = "Demo '{$def['name']}' creado: 15 inscriptos, $completed luchas jugadas.";
    }
    recompute_rankings();
    return $out;
}
