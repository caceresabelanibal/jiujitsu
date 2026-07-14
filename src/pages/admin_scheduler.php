<?php
require_admin();

$tasks = [
    'emails'            => ['mail', 'Procesa la cola de mails (verificaciones, certificados)', '* * * * *'],
    'certificates'      => ['award', 'Genera los PDFs de certificados pendientes por lotes', '*/5 * * * *'],
    'rankings'          => ['chart', 'Recalcula el ranking global de competidores', '0 * * * *'],
    'tournament_status' => ['calendar', 'Pasa a "en curso" al llegar la fecha y a "finalizado" si ya no quedan luchas', '0,15,30,45 * * * *'],
    'cleanup'           => ['trash', 'Borra inscripciones no verificadas (>72h) y mails viejos', '0 4 * * *'],
    'delete_old_tournaments' => ['trash', 'Borra torneos mas viejos que la retencion configurada (deshabilitado si es 0)', '0 5 * * *'],
    'reset_demo'        => ['shuffle', 'Resetea los torneos de muestra al momento cero (mitad de luchas + certificados)', '0 6 * * *'],
];
$key = CRON_KEY;
view_header(t('scheduler'));
?>
<h1><?= icon('clock', 24) ?> <?= t('scheduler') ?></h1>

<div class="card">
  <h3><?= t('cron_tasks') ?></h3>
  <div class="table-wrap"><table>
    <tr><th><?= t('task') ?></th><th></th><th><?= t('last_run') ?></th><th></th></tr>
    <?php foreach ($tasks as $name => [$icon, $desc, $sched]):
        $last = row('SELECT * FROM cron_log WHERE task = ? ORDER BY id DESC LIMIT 1', [$name]); ?>
    <tr>
      <td><b><?= icon($icon, 14) ?> <?= $name ?></b></td>
      <td class="muted"><?= e($desc) ?></td>
      <td><?= $last ? date('d/m/Y H:i', strtotime($last['ran_at'])) . ' <span class="muted">' . e($last['detail'] ?? '') . '</span>' : t('never') ?></td>
      <td><a class="btn sm" href="<?= APP_URL ?>/cron.php?task=<?= $name ?>&key=<?= e($key) ?>" target="_blank"><?= icon('play', 12) ?> <?= t('run_now') ?></a></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>

<div class="card">
  <h3><?= t('cron_hint') ?></h3>
  <pre style="background:var(--bg2);padding:14px;border-radius:8px;overflow-x:auto"><?php
  foreach ($tasks as $name => [$icon, $desc, $sched]) {
      echo e("$sched curl -s \"" . APP_URL . "/cron.php?task=$name&key=$key\" > /dev/null\n");
  }
  ?></pre>
</div>

<div class="card">
  <h3><?= icon('mail', 16) ?> <?= t('emails_pending') ?>: <?= (int)scalar('SELECT COUNT(*) FROM email_queue WHERE status = "pending"') ?></h3>
  <div class="table-wrap"><table>
    <tr><th><?= t('email') ?></th><th>Asunto</th><th><?= t('status') ?></th><th><?= t('date') ?></th></tr>
    <?php foreach (rows('SELECT * FROM email_queue ORDER BY id DESC LIMIT 20') as $m): ?>
    <tr>
      <td><?= e($m['to_email']) ?></td>
      <td class="muted"><?= e($m['subject']) ?></td>
      <td><span class="badge <?= ['pending'=>'grey','sent'=>'green','error'=>'red'][$m['status']] ?>"><?= $m['status'] ?></span></td>
      <td class="muted"><?= $m['sent_at'] ?? $m['created_at'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php view_footer();
