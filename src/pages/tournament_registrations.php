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

$divOrder = $t['discipline'] === 'nogi'
    ? nogi_registrant_order_case_sql(nogi_division_order_for($t), nogi_tiers_for($t))
    : division_order_case_sql(division_order_for($t));
$ageOrder = age_order_case_sql(age_order_for($t));
$weightOrder = weight_order_case_sql(weight_order_for($t));
$regs = rows("SELECT r.*, b.name_es b_es, b.name_en b_en, b.name_pt b_pt, b.color_hex, b.code belt_code,
                     ad.name_es a_es, ad.name_en a_en, ad.name_pt a_pt, ad.is_kids age_is_kids, ad.code age_code,
                     wc.name_es w_es, wc.name_en w_en, wc.name_pt w_pt,
                     a.name academy_name, p.name professor_name
              FROM registrations r
              JOIN belts b ON b.id=r.belt_id
              JOIN age_divisions ad ON ad.id=r.age_division_id
              JOIN weight_classes wc ON wc.id=r.weight_class_id
              LEFT JOIN tournament_academies a ON a.id=r.academy_id
              LEFT JOIN tournament_professors p ON p.id=r.professor_id
              WHERE r.tournament_id=? ORDER BY $divOrder, r.gender, $ageOrder, b.sort, $weightOrder, r.name", [$tid]);
$nogiTierMap = $t['discipline'] === 'nogi' ? nogi_tiers_for($t) : [];
view_header(t('registrations'));
?>
<h1><?= e($t['name']) ?></h1>
<?php tournament_tabs($t, 'registrations'); ?>
<div class="card table-wrap">
<table>
  <tr><th><?= t('name') ?></th><th><?= t('email') ?></th><th><?= t('gender') ?></th><th><?= $t['discipline'] === 'nogi' ? t('category') : t('belt') ?></th>
      <th><?= t('age_division') ?></th><th><?= t('weight_class') ?></th><th><?= t('academy') ?></th>
      <th><?= t('status') ?></th><th></th></tr>
  <?php foreach ($regs as $r): ?>
  <tr>
    <td><?php if ($r['photo']): ?><img src="<?= APP_URL . '/' . e($r['photo']) ?>" alt="" class="reg-photo-sm"> <?php endif; ?><b><?= e($r['name']) ?></b></td>
    <td class="muted"><?= e($r['email']) ?></td>
    <td><?= $r['gender'] === 'M' ? t('male') : t('female') ?></td>
    <td>
      <?php if ($t['discipline'] === 'nogi'):
          if ($r['age_is_kids'] || $r['age_code'] === 'juvenil') {
              echo nogi_category_badge('kids_juvenile', t('div_order_kids_juvenile'));
          } else {
              $tier = $nogiTierMap[$r['belt_code']] ?? 'amateur';
              echo nogi_category_badge($tier, nogi_tier_labels()[$tier]);
          }
      else: ?>
      <span class="belt-chip" style="background:<?= e($r['color_hex']) ?>"></span><?= e(loc_col($r, 'b')) ?>
      <?php endif; ?>
    </td>
    <td><?= e(loc_col($r, 'a')) ?></td>
    <td><?php if ($r['competes_in'] === 'absolute'): ?><span class="badge gold"><?= t('compete_absolute') ?></span><?php else: ?><?= e(loc_col($r, 'w')) ?> <span class="muted">(<?= e($r['weight_kg']) ?> kg)</span><?php if ($r['competes_in'] === 'both'): ?> <span class="badge gold"><?= t('compete_absolute') ?></span><?php endif; endif; ?></td>
    <td><?= e($r['academy_name'] ?? '—') ?><?= $r['professor_name'] ? '<br><small class="muted">' . e($r['professor_name']) . '</small>' : '' ?></td>
    <td><span class="badge <?= $r['verified'] ? 'green' : 'grey' ?>"><?= $r['verified'] ? t('verified') : t('pending') ?></span></td>
    <td class="right" style="white-space:nowrap">
      <a class="btn sm secondary" href="<?= APP_URL ?>/registration/<?= $r['id'] ?>/edit" title="<?= t('edit') ?>"><?= icon('edit', 13) ?></a>
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
