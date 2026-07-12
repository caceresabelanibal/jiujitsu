<?php
require_admin();
$stats = [
    [icon('user', 14) . ' ' . t('users'), (int)scalar('SELECT COUNT(*) FROM users'), '/admin/users'],
    [icon('trophy', 14) . ' ' . t('nav_tournaments'), (int)scalar('SELECT COUNT(*) FROM tournaments'), '/dashboard'],
    [icon('clipboard', 14) . ' ' . t('registrations'), (int)scalar('SELECT COUNT(*) FROM registrations'), null],
    [icon('swords', 14) . ' ' . t('matches'), (int)scalar('SELECT COUNT(*) FROM matches WHERE status="done"'), null],
    [icon('mail', 14) . ' ' . t('emails_pending'), (int)scalar('SELECT COUNT(*) FROM email_queue WHERE status="pending"'), '/admin/scheduler'],
];
view_header(t('admin_panel'));
?>
<h1><?= icon('sliders', 24) ?> <?= t('admin_panel') ?></h1>
<div class="grid cols3 mb">
  <?php foreach ($stats as [$label, $v, $link]): ?>
  <div class="stat"><div class="k"><?= $label ?></div><div class="v"><?= $v ?></div>
    <?php if ($link): ?><a href="<?= APP_URL . $link ?>">→</a><?php endif; ?></div>
  <?php endforeach; ?>
</div>
<div class="flex">
  <a class="btn" href="<?= APP_URL ?>/admin/users"><?= icon('user', 14) ?> <?= t('users') ?></a>
  <a class="btn" href="<?= APP_URL ?>/admin/settings"><?= icon('settings', 14) ?> <?= t('settings') ?></a>
  <a class="btn" href="<?= APP_URL ?>/admin/ads"><?= icon('megaphone', 14) ?> <?= t('ads') ?></a>
  <a class="btn" href="<?= APP_URL ?>/admin/scheduler"><?= icon('clock', 14) ?> <?= t('scheduler') ?></a>
</div>
<?php view_footer();
