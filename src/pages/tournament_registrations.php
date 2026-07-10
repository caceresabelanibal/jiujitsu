<?php
$t = require_tournament_owner((int)$params[0]);
$tid = (int)$t['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'verify') {
        q('UPDATE registrations SET verified = 1, verify_token = NULL WHERE id=? AND tournament_id=?', [(int)$_POST['id'], $tid]);
    } elseif ($do === 'delete') {
        q('DELETE FROM registrations WHERE id=? AND tournament_id=?', [(int)$_POST['id'], $tid]);
    }
    redirect("/tournament/$tid/registrations");
}

$divOrder = division_order_case_sql(division_order_for($t));
$regs = rows("SELECT r.*, b.name_es b_es, b.name_en b_en, b.color_hex,
                     ad.name_es a_es, ad.name_en a_en, wc.name_es w_es, wc.name_en w_en,
                     a.name academy_name, p.name professor_name
              FROM registrations r
              JOIN belts b ON b.id=r.belt_id
              JOIN age_divisions ad ON ad.id=r.age_division_id
              JOIN weight_classes wc ON wc.id=r.weight_class_id
              LEFT JOIN tournament_academies a ON a.id=r.academy_id
              LEFT JOIN tournament_professors p ON p.id=r.professor_id
              WHERE r.tournament_id=? ORDER BY $divOrder, r.gender, ad.sort, b.sort, wc.sort, r.name", [$tid]);
$isEn = lang() === 'en';
view_header(t('registrations'));
?>
<h1><?= e($t['name']) ?></h1>
<?php tournament_tabs($t, 'registrations'); ?>
<div class="card table-wrap">
<table>
  <tr><th><?= t('name') ?></th><th><?= t('email') ?></th><th><?= t('gender') ?></th><th><?= t('belt') ?></th>
      <th><?= t('age_division') ?></th><th><?= t('weight_class') ?></th><th><?= t('academy') ?></th>
      <th><?= t('status') ?></th><th></th></tr>
  <?php foreach ($regs as $r): ?>
  <tr>
    <td><b><?= e($r['name']) ?></b></td>
    <td class="muted"><?= e($r['email']) ?></td>
    <td><?= $r['gender'] === 'M' ? t('male') : t('female') ?></td>
    <td><span class="belt-chip" style="background:<?= e($r['color_hex']) ?>"></span><?= e($isEn ? $r['b_en'] : $r['b_es']) ?></td>
    <td><?= e($isEn ? $r['a_en'] : $r['a_es']) ?></td>
    <td><?= e($isEn ? $r['w_en'] : $r['w_es']) ?> <span class="muted">(<?= e($r['weight_kg']) ?> kg)</span></td>
    <td><?= e($r['academy_name'] ?? '—') ?><?= $r['professor_name'] ? '<br><small class="muted">' . e($r['professor_name']) . '</small>' : '' ?></td>
    <td><span class="badge <?= $r['verified'] ? 'green' : 'grey' ?>"><?= $r['verified'] ? t('verified') : t('pending') ?></span></td>
    <td class="right" style="white-space:nowrap">
      <?php if (!$r['verified']): ?>
      <form class="inline-form" method="post"><?= csrf_field() ?><input type="hidden" name="do" value="verify"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm green"><?= icon('check', 13) ?></button></form>
      <?php endif; ?>
      <form class="inline-form" method="post" data-confirm="<?= t('confirm_delete') ?>"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger"><?= icon('x', 13) ?></button></form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
<?php view_footer();
