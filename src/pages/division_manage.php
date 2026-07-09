<?php
$d = row('SELECT * FROM divisions WHERE id = ?', [(int)$params[0]]);
if (!$d) { http_response_code(404); require BASE_PATH . '/src/pages/_404.php'; exit; }
$t = require_tournament_owner((int)$d['tournament_id']);
$did = (int)$d['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';
    try {
        if ($do === 'random') {
            generate_bracket($did, [], true);
            flash('success', t('bracket_generated'));
        } elseif ($do === 'manual') {
            $order = array_map('intval', $_POST['order'] ?? []);
            generate_bracket($did, array_values(array_filter($order)));
            flash('success', t('bracket_generated'));
        } elseif ($do === 'duration') {
            $sec = max(60, (int)$_POST['minutes'] * 60 + (int)$_POST['seconds']);
            q('UPDATE divisions SET duration_sec = ? WHERE id = ?', [$sec, $did]);
            q('UPDATE matches SET duration_sec = ?, timer_remaining = ? WHERE division_id = ? AND status = "pending"', [$sec, $sec, $did]);
            flash('success', t('settings_saved'));
        }
    } catch (RuntimeException $e) {
        flash('error', t('need_two'));
    }
    redirect("/division/$did");
}

$regs = division_registrations($did);
$hasBracket = (int)scalar('SELECT COUNT(*) FROM matches WHERE division_id = ?', [$did]) > 0;
view_header(t('division'));
?>
<script src="<?= APP_URL ?>/assets/js/bracket.js"></script>
<p><a href="<?= APP_URL ?>/tournament/<?= $t['id'] ?>/divisions">← <?= t('back') ?></a></p>
<div class="flex spread">
  <h1><?= e(division_label($d)) ?></h1>
  <a class="btn secondary" href="<?= APP_URL ?>/division/<?= $did ?>/view" target="_blank"><?= icon('screen', 15) ?> <?= t('projector_view') ?></a>
</div>

<div class="grid cols2 mb">
  <div class="card">
    <h3><?= t('competitors') ?> (<?= count($regs) ?>)</h3>
    <?php if (count($regs) < 2): ?>
      <p class="muted"><?= t('no_competitors') ?></p>
    <?php else: ?>
    <p class="muted"><?= t('manual_order_hint') ?></p>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="manual">
      <?php foreach ($regs as $i => $r): ?>
      <div class="flex" style="margin-bottom:6px">
        <select name="order[]" style="flex:1">
          <?php foreach ($regs as $r2): ?>
          <option value="<?= $r2['id'] ?>" <?= $r2['id'] === $r['id'] ? 'selected' : '' ?>><?= e($r2['name']) ?> (<?= e($r2['academy_name'] ?? '—') ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endforeach; ?>
      <div class="flex mt">
        <button class="btn"><?= $hasBracket ? t('regenerate') : t('save_bracket') ?></button>
        <button class="btn warn" name="do" value="random"><?= icon('shuffle', 14) ?> <?= t('seed_random') ?></button>
      </div>
    </form>
    <?php endif; ?>
  </div>
  <div class="card">
    <h3><?= t('duration') ?></h3>
    <form method="post" class="flex">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="duration">
      <div><label>Min</label><input type="number" name="minutes" value="<?= intdiv((int)$d['duration_sec'], 60) ?>" min="0" max="20" style="width:90px"></div>
      <div><label>Seg</label><input type="number" name="seconds" value="<?= (int)$d['duration_sec'] % 60 ?>" min="0" max="59" style="width:90px"></div>
      <button class="btn" style="margin-top:26px"><?= t('save') ?></button>
    </form>
  </div>
</div>

<?php if ($hasBracket): ?>
<div class="card">
  <h3><?= t('bracket') ?></h3>
  <?php render_bracket($did, true); ?>
</div>
<?php endif;
view_footer();
