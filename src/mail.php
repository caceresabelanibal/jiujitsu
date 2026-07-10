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

function mail_layout(string $title, string $content): string {
    $site = e((string)setting('site_name', 'BJJ Tournament Manager'));
    return <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#f7f7f9;padding:24px;border-radius:8px">
  <div style="background:#141824;color:#fff;padding:16px 24px;border-radius:8px 8px 0 0">
    <h2 style="margin:0;font-size:20px">🥋 $site</h2>
  </div>
  <div style="background:#fff;padding:24px;border-radius:0 0 8px 8px">
    <h3 style="margin-top:0">$title</h3>
    $content
  </div>
  <p style="color:#888;font-size:12px;text-align:center;margin-top:16px">$site</p>
</div>
HTML;
}
