<?php
require_admin();

$scoring = setting('scoring', []);
$ranking = setting('ranking', []);
$smtp = smtp_config();
$divOrder = division_order_global();
$ageOrder = age_order_global();
$weightOrder = weight_order_global();
$beltDur = belt_durations_global();
$ageTh = age_thresholds_global();
$nogiTiers = nogi_tiers_global();
$nogiDivOrder = nogi_division_order_global();
$nogiTierDur = nogi_tier_durations_global();
$testResult = null;
$me = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? 'save';

    set_setting('site_name', trim($_POST['site_name'] ?? 'Taninzu'));
    set_setting('tournament_weekly_limit', (string)max(1, (int)($_POST['weekly_limit'] ?? 1)));
    set_setting('tournament_retention_months', (string)max(0, (int)($_POST['retention_months'] ?? 0)));
    set_setting('scoring', [
        'takedown' => (int)$_POST['sc_takedown'], 'sweep' => (int)$_POST['sc_sweep'],
        'knee_on_belly' => (int)$_POST['sc_kob'], 'guard_pass' => (int)$_POST['sc_pass'],
        'mount' => (int)$_POST['sc_mount'], 'back_control' => (int)$_POST['sc_back'],
    ]);
    set_setting('ranking', [
        'gold' => (int)$_POST['rk_gold'], 'silver' => (int)$_POST['rk_silver'], 'bronze' => (int)$_POST['rk_bronze'],
        'win' => (int)$_POST['rk_win'], 'submission_bonus' => (int)$_POST['rk_sub'],
    ]);
    $divKeys = division_order_default();
    usort($divKeys, fn($a, $b) => (int)($_POST["div_ord_$a"] ?? 0) <=> (int)($_POST["div_ord_$b"] ?? 0));
    set_setting('division_order', division_order_sanitize($divKeys));
    $divOrder = division_order_global();

    $ageKeys = age_order_default();
    usort($ageKeys, fn($a, $b) => (int)($_POST["age_ord_$a"] ?? 0) <=> (int)($_POST["age_ord_$b"] ?? 0));
    set_setting('age_order', age_order_sanitize($ageKeys));
    $ageOrder = age_order_global();

    $wtKeys = weight_order_default();
    usort($wtKeys, fn($a, $b) => (int)($_POST["wt_ord_$a"] ?? 0) <=> (int)($_POST["wt_ord_$b"] ?? 0));
    set_setting('weight_order', weight_order_sanitize($wtKeys));
    $weightOrder = weight_order_global();

    $durInput = [];
    foreach (belt_duration_defaults() as $key => $def) {
        $durInput[$key] = max(1, (int)($_POST["dur_$key"] ?? 0)) * 60;
    }
    set_setting('belt_durations', belt_duration_sanitize($durInput));
    $beltDur = belt_durations_global();

    $nogiDurInput = [];
    foreach (nogi_tier_duration_defaults() as $key => $def) {
        $nogiDurInput[$key] = max(1, (int)($_POST["nogi_dur_$key"] ?? 0)) * 60;
    }
    set_setting('nogi_tier_durations', nogi_tier_duration_sanitize($nogiDurInput));
    $nogiTierDur = nogi_tier_durations_global();

    set_setting('age_thresholds', age_threshold_sanitize([
        'kids_max' => (int)($_POST['age_kids_max'] ?? 0),
        'juvenile_max' => (int)($_POST['age_juvenile_max'] ?? 0),
    ]));
    $ageTh = age_thresholds_global();

    $tierInput = [];
    foreach (nogi_tier_default() as $belt => $def) {
        $tierInput[$belt] = $_POST["tier_$belt"] ?? $def;
    }
    set_setting('nogi_tiers', nogi_tiers_sanitize($tierInput));
    $nogiTiers = nogi_tiers_global();

    $nogiDivKeys = nogi_division_order_default();
    usort($nogiDivKeys, fn($a, $b) => (int)($_POST["nogi_div_ord_$a"] ?? 0) <=> (int)($_POST["nogi_div_ord_$b"] ?? 0));
    set_setting('nogi_division_order', nogi_division_order_sanitize($nogiDivKeys));
    $nogiDivOrder = nogi_division_order_global();

    // La contraseña se deja en blanco para no reemplazarla (no se muestra el valor guardado en el form).
    $newPass = trim($_POST['smtp_pass'] ?? '');
    set_setting('smtp', [
        'host' => trim($_POST['smtp_host'] ?? ''),
        'port' => (int)($_POST['smtp_port'] ?? 587),
        'user' => trim($_POST['smtp_user'] ?? ''),
        'pass' => $newPass !== '' ? $newPass : $smtp['pass'],
        'secure' => in_array($_POST['smtp_secure'] ?? '', ['', 'tls', 'ssl']) ? $_POST['smtp_secure'] : '',
        'from' => trim($_POST['smtp_from'] ?? ''),
        'from_name' => trim($_POST['smtp_from_name'] ?? ''),
    ]);
    $smtp = smtp_config();

    if ($do === 'send_test') {
        $to = trim($_POST['test_to'] ?? $me['email'] ?? '');
        if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $id = queue_mail($to, null, t('smtp_test_subject'),
                mail_layout(t('smtp_test_subject'), '<p>' . t('smtp_test_body') . '</p>'));
            $sent = row('SELECT status, error FROM email_queue WHERE id = ?', [$id]);
            $testResult = $sent['status'] === 'sent'
                ? ['ok' => true, 'msg' => t('smtp_test_ok')]
                : ['ok' => false, 'msg' => t('smtp_test_fail') . ' ' . e((string)$sent['error'])];
        }
    } else {
        flash('success', t('settings_saved'));
        redirect('/admin/settings');
    }
}
view_header(t('settings'));
?>
<h1><?= icon('settings', 24) ?> <?= t('settings') ?></h1>
<?php if ($testResult): ?>
  <div class="flash flash-<?= $testResult['ok'] ? 'success' : 'error' ?>"><?= $testResult['msg'] ?></div>
