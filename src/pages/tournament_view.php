<?php
$t = require_tournament_owner((int)$params[0]);
$tid = (int)$t['id'];
$regs = (int)scalar('SELECT COUNT(*) FROM registrations WHERE tournament_id=? AND verified=1', [$tid]);
$pending = (int)scalar('SELECT COUNT(*) FROM registrations WHERE tournament_id=? AND verified=0', [$tid]);
$divs = (int)scalar('SELECT COUNT(*) FROM divisions WHERE tournament_id=?', [$tid]);
$fights = (int)scalar('SELECT COUNT(*) FROM matches WHERE tournament_id=? AND status="done" AND red_reg_id IS NOT NULL AND blue_reg_id IS NOT NULL', [$tid]);
$link = APP_URL . '/t/' . $t['slug'];

view_header($t['name']);
?>
<div class="flex spread">
  <h1><?php if ($t['logo']): ?><img class="tlogo" src="<?= APP_URL . '/' . e($t['logo']) ?>" alt=""> <?php endif; ?><?= e($t['name']) ?></h1>
  <span class="badge <?= ['draft'=>'grey','open'=>'green','running'=>'blue','finished'=>'gold'][$t['status']] ?>"><?= t('status_' . $t['status']) ?></span>
</div>
<?php tournament_tabs($t, 'overview'); ?>

<div class="grid cols4 mb">
  <div class="stat"><div class="k"><?= t('participants') ?></div><div class="v"><?= $regs ?></div><div class="sub"><?= $pending ?> <?= t('pending') ?></div></div>
  <div class="stat"><div class="k"><?= t('divisions') ?></div><div class="v"><?= $divs ?></div></div>
  <div class="stat"><div class="k"><?= t('matches') ?></div><div class="v"><?= $fights ?></div></div>
  <div class="stat"><div class="k"><?= t('date') ?></div><div class="v" style="font-size:1.1rem"><?= $t['event_date'] ? date('d/m/Y', strtotime($t['event_date'])) : '—' ?></div></div>
</div>

<div class="card">
  <h3><?= t('registration_link') ?></h3>
  <div class="copybox">
    <input type="text" id="reglink" value="<?= e($link) ?>" readonly>
    <button class="btn secondary" data-copied="<?= t('copied') ?>" onclick="copyLink('reglink', this)"><?= t('copy_link') ?></button>
  </div>
</div>

<div class="card">
  <h3><?= t('edit') ?></h3>
  <form method="post" action="<?= APP_URL ?>/tournament/<?= $tid ?>/edit" enctype="multipart/form-data" class="grid cols2">
    <?= csrf_field() ?>
    <div>
      <label><?= t('tournament_name') ?></label>
      <input type="text" name="name" value="<?= e($t['name']) ?>" required>
      <label><?= t('event_date') ?></label>
      <input type="date" name="event_date" value="<?= e($t['event_date'] ?? '') ?>">
      <label><?= t('max_participants') ?></label>
      <input type="number" name="max_participants" value="<?= (int)$t['max_participants'] ?>" min="2">
    </div>
    <div>
      <label><?= t('status') ?></label>
      <select name="status">
        <?php foreach (['draft','open','running','finished'] as $st): ?>
        <option value="<?= $st ?>" <?= $t['status'] === $st ? 'selected' : '' ?>><?= t('status_' . $st) ?></option>
        <?php endforeach; ?>
      </select>
      <label><?= t('fight_duration_default') ?></label>
      <input type="number" name="duration_min" value="<?= (int)round($t['default_duration_sec'] / 60) ?>" min="1" max="20">
      <label><?= t('logo') ?></label>
      <input type="file" name="logo" accept="image/*">
    </div>
    <div><button class="btn"><?= t('save') ?></button></div>
  </form>
</div>
<?php view_footer();
