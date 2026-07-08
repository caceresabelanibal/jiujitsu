<?php
// Ranking publico con filtros por genero, cinturon, edad y peso
$belts = rows('SELECT * FROM belts ORDER BY sort');
$ages = rows('SELECT * FROM age_divisions ORDER BY sort');
$fg = $_GET['gender'] ?? '';
$fb = (int)($_GET['belt'] ?? 0);
$fa = (int)($_GET['age'] ?? 0);
$fw = (int)($_GET['weight'] ?? 0);

$where = '1=1';
$args = [];
if (in_array($fg, ['M', 'F'])) { $where .= ' AND rp.gender = ?'; $args[] = $fg; }
if ($fb) { $where .= ' AND rp.belt_id = ?'; $args[] = $fb; }
if ($fa) { $where .= ' AND rp.age_division_id = ?'; $args[] = $fa; }
if ($fw) { $where .= ' AND rp.weight_class_id = ?'; $args[] = $fw; }

$ranks = rows("SELECT rp.*, b.name_es b_es, b.name_en b_en, b.color_hex,
                      ad.name_es a_es, ad.name_en a_en, wc.name_es w_es, wc.name_en w_en
               FROM ranking_points rp
               JOIN belts b ON b.id = rp.belt_id
               JOIN age_divisions ad ON ad.id = rp.age_division_id
               JOIN weight_classes wc ON wc.id = rp.weight_class_id
               WHERE $where
               ORDER BY rp.points DESC, rp.golds DESC, rp.wins DESC LIMIT 100", $args);

$weights = rows('SELECT * FROM weight_classes ' . (in_array($fg, ['M','F']) ? "WHERE gender IN ('A', ?)" : '') . ' ORDER BY gender, sort',
    in_array($fg, ['M','F']) ? [$fg] : []);
$isEn = lang() === 'en';
view_header(t('rankings_title'));
?>
<h1>📊 <?= t('rankings_title') ?></h1>
<div class="card">
  <form method="get" class="flex">
    <div><label><?= t('gender') ?></label>
      <select name="gender"><option value=""><?= t('all') ?></option>
        <option value="M" <?= $fg === 'M' ? 'selected' : '' ?>><?= t('male') ?></option>
        <option value="F" <?= $fg === 'F' ? 'selected' : '' ?>><?= t('female') ?></option></select></div>
    <div><label><?= t('belt') ?></label>
      <select name="belt"><option value="0"><?= t('all') ?></option>
        <?php foreach ($belts as $b): ?><option value="<?= $b['id'] ?>" <?= $fb === (int)$b['id'] ? 'selected' : '' ?>><?= e(loc_name($b)) ?></option><?php endforeach; ?></select></div>
    <div><label><?= t('age_division') ?></label>
      <select name="age"><option value="0"><?= t('all') ?></option>
        <?php foreach ($ages as $a): ?><option value="<?= $a['id'] ?>" <?= $fa === (int)$a['id'] ? 'selected' : '' ?>><?= e(loc_name($a)) ?></option><?php endforeach; ?></select></div>
    <div><label><?= t('weight_class') ?></label>
      <select name="weight"><option value="0"><?= t('all') ?></option>
        <?php foreach ($weights as $w): ?><option value="<?= $w['id'] ?>" <?= $fw === (int)$w['id'] ? 'selected' : '' ?>><?= e(loc_name($w)) ?> (<?= $w['gender'] ?>)</option><?php endforeach; ?></select></div>
    <button class="btn" style="margin-top:26px"><?= t('filter') ?></button>
  </form>
</div>

<?php if (!$ranks): ?>
<div class="card center muted"><?= t('no_ranking_data') ?></div>
<?php else: ?>
<div class="card table-wrap">
<table>
  <tr><th><?= t('position') ?></th><th><?= t('competitor') ?></th><th><?= t('category') ?></th>
      <th><?= t('points_col') ?></th><th>🥇</th><th>🥈</th><th>🥉</th><th><?= t('wins') ?></th><th><?= t('subs') ?></th></tr>
  <?php foreach ($ranks as $i => $r): ?>
  <tr>
    <td><b><?= $i + 1 ?></b><?= $i === 0 ? ' 👑' : '' ?></td>
    <td><b><?= e($r['name']) ?></b></td>
    <td class="muted" style="font-size:.82rem">
      <span class="belt-chip" style="background:<?= e($r['color_hex']) ?>"></span>
      <?= ($r['gender'] === 'M' ? t('male') : t('female')) . ' · ' . e($isEn ? $r['a_en'] : $r['a_es']) . ' · ' . e($isEn ? $r['b_en'] : $r['b_es']) . ' · ' . e($isEn ? $r['w_en'] : $r['w_es']) ?>
    </td>
    <td><b style="color:var(--accent2)"><?= (int)$r['points'] ?></b></td>
    <td><?= (int)$r['golds'] ?></td><td><?= (int)$r['silvers'] ?></td><td><?= (int)$r['bronzes'] ?></td>
    <td><?= (int)$r['wins'] ?></td><td><?= (int)$r['submissions'] ?></td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
<?php endif;
view_footer();
