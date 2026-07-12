<?php
$t = require_tournament_owner((int)$params[0]);
$tid = (int)$t['id'];
$divOrder = $t['discipline'] === 'nogi'
    ? nogi_division_order_case_sql(nogi_division_order_for($t))
    : division_order_case_sql(division_order_for($t));
$ageOrder = age_order_case_sql(age_order_for($t));
$weightOrder = weight_order_case_sql(weight_order_for($t));
$matches = rows("SELECT m.*, r1.name red_name, r2.name blue_name, d.id did,
                        d.gender, d.belt_id, d.tier, d.kind, d.name dname, d.age_division_id, d.weight_class_id, b.color_hex
                 FROM matches m
                 JOIN divisions d ON d.id = m.division_id
                 LEFT JOIN belts b ON b.id = d.belt_id
                 LEFT JOIN age_divisions ad ON ad.id = d.age_division_id
                 LEFT JOIN weight_classes wc ON wc.id = d.weight_class_id
                 LEFT JOIN registrations r1 ON r1.id = m.red_reg_id
                 LEFT JOIN registrations r2 ON r2.id = m.blue_reg_id
                 WHERE m.tournament_id = ? AND m.red_reg_id IS NOT NULL AND m.blue_reg_id IS NOT NULL
                 ORDER BY (m.status = \"live\") DESC, (m.status = \"pending\") DESC, $divOrder, d.gender, $ageOrder, b.sort, $weightOrder, m.round, m.slot", [$tid]);
view_header(t('matches'));
?>
<h1><?= e($t['name']) ?></h1>
<?php tournament_tabs($t, 'matches'); ?>
<div class="card table-wrap">
<table>
  <tr><th><?= t('division') ?></th><th><?= t('match') ?></th><th><?= t('status') ?></th><th><?= t('result') ?></th><th></th></tr>
  <?php foreach ($matches as $m): ?>
  <tr>
    <td class="muted" style="font-size:.82rem">
      <?= division_label(['gender' => $m['gender'], 'belt_id' => $m['belt_id'], 'tier' => $m['tier'], 'kind' => $m['kind'], 'name' => $m['dname'], 'age_division_id' => $m['age_division_id'], 'weight_class_id' => $m['weight_class_id']], true) ?>
      <?php if ($m['color_hex']): ?><span class="belt-chip" style="background:<?= e($m['color_hex']) ?>"></span><?php endif; ?>
      <?= $m['is_bronze'] ? '<span class="badge grey">' . icon('award', 11, 'ic-bronze') . '</span>' : '' ?>
    </td>
    <td><b><?= e($m['red_name']) ?></b> <span class="muted"><?= t('vs') ?></span> <b><?= e($m['blue_name']) ?></b></td>
    <td>
      <?php if ($m['status'] === 'live'): ?><span class="badge red"><?= t('live') ?></span>
      <?php elseif ($m['status'] === 'done'): ?><span class="badge green"><?= t('done') ?></span>
      <?php else: ?><span class="badge grey"><?= t('pending') ?></span><?php endif; ?>
    </td>
    <td>
      <?php if ($m['status'] === 'done'): ?>
        <?= (int)$m['red_points'] ?>-<?= (int)$m['blue_points'] ?>
        <span class="muted">· <?= icon('trophy', 12, 'ic-gold') ?> <?= e($m['winner_reg_id'] == $m['red_reg_id'] ? $m['red_name'] : $m['blue_name']) ?></span>
      <?php endif; ?>
    </td>
    <td class="right" style="white-space:nowrap">
      <?php if ($m['status'] !== 'done'): ?>
      <a class="btn sm" href="<?= APP_URL ?>/match/<?= $m['id'] ?>/operator"><?= icon('timer', 14) ?> <?= t('operator') ?></a>
      <?php else: ?>
      <a class="btn sm secondary" href="<?= APP_URL ?>/match/<?= $m['id'] ?>/operator" title="<?= t('edit_result') ?>"><?= icon('edit', 14) ?> <?= t('edit_result') ?></a>
      <?php endif; ?>
      <a class="btn sm secondary" href="<?= APP_URL ?>/match/<?= $m['id'] ?>/display" target="_blank"><?= icon('screen', 14) ?></a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
<?php view_footer();
