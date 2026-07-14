<?php
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Config SMTP: primero lo que haya guardado el admin en /admin/settings
 * (tabla settings, clave "smtp"); si no configuro nada todavia, cae a las
 * variables de entorno del docker-compose (asi sigue andando con MailHog
 * en dev sin que haga falta tocar nada).
 */
function smtp_config(): array {
    $defaults = [
        'host' => getenv('SMTP_HOST') ?: 'mailhog',
        'port' => (int)(getenv('SMTP_PORT') ?: 1025),
        'user' => getenv('SMTP_USER') ?: '',
        'pass' => getenv('SMTP_PASS') ?: '',
        'secure' => getenv('SMTP_SECURE') ?: '',
        'from' => getenv('MAIL_FROM') ?: 'torneos@taninzu.com',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Taninzu',
    ];
    $stored = setting('smtp', null);
    return is_array($stored) ? array_merge($defaults, $stored) : $defaults;
}

/** Encola un mail (lo procesa el cron o el envio inmediato best-effort) */
function queue_mail(string $to, ?string $toName, string $subject, string $bodyHtml, ?string $attachment = null): int {
    q('INSERT INTO email_queue (to_email, to_name, subject, body_html, attachment_path) VALUES (?,?,?,?,?)',
        [$to, $toName, $subject, $bodyHtml, $attachment]);
    $id = (int)db()->lastInsertId();
    // Intento inmediato best-effort; si falla queda en cola para el cron
    try { send_queued_mail($id); } catch (Throwable $e) { /* la cola reintenta */ }
    return $id;
}

function send_queued_mail(int $id): bool {
    $m = row('SELECT * FROM email_queue WHERE id = ? AND status != "sent"', [$id]);
    if (!$m) return false;
    try {
        $cfg = smtp_config();
        $mailer = new PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $cfg['host'];
        $mailer->Port = (int)$cfg['port'];
        if ($cfg['user']) {
            $mailer->SMTPAuth = true;
            $mailer->Username = $cfg['user'];
            $mailer->Password = $cfg['pass'];
        }
        if ($cfg['secure']) $mailer->SMTPSecure = $cfg['secure'];
        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom($cfg['from'], $cfg['from_name']);
        $mailer->addAddress($m['to_email'], $m['to_name'] ?? '');
        $mailer->isHTML(true);
        $mailer->Subject = $m['subject'];
        $mailer->Body = $m['body_html'];
        if ($m['attachment_path'] && file_exists($m['attachment_path'])) {
            $mailer->addAttachment($m['attachment_path']);
        }
        $mailer->send();
        q('UPDATE email_queue SET status = "sent", sent_at = NOW(), attempts = attempts + 1 WHERE id = ?', [$id]);
        return true;
    } catch (Throwable $e) {
        q('UPDATE email_queue SET status = IF(attempts >= 4, "error", "pending"), error = ?, attempts = attempts + 1 WHERE id = ?',
            [substr($e->getMessage(), 0, 500), $id]);
        return false;
    }
}

/**
 * Plantilla responsive de email (tabla + estilos inline, la unica forma
 * confiable en clientes de correo). Se ve bien tanto en escritorio como en
 * celular: contenedor de 600px que baja a 100% del ancho, con media query
 * para achicar el padding en pantallas chicas.
 *
 * El cuerpo ($content) se arma con los helpers mail_p()/mail_button()/
 * mail_link_fallback() para que cada bloque (texto, boton, link) quede
 * separado con su propio margen y nunca "se junten".
 */
function mail_layout(string $title, string $content): string {
    $site = e((string)setting('site_name', 'Taninzu'));
    $ttl = e($title);
    $lang = e(lang());
    $year = date('Y');
    return <<<HTML
<!DOCTYPE html>
<html lang="$lang" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="x-apple-disable-message-reformatting">
<title>$ttl</title>
<style>
  body { margin:0; padding:0; width:100% !important; }
  @media only screen and (max-width:600px) {
    .m-pad { padding: 28px 22px !important; }
    .m-head { padding: 24px 22px !important; }
    .m-title { font-size: 20px !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background:#eef0f4;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef0f4">
    <tr>
      <td align="center" style="padding:28px 12px">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 1px 4px rgba(20,24,36,0.10)">
          <tr>
            <td class="m-head" align="center" style="background:#141824;padding:30px 32px">
              <div style="font-size:30px;line-height:1">🥋</div>
              <div style="color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:bold;letter-spacing:.5px;margin-top:8px">$site</div>
            </td>
          </tr>
          <tr>
            <td class="m-pad" style="padding:34px 36px">
              <h1 class="m-title" style="margin:0 0 22px;font-family:Arial,Helvetica,sans-serif;font-size:22px;line-height:1.3;color:#141824;font-weight:bold">$ttl</h1>
              $content
            </td>
          </tr>
          <tr>
            <td align="center" style="background:#f7f8fa;padding:22px 32px;border-top:1px solid #eceef2">
              <div style="color:#9aa0ac;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6">$site &middot; taninzu.com<br>&copy; $year</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

/** Parrafo de cuerpo de email con tipografia/espaciado consistentes. */
function mail_p(string $html): string {
    return '<p style="margin:0 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.6;color:#33373f">' . $html . '</p>';
}

/** Boton "a prueba de balas" (tabla + bgcolor) centrado, con margen propio arriba y abajo. */
function mail_button(string $url, string $label, string $color = '#30a46c'): string {
    $u = e($url);
    $l = e($label);
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:10px 0 26px">'
        . '<tr><td align="center">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>'
        . '<td align="center" bgcolor="' . $color . '" style="border-radius:10px">'
        . '<a href="' . $u . '" target="_blank" style="display:inline-block;padding:15px 34px;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:10px">' . $l . '</a>'
        . '</td></tr></table>'
        . '</td></tr></table>';
}

/** Bloque "¿no funciona el boton? copiá este link", separado con una linea arriba. */
function mail_link_fallback(string $url): string {
    $u = e($url);
    return '<p style="margin:26px 0 0;padding-top:18px;border-top:1px solid #eceef2;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;color:#9aa0ac">'
        . e(t('mail_link_fallback')) . '<br>'
        . '<a href="' . $u . '" target="_blank" style="color:#3b82f6;text-decoration:underline;word-break:break-all">' . $u . '</a></p>';
}
