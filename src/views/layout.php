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
<script>
// Tema antes del primer render (evita flash)
(function () {
  var t = localStorage.getItem('theme');
  if (!t) t = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
  document.documentElement.dataset.theme = t;
})();
</script>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
<link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/img/logo.svg">
</head>
<body class="<?= $bare ? 'bare' : '' ?>">
<?php icons_sprite(); ?>
<?php if (!$bare): ?>
<header class="topnav">
  <a class="brand" href="<?= APP_URL ?>/"><img class="brandlogo" src="<?= APP_URL ?>/assets/img/logo.svg" alt=""> <?= e($site) ?></a>
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
    <button class="themetoggle" type="button" onclick="toggleTheme(this)" title="<?= t('theme') ?>">◐</button>
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
<footer class="footer"><img class="brandlogo" src="<?= APP_URL ?>/assets/img/logo.svg" alt="" style="height:18px;vertical-align:-4px"> <?= e((string)setting('site_name', 'Taninzu')) ?> · taninzu.com · <?= date('Y') ?></footer>
<?php endif; ?>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html><?php
}

/** Tabs de gestion de un torneo */
function tournament_tabs(array $t, string $active): void {
    $tabs = [
        'overview'      => ['', t('operation'), 'timer'],
        'academies'     => ['/academies', t('academies'), 'flag'],
        'registrations' => ['/registrations', t('registrations'), 'clipboard'],
        'divisions'     => ['/divisions', t('divisions'), 'bracket'],
        'matches'       => ['/matches', t('matches'), 'swords'],
        'dashboard'     => ['/dashboard', t('dashboard'), 'chart'],
        'certificates'  => ['/certificates', t('certificates'), 'award'],
        'settings'      => ['/settings', t('settings'), 'settings'],
    ];
    echo '<div class="tabs">';
    foreach ($tabs as $key => [$suffix, $label, $ic]) {
        $cls = $key === $active ? 'tab active' : 'tab';
        echo '<a class="' . $cls . '" href="' . APP_URL . '/tournament/' . $t['id'] . $suffix . '">' . icon($ic, 15) . ' ' . e($label) . '</a>';
    }
    echo '</div>';
}
