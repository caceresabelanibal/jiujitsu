<?php
$open = rows('SELECT t.*, (SELECT COUNT(*) FROM registrations r WHERE r.tournament_id=t.id AND r.verified=1) regs
              FROM tournaments t WHERE t.status = "open" ORDER BY t.event_date IS NULL, t.event_date LIMIT 12');

$nums = [
    ['trophy', (int)scalar('SELECT COUNT(*) FROM tournaments'), t('nav_tournaments')],
    ['users', (int)scalar('SELECT COUNT(*) FROM registrations WHERE verified=1'), t('competitors')],
    ['swords', (int)scalar('SELECT COUNT(*) FROM matches WHERE status="done" AND red_reg_id IS NOT NULL AND blue_reg_id IS NOT NULL'), t('matches')],
    ['award', (int)scalar('SELECT COUNT(*) FROM certificates'), t('certificates')],
];

view_header(t('nav_home'));
?>
<div class="landing">
  <div class="blob b1"></div><div class="blob b2"></div><div class="blob b3"></div>
  <?php // Marcas de agua del logo: fijas detras de todo (z-index -1), muy
        // transparentes; app.js las desplaza lento con el scroll (parallax). ?>
  <img class="wm wm1" src="<?= asset('/assets/img/logo.png') ?>" alt="" aria-hidden="true">
  <img class="wm wm2" src="<?= asset('/assets/img/logo.png') ?>" alt="" aria-hidden="true">
  <img class="wm wm3" src="<?= asset('/assets/img/logo.png') ?>" alt="" aria-hidden="true">

  <!-- HERO -->
  <section class="hero2">
    <div class="hero2-text">
      <h1 class="grad"><?= t('hero_title') ?></h1>
      <p class="lead"><?= t('hero_sub') ?></p>
      <div class="flex" style="gap:14px">
        <a class="btn xl glow" href="<?= APP_URL ?>/tournaments/create"><?= t('hero_cta') ?> <?= icon('arrow-right', 18) ?></a>
        <a class="btn xl secondary" href="<?= APP_URL ?>/rankings"><?= icon('chart', 17) ?> <?= t('nav_rankings') ?></a>
      </div>
    </div>
    <div class="hero2-art glass float">
      <img src="<?= asset('/assets/img/fighter1.png') ?>" alt="" class="art-fighters">
    </div>
  </section>

  <!-- STATS -->
  <section class="statstrip reveal">
    <?php foreach ($nums as [$ic, $n, $label]): ?>
    <div class="glass statb">
      <?= icon($ic, 26, 'ic-red') ?>
      <div><b class="statn" data-count="<?= $n ?>"><?= $n ?></b><span><?= e($label) ?></span></div>
    </div>
    <?php endforeach; ?>
  </section>

  <!-- FEATURES -->
  <section class="features2">
    <h2 class="center grad2 reveal"><?= t('features_title') ?></h2>
    <div class="fgrid">
      <?php
      $feats = [
          ['bracket', t('feature_brackets'), t('feature_brackets_d')],
          ['timer', t('feature_score'), t('feature_score_d')],
          ['award', t('feature_certs'), t('feature_certs_d')],
          ['chart', t('feature_rank'), t('feature_rank_d')],
          ['screen', t('feature_screens'), t('feature_screens_d')],
          ['user-check', t('feature_staff'), t('feature_staff_d')],
      ];
      foreach ($feats as [$ic, $title, $desc]): ?>
      <div class="fcard glass reveal">
        <span class="fic"><?= icon($ic, 26) ?></span>
        <h3><?= e($title) ?></h3>
        <p><?= e($desc) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- COMO FUNCIONA -->
  <section class="how">
    <div class="how-art glass float reveal">
      <img src="<?= asset('/assets/img/fighter2.png') ?>" alt="" class="art-fighters flip">
    </div>
    <div class="how-steps">
      <h2 class="grad2 reveal"><?= t('how_title') ?></h2>
      <?php foreach ([1, 2, 3] as $i): ?>
      <div class="step reveal">
        <span class="stepn"><?= $i ?></span>
        <div><h3><?= t("how{$i}_t") ?></h3><p><?= t("how{$i}_d") ?></p></div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- TORNEOS ABIERTOS -->
  <?php if ($open): ?>
  <section class="opensec">
    <h2 class="center grad2 reveal"><?= t('open_tournaments') ?></h2>
    <div class="grid cols3">
      <?php foreach ($open as $o): ?>
      <div class="card reveal" style="margin:0">
        <?php if ($o['logo']): ?><img class="tlogo" src="<?= APP_URL . '/' . e($o['logo']) ?>" alt=""><?php endif; ?>
        <h3><?= e($o['name']) ?></h3>
        <p class="muted"><?= $o['event_date'] ? icon('calendar', 13) . ' ' . date('d/m/Y', strtotime($o['event_date'])) . ' · ' : '' ?><?= icon('users', 13) ?> <?= (int)$o['regs'] ?> <?= t('participants') ?></p>
        <a class="btn" href="<?= APP_URL ?>/t/<?= e($o['slug']) ?>"><?= t('submit_registration') ?> <?= icon('arrow-right', 14) ?></a>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- CTA FINAL -->
  <section class="ctaband glass reveal">
    <img src="<?= asset('/assets/img/logo.png') ?>" alt="" style="height:368px;max-width:100%;object-fit:contain">
    <h2><?= t('cta_title') ?></h2>
    <p class="muted"><?= t('cta_sub') ?></p>
    <a class="btn xl glow" href="<?= APP_URL ?>/register"><?= t('nav_register') ?> <?= icon('arrow-right', 18) ?></a>
  </section>

  <!-- SITIOS AMIGOS -->
  <?php
  $friends = [
      ['name' => 'RollApp', 'url' => 'https://rollapp.ar/', 'logo' => '/assets/img/friends/rollapp-symbol.png', 'desc' => t('friend_rollapp_desc')],
  ];
  // Repetimos la lista hasta llenar la cinta (para que el carrusel se vea bien
  // aunque haya pocos amigos) y luego la duplicamos: el track loopea a -50% sin
  // cortes. Al agregar más amigos, se diversifica solo.
  $strip = [];
  while (count($strip) < 6) foreach ($friends as $f) $strip[] = $f;
  ?>
  <section class="friends reveal">
    <h2 class="grad2"><?= t('friends_title') ?></h2>
    <p class="sub"><?= t('friends_sub') ?></p>
    <div class="friends-marquee">
      <div class="friends-track">
        <?php foreach (array_merge($strip, $strip) as $i => $f): ?>
        <a class="friend-card glass" href="<?= e($f['url']) ?>" target="_blank" rel="noopener noreferrer"
           aria-hidden="<?= $i >= count($strip) ? 'true' : 'false' ?>" tabindex="<?= $i >= count($strip) ? '-1' : '0' ?>">
          <img src="<?= asset($f['logo']) ?>" alt="<?= e($f['name']) ?>" loading="lazy">
          <span><span class="fn"><?= e($f['name']) ?></span><br><span class="fd"><?= e($f['desc']) ?></span></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>
<?php view_footer();
