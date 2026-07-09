<?php
require_admin();

$scoring = setting('scoring', []);
$ranking = setting('ranking', []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    set_setting('site_name', trim($_POST['site_name'] ?? 'BJJ Tournament Manager'));
    set_setting('tournament_weekly_limit', (string)max(1, (int)($_POST['weekly_limit'] ?? 1)));
    set_setting('scoring', [
        'takedown' => (int)$_POST['sc_takedown'], 'sweep' => (int)$_POST['sc_sweep'],
        'knee_on_belly' => (int)$_POST['sc_kob'], 'guard_pass' => (int)$_POST['sc_pass'],
        'mount' => (int)$_POST['sc_mount'], 'back_control' => (int)$_POST['sc_back'],
    ]);
    set_setting('ranking', [
        'gold' => (int)$_POST['rk_gold'], 'silver' => (int)$_POST['rk_silver'], 'bronze' => (int)$_POST['rk_bronze'],
        'win' => (int)$_POST['rk_win'], 'submission_bonus' => (int)$_POST['rk_sub'],
    ]);
    flash('success', t('settings_saved'));
    redirect('/admin/settings');
}
view_header(t('settings'));
?>
<h1><?= icon('settings', 24) ?> <?= t('settings') ?></h1>
<form method="post">
  <?= csrf_field() ?>
  <div class="grid cols2">
    <div class="card">
      <h3><?= t('settings') ?></h3>
      <label><?= t('site_name_setting') ?></label>
      <input type="text" name="site_name" value="<?= e((string)setting('site_name', 'BJJ Tournament Manager')) ?>">
      <label><?= t('weekly_limit') ?></label>
      <input type="number" name="weekly_limit" value="<?= (int)setting('tournament_weekly_limit', 1) ?>" min="1">
    </div>
    <div class="card">
      <h3><?= t('scoring_config') ?></h3>
      <div class="grid cols2">
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
      <div class="grid cols2">
        <div><label><?= t('rank_gold') ?></label><input type="number" name="rk_gold" value="<?= (int)($ranking['gold'] ?? 9) ?>"></div>
        <div><label><?= t('rank_silver') ?></label><input type="number" name="rk_silver" value="<?= (int)($ranking['silver'] ?? 3) ?>"></div>
        <div><label><?= t('rank_bronze') ?></label><input type="number" name="rk_bronze" value="<?= (int)($ranking['bronze'] ?? 1) ?>"></div>
        <div><label><?= t('rank_win') ?></label><input type="number" name="rk_win" value="<?= (int)($ranking['win'] ?? 2) ?>"></div>
        <div><label><?= t('rank_sub_bonus') ?></label><input type="number" name="rk_sub" value="<?= (int)($ranking['submission_bonus'] ?? 1) ?>"></div>
      </div>
    </div>
  </div>
  <button class="btn mt"><?= t('save') ?></button>
</form>
<?php view_footer();
