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
    } elseif ($do === 'save_nogi_order') {
        $nogiDivKeys = nogi_division_order_default();
        usort($nogiDivKeys, fn($a, $b) => (int)($_POST["nogi_div_ord_$a"] ?? 0) <=> (int)($_POST["nogi_div_ord_$b"] ?? 0));
        q('UPDATE tournaments SET nogi_division_order = ? WHERE id = ?', [json_encode(nogi_division_order_sanitize($nogiDivKeys), JSON_UNESCAPED_UNICODE), $tid]);
        flash('success', t('settings_saved'));
    } elseif ($do === 'reset_nogi_order') {
        q('UPDATE tournaments SET nogi_division_order = NULL WHERE id = ?', [$tid]);
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
    } elseif ($do === 'save_nogi_duration') {
        $nogiDurInput = [];
        foreach (nogi_tier_duration_defaults() as $key => $def) {
            $nogiDurInput[$key] = max(1, (int)($_POST["nogi_dur_$key"] ?? 0)) * 60;
        }
        $nogiDurations = nogi_tier_duration_sanitize($nogiDurInput);
        q('UPDATE tournaments SET nogi_tier_durations = ? WHERE id = ?', [json_encode($nogiDurations, JSON_UNESCAPED_UNICODE), $tid]);
        apply_nogi_tier_durations($tid, $nogiDurations);
        flash('success', t('settings_saved'));
    } elseif ($do === 'reset_nogi_duration') {
        q('UPDATE tournaments SET nogi_tier_durations = NULL WHERE id = ?', [$tid]);
        apply_nogi_tier_durations($tid, nogi_tier_durations_global());
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
    } elseif ($do === 'save_age_order') {
        $ageKeys = age_order_default();
        usort($ageKeys, fn($a, $b) => (int)($_POST["age_ord_$a"] ?? 0) <=> (int)($_POST["age_ord_$b"] ?? 0));
        q('UPDATE tournaments SET age_order = ? WHERE id = ?', [json_encode(age_order_sanitize($ageKeys), JSON_UNESCAPED_UNICODE), $tid]);
        flash('success', t('settings_saved'));
    } elseif ($do === 'reset_age_order') {
        q('UPDATE tournaments SET age_order = NULL WHERE id = ?', [$tid]);
        flash('success', t('settings_saved'));
    } elseif ($do === 'save_weight_order') {
        $wtKeys = weight_order_default();
        usort($wtKeys, fn($a, $b) => (int)($_POST["wt_ord_$a"] ?? 0) <=> (int)($_POST["wt_ord_$b"] ?? 0));
        q('UPDATE tournaments SET weight_order = ? WHERE id = ?', [json_encode(weight_order_sanitize($wtKeys), JSON_UNESCAPED_UNICODE), $tid]);
        flash('success', t('settings_saved'));
    } elseif ($do === 'reset_weight_order') {
        q('UPDATE tournaments SET weight_order = NULL WHERE id = ?', [$tid]);
        flash('success', t('settings_saved'));
    } elseif ($do === 'save_tiers') {
        $tierInput = [];
        foreach (nogi_tier_default() as $belt => $def) {
            $tierInput[$belt] = $_POST["tier_$belt"] ?? $def;
        }
        q('UPDATE tournaments SET nogi_tiers = ? WHERE id = ?', [json_encode(nogi_tiers_sanitize($tierInput), JSON_UNESCAPED_UNICODE), $tid]);
        reconcile_nogi_tier_divisions($tid);
        flash('success', t('settings_saved'));
    } elseif ($do === 'reset_tiers') {
        q('UPDATE tournaments SET nogi_tiers = NULL WHERE id = ?', [$tid]);
        reconcile_nogi_tier_divisions($tid);
        flash('success', t('settings_saved'));
    }
    redirect("/tournament/$tid/settings");
}

