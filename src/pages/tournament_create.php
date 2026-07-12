<?php
$u = require_login();

if (!can_create_tournament($u)) {
    flash('warning', t('weekly_limit_reached'));
    redirect('/dashboard');
}

$beltDurGlobal = belt_durations_global();
$ageThGlobal = age_thresholds_global();
$divOrderGlobal = division_order_global();
$ageOrderGlobal = age_order_global();
$weightOrderGlobal = weight_order_global();
$nogiTiersGlobal = nogi_tiers_global();
$nogiDivOrderGlobal = nogi_division_order_global();
$nogiTierDurGlobal = nogi_tier_durations_global();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $type = ($_POST['type'] ?? 'internal') === 'open' ? 'open' : 'internal';
    if ($name) {
        $logo = upload_image('logo', 'tournament');
        $durInput = [];
        foreach (belt_duration_defaults() as $key => $def) {
            $durInput[$key] = max(1, (int)($_POST["dur_$key"] ?? round($def / 60))) * 60;
        }
        $durInput = belt_duration_sanitize($durInput);
        $durCustom = $durInput !== $beltDurGlobal ? json_encode($durInput, JSON_UNESCAPED_UNICODE) : null;

        $nogiDurInput = [];
        foreach (nogi_tier_duration_defaults() as $key => $def) {
            $nogiDurInput[$key] = max(1, (int)($_POST["nogi_dur_$key"] ?? round($def / 60))) * 60;
        }
        $nogiDurInput = nogi_tier_duration_sanitize($nogiDurInput);
        $nogiDurCustom = $nogiDurInput !== $nogiTierDurGlobal ? json_encode($nogiDurInput, JSON_UNESCAPED_UNICODE) : null;
        $ageInput = age_threshold_sanitize([
            'kids_max' => (int)($_POST['age_kids_max'] ?? $ageThGlobal['kids_max']),
            'juvenile_max' => (int)($_POST['age_juvenile_max'] ?? $ageThGlobal['juvenile_max']),
        ]);
        $ageCustom = $ageInput !== $ageThGlobal ? json_encode($ageInput, JSON_UNESCAPED_UNICODE) : null;

        $discipline = ($_POST['discipline'] ?? 'gi') === 'nogi' ? 'nogi' : 'gi';
        $tierInput = [];
        foreach (nogi_tier_default() as $belt => $def) {
            $tierInput[$belt] = $_POST["tier_$belt"] ?? $def;
        }
        $tierInput = nogi_tiers_sanitize($tierInput);
        $tierCustom = $tierInput !== $nogiTiersGlobal ? json_encode($tierInput, JSON_UNESCAPED_UNICODE) : null;

        $divKeys = division_order_default();
        usort($divKeys, fn($a, $b) => (int)($_POST["div_ord_$a"] ?? 0) <=> (int)($_POST["div_ord_$b"] ?? 0));
        $divInput = division_order_sanitize($divKeys);
        $divCustom = $divInput !== $divOrderGlobal ? json_encode($divInput, JSON_UNESCAPED_UNICODE) : null;

        $nogiDivKeys = nogi_division_order_default();
        usort($nogiDivKeys, fn($a, $b) => (int)($_POST["nogi_div_ord_$a"] ?? 0) <=> (int)($_POST["nogi_div_ord_$b"] ?? 0));
        $nogiDivInput = nogi_division_order_sanitize($nogiDivKeys);
        $nogiDivCustom = $nogiDivInput !== $nogiDivOrderGlobal ? json_encode($nogiDivInput, JSON_UNESCAPED_UNICODE) : null;

        $ageOrdKeys = age_order_default();
        usort($ageOrdKeys, fn($a, $b) => (int)($_POST["age_ord_$a"] ?? 0) <=> (int)($_POST["age_ord_$b"] ?? 0));
        $ageOrdInput = age_order_sanitize($ageOrdKeys);
        $ageOrdCustom = $ageOrdInput !== $ageOrderGlobal ? json_encode($ageOrdInput, JSON_UNESCAPED_UNICODE) : null;

        $wtKeys = weight_order_default();
        usort($wtKeys, fn($a, $b) => (int)($_POST["wt_ord_$a"] ?? 0) <=> (int)($_POST["wt_ord_$b"] ?? 0));
        $wtInput = weight_order_sanitize($wtKeys);
        $wtCustom = $wtInput !== $weightOrderGlobal ? json_encode($wtInput, JSON_UNESCAPED_UNICODE) : null;

        q('INSERT INTO tournaments (user_id, name, slug, type, discipline, logo, event_date, max_participants, default_duration_sec, belt_durations, age_thresholds, division_order, age_order, weight_order, nogi_tiers, nogi_division_order, nogi_tier_durations)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$u['id'], $name, slug_token(10), $type, $discipline, $logo, $_POST['event_date'] ?? null ?: null,
             max(2, (int)($_POST['max_participants'] ?: 200)),
             max(60, (int)(($_POST['duration_min'] ?: 5)) * 60), $durCustom, $ageCustom, $divCustom, $ageOrdCustom, $wtCustom, $tierCustom, $nogiDivCustom, $nogiDurCustom]);
        $tid = (int)db()->lastInsertId();
        // Torneo interno: se crea la academia organizadora con el mismo logo
        if ($type === 'internal') {
            q('INSERT INTO tournament_academies (tournament_id, name, logo) VALUES (?,?,?)',
                [$tid, trim($_POST['academy_name'] ?: $name), $logo]);
        }
        flash('success', t('tournament_created'));
        redirect("/tournament/$tid/academies");
    }
}
view_header(t('create_tournament'));
?>
<div class="card" style="max-width:560px;margin:0 auto">
  <h2><?= t('create_tournament') ?></h2>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label><?= t('tournament_name') ?></label>
    <input type="text" name="name" required autofocus>
    <label><?= t('tournament_type') ?></label>
    <select name="type" onchange="document.getElementById('acadname').style.display = this.value==='internal' ? '' : 'none'">
      <option value="internal"><?= t('type_internal') ?></option>
      <option value="open"><?= t('type_open') ?></option>
    </select>
    <div id="acadname">
      <label><?= t('academy_name') ?></label>
      <input type="text" name="academy_name">
    </div>
    <label><?= t('academy_logo') ?> / <?= t('logo') ?></label>
    <input type="file" name="logo" accept="image/*">
    <label><?= t('event_date') ?></label>
    <input type="date" name="event_date">
    <label><?= t('max_participants') ?></label>
    <input type="number" name="max_participants" value="200" min="2">
    <label><?= t('fight_duration_default') ?></label>
    <input type="number" name="duration_min" value="5" min="1" max="20">

    <label class="mt"><?= t('discipline') ?></label>
    <select name="discipline" id="disciplinesel" onchange="
      document.getElementById('nogitiers').style.display = this.value==='nogi' ? '' : 'none';
      document.getElementById('divordgi').style.display = this.value==='nogi' ? 'none' : '';
      document.getElementById('divordnogi').style.display = this.value==='nogi' ? '' : 'none';
      document.getElementById('durgi').style.display = this.value==='nogi' ? 'none' : '';
      document.getElementById('durnogi').style.display = this.value==='nogi' ? '' : 'none';
      document.getElementById('durhintgi').style.display = this.value==='nogi' ? 'none' : '';
      document.getElementById('durhintnogi').style.display = this.value==='nogi' ? '' : 'none';
    ">
      <option value="gi"><?= t('discipline_gi') ?></option>
      <option value="nogi"><?= t('discipline_nogi') ?></option>
    </select>
    <p class="muted" style="margin-top:0"><?= t('discipline_hint') ?></p>
    <div id="nogitiers" style="display:none">
      <label><?= t('nogi_tiers_title') ?></label>
      <p class="muted" style="margin-top:0"><?= t('nogi_tiers_hint') ?></p>
      <div class="grid cols3">
        <?php foreach (division_order_labels() as $key => $label): if (!in_array($key, ['white','blue','purple','brown','black'], true)) continue; ?>
        <div>
          <label><?= e($label) ?></label>
          <select name="tier_<?= e($key) ?>">
            <?php foreach (['amateur','semipro','pro'] as $tier): ?>
            <option value="<?= $tier ?>" <?= $nogiTiersGlobal[$key] === $tier ? 'selected' : '' ?>><?= t('nogi_' . $tier) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <label class="mt"><?= t('division_order_title') ?></label>
    <p class="muted" style="margin-top:0"><?= t('division_order_hint') ?></p>
    <div id="divordgi">
      <?php render_drag_order('div_ord', division_order_labels(), $divOrderGlobal); ?>
    </div>
    <div id="divordnogi" style="display:none">
      <?php render_drag_order('nogi_div_ord', nogi_division_order_labels(), $nogiDivOrderGlobal); ?>
    </div>

    <label class="mt"><?= t('age_order_title') ?></label>
    <p class="muted" style="margin-top:0"><?= t('age_order_hint') ?></p>
    <?php render_drag_order('age_ord', age_order_labels(), $ageOrderGlobal); ?>

    <label class="mt"><?= t('weight_order_title') ?></label>
    <p class="muted" style="margin-top:0"><?= t('weight_order_hint') ?></p>
    <?php render_drag_order('wt_ord', weight_order_labels(), $weightOrderGlobal); ?>

    <label class="mt"><?= t('belt_duration_title') ?></label>
    <p class="muted" style="margin-top:0" id="durhintgi"><?= t('belt_duration_hint') ?></p>
    <p class="muted" style="margin-top:0;display:none" id="durhintnogi"><?= t('nogi_duration_hint') ?></p>
    <div class="grid cols3" id="durgi">
      <?php foreach (division_order_labels() as $key => $label): ?>
      <div>
        <label><?= e($label) ?></label>
        <input type="number" name="dur_<?= e($key) ?>" value="<?= (int)round($beltDurGlobal[$key] / 60) ?>" min="1" max="30"> <span class="muted"><?= t('belt_duration_minutes') ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="grid cols3" id="durnogi" style="display:none">
      <?php foreach (nogi_division_order_labels() as $key => $label): ?>
      <div>
        <label><?= e($label) ?></label>
        <input type="number" name="nogi_dur_<?= e($key) ?>" value="<?= (int)round($nogiTierDurGlobal[$key] / 60) ?>" min="1" max="30"> <span class="muted"><?= t('belt_duration_minutes') ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <label class="mt"><?= t('age_thresholds_title') ?></label>
    <p class="muted" style="margin-top:0"><?= t('age_thresholds_hint') ?></p>
    <div class="grid cols3">
      <div>
        <label><?= t('age_kids_max') ?></label>
        <input type="number" name="age_kids_max" value="<?= (int)$ageThGlobal['kids_max'] ?>" min="3" max="17"> <span class="muted"><?= t('age_years') ?></span>
      </div>
      <div>
        <label><?= t('age_juvenile_max') ?></label>
        <input type="number" name="age_juvenile_max" value="<?= (int)$ageThGlobal['juvenile_max'] ?>" min="4" max="20"> <span class="muted"><?= t('age_years') ?></span>
      </div>
    </div>

    <button class="btn mt" style="width:100%"><?= t('create') ?></button>
  </form>
</div>
<script src="<?= asset('/assets/js/dragorder.js') ?>"></script>
<?php view_footer();
