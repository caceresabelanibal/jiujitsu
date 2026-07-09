<?php
$open = rows('SELECT t.*, (SELECT COUNT(*) FROM registrations r WHERE r.tournament_id=t.id AND r.verified=1) regs
              FROM tournaments t WHERE t.status = "open" ORDER BY t.event_date IS NULL, t.event_date LIMIT 12');
view_header(t('nav_home'));
?>
<div class="hero">
  <img src="<?= APP_URL ?>/assets/img/logo.svg" alt="Taninzu" style="height:96px">
  <h1><?= t('hero_title') ?></h1>
  <p><?= t('hero_sub') ?></p>
  <a class="btn xl" href="<?= APP_URL ?>/tournaments/create"><?= t('hero_cta') ?></a>
  <div class="features">
    <div class="feature"><span>🏆</span><?= t('feature_brackets') ?></div>
    <div class="feature"><span>⏱️</span><?= t('feature_score') ?></div>
    <div class="feature"><span>📜</span><?= t('feature_certs') ?></div>
    <div class="feature"><span>📊</span><?= t('feature_rank') ?></div>
  </div>
</div>
<?php if ($open): ?>
<h2><?= t('open_tournaments') ?></h2>
<div class="grid cols3">
  <?php foreach ($open as $o): ?>
  <div class="card">
    <?php if ($o['logo']): ?><img class="tlogo" src="<?= APP_URL . '/' . e($o['logo']) ?>" alt=""><?php endif; ?>
    <h3><?= e($o['name']) ?></h3>
    <p class="muted"><?= $o['event_date'] ? date('d/m/Y', strtotime($o['event_date'])) : '' ?> · <?= (int)$o['regs'] ?> <?= t('participants') ?></p>
    <a class="btn" href="<?= APP_URL ?>/t/<?= e($o['slug']) ?>"><?= t('submit_registration') ?></a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif;
view_footer();
