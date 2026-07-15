<?php
/**
 * Captcha propio con GD — sin servicios externos (nada de reCAPTCHA/keys),
 * gratis y sin mandar datos a terceros. Imagen de 5 caracteres rotados con
 * ruido, código guardado en sesión y de un solo uso (se descarta al validar,
 * así no se puede reusar la misma respuesta para varios POST).
 *
 * Se usa en los forms públicos que CREAN usuarios (crear cuenta e inscripción
 * pública) para frenar a los bots. Uso:
 *   - en el form:  <?= captcha_field() ?>
 *   - en el POST:  if (!captcha_check()) { flash('error', t('captcha_wrong')); ... }
 *   - la imagen la sirve la ruta /captcha.png (src/pages/captcha.php)
 */

// Sin 0/O ni 1/I/L para que no haya ambigüedad al leer
const CAPTCHA_CHARS = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

/** Genera un código nuevo y lo guarda en sesión (pisa el anterior). */
function captcha_new(int $len = 5): string {
    $code = '';
    for ($i = 0; $i < $len; $i++) {
        $code .= CAPTCHA_CHARS[random_int(0, strlen(CAPTCHA_CHARS) - 1)];
    }
    $_SESSION['captcha'] = $code;
    return $code;
}

/** Valida lo tipeado contra la sesión (insensible a mayúsculas). Un solo intento por imagen. */
function captcha_check(): bool {
    $expected = $_SESSION['captcha'] ?? '';
    unset($_SESSION['captcha']); // un uso: el próximo intento necesita imagen nueva
    $given = strtoupper(trim($_POST['captcha'] ?? ''));
    return $expected !== '' && hash_equals($expected, $given);
}

/** Bloque de form: imagen + botón de recargar + input. */
function captcha_field(): string {
    $src = APP_URL . '/captcha.png';
    $label = t('captcha_label');
    $reload = e(t('captcha_reload'));
    $ph = e(t('captcha_placeholder'));
    $r = mt_rand();
    return <<<HTML
<label>$label</label>
<div class="captcha-row">
  <img src="$src?r=$r" alt="CAPTCHA" class="captcha-img" id="captcha-img">
  <button type="button" class="captcha-reload" title="$reload" aria-label="$reload"
          onclick="document.getElementById('captcha-img').src='$src?r='+Date.now()">&#x21bb;</button>
  <input type="text" name="captcha" required autocomplete="off" maxlength="5"
         placeholder="$ph" style="text-transform:uppercase">
</div>
HTML;
}

/** Dibuja el PNG del captcha (lo llama /captcha.png). */
function captcha_render(string $code): void {
    $w = 170; $h = 56;
    $im = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($im, 243, 246, 250);
    imagefilledrectangle($im, 0, 0, $w, $h, $bg);

    // líneas de fondo suaves
    for ($i = 0; $i < 5; $i++) {
        $c = imagecolorallocate($im, mt_rand(170, 210), mt_rand(170, 210), mt_rand(170, 210));
        imageline($im, mt_rand(0, $w), mt_rand(0, $h), mt_rand(0, $w), mt_rand(0, $h), $c);
    }

    // cada carácter en su tile, rotado y escalado al canvas
    $n = strlen($code);
    $slot = (int)(($w - 24) / max(1, $n));
    for ($i = 0; $i < $n; $i++) {
        $tile = imagecreatetruecolor(24, 28);
        $tbg = imagecolorallocate($tile, 243, 246, 250);
        imagefilledrectangle($tile, 0, 0, 24, 28, $tbg);
        $fg = imagecolorallocate($tile, mt_rand(20, 90), mt_rand(20, 90), mt_rand(30, 130));
        imagestring($tile, 5, 7, 6, $code[$i], $fg); // font 5 = 9x15 px
        $rot = imagerotate($tile, mt_rand(-22, 22), $tbg);
        $rw = imagesx($rot); $rh = imagesy($rot);
        $dw = (int)($rw * 1.7); $dh = (int)($rh * 1.7);
        imagecopyresampled($im, $rot,
            8 + $i * $slot, (int)(($h - $dh) / 2) + mt_rand(-3, 3),
            0, 0, $dw, $dh, $rw, $rh);
        imagedestroy($tile);
        imagedestroy($rot);
    }

    // ruido por encima: puntos y un par de líneas
    for ($i = 0; $i < 140; $i++) {
        $c = imagecolorallocate($im, mt_rand(120, 200), mt_rand(120, 200), mt_rand(120, 200));
        imagesetpixel($im, mt_rand(0, $w - 1), mt_rand(0, $h - 1), $c);
    }
    for ($i = 0; $i < 2; $i++) {
        $c = imagecolorallocate($im, mt_rand(120, 180), mt_rand(120, 180), mt_rand(120, 180));
        imageline($im, 0, mt_rand(0, $h), $w, mt_rand(0, $h), $c);
    }

    header('Content-Type: image/png');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    imagepng($im);
    imagedestroy($im);
}