$staff = rows('SELECT s.id, u.name, u.email FROM tournament_staff s JOIN users u ON u.id = s.user_id WHERE s.tournament_id = ? ORDER BY u.name', [$tid]);
$divOrder = division_order_for($t);
$divOrderCustom = !empty($t['division_order']);
$nogiTiers = nogi_tiers_for($t);
$nogiDivOrder = nogi_division_order_for($t);
$nogiDivOrderCustom = !empty($t['nogi_division_order']);
$beltDur = belt_durations_for($t);
$beltDurCustom = !empty($t['belt_durations']);
$nogiTierDur = nogi_tier_durations_for($t);
$nogiTierDurCustom = !empty($t['nogi_tier_durations']);
$ageTh = age_thresholds_for($t);
$ageThCustom = !empty($t['age_thresholds']);
$ageOrder = age_order_for($t);
$ageOrderCustom = !empty($t['age_order']);
$weightOrder = weight_order_for($t);
$weightOrderCustom = !empty($t['weight_order']);
$nogiTiersCustom = !empty($t['nogi_tiers']);
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
      <label><?= t('reg_close_date') ?></label>
      <input type="date" name="reg_close_date" value="<?= e($t['reg_close_date'] ?? '') ?>">
      <small class="muted"><?= t('reg_close_hint') ?></small>
      <label><?= t('max_participants') ?></label>
      <input type="number" name="max_participants" value="<?= (int)$t['max_participants'] ?>" min="2">
      <label><?= t('fight_duration_default') ?></label>
      <input type="number" name="duration_min" value="<?= (int)round($t['default_duration_sec'] / 60) ?>" min="1" max="20">
      <label><?= t('discipline') ?></label>
      <select name="discipline">
        <option value="gi" <?= $t['discipline'] === 'gi' ? 'selected' : '' ?>><?= t('discipline_gi') ?></option>
        <option value="nogi" <?= $t['discipline'] === 'nogi' ? 'selected' : '' ?>><?= t('discipline_nogi') ?></option>
      </select>
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

<?php if ($t['discipline'] === 'gi'): ?>
<div class="card">
  <h3><?= icon('sliders', 16) ?> <?= t('division_order_title') ?> <?php if ($divOrderCustom): ?><span class="badge blue"><?= t('division_order_custom_badge') ?></span><?php endif; ?></h3>
  <p class="muted" style="margin-top:0"><?= t('division_order_tournament_hint') ?></p>
  <form method="post">
    <?= csrf_field() ?>
    <?php render_drag_order('div_ord', division_order_labels(), $divOrder); ?>
    <div class="flex mt">
      <button class="btn" type="submit" name="do" value="save_order"><?= t('save') ?></button>
      <?php if ($divOrderCustom): ?>
      <button class="btn secondary" type="submit" name="do" value="reset_order"><?= t('division_order_reset') ?></button>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php else: ?>
<div class="card">
  <h3><?= icon('sliders', 16) ?> <?= t('division_order_title') ?> <?php if ($nogiDivOrderCustom): ?><span class="badge blue"><?= t('nogi_division_order_custom_badge') ?></span><?php endif; ?></h3>
  <p class="muted" style="margin-top:0"><?= t('nogi_division_order_tournament_hint') ?></p>
  <form method="post">
    <?= csrf_field() ?>
    <?php render_drag_order('nogi_div_ord', nogi_division_order_labels(), $nogiDivOrder); ?>
    <div class="flex mt">
      <button class="btn" type="submit" name="do" value="save_nogi_order"><?= t('save') ?></button>
      <?php if ($nogiDivOrderCustom): ?>
      <button class="btn secondary" type="submit" name="do" value="reset_nogi_order"><?= t('nogi_division_order_reset') ?></button>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <h3><?= icon('calendar', 16) ?> <?= t('age_order_title') ?> <?php if ($ageOrderCustom): ?><span class="badge blue"><?= t('age_order_custom_badge') ?></span><?php endif; ?></h3>
  <p class="muted" style="margin-top:0"><?= t('age_order_tournament_hint') ?></p>
  <form method="post">
    <?= csrf_field() ?>
    <?php render_drag_order('age_ord', age_order_labels(), $ageOrder); ?>
    <div class="flex mt">
      <button class="btn" type="submit" name="do" value="save_age_order"><?= t('save') ?></button>
      <?php if ($ageOrderCustom): ?>
      <button class="btn secondary" type="submit" name="do" value="reset_age_order"><?= t('age_order_reset') ?></button>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <h3><?= icon('sliders', 16) ?> <?= t('weight_order_title') ?> <?php if ($weightOrderCustom): ?><span class="badge blue"><?= t('weight_order_custom_badge') ?></span><?php endif; ?></h3>
  <p class="muted" style="margin-top:0"><?= t('weight_order_tournament_hint') ?></p>
  <form method="post">
    <?= csrf_field() ?>
    <?php render_drag_order('wt_ord', weight_order_labels(), $weightOrder); ?>
    <div class="flex mt">
      <button class="btn" type="submit" name="do" value="save_weight_order"><?= t('save') ?></button>
      <?php if ($weightOrderCustom): ?>
      <button class="btn secondary" type="submit" name="do" value="reset_weight_order"><?= t('weight_order_reset') ?></button>
      <?php endif; ?>
    </div>
  </form>
