<?php
$t = require_tournament_owner((int)$params[0]);
$tid = (int)$t['id'];
$matches = rows('SELECT m.*, r1.name red_name, r2.name blue_name, d.id did,
                        b.name_es b_es, b.name_en b_en, ad.name_es ad_es, ad.name_en ad_en,
                        wc.name_es w_es, wc.name_en w_en, d.gender
                 FROM matches m
                 JOIN divisions d ON d.id = m.division_id
                 JOIN belts b ON b.id = d.belt_id
                 JOIN age_divisions ad ON ad.id = d.age_division_id
                 JOIN weight_classes wc ON wc.id = d.weight_class_id
                 LEFT JOIN registrations r1 ON r1.id = m.red_reg_id
                 LEFT JOIN registrations r2 ON r2.id = m.blue_reg_id
                 WHERE m.tournament_id = ? AND m.red_reg_id IS NOT NULL AND m.blue_reg_id IS NOT NULL
                 ORDER BY (m.status = "live") DESC, (m.status = "pending") DESC, d.id, m.round, m.slot', [$tid]);
$isEn = lang() === 'en';
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
      <?= ($m['gender'] === 'M' ? t('male') : t('female')) . ' · ' . e($isEn ? $m['ad_en'] : $m['ad_es']) . ' · ' . e($isEn ? $m['b_en'] : $m['b_es']) . ' · ' . e($isEn ? $m['w_en'] : $m['w_es']) ?>
      <?= $m['is_bronze'] ? '<span class="badge grey">🥉</span>' : '' ?>
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
        <span class="muted">· 🏆 <?= e($m['winner_reg_id'] == $m['red_reg_id'] ? $m['red_name'] : $m['blue_name']) ?></span>
      <?php endif; ?>
    </td>
    <td class="right" style="white-space:nowrap">
      <?php if ($m['status'] !== 'done'): ?>
      <a class="btn sm" href="<?= APP_URL ?>/match/<?= $m['id'] ?>/operator">⏱ <?= t('operator') ?></a>
      <?php endif; ?>
      <a class="btn sm secondary" href="<?= APP_URL ?>/match/<?= $m['id'] ?>/display" target="_blank">📺</a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
<?php view_footer();
