<?php
function view_header(string $title, bool $bare = false, string $bodyClass = ''): void {
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
<link rel="stylesheet" href="<?= asset('/assets/css/app.css') ?>">
<link rel="icon" type="image/png" href="<?= asset('/assets/img/logo.png') ?>">
</head>
<body class="<?= trim(($bare ? 'bare' : '') . ' ' . $bodyClass) ?>">
<?php icons_sprite(); ?>
<?php if (!$bare): ?>
<header class="topnav">
  <a class="brand" href="<?= APP_URL ?>/"><img class="brandlogo" src="<?= asset('/assets/img/logo.png') ?>" alt="<?= e($site) ?>"></a>
  <button class="navtoggle" onclick="document.querySelector('.navlinks').classList.toggle('show')">☰</button>
  <nav class="navlinks">
    <a href="<?= APP_URL ?>/rankings"><?= t('nav_rankings') ?></a>
    <?php if ($u): ?>
      <a href="<?= APP_URL ?>/dashboard"><?= t('nav_dashboard') ?></a>
      <?php if (is_admin()): ?><a href="<?= APP_URL ?>/admin"><?= t('nav_admin') ?></a><?php endif; ?>
      <a href="<?= APP_URL ?>/logout"><?= t('nav_logout') ?> (<?= e($u['name']) ?>)</a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/login"><?= t('nav_login') ?></a>
      <a href="<?= APP_URL ?>/register"><?= t('nav_register') ?></a>
    <?php endif; ?>
    <?php /* OJO: en handlers inline el scope incluye document, y document.URL (string)
             tapa al constructor global URL — hay que usar window.URL explicito. */ ?>
    <select class="langsel" title="<?= t('language') ?>" aria-label="<?= t('language') ?>"
            onchange="const u = new window.URL(window.location.href); u.searchParams.set('lang', this.value); window.location.href = u;">
      <?php foreach (APP_LANGS as $code => $label): ?>
      <option value="<?= $code ?>" <?= $l === $code ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="themetoggle" type="button" onclick="toggleTheme(this)" title="<?= t('theme') ?>">◐</button>
    <a class="themetoggle navhelp" href="<?= APP_URL ?>/help" title="<?= t('help') ?>" aria-label="<?= t('help') ?>">?</a>
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
<footer class="footer"><img class="brandlogo" src="<?= asset('/assets/img/logo.png') ?>" alt="" style="height:36px;vertical-align:-12px"> <?= e((string)setting('site_name', 'Taninzu')) ?> · taninzu.com · <?= date('Y') ?></footer>
<?php endif; ?>
<script src="<?= asset('/assets/js/app.js') ?>"></script>
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
