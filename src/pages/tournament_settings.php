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
    } elseif ($do === 'save_order') {
        $divKeys = division_order_default();
        usort($divKeys, fn($a, $b) => (int)($_POST["div_ord_$a"] ?? 0) <=> (int)($_POST["div_ord_$b"] ?? 0));
        q('UPDATE tournaments SET division_order = ? WHERE id = ?', [json_encode(division_order_sanitize($divKeys), JSON_UNESCAPED_UNICODE), $tid]);
        flash('success', t('settings_saved'));
    } elseif ($do === 'reset_order') {
        q('UPDATE tournaments SET division_order = NULL WHERE id = ?', [$tid]);
        flash('success', t('settings_saved'));
    } elseif ($do === 'save_duration') {
        $durInput = [];
        foreach (belt_duration_defaults() as $key => $def) {
            $durInput[$key] = max(1, (int)($_POST["dur_$key"] ?? 0)) * 60;
        }
        $durations = belt_duration_sanitize($durInput);
        q('UPDATE tournaments SET belt_durations = ? WHERE id = ?', [json_encode($durations, JSON_UNESCAPED_UNICODE), $tid]);
        apply_belt_durations($tid, $durations);
        flash('success', t('settings_saved'));
    } elseif ($do === 'reset_duration') {
        q('UPDATE tournaments SET belt_durations = NULL WHERE id = ?', [$tid]);
        apply_belt_durations($tid, belt_durations_global());
        flash('success', t('settings_saved'));
    } elseif ($do === 'save_age') {
        $ages = age_threshold_sanitize([
            'kids_max' => (int)($_POST['age_kids_max'] ?? 0),
            'juvenile_max' => (int)($_POST['age_juvenile_max'] ?? 0),
        ]);
        q('UPDATE tournaments SET age_thresholds = ? WHERE id = ?', [json_encode($ages, JSON_UNESCAPED_UNICODE), $tid]);
        flash('success', t('settings_saved'));
    } elseif ($do === 'reset_age') {
        q('UPDATE tournaments SET age_thresholds = NULL WHERE id = ?', [$tid]);
        flash('success', t('settings_saved'));
    }
    redirect("/tournament/$tid/settings");
}

$staff = rows('SELECT s.id, u.name, u.email FROM tournament_staff s JOIN users u ON u.id = s.user_id WHERE s.tournament_id = ? ORDER BY u.name', [$tid]);
$divOrder = division_order_for($t);
$divOrderCustom = !empty($t['division_order']);
$beltDur = belt_durations_for($t);
$beltDurCustom = !empty($t['belt_durations']);
$ageTh = age_thresholds_for($t);
$ageThCustom = !empty($t['age_thresholds']);
$u = current_user();
$canClone = is_admin() || (int)$t['user_id'] === (int)$u['id'];
view_header(t('settings'));
?>
<div class="flex spread">
  <h1><?= e($t['name']) ?></h1>
  <?php if ($canClone): ?>
  <a class="btn secondary" href="<?= APP_URL ?>/tournament/<?= $tid ?>/clone"><?= icon('shuffle', 15) ?> <?= t('clone_tournament') ?></a>
  <?php endif; ?>
</div>
<?php tournament_tabs($t, 'settings'); ?>

<div class="card">
  <h3><?= icon('link', 16) ?> <?= t('registration_link') ?></h3>
  <div class="copybox">
    <input type="text" id="reglink" value="<?= e($link) ?>" readonly>
    <button class="btn secondary" data-copied="<?= t('copied') ?>" onclick="copyLink('reglink', this)"><?= t('copy_link') ?></button>
  </div>
</div>

<div class="grid cols2">
  <div class="card">
    <h3><?= icon('settings', 16) ?> <?= t('settings') ?></h3>
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
    <h3><?= icon('user-check', 16) ?> <?= t('staff') ?></h3>
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
        <button class="btn sm danger"><?= icon('x', 13) ?></button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <h3><?= icon('sliders', 16) ?> <?= t('division_order_title') ?> <?php if ($divOrderCustom): ?><span class="badge blue"><?= t('division_order_custom_badge') ?></span><?php endif; ?></h3>
  <p class="muted" style="margin-top:0"><?= t('division_order_tournament_hint') ?></p>
  <form method="post">
    <?= csrf_field() ?>
    <div class="grid cols3">
      <?php foreach (division_order_labels() as $key => $label): ?>
      <div>
        <label><?= e($label) ?></label>
        <input type="number" name="div_ord_<?= e($key) ?>" value="<?= array_search($key, $divOrder, true) + 1 ?>" min="1" max="6">
      </div>
      <?php endforeach; ?>
    </div>
    <div class="flex mt">
      <button class="btn" type="submit" name="do" value="save_order"><?= t('save') ?></button>
      <?php if ($divOrderCustom): ?>
      <button class="btn secondary" type="submit" name="do" value="reset_order"><?= t('division_order_reset') ?></button>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <h3><?= icon('clock', 16) ?> <?= t('belt_duration_title') ?> <?php if ($beltDurCustom): ?><span class="badge blue"><?= t('belt_duration_custom_badge') ?></span><?php endif; ?></h3>
  <p class="muted" style="margin-top:0"><?= t('belt_duration_tournament_hint') ?></p>
  <form method="post">
    <?= csrf_field() ?>
    <div class="grid cols3">
      <?php foreach (division_order_labels() as $key => $label): ?>
      <div>
        <label><?= e($label) ?></label>
        <input type="number" name="dur_<?= e($key) ?>" value="<?= (int)round($beltDur[$key] / 60) ?>" min="1" max="30"> <span class="muted"><?= t('belt_duration_minutes') ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="flex mt">
      <button class="btn" type="submit" name="do" value="save_duration"><?= t('save') ?></button>
      <?php if ($beltDurCustom): ?>
      <button class="btn secondary" type="submit" name="do" value="reset_duration"><?= t('belt_duration_reset') ?></button>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <h3><?= icon('calendar', 16) ?> <?= t('age_thresholds_title') ?> <?php if ($ageThCustom): ?><span class="badge blue"><?= t('age_thresholds_custom_badge') ?></span><?php endif; ?></h3>
  <p class="muted" style="margin-top:0"><?= t('age_thresholds_tournament_hint') ?></p>
  <form method="post">
    <?= csrf_field() ?>
    <div class="grid cols3">
      <div>
        <label><?= t('age_kids_max') ?></label>
        <input type="number" name="age_kids_max" value="<?= (int)$ageTh['kids_max'] ?>" min="3" max="17"> <span class="muted"><?= t('age_years') ?></span>
      </div>
      <div>
        <label><?= t('age_juvenile_max') ?></label>
        <input type="number" name="age_juvenile_max" value="<?= (int)$ageTh['juvenile_max'] ?>" min="4" max="20"> <span class="muted"><?= t('age_years') ?></span>
      </div>
    </div>
    <div class="flex mt">
      <button class="btn" type="submit" name="do" value="save_age"><?= t('save') ?></button>
      <?php if ($ageThCustom): ?>
      <button class="btn secondary" type="submit" name="do" value="reset_age"><?= t('age_thresholds_reset') ?></button>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php view_footer();
