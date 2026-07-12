<?php
// Clonar torneo: misma estructura y configuracion (academias, profesores, ajustes),
// sin competidores/divisiones/llaves. Solo el creador del torneo o un admin puede clonarlo.
$u = require_login();
$src = require_tournament_creator((int)$params[0]);
$sid = (int)$src['id'];

$organizers = is_admin()
    ? rows("SELECT id, name, email FROM users WHERE role IN ('user','admin') ORDER BY name")
    : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $targetUserId = $u['id'];
    if (is_admin() && !empty($_POST['owner_id'])) {
        $picked = row('SELECT id FROM users WHERE id = ?', [(int)$_POST['owner_id']]);
        if ($picked) $targetUserId = (int)$picked['id'];
    }
    if (!is_admin() && !can_create_tournament($u)) {
        flash('warning', t('weekly_limit_reached'));
        redirect('/dashboard');
    }

    $name = trim($_POST['name'] ?? '') ?: ($src['name'] . ' ' . t('clone_suffix'));
    q('INSERT INTO tournaments (user_id, name, slug, type, discipline, logo, max_participants, default_duration_sec, ads_mode,
                                division_order, belt_durations, age_thresholds, age_order, weight_order, nogi_tiers, nogi_division_order, nogi_tier_durations, status)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [$targetUserId, $name, slug_token(10), $src['type'], $src['discipline'], $src['logo'],
         $src['max_participants'], $src['default_duration_sec'], $src['ads_mode'],
         $src['division_order'], $src['belt_durations'], $src['age_thresholds'],
         $src['age_order'], $src['weight_order'], $src['nogi_tiers'], $src['nogi_division_order'], $src['nogi_tier_durations'], 'draft']);
    $newId = (int)db()->lastInsertId();

    $academyMap = [];
    foreach (rows('SELECT * FROM tournament_academies WHERE tournament_id = ?', [$sid]) as $a) {
        q('INSERT INTO tournament_academies (tournament_id, name, logo) VALUES (?,?,?)', [$newId, $a['name'], $a['logo']]);
        $academyMap[$a['id']] = (int)db()->lastInsertId();
    }
    foreach (rows('SELECT * FROM tournament_professors WHERE tournament_id = ?', [$sid]) as $p) {
        q('INSERT INTO tournament_professors (tournament_id, academy_id, name, sede) VALUES (?,?,?,?)',
            [$newId, $academyMap[$p['academy_id']] ?? null, $p['name'], $p['sede']]);
    }

    flash('success', t('clone_success'));
    redirect("/tournament/$newId/settings");
}

view_header(t('clone_tournament'));
?>
<div class="card" style="max-width:560px;margin:0 auto">
  <h2><?= icon('shuffle', 20) ?> <?= t('clone_tournament') ?></h2>
  <p class="muted"><?= t('clone_hint') ?></p>
  <form method="post">
    <?= csrf_field() ?>
    <label><?= t('tournament_name') ?></label>
    <input type="text" name="name" value="<?= e($src['name'] . ' ' . t('clone_suffix')) ?>" required autofocus>
    <?php if (is_admin()): ?>
    <label><?= t('clone_assign_to') ?></label>
    <select name="owner_id">
      <?php foreach ($organizers as $o): ?>
      <option value="<?= $o['id'] ?>" <?= (int)$o['id'] === (int)$src['user_id'] ? 'selected' : '' ?>><?= e($o['name']) ?> (<?= e($o['email']) ?>)</option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <button class="btn mt" style="width:100%"><?= t('clone_tournament') ?></button>
  </form>
</div>
<?php view_footer();
