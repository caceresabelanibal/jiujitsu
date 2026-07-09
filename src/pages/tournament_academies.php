<?php
$t = require_tournament_owner((int)$params[0]);
$tid = (int)$t['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'add_academy' && trim($_POST['name'] ?? '')) {
        // Torneo interno: solo una academia
        $count = (int)scalar('SELECT COUNT(*) FROM tournament_academies WHERE tournament_id=?', [$tid]);
        if ($t['type'] === 'open' || $count === 0) {
            q('INSERT INTO tournament_academies (tournament_id, name, logo) VALUES (?,?,?)',
                [$tid, trim($_POST['name']), upload_image('logo', 'academy')]);
        }
    } elseif ($do === 'del_academy') {
        q('DELETE FROM tournament_academies WHERE id=? AND tournament_id=?', [(int)$_POST['id'], $tid]);
    } elseif ($do === 'add_professor' && trim($_POST['name'] ?? '')) {
        q('INSERT INTO tournament_professors (tournament_id, academy_id, name, sede) VALUES (?,?,?,?)',
            [$tid, (int)$_POST['academy_id'], trim($_POST['name']), trim($_POST['sede'] ?? '') ?: null]);
    } elseif ($do === 'del_professor') {
        q('DELETE FROM tournament_professors WHERE id=? AND tournament_id=?', [(int)$_POST['id'], $tid]);
    }
    redirect("/tournament/$tid/academies");
}

$academies = rows('SELECT * FROM tournament_academies WHERE tournament_id=? ORDER BY name', [$tid]);
view_header(t('academies'));
?>
<h1><?= e($t['name']) ?></h1>
<?php tournament_tabs($t, 'academies'); ?>

<?php if ($t['type'] === 'open' || !$academies): ?>
<div class="card">
  <h3><?= t('add_academy') ?><?= $t['type'] === 'open' ? ' (Open)' : '' ?></h3>
  <form method="post" enctype="multipart/form-data" class="flex">
    <?= csrf_field() ?>
    <input type="hidden" name="do" value="add_academy">
    <div style="flex:2;min-width:200px"><input type="text" name="name" placeholder="<?= t('academy_name') ?>" required></div>
    <div style="flex:1;min-width:180px"><input type="file" name="logo" accept="image/*"></div>
    <button class="btn">+</button>
  </form>
</div>
<?php endif; ?>

<div class="grid cols2">
<?php foreach ($academies as $a):
    $profs = rows('SELECT * FROM tournament_professors WHERE academy_id=? ORDER BY name', [$a['id']]); ?>
  <div class="card">
    <div class="flex spread">
      <h3><?php if ($a['logo']): ?><img class="logo-sm" src="<?= APP_URL . '/' . e($a['logo']) ?>" alt=""> <?php endif; ?><?= e($a['name']) ?></h3>
      <form method="post" data-confirm="<?= t('confirm_delete') ?>">
        <?= csrf_field() ?><input type="hidden" name="do" value="del_academy"><input type="hidden" name="id" value="<?= $a['id'] ?>">
        <button class="btn sm danger"><?= icon('x', 13) ?></button>
      </form>
    </div>
    <h4 class="muted"><?= t('professors') ?></h4>
    <?php foreach ($profs as $p): ?>
      <div class="flex spread" style="padding:5px 0;border-bottom:1px solid var(--border)">
        <span><?= e($p['name']) ?><?= $p['sede'] ? ' <span class="muted">· ' . e($p['sede']) . '</span>' : '' ?></span>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="del_professor"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn sm secondary"><?= icon('x', 12) ?></button></form>
      </div>
    <?php endforeach; ?>
    <form method="post" class="flex mt">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="add_professor">
      <input type="hidden" name="academy_id" value="<?= $a['id'] ?>">
      <div style="flex:1"><input type="text" name="name" placeholder="<?= t('professor_name') ?>" required></div>
      <div style="flex:1"><input type="text" name="sede" placeholder="<?= t('sede') ?>"></div>
      <button class="btn sm">+</button>
    </form>
  </div>
<?php endforeach; ?>
</div>
<?php view_footer();