</div>

<?php if ($t['discipline'] === 'gi'): ?>
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
<?php else: ?>
<div class="card">
  <h3><?= icon('clock', 16) ?> <?= t('nogi_duration_title') ?> <?php if ($nogiTierDurCustom): ?><span class="badge blue"><?= t('nogi_duration_custom_badge') ?></span><?php endif; ?></h3>
  <p class="muted" style="margin-top:0"><?= t('nogi_duration_tournament_hint') ?></p>
  <form method="post">
    <?= csrf_field() ?>
    <div class="grid cols3">
      <?php foreach (nogi_division_order_labels() as $key => $label): ?>
      <div>
        <label><?= e($label) ?></label>
        <input type="number" name="nogi_dur_<?= e($key) ?>" value="<?= (int)round($nogiTierDur[$key] / 60) ?>" min="1" max="30"> <span class="muted"><?= t('belt_duration_minutes') ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="flex mt">
      <button class="btn" type="submit" name="do" value="save_nogi_duration"><?= t('save') ?></button>
      <?php if ($nogiTierDurCustom): ?>
      <button class="btn secondary" type="submit" name="do" value="reset_nogi_duration"><?= t('nogi_duration_reset') ?></button>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php endif; ?>

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

<?php if ($t['discipline'] === 'nogi'): ?>
<div class="card">
  <h3><?= icon('flag', 16) ?> <?= t('nogi_tiers_title') ?> <?php if ($nogiTiersCustom): ?><span class="badge blue"><?= t('nogi_tiers_custom_badge') ?></span><?php endif; ?></h3>
  <p class="muted" style="margin-top:0"><?= t('nogi_tiers_tournament_hint') ?></p>
  <form method="post">
    <?= csrf_field() ?>
    <div class="grid cols3">
      <?php foreach (division_order_labels() as $key => $label): if (!in_array($key, ['white','blue','purple','brown','black'], true)) continue; ?>
      <div>
        <label><?= e($label) ?></label>
        <select name="tier_<?= e($key) ?>">
          <?php foreach (['amateur','semipro','pro'] as $tier): ?>
          <option value="<?= $tier ?>" <?= $nogiTiers[$key] === $tier ? 'selected' : '' ?>><?= t('nogi_' . $tier) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="flex mt">
      <button class="btn" type="submit" name="do" value="save_tiers"><?= t('save') ?></button>
      <?php if ($nogiTiersCustom): ?>
      <button class="btn secondary" type="submit" name="do" value="reset_tiers"><?= t('nogi_tiers_reset') ?></button>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php endif; ?>

<?php if ($canClone): ?>
<div class="card" style="border-color:var(--red)">
  <h3><?= icon('trash', 16) ?> <?= t('danger_zone') ?></h3>
  <p class="muted" style="margin-top:0"><?= t('delete_tournament_hint') ?></p>
  <a class="btn danger" href="<?= APP_URL ?>/tournament/<?= $tid ?>/delete"><?= icon('trash', 15) ?> <?= t('delete_tournament') ?></a>
</div>
<?php endif; ?>
<script src="<?= asset('/assets/js/dragorder.js') ?>"></script>
<?php view_footer();