<?php endif; ?>
<form method="post">
  <?= csrf_field() ?>
  <div class="card">
    <h3><?= t('settings') ?></h3>
    <div class="grid cols3">
      <div>
        <label><?= t('site_name_setting') ?></label>
        <input type="text" name="site_name" value="<?= e((string)setting('site_name', 'Taninzu')) ?>">
      </div>
      <div>
        <label><?= t('weekly_limit') ?></label>
        <input type="number" name="weekly_limit" value="<?= (int)setting('tournament_weekly_limit', 1) ?>" min="1">
      </div>
      <div>
        <label><?= t('retention_months') ?></label>
        <input type="number" name="retention_months" value="<?= (int)setting('tournament_retention_months', 0) ?>" min="0">
        <span class="muted"><?= t('retention_months_hint') ?></span>
      </div>
    </div>
  </div>

  <div class="card">
    <h3><?= icon('sliders', 17) ?> <?= t('division_order_title') ?> · Gi</h3>
    <p class="muted" style="margin-top:0"><?= t('division_order_hint') ?></p>
    <?php render_drag_order('div_ord', division_order_labels(), $divOrder); ?>
  </div>

  <div class="card">
    <h3><?= icon('sliders', 17) ?> <?= t('division_order_title') ?> · NoGi</h3>
    <p class="muted" style="margin-top:0"><?= t('nogi_division_order_hint') ?></p>
    <?php render_drag_order('nogi_div_ord', nogi_division_order_labels(), $nogiDivOrder); ?>
  </div>

  <div class="card">
    <h3><?= icon('calendar', 17) ?> <?= t('age_order_title') ?></h3>
    <p class="muted" style="margin-top:0"><?= t('age_order_hint') ?></p>
    <?php render_drag_order('age_ord', age_order_labels(), $ageOrder); ?>
  </div>

  <div class="card">
    <h3><?= icon('sliders', 17) ?> <?= t('weight_order_title') ?></h3>
    <p class="muted" style="margin-top:0"><?= t('weight_order_hint') ?></p>
    <?php render_drag_order('wt_ord', weight_order_labels(), $weightOrder); ?>
  </div>

  <div class="card">
    <h3><?= icon('clock', 17) ?> <?= t('belt_duration_title') ?> · Gi</h3>
    <p class="muted" style="margin-top:0"><?= t('belt_duration_hint') ?></p>
    <div class="grid cols3">
      <?php foreach (division_order_labels() as $key => $label): ?>
      <div>
        <label><?= e($label) ?></label>
        <input type="number" name="dur_<?= e($key) ?>" value="<?= (int)round($beltDur[$key] / 60) ?>" min="1" max="30"> <span class="muted"><?= t('belt_duration_minutes') ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <h3><?= icon('clock', 17) ?> <?= t('nogi_duration_title') ?> · NoGi</h3>
    <p class="muted" style="margin-top:0"><?= t('nogi_duration_hint') ?></p>
    <div class="grid cols3">
      <?php foreach (nogi_division_order_labels() as $key => $label): ?>
      <div>
        <label><?= e($label) ?></label>
        <input type="number" name="nogi_dur_<?= e($key) ?>" value="<?= (int)round($nogiTierDur[$key] / 60) ?>" min="1" max="30"> <span class="muted"><?= t('belt_duration_minutes') ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <h3><?= icon('calendar', 17) ?> <?= t('age_thresholds_title') ?></h3>
    <p class="muted" style="margin-top:0"><?= t('age_thresholds_hint') ?></p>
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
  </div>

  <div class="card">
    <h3><?= icon('flag', 17) ?> <?= t('nogi_tiers_title') ?></h3>
    <p class="muted" style="margin-top:0"><?= t('nogi_tiers_hint') ?></p>
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
  </div>

  <div class="card">
    <h3><?= icon('mail', 17) ?> <?= t('smtp_config') ?></h3>
    <p class="muted" style="margin-top:0"><?= t('smtp_hint') ?></p>
    <div class="grid cols3">
      <div>
        <label><?= t('smtp_host') ?></label>
        <input type="text" name="smtp_host" value="<?= e($smtp['host']) ?>" placeholder="smtp.gmail.com">
      </div>
      <div>
        <label><?= t('smtp_port') ?></label>
        <input type="number" name="smtp_port" value="<?= (int)$smtp['port'] ?>" placeholder="587">
      </div>
      <div>
        <label><?= t('smtp_user') ?></label>
        <input type="text" name="smtp_user" value="<?= e($smtp['user']) ?>" autocomplete="off">
      </div>
      <div>
        <label><?= t('smtp_pass') ?></label>
        <input type="password" name="smtp_pass" value="" placeholder="<?= $smtp['pass'] ? t('smtp_pass_unchanged') : '' ?>" autocomplete="new-password">
      </div>
      <div>
        <label><?= t('smtp_secure') ?></label>
        <select name="smtp_secure">
          <option value="" <?= $smtp['secure'] === '' ? 'selected' : '' ?>><?= t('smtp_secure_none') ?></option>
          <option value="tls" <?= $smtp['secure'] === 'tls' ? 'selected' : '' ?>>STARTTLS</option>
          <option value="ssl" <?= $smtp['secure'] === 'ssl' ? 'selected' : '' ?>>SSL/TLS</option>
        </select>
      </div>
      <div>
        <label><?= t('smtp_from') ?></label>
        <input type="email" name="smtp_from" value="<?= e($smtp['from']) ?>">
      </div>
      <div>
        <label><?= t('smtp_from_name') ?></label>
        <input type="text" name="smtp_from_name" value="<?= e($smtp['from_name']) ?>">
      </div>
    </div>
    <label class="mt"><?= t('smtp_test_to') ?></label>
    <div class="flex">
      <div style="flex:1;min-width:180px"><input type="email" name="test_to" value="<?= e($me['email'] ?? '') ?>"></div>
      <button class="btn secondary" type="submit" name="do" value="send_test"><?= icon('zap', 14) ?> <?= t('smtp_send_test') ?></button>
    </div>
  </div>

  <div class="card">
    <h3><?= t('scoring_config') ?></h3>
    <div class="grid cols4">
      <div><label><?= t('takedown') ?></label><input type="number" name="sc_takedown" value="<?= (int)($scoring['takedown'] ?? 2) ?>"></div>
      <div><label><?= t('sweep') ?></label><input type="number" name="sc_sweep" value="<?= (int)($scoring['sweep'] ?? 2) ?>"></div>
      <div><label><?= t('knee_on_belly') ?></label><input type="number" name="sc_kob" value="<?= (int)($scoring['knee_on_belly'] ?? 2) ?>"></div>
      <div><label><?= t('guard_pass') ?></label><input type="number" name="sc_pass" value="<?= (int)($scoring['guard_pass'] ?? 3) ?>"></div>
      <div><label><?= t('mount') ?></label><input type="number" name="sc_mount" value="<?= (int)($scoring['mount'] ?? 4) ?>"></div>
      <div><label><?= t('back_control') ?></label><input type="number" name="sc_back" value="<?= (int)($scoring['back_control'] ?? 4) ?>"></div>
    </div>
  </div>

  <div class="card">
    <h3><?= t('ranking_config') ?></h3>
    <div class="grid cols4">
      <div><label><?= t('rank_gold') ?></label><input type="number" name="rk_gold" value="<?= (int)($ranking['gold'] ?? 9) ?>"></div>
      <div><label><?= t('rank_silver') ?></label><input type="number" name="rk_silver" value="<?= (int)($ranking['silver'] ?? 3) ?>"></div>
      <div><label><?= t('rank_bronze') ?></label><input type="number" name="rk_bronze" value="<?= (int)($ranking['bronze'] ?? 1) ?>"></div>
      <div><label><?= t('rank_win') ?></label><input type="number" name="rk_win" value="<?= (int)($ranking['win'] ?? 2) ?>"></div>
      <div><label><?= t('rank_sub_bonus') ?></label><input type="number" name="rk_sub" value="<?= (int)($ranking['submission_bonus'] ?? 1) ?>"></div>
    </div>
  </div>

  <button class="btn mt" type="submit" name="do" value="save"><?= t('save') ?></button>
</form>
<script src="<?= asset('/assets/js/dragorder.js') ?>"></script>
<?php view_footer();
