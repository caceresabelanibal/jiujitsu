<?php
require_admin();
$stats = [
    ['👤 ' . t('users'), (int)scalar('SELECT COUNT(*) FROM users'), '/admin/users'],
    ['🏆 ' . t('nav_tournaments'), (int)scalar('SELECT COUNT(*) FROM tournaments'), '/tournaments'],
    ['📝 ' . t('registrations'), (int)scalar('SELECT COUNT(*) FROM registrations'), null],
    ['⚔️ ' . t('matches'), (int)scalar('SELECT COUNT(*) FROM matches WHERE status="done"'), null],
    ['📨 ' . t('emails_pending'), (int)scalar('SELECT COUNT(*) FROM email_queue WHERE status="pending"'), '/admin/scheduler'],
];
view_header(t('admin_panel'));
?>
<h1>⚙️ <?= t('admin_panel') ?></h1>
<div class="grid cols3 mb">
  <?php foreach ($stats as [$label, $v, $link]): ?>
  <div class="stat"><div class="k"><?= $label ?></div><div class="v"><?= $v ?></div>
    <?php if ($link): ?><a href="<?= APP_URL . $link ?>">→</a><?php endif; ?></div>
  <?php endforeach; ?>
</div>
<div class="flex">
  <a class="btn" href="<?= APP_URL ?>/admin/users">👤 <?= t('users') ?></a>
  <a class="btn" href="<?= APP_URL ?>/admin/settings">⚙️ <?= t('settings') ?></a>
  <a class="btn" href="<?= APP_URL ?>/admin/ads">📣 <?= t('ads') ?></a>
  <a class="btn" href="<?= APP_URL ?>/admin/scheduler">⏰ <?= t('scheduler') ?></a>
</div>
<?php view_footer();
