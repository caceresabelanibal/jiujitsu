<?php
$t = require_tournament_owner((int)$params[0]);
$tid = (int)$t['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    // Primer lote inline; el resto lo completa el cron "certificates"
    $r = certificates_send_all($tid, isset($_POST['podium']), isset($_POST['participation']), 15);
    $msg = $r['sent'] . ' ' . t('certs_sent');
    if ($r['remaining'] > 0) $msg .= ' (+' . $r['remaining'] . ' → cron)';
    flash('success', $msg);
    redirect("/tournament/$tid/certificates");
}

$certs = rows('SELECT c.*, r.name reg_name, r.email FROM certificates c
               JOIN registrations r ON r.id = c.registration_id
               WHERE c.tournament_id = ? ORDER BY FIELD(c.type,"gold","silver","bronze","participation"), r.name', [$tid]);
view_header(t('certificates'));
?>
<h1><?= e($t['name']) ?></h1>
<?php tournament_tabs($t, 'certificates'); ?>

<div class="card">
  <h3><?= t('send_certificates') ?></h3>
  <form method="post" class="flex">
    <?= csrf_field() ?>
    <label class="flex" style="margin:0"><input type="checkbox" name="podium" checked style="width:auto"> <?= t('certs_podium') ?></label>
    <label class="flex" style="margin:0"><input type="checkbox" name="participation" checked style="width:auto"> <?= t('certs_participation') ?></label>
    <button class="btn">📨 <?= t('send_certificates') ?></button>
  </form>
</div>

<?php if ($certs): ?>
<div class="card table-wrap">
<table>
  <tr><th></th><th><?= t('name') ?></th><th><?= t('email') ?></th><th><?= t('emailed') ?></th><th></th></tr>
  <?php $icons = ['gold' => '🥇', 'silver' => '🥈', 'bronze' => '🥉', 'participation' => '🎖'];
  foreach ($certs as $c): ?>
  <tr>
    <td class="medal-ico"><?= $icons[$c['type']] ?></td>
    <td><b><?= e($c['reg_name']) ?></b></td>
    <td class="muted"><?= e($c['email']) ?></td>
    <td><?= $c['emailed_at'] ? '<span class="badge green">' . date('d/m H:i', strtotime($c['emailed_at'])) . '</span>' : '<span class="badge grey">' . t('pending') . '</span>' ?></td>
    <td class="right"><a class="btn sm secondary" href="<?= APP_URL ?>/certificate/<?= $c['id'] ?>/download">⬇ <?= t('download') ?></a></td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
<?php endif;
view_footer();
