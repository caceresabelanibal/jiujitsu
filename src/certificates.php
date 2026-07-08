<?php
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Certificados en PDF (dompdf) con estilo: medalla, cinturon, logos, torneo.
 */
function certificate_generate(int $registrationId, string $type): string {
    $r = row('SELECT r.*, t.name AS tournament_name, t.logo AS tournament_logo, t.event_date,
                     a.name AS academy_name, a.logo AS academy_logo,
                     b.name_es AS belt_es, b.name_en AS belt_en, b.color_hex,
                     ad.name_es AS age_es, ad.name_en AS age_en,
                     wc.name_es AS wc_es, wc.name_en AS wc_en
              FROM registrations r
              JOIN tournaments t ON t.id = r.tournament_id
              LEFT JOIN tournament_academies a ON a.id = r.academy_id
              JOIN belts b ON b.id = r.belt_id
              JOIN age_divisions ad ON ad.id = r.age_division_id
              JOIN weight_classes wc ON wc.id = r.weight_class_id
              WHERE r.id = ?', [$registrationId]);
    if (!$r) throw new RuntimeException('Registration not found');

    $isEn = lang() === 'en';
    $fights = fights_count($registrationId);

    $titles = [
        'gold'          => [$isEn ? 'GOLD MEDAL - 1st PLACE' : 'MEDALLA DE ORO - 1er PUESTO', '#d4af37', '1'],
        'silver'        => [$isEn ? 'SILVER MEDAL - 2nd PLACE' : 'MEDALLA DE PLATA - 2do PUESTO', '#9fa8b3', '2'],
        'bronze'        => [$isEn ? 'BRONZE MEDAL - 3rd PLACE' : 'MEDALLA DE BRONCE - 3er PUESTO', '#b0793d', '3'],
        'participation' => [$isEn ? 'CERTIFICATE OF PARTICIPATION' : 'CERTIFICADO DE PARTICIPACIÓN', '#3a6ea5', '★'],
    ];
    [$title, $accent, $medal] = $titles[$type];

    $beltName = $isEn ? $r['belt_en'] : $r['belt_es'];
    $ageName  = $isEn ? $r['age_en'] : $r['age_es'];
    $wcName   = $isEn ? $r['wc_en'] : $r['wc_es'];
    $genderName = $r['gender'] === 'M' ? ($isEn ? 'Male' : 'Masculino') : ($isEn ? 'Female' : 'Femenino');
    $category = e("$genderName · $ageName · $wcName");

    $fightsLine = '';
    if ($fights > 0) {
        $fightsLine = '<p class="fights">' . ($isEn ? "Fights in this tournament: <b>$fights</b>" : "Luchas disputadas en este torneo: <b>$fights</b>") . '</p>';
    }

    $logoHtml = '';
    foreach (['tournament_logo', 'academy_logo'] as $lg) {
        if ($r[$lg] && file_exists(BASE_PATH . '/public/' . $r[$lg])) {
            $data = base64_encode(file_get_contents(BASE_PATH . '/public/' . $r[$lg]));
            $mime = mime_content_type(BASE_PATH . '/public/' . $r[$lg]);
            $logoHtml .= "<img src=\"data:$mime;base64,$data\" style=\"height:70px;margin:0 14px\">";
        }
    }

    $certText = $isEn ? 'This certificate is proudly presented to' : 'Se otorga el presente certificado a';
    $academyLine = $r['academy_name'] ? '<p class="academy">' . e($r['academy_name']) . '</p>' : '';
    $dateStr = $r['event_date'] ? date('d/m/Y', strtotime($r['event_date'])) : date('d/m/Y');
    $beltColor = $r['color_hex'];
    // Texto oscuro sobre cinturones claros (blanco, amarillo, gris...)
    [$cr, $cg, $cb] = sscanf($beltColor, '#%02x%02x%02x');
    $beltFg = (0.299 * $cr + 0.587 * $cg + 0.114 * $cb) > 150 ? '#222' : '#fff';
    $beltText = $isEn ? "Belt: " : "Cinturón: ";
    $tournamentNameEsc = e($r['tournament_name']);
    $nameEsc = e($r['name']);
    $beltNameEsc = e($beltName);

    $html = <<<HTML
<style>
  @page { margin: 0; }
  body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 0; }
  .cert { position: absolute; top: 0; left: 0; width: 1118px; height: 790px;
          padding: 34px 44px; box-sizing: border-box; border: 14px solid $accent; background: #fdfcf8; }
  .inner { border: 3px double #555; height: 100%; box-sizing: border-box;
           padding: 40px 40px 0; text-align: center; position: relative; }
  h1 { color: $accent; font-size: 32px; letter-spacing: 3px; margin: 14px 0 4px; }
  .medal { display: inline-block; width: 84px; height: 84px; border-radius: 50%;
           background: $accent; border: 5px solid #444; color: #fff; font-size: 44px;
           font-weight: bold; line-height: 78px; }
  .tname { font-size: 24px; color: #222; margin: 6px 0 20px; font-weight: bold; }
  .present { color: #666; font-size: 14px; margin: 16px 0 4px; text-transform: uppercase; letter-spacing: 2px; }
  .name { font-size: 40px; color: #111; margin: 8px 0; font-weight: bold;
          border-bottom: 3px solid $accent; display: inline-block; padding: 0 36px 8px; }
  .category { font-size: 17px; color: #333; margin: 16px 0 6px; }
  .belt { display: inline-block; padding: 7px 30px; border-radius: 5px; color: $beltFg; font-weight: bold;
          font-size: 16px; background: $beltColor; border: 1px solid #333; margin-top: 8px; }
  .academy { font-size: 18px; color: #555; font-style: italic; margin: 12px 0 0; }
  .fights { font-size: 14px; color: #555; margin: 10px 0 0; }
  .footer { position: absolute; bottom: 16px; left: 0; right: 0; font-size: 12px; color: #888; }
  .logos { margin-bottom: 8px; }
</style>
<div class="cert"><div class="inner">
  <div class="logos">$logoHtml</div>
  <span class="medal">$medal</span>
  <h1>$title</h1>
  <p class="tname">$tournamentNameEsc</p>
  <p class="present">$certText</p>
  <p class="name">$nameEsc</p>
  <p class="category">$category</p>
  <span class="belt">$beltText$beltNameEsc</span>
  $academyLine
  $fightsLine
  <div class="footer">Jiu-Jitsu · $dateStr · BJJ Tournament Manager</div>
</div></div>
HTML;

    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $dir = BASE_PATH . '/storage/certificates';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $path = "$dir/cert-{$r['tournament_id']}-{$registrationId}-{$type}.pdf";
    file_put_contents($path, $dompdf->output());

    q('INSERT INTO certificates (tournament_id, registration_id, type, pdf_path) VALUES (?,?,?,?)
       ON DUPLICATE KEY UPDATE pdf_path = VALUES(pdf_path)',
        [$r['tournament_id'], $registrationId, $type, $path]);
    return $path;
}

/**
 * Genera y encola por mail los certificados del torneo:
 * podios de cada division terminada + participacion de todos los verificados.
 * $limit > 0 procesa solo un lote (el resto lo completa el cron "certificates").
 * Devuelve ['sent' => procesados ahora, 'remaining' => pendientes].
 */
function certificates_send_all(int $tournamentId, bool $podium = true, bool $participation = true, int $limit = 0): array {
    set_time_limit(0);
    $t = row('SELECT * FROM tournaments WHERE id = ?', [$tournamentId]);
    $isEn = lang() === 'en';

    $queue = []; // reg_id => type (el podio pisa participacion)
    if ($participation) {
        foreach (rows('SELECT id FROM registrations WHERE tournament_id=? AND verified=1', [$tournamentId]) as $r) {
            $queue[(int)$r['id']] = 'participation';
        }
    }
    if ($podium) {
        foreach (rows('SELECT id FROM divisions WHERE tournament_id=? AND status="done"', [$tournamentId]) as $d) {
            [$g, $s, $b] = division_podium((int)$d['id']);
            if ($g) $queue[$g] = 'gold';
            if ($s) $queue[$s] = 'silver';
            if ($b) $queue[$b] = 'bronze';
        }
    }

    // Saca los ya enviados
    foreach (rows('SELECT registration_id, type FROM certificates WHERE tournament_id=? AND emailed_at IS NOT NULL', [$tournamentId]) as $c) {
        if (($queue[(int)$c['registration_id']] ?? null) === $c['type']) {
            unset($queue[(int)$c['registration_id']]);
        }
    }

    $sent = 0;
    foreach ($queue as $regId => $type) {
        if ($limit > 0 && $sent >= $limit) break;
        $path = certificate_generate($regId, $type);
        $reg = row('SELECT * FROM registrations WHERE id = ?', [$regId]);
        $subject = ($isEn ? 'Your certificate - ' : 'Tu certificado - ') . $t['name'];
        $body = mail_layout($subject, '<p>' .
            ($isEn ? "Congratulations <b>{$reg['name']}</b>! Attached is your certificate from <b>{$t['name']}</b>. OSS! 🥋"
                   : "¡Felicitaciones <b>{$reg['name']}</b>! Te adjuntamos tu certificado del torneo <b>{$t['name']}</b>. ¡OSS! 🥋") . '</p>');
        queue_mail($reg['email'], $reg['name'], $subject, $body, $path);
        q('UPDATE certificates SET emailed_at = NOW() WHERE tournament_id=? AND registration_id=? AND type=?',
            [$tournamentId, $regId, $type]);
        $sent++;
    }
    $remaining = count($queue) - $sent;

    // Marca/limpia el flag para que el cron siga o pare
    q('UPDATE tournaments SET certs_requested = ? WHERE id = ?', [$remaining > 0 ? 1 : 0, $tournamentId]);
    if ($remaining > 0) {
        set_setting("certs_opts_$tournamentId", ['podium' => $podium, 'participation' => $participation]);
    }
    return ['sent' => $sent, 'remaining' => $remaining];
}
