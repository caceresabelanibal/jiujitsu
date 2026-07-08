<?php
function view_header(string $title, bool $bare = false): void {
    $site = (string)setting('site_name', 'BJJ Tournament Manager');
    $u = current_user();
    $l = lang();
    ?><!DOCTYPE html>
<html lang="<?= $l ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · <?= e($site) ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
<link rel="icon" href="data:image/svg+xml,<text y='0.9em' font-size='90'>🥋</text>">
</head>
<body class="<?= $bare ? 'bare' : '' ?>">
<?php if (!$bare): ?>
<header class="topnav">
  <a class="brand" href="<?= APP_URL ?>/">🥋 <?= e($site) ?></a>
  <button class="navtoggle" onclick="document.querySelector('.navlinks').classList.toggle('show')">☰</button>
  <nav class="navlinks">
    <a href="<?= APP_URL ?>/rankings"><?= t('nav_rankings') ?></a>
    <?php if ($u): ?>
      <a href="<?= APP_URL ?>/dashboard"><?= t('nav_dashboard') ?></a>
      <a href="<?= APP_URL ?>/tournaments"><?= t('nav_tournaments') ?></a>
      <?php if (is_admin()): ?><a href="<?= APP_URL ?>/admin"><?= t('nav_admin') ?></a><?php endif; ?>
      <a href="<?= APP_URL ?>/logout"><?= t('nav_logout') ?> (<?= e($u['name']) ?>)</a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/login"><?= t('nav_login') ?></a>
      <a href="<?= APP_URL ?>/register"><?= t('nav_register') ?></a>
    <?php endif; ?>
    <span class="langswitch">
      <a href="?lang=es" class="<?= $l === 'es' ? 'active' : '' ?>">ES</a>·<a href="?lang=en" class="<?= $l === 'en' ? 'active' : '' ?>">EN</a>
    </span>
  </nav>
</header>
<?php endif; ?>
<main class="<?= $bare ? 'bare-main' : 'container' ?>">
<?php foreach (get_flashes() as $f): ?>
  <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endforeach;
}

function view_footer(bool $bare = false): void {
    ?></main>
<?php if (!$bare): ?>
<footer class="footer">🥋 BJJ Tournament Manager · <?= date('Y') ?></footer>
<?php endif; ?>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html><?php
}

/** Tabs de gestion de un torneo */
function tournament_tabs(array $t, string $active): void {
    $tabs = [
        'overview'      => ['', t('overview')],
        'academies'     => ['/academies', t('academies')],
        'registrations' => ['/registrations', t('registrations')],
        'divisions'     => ['/divisions', t('divisions')],
        'matches'       => ['/matches', t('matches')],
        'dashboard'     => ['/dashboard', t('dashboard')],
        'certificates'  => ['/certificates', t('certificates')],
    ];
    echo '<div class="tabs">';
    foreach ($tabs as $key => [$suffix, $label]) {
        $cls = $key === $active ? 'tab active' : 'tab';
        echo '<a class="' . $cls . '" href="' . APP_URL . '/tournament/' . $t['id'] . $suffix . '">' . e($label) . '</a>';
    }
    echo '</div>';
}
