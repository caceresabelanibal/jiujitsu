<?php
$t = require_tournament_owner((int)$params[0]);
$tid = (int)$t['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'generate') {
        $n = ensure_divisions($tid);
        flash('success', "+$n " . t('divisions'));
    } elseif ($do === 'create_special') {
        $name = trim($_POST['name'] ?? '');
        $gender = ($_POST['gender'] ?? 'M') === 'F' ? 'F' : 'M';
        if ($name) {
            try {
                q("INSERT INTO divisions (tournament_id, gender, kind, name, duration_sec) VALUES (?,?,'special',?,?)",
                    [$tid, $gender, $name, (int)($t['default_duration_sec'] ?: 300)]);
                flash('success', t('special_created'));
            } catch (PDOException $e) {
                // uq_special: ya existe una categoria especial con ese nombre/genero (doble click, etc.)
                flash('success', t('special_created'));
            }
        }
    } elseif ($do === 'delete') {
        q('DELETE FROM divisions WHERE id=? AND tournament_id=?', [(int)($_POST['id'] ?? 0), $tid]);
        flash('success', t('division_deleted'));
    }
    redirect("/tournament/$tid/divisions");
}

$divOrder = $t['discipline'] === 'nogi'
    ? nogi_division_order_case_sql(nogi_division_order_for($t))
    : division_order_case_sql(division_order_for($t));
$ageOrder = age_order_case_sql(age_order_for($t));
$weightOrder = weight_order_case_sql(weight_order_for($t));
$divs = rows("SELECT d.*, b.color_hex,
                     (SELECT COUNT(*) FROM matches m WHERE m.division_id=d.id) has_bracket
              FROM divisions d
              LEFT JOIN belts b ON b.id=d.belt_id
              LEFT JOIN age_divisions ad ON ad.id=d.age_division_id
              LEFT JOIN weight_classes wc ON wc.id=d.weight_class_id
              WHERE d.tournament_id=? ORDER BY $divOrder, d.gender, $ageOrder, b.sort, $weightOrder", [$tid]);
view_header(t('divisions'));
?>
<h1><?= e($t['name']) ?></h1>
<?php tournament_tabs($t, 'divisions'); ?>
<form method="post" class="mb"><?= csrf_field() ?><input type="hidden" name="do" value="generate">
  <button class="btn"><?= t('generate_divisions') ?></button>
  <span class="muted">← <?= t('registrations') ?> → <?= t('divisions') ?></span>
</form>

<div class="card mb">
  <h3><?= icon('star', 16) ?> <?= t('special_create_title') ?></h3>
  <p class="muted" style="margin-top:0"><?= t('special_create_hint') ?></p>
  <form method="post" class="flex">
    <?= csrf_field() ?>
    <input type="hidden" name="do" value="create_special">
    <div style="flex:1;min-width:200px"><input type="text" name="name" placeholder="<?= t('special_name_ph') ?>" required></div>
    <select name="gender" style="width:auto"><option value="M"><?= t('male') ?></option><option value="F"><?= t('female') ?></option></select>
    <button class="btn">+ <?= t('special_create_btn') ?></button>
  </form>
</div>

<div class="card table-wrap">
<table>
  <tr><th><?= t('gender') ?></th><th><?= t('category') ?></th>
      <th><?= t('competitors') ?></th><th><?= t('duration') ?></th><th><?= t('status') ?></th><th></th></tr>
  <?php foreach ($divs as $d):
      $n = count(division_registrations((int)$d['id'])); ?>
  <tr>
    <td><?= $d['gender'] === 'M' ? t('male') : t('female') ?></td>
    <td><?php if ($d['color_hex']): ?><span class="belt-chip" style="background:<?= e($d['color_hex']) ?>"></span><?php endif; ?><?= division_category_label($d, true) ?></td>
    <td data-label="<?= t('competitors') ?>"><b><?= $n ?></b></td>
    <td data-label="<?= t('duration') ?>"><?= fmt_time((int)$d['duration_sec']) ?></td>
    <td><span class="badge <?= ['pending'=>'grey','bracketed'=>'blue','done'=>'gold'][$d['status']] ?>"><?= $d['has_bracket'] ? ($d['status'] === 'done' ? t('done') : t('bracket')) : t('pending') ?></span></td>
    <td class="right">
      <a class="btn sm" href="<?= APP_URL ?>/division/<?= $d['id'] ?>"><?= icon('bracket', 13) ?> <?= t('bracket') ?></a>
      <a class="btn sm secondary" href="<?= APP_URL ?>/division/<?= $d['id'] ?>/view" target="_blank"><?= icon('screen', 13) ?></a>
      <form method="post" style="display:inline" data-confirm="<?= t('confirm_delete_division') ?>">
        <?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $d['id'] ?>">
        <button class="btn sm danger"><?= icon('x', 13) ?></button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
<?php view_footer();
