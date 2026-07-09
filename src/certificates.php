<?php
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Certificados estilo diploma: fuentes caligraficas, patron de seguridad (guilloche),
 * cinturon dibujado, sello Taninzu y codigo de verificacion unico.
 */

/** Fondo de seguridad tipo guilloche + microtexto (generado con GD, cacheado) */
function certificate_pattern_png(): string {
    $path = BASE_PATH . '/storage/certificates/_pattern.png';
    if (file_exists($path)) return $path;
    $w = 1122; $h = 790;
    $im = imagecreatetruecolor($w, $h);
    imagefill($im, 0, 0, imagecolorallocate($im, 253, 251, 244));
    // Familias de ondas entrelazadas muy sutiles
    $c1 = imagecolorallocate($im, 238, 231, 215);
    $c2 = imagecolorallocate($im, 242, 236, 222);
    for ($k = 0; $k < 46; $k++) {
        $base = $k * ($h / 44);
        $amp = 6 + ($k % 3) * 3;
        $phase = $k * 0.7;
        $col = $k % 2 ? $c1 : $c2;
        $prev = null;
        for ($x = 0; $x <= $w; $x += 3) {
            $y = (int)round($base + sin($x / 38 + $phase) * $amp + sin($x / 11 - $phase) * 2);
            if ($prev) imageline($im, $prev[0], $prev[1], $x, $y, $col);
            $prev = [$x, $y];
        }
    }
    // Microtexto de seguridad arriba y abajo
    $micro = imagecolorallocate($im, 210, 200, 178);
    $line = str_repeat('TANINZU.COM * CERTIFICADO ORIGINAL * ', 6);
    imagestring($im, 1, 6, 3, $line, $micro);
    imagestring($im, 1, 6, $h - 11, $line, $micro);
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0775, true);
    imagepng($im, $path);
    return $path;
}

/** Logo Taninzu (cinturon rojo) rasterizado para el PDF (cacheado) */
function taninzu_logo_png(): string {
    $path = BASE_PATH . '/storage/certificates/_logo.png';
    if (file_exists($path)) return $path;
    $s = 8;
    $im = imagecreatetruecolor(64 * $s, 64 * $s);
    imagesavealpha($im, true);
    imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
    $red   = imagecolorallocate($im, 201, 37, 44);
    $dark  = imagecolorallocate($im, 156, 27, 33);
    $mid   = imagecolorallocate($im, 224, 58, 65);
    $black = imagecolorallocate($im, 28, 28, 30);
    imagefilledrectangle($im, 2 * $s, 25 * $s, 62 * $s, 39 * $s, $red);          // banda
    imagefilledrectangle($im, 50 * $s, 25 * $s, 57 * $s, 39 * $s, $black);       // barra de graduacion
    imagefilledpolygon($im, [29*$s,37*$s, 21*$s,60*$s, 30*$s,60*$s, 33*$s,40*$s], $red);   // punta izq
    imagefilledpolygon($im, [35*$s,37*$s, 43*$s,60*$s, 34*$s,60*$s, 31*$s,40*$s], $dark);  // punta der
    imagefilledrectangle($im, 23 * $s, 23 * $s, 41 * $s, 41 * $s, $dark);        // nudo
    imagefilledpolygon($im, [25*$s,25*$s, 32*$s,32*$s, 26*$s,39*$s], $mid);
    imagefilledpolygon($im, [39*$s,25*$s, 32*$s,32*$s, 38*$s,39*$s], $mid);
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0775, true);
    imagepng($im, $path);
    return $path;
}

function img_data_uri(string $path): string {
    return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
}

