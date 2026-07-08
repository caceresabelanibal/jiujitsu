<?php
$u = require_login();

if (!can_create_tournament($u)) {
    flash('warning', t('weekly_limit_reached'));
    redirect('/tournaments');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $type = ($_POST['type'] ?? 'internal') === 'open' ? 'open' : 'internal';
    if ($name) {
        $logo = upload_image('logo', 'tournament');
        q('INSERT INTO tournaments (user_id, name, slug, type, logo, event_date, max_participants, default_duration_sec)
           VALUES (?,?,?,?,?,?,?,?)',
            [$u['id'], $name, slug_token(10), $type, $logo, $_POST['event_date'] ?: null,
             max(2, (int)($_POST['max_participants'] ?: 200)),
             max(60, (int)(($_POST['duration_min'] ?: 5)) * 60)]);
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
    <button class="btn mt" style="width:100%"><?= t('create') ?></button>
  </form>
</div>
<?php view_footer();
