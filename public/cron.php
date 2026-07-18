<?php
/**
 * Runner de tareas programadas. Meter en el crontab del servidor:
 *   * * * * *  curl -s "https://tu-dominio/cron.php?task=emails&key=CRON_KEY"
 *   0 * * * *  curl -s "https://tu-dominio/cron.php?task=rankings&key=CRON_KEY"
 *   0 4 * * *  curl -s "https://tu-dominio/cron.php?task=cleanup&key=CRON_KEY"
 *   0,15,30,45 * * * *  curl -s "https://tu-dominio/cron.php?task=tournament_status&key=CRON_KEY"
 *   0 5 * * *  curl -s "https://tu-dominio/cron.php?task=delete_old_tournaments&key=CRON_KEY"
 *   0 6 * * *  curl -s "https://tu-dominio/cron.php?task=reset_demo&key=CRON_KEY"
 *   0,15,30,45 * * * *  curl -s "https://tu-dominio/cron.php?task=registration_close&key=CRON_KEY"
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

    case 'delete_old_tournaments':
        $months = (int)setting('tournament_retention_months', 0);
        if ($months <= 0) {
            $detail = 'deshabilitado (retencion no configurada en /admin/settings)';
            break;
        }
        $old = rows('SELECT id FROM tournaments WHERE COALESCE(event_date, created_at) < DATE_SUB(NOW(), INTERVAL ? MONTH)', [$months]);
        foreach ($old as $tt) {
            q('DELETE FROM tournaments WHERE id = ?', [(int)$tt['id']]);
        }
        if ($old) recompute_rankings();
        $detail = count($old) . " torneos eliminados (mas de $months meses)";
        break;

    case 'registration_close':
        // Cierra las inscripciones de los torneos cuya fecha de cierre ya llegó
        // y le avisa al organizador (con el detalle de divisiones con un solo
        // competidor para que reorganice las llaves o declare ganadores).
        $closed = 0;
        foreach (rows("SELECT * FROM tournaments WHERE status = 'open' AND regs_closed_at IS NULL
                       AND reg_close_date IS NOT NULL AND reg_close_date <= CURDATE()") as $tt) {
            $ttid = (int)$tt['id'];
            q('UPDATE tournaments SET regs_closed_at = NOW() WHERE id = ?', [$ttid]);
            ensure_divisions($ttid);
            $solos = solo_divisions($ttid);
            $owner = row('SELECT * FROM users WHERE id = ?', [$tt['user_id']]);
            if ($owner) {
                queue_mail($owner['email'], $owner['name'], t('mail_regs_closed_subject') . ' - ' . $tt['name'],
                    mail_layout(t('mail_regs_closed_subject'),
                        mail_p(sprintf(t('mail_regs_closed_body1'), e($tt['name']))) .
                        mail_p($solos ? sprintf(t('mail_regs_closed_body2'), count($solos)) : t('mail_regs_closed_body2_ok')) .
                        mail_button(APP_URL . '/tournament/' . $ttid . '/reorganize', t('mail_regs_closed_button'))));
            }
            $closed++;
        }
        $detail = "$closed torneos con inscripciones cerradas";
        break;

    case 'reset_demo':
        $res = reset_all_demo_tournaments();
        $detail = $res ? implode(' · ', $res) : 'no hay torneos de muestra';
        break;

    case 'tournament_status':
        // Inscripcion abierta -> en curso cuando llega la fecha del evento
        $started = q("UPDATE tournaments SET status = 'running'
                      WHERE status = 'open' AND event_date IS NOT NULL AND event_date <= CURDATE()")->rowCount();
        // Red de seguridad: si alguna llave quedo completa sin pasar por check_tournament_done()
        // (p.ej. reopen/edicion manual), lo detecta igual en el proximo barrido.
        $finished = 0;
        foreach (rows("SELECT id FROM tournaments WHERE status = 'running'") as $tt) {
            if (check_tournament_done((int)$tt['id'])) $finished++;
        }
        $detail = "$started a en curso, $finished a finalizado";
        break;

    default:
        http_response_code(400);
        exit("unknown task\n");
}

q('INSERT INTO cron_log (task, detail) VALUES (?, ?)', [$task, $detail]);
echo "$task: $detail\n";
