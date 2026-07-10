<?php
$t = require_tournament_owner((int)$params[0]);
$tid = (int)$t['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['do'] ?? '') === 'generate') {
        $n = ensure_divisions($tid);
        flash('success', "+$n " . t('divisions'));
    }
    redirect("/tournament/$tid/divisions");
}

$divOrder = division_order_case_sql(division_order_for($t));
$divs = rows("SELECT d.*, b.name_es b_es, b.name_en b_en, b.color_hex,
                     ad.name_es a_es, ad.name_en a_en, wc.name_es w_es, wc.name_en w_en,
                     (SELECT COUNT(*) FROM matches m WHERE m.division_id=d.id) has_bracket
              FROM divisions d
              JOIN belts b ON b.id=d.belt_id
              JOIN age_divisions ad ON ad.id=d.age_division_id
              JOIN weight_classes wc ON wc.id=d.weight_class_id
              WHERE d.tournament_id=? ORDER BY $divOrder, d.gender, ad.sort, b.sort, wc.sort", [$tid]);
$isEn = lang() === 'en';
view_header(t('divisions'));
?>
<h1><?= e($t['name']) ?></h1>
<?php tournament_tabs($t, 'divisions'); ?>
<form method="post" class="mb"><?= csrf_field() ?><input type="hidden" name="do" value="generate">
  <button class="btn"><?= t('generate_divisions') ?></button>
  <span class="muted">← <?= t('registrations') ?> → <?= t('divisions') ?></span>
</form>
<div class="card table-wrap">
<table>
  <tr><th><?= t('gender') ?></th><th><?= t('age_division') ?></th><th><?= t('belt') ?></th><th><?= t('weight_class') ?></th>
      <th><?= t('competitors') ?></th><th><?= t('duration') ?></th><th><?= t('status') ?></th><th></th></tr>
  <?php foreach ($divs as $d):
      $n = count(division_registrations((int)$d['id'])); ?>
  <tr>
    <td><?= $d['gender'] === 'M' ? t('male') : t('female') ?></td>
    <td><?= e($isEn ? $d['a_en'] : $d['a_es']) ?></td>
    <td><span class="belt-chip" style="background:<?= e($d['color_hex']) ?>"></span><?= e($isEn ? $d['b_en'] : $d['b_es']) ?></td>
    <td><?= e($isEn ? $d['w_en'] : $d['w_es']) ?></td>
    <td data-label="<?= t('competitors') ?>"><b><?= $n ?></b></td>
    <td data-label="<?= t('duration') ?>"><?= fmt_time((int)$d['duration_sec']) ?></td>
    <td><span class="badge <?= ['pending'=>'grey','bracketed'=>'blue','done'=>'gold'][$d['status']] ?>"><?= $d['has_bracket'] ? ($d['status'] === 'done' ? t('done') : t('bracket')) : t('pending') ?></span></td>
    <td class="right">
      <a class="btn sm" href="<?= APP_URL ?>/division/<?= $d['id'] ?>"><?= icon('bracket', 13) ?> <?= t('bracket') ?></a>
      <a class="btn sm secondary" href="<?= APP_URL ?>/division/<?= $d['id'] ?>/view" target="_blank"><?= icon('screen', 13) ?></a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
<?php view_footer();
