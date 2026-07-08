<?php
/**
 * Runner de tareas programadas. Meter en el crontab del servidor:
 *   * * * * *  curl -s "https://tu-dominio/cron.php?task=emails&key=CRON_KEY"
 *   0 * * * *  curl -s "https://tu-dominio/cron.php?task=rankings&key=CRON_KEY"
 *   0 4 * * *  curl -s "https://tu-dominio/cron.php?task=cleanup&key=CRON_KEY"
 */
require_once dirname(__DIR__) . '/src/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
if (($_GET['key'] ?? '') !== CRON_KEY) {
    http_response_code(403);
    exit("forbidden\n");
}

$task = $_GET['task'] ?? '';
$detail = '';

switch ($task) {
    case 'emails':
        $pending = rows('SELECT id FROM email_queue WHERE status = "pending" AND attempts < 5 ORDER BY id LIMIT 50');
        $ok = 0;
        foreach ($pending as $p) {
            if (send_queued_mail((int)$p['id'])) $ok++;
        }
        $detail = "$ok/" . count($pending) . ' enviados';
        break;

    case 'rankings':
        $n = recompute_rankings();
        $detail = "$n filas de ranking";
        break;

    case 'certificates':
        $parts = [];
        foreach (rows('SELECT id FROM tournaments WHERE certs_requested = 1') as $tt) {
            $opts = setting('certs_opts_' . $tt['id'], ['podium' => true, 'participation' => true]);
            $r = certificates_send_all((int)$tt['id'], (bool)$opts['podium'], (bool)$opts['participation'], 25);
            $parts[] = "t{$tt['id']}: {$r['sent']} (quedan {$r['remaining']})";
        }
        $detail = $parts ? implode(' · ', $parts) : 'nada pendiente';
        break;

    case 'cleanup':
        $a = q('DELETE FROM registrations WHERE verified = 0 AND created_at < DATE_SUB(NOW(), INTERVAL 72 HOUR)')->rowCount();
        $b = q('DELETE FROM email_queue WHERE status = "sent" AND sent_at < DATE_SUB(NOW(), INTERVAL 30 DAY)')->rowCount();
        $c = q('DELETE FROM cron_log WHERE ran_at < DATE_SUB(NOW(), INTERVAL 30 DAY)')->rowCount();
        $detail = "$a inscripciones, $b mails, $c logs";
        break;

    default:
        http_response_code(400);
        exit("unknown task\n");
}

q('INSERT INTO cron_log (task, detail) VALUES (?, ?)', [$task, $detail]);
echo "$task: $detail\n";