function certificate_generate(int $registrationId, string $type): string {
    $r = row('SELECT r.*, t.name AS tournament_name, t.logo AS tournament_logo, t.event_date, t.user_id AS owner_id,
                     a.name AS academy_name, a.logo AS academy_logo,
                     b.code AS belt_code, b.name_es AS belt_es, b.name_en AS belt_en, b.color_hex,
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
    $site = (string)setting('site_name', 'Taninzu');

    $titles = [
        'gold'          => [$isEn ? 'GOLD MEDAL · 1st PLACE' : 'MEDALLA DE ORO · 1er PUESTO', '#b8912a'],
        'silver'        => [$isEn ? 'SILVER MEDAL · 2nd PLACE' : 'MEDALLA DE PLATA · 2do PUESTO', '#7c8794'],
        'bronze'        => [$isEn ? 'BRONZE MEDAL · 3rd PLACE' : 'MEDALLA DE BRONCE · 3er PUESTO', '#9c6a33'],
        'participation' => [$isEn ? 'CERTIFICATE OF PARTICIPATION' : 'CERTIFICADO DE PARTICIPACIÓN', '#31577e'],
    ];
    [$award, $accent] = $titles[$type];

    // Codigo de verificacion unico (HMAC del torneo+inscripto+tipo)
    $code = strtoupper(implode('-', str_split(substr(hash_hmac('sha256', $r['tournament_id'] . '|' . $registrationId . '|' . $type, CRON_KEY), 0, 12), 4)));

    $beltName = $isEn ? $r['belt_en'] : $r['belt_es'];
    $ageName  = $isEn ? $r['age_en'] : $r['age_es'];
    $wcName   = $isEn ? $r['wc_en'] : $r['wc_es'];
    $genderName = $r['gender'] === 'M' ? ($isEn ? 'Male' : 'Masculino') : ($isEn ? 'Female' : 'Femenino');
    $category = e("$genderName · $ageName · $wcName");
    $beltColor = $r['color_hex'];
    $tipColor = $r['belt_code'] === 'black' ? '#c9252c' : '#1c1c1e';
    $certText = $isEn ? 'hereby confers this certificate upon' : 'otorga el presente certificado a';
    $beltLabel = ($isEn ? 'Belt' : 'Cinturón') . ': ' . e($beltName);
    $dateStr = $r['event_date'] ? date('d/m/Y', strtotime($r['event_date'])) : date('d/m/Y');
    $tournamentNameEsc = e($r['tournament_name']);
    $nameEsc = e($r['name']);
    $organizer = e((string)(scalar('SELECT name FROM users WHERE id = ?', [$r['owner_id']]) ?: $site));
    $orgLabel = $isEn ? 'Organization' : 'Organización';
    $certLabel = $isEn ? 'Official certification' : 'Certificación oficial';
    $verifyLabel = ($isEn ? 'Verification code' : 'Código de verificación') . ': ' . $code;

    $academyLine = $r['academy_name'] ? '<p class="academy">' . e($r['academy_name']) . '</p>' : '';
    $fightsLine = $fights > 0
        ? '<p class="fights">' . ($isEn ? "Fights in this tournament: <b>$fights</b>" : "Luchas disputadas en este torneo: <b>$fights</b>") . '</p>'
        : '';

    // Logos del torneo/academia (esquinas superiores)
    $logoLeft = $logoRight = '';
    if ($r['tournament_logo'] && file_exists(BASE_PATH . '/public/' . $r['tournament_logo'])) {
        $logoLeft = '<img src="' . img_data_uri(BASE_PATH . '/public/' . $r['tournament_logo']) . '" style="height:58px">';
    }
    if ($r['academy_logo'] && file_exists(BASE_PATH . '/public/' . $r['academy_logo'])) {
        $logoRight = '<img src="' . img_data_uri(BASE_PATH . '/public/' . $r['academy_logo']) . '" style="height:58px">';
    }

    $pattern = certificate_pattern_png();
    $logoUri = img_data_uri(taninzu_logo_png());
    $fontsDir = BASE_PATH . '/public/assets/fonts';

    $html = <<<HTML
<style>
  @page { margin: 0; }
  @font-face { font-family: 'gothic'; font-weight: normal; font-style: normal;
               src: url('$fontsDir/UnifrakturMaguntia-Regular.ttf') format('truetype'); }
  @font-face { font-family: 'script'; font-weight: normal; font-style: normal;
               src: url('$fontsDir/GreatVibes-Regular.ttf') format('truetype'); }
  body { margin: 0; padding: 0; font-family: 'DejaVu Serif', serif; color: #2f2a20; }
  .cert  { position: absolute; top: 0; left: 0; width: 1122px; height: 790px;
           background-image: url('$pattern'); background-repeat: no-repeat; }
  .frame { position: absolute; top: 24px; left: 24px; width: 1068px; height: 736px;
           border: 4px double #4a3b28; }
  .inner { position: absolute; top: 7px; left: 7px; width: 1052px; height: 720px;
           border: 1px solid #c9a648; text-align: center; }
  .corner-l { position: absolute; top: 16px; left: 22px; }
  .corner-r { position: absolute; top: 16px; right: 22px; }
  .gothic  { font-family: 'gothic'; font-size: 52px; color: #1e1a12; margin: 48px 60px 0; line-height: 1.05; }
  .present { font-style: italic; font-size: 16px; color: #6a6252; margin: 22px 0 0; }
  .script-name { font-family: 'script'; font-size: 66px; color: #1e1a12; margin: 8px 0 0; line-height: 1.1; }
  .rule    { width: 420px; border-bottom: 1.5px solid $accent; margin: 4px auto 18px; }
  .award   { font-size: 26px; font-weight: bold; letter-spacing: 4px; color: $accent; margin: 10px 0 6px; }
  .category{ font-style: italic; font-size: 17px; color: #4a4335; margin: 12px 0 18px; }
  .belt    { width: 260px; height: 24px; margin: 0 auto; border: 2px solid #2b2b2b; border-radius: 4px;
             position: relative; background: $beltColor; }
  .belttip { position: absolute; right: 12px; top: 0; width: 52px; height: 24px; background: $tipColor; }
  .beltknot{ position: absolute; left: 112px; top: -5px; width: 34px; height: 30px; background: $beltColor;
             border: 2px solid #2b2b2b; border-radius: 5px; }
  .beltlabel { font-size: 15px; color: #4a4335; margin: 10px 0 0; font-weight: bold; }
  .academy { font-style: italic; font-size: 19px; color: #55503f; margin: 14px 0 0; }
  .fights  { font-size: 14px; color: #6a6252; margin: 8px 0 0; }
  .footerrow { position: absolute; bottom: 22px; left: 0; width: 100%; }
  .footerrow table { width: 100%; border-collapse: collapse; }
  .footerrow td { width: 33.33%; vertical-align: bottom; text-align: center; }
  .sig  { border-top: 1.5px solid #4a4335; width: 230px; margin: 0 auto; padding-top: 5px;
          font-size: 12px; color: #4a4335; }
  .signame { font-size: 15px; font-weight: bold; color: #2f2a20; margin-bottom: 3px; }
  .seal { width: 118px; height: 118px; border: 4px double $accent; border-radius: 50%;
          margin: 0 auto; background: rgba(255,255,255,.55); }
  .seal img { height: 52px; margin-top: 16px; }
  .sealname { display: block; font-size: 13px; font-weight: bold; letter-spacing: 3px; color: $accent; }
  .sealsite { display: block; font-size: 9px; color: #6a6252; }
  .code { font-size: 10px; color: #6a6252; margin-top: 5px; letter-spacing: 1px; }
  .datefoot { font-size: 12px; color: #6a6252; margin-top: 4px; }
</style>
<div class="cert">
  <div class="frame"><div class="inner">
    <div class="corner-l">$logoLeft</div>
    <div class="corner-r">$logoRight</div>
    <div class="gothic">$tournamentNameEsc</div>
    <p class="present">$certText</p>
    <div class="script-name">$nameEsc</div>
    <div class="rule"></div>
    <div class="award">$award</div>
    <p class="category">$category</p>
    <div class="belt"><div class="belttip"></div><div class="beltknot"></div></div>
    <div class="beltlabel">$beltLabel</div>
    $academyLine
    $fightsLine
    <div class="footerrow">
      <table><tr>
        <td>
          <div class="signame">$organizer</div>
          <div class="sig">$orgLabel</div>
        </td>
        <td>
          <div class="seal"><img src="$logoUri"><span class="sealname">TANINZU</span><span class="sealsite">taninzu.com</span></div>
          <div class="code">$verifyLabel</div>
        </td>
        <td>
          <div class="signame">$site</div>
          <div class="sig">$certLabel · taninzu.com</div>
          <div class="datefoot">$dateStr</div>
        </td>
      </tr></table>
    </div>
  </div></div>
</div>
HTML;

    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $options->setChroot(BASE_PATH);
    $fontStorage = BASE_PATH . '/storage/fonts';
    if (!is_dir($fontStorage)) mkdir($fontStorage, 0775, true);
    $options->setFontDir($fontStorage);
    $options->setFontCache($fontStorage);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $dir = BASE_PATH . '/storage/certificates';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $path = "$dir/cert-{$r['tournament_id']}-{$registrationId}-{$type}.pdf";
    file_put_contents($path, $dompdf->output());

    q('INSERT INTO certificates (tournament_id, registration_id, type, code, pdf_path) VALUES (?,?,?,?,?)
       ON DUPLICATE KEY UPDATE pdf_path = VALUES(pdf_path), code = VALUES(code)',
        [$r['tournament_id'], $registrationId, $type, $code, $path]);
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
