<?php
// Configuracion del torneo: datos generales, personal (arbitros/mesa) y link de inscripcion
$t = require_tournament_owner((int)$params[0]);
$tid = (int)$t['id'];
$link = APP_URL . '/t/' . $t['slug'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'add_staff') {
        $u = row('SELECT * FROM users WHERE email = ?', [strtolower(trim($_POST['email'] ?? ''))]);
        if ($u) {
            q('INSERT IGNORE INTO tournament_staff (tournament_id, user_id) VALUES (?,?)', [$tid, $u['id']]);
            flash('success', t('settings_saved'));
        } else {
            flash('error', t('user_not_found'));
        }
    } elseif ($do === 'del_staff') {
        q('DELETE FROM tournament_staff WHERE id = ? AND tournament_id = ?', [(int)$_POST['id'], $tid]);
        flash('success', t('settings_saved'));
    }
    redirect("/tournament/$tid/settings");
}

$staff = rows('SELECT s.id, u.name, u.email FROM tournament_staff s JOIN users u ON u.id = s.user_id WHERE s.tournament_id = ? ORDER BY u.name', [$tid]);
view_header(t('settings'));
?>
<h1><?= e($t['name']) ?></h1>
<?php tournament_tabs($t, 'settings'); ?>

<div class="card">
  <h3>🔗 <?= t('registration_link') ?></h3>
  <div class="copybox">
    <input type="text" id="reglink" value="<?= e($link) ?>" readonly>
    <button class="btn secondary" data-copied="<?= t('copied') ?>" onclick="copyLink('reglink', this)"><?= t('copy_link') ?></button>
  </div>
</div>

<div class="grid cols2">
  <div class="card">
    <h3>⚙️ <?= t('settings') ?></h3>
    <form method="post" action="<?= APP_URL ?>/tournament/<?= $tid ?>/edit" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <label><?= t('tournament_name') ?></label>
      <input type="text" name="name" value="<?= e($t['name']) ?>" required>
      <label><?= t('status') ?></label>
      <select name="status">
        <?php foreach (['draft','open','running','finished'] as $st): ?>
        <option value="<?= $st ?>" <?= $t['status'] === $st ? 'selected' : '' ?>><?= t('status_' . $st) ?></option>
        <?php endforeach; ?>
      </select>
      <label><?= t('event_date') ?></label>
      <input type="date" name="event_date" value="<?= e($t['event_date'] ?? '') ?>">
      <label><?= t('max_participants') ?></label>
      <input type="number" name="max_participants" value="<?= (int)$t['max_participants'] ?>" min="2">
      <label><?= t('fight_duration_default') ?></label>
      <input type="number" name="duration_min" value="<?= (int)round($t['default_duration_sec'] / 60) ?>" min="1" max="20">
      <label><?= t('logo') ?></label>
      <input type="file" name="logo" accept="image/*">
      <button class="btn mt"><?= t('save') ?></button>
    </form>
  </div>

  <div class="card">
    <h3>🧑‍⚖️ <?= t('staff') ?></h3>
    <p class="muted"><?= t('staff_hint') ?></p>
    <form method="post" class="flex">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="add_staff">
      <div style="flex:1;min-width:200px"><input type="email" name="email" placeholder="<?= t('email') ?>" required></div>
      <button class="btn">+ <?= t('add_staff') ?></button>
    </form>
    <?php foreach ($staff as $s): ?>
    <div class="flex spread" style="padding:8px 0;border-bottom:1px solid var(--border)">
      <span><b><?= e($s['name']) ?></b> <span class="muted"><?= e($s['email']) ?></span></span>
      <form method="post" data-confirm="<?= t('confirm_delete') ?>">
        <?= csrf_field() ?><input type="hidden" name="do" value="del_staff"><input type="hidden" name="id" value="<?= $s['id'] ?>">
        <button class="btn sm danger">✕</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php view_footer();
