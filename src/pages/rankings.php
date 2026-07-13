<?php
// Ranking publico con filtros por genero, cinturon, edad y peso — gi y nogi
// son dos rankings separados (cada torneo aporta solo al de su disciplina)
$belts = rows('SELECT * FROM belts ORDER BY sort');
$ages = rows('SELECT * FROM age_divisions ORDER BY sort');
$fd = ($_GET['discipline'] ?? 'gi') === 'nogi' ? 'nogi' : 'gi';
$fg = $_GET['gender'] ?? '';
$fb = (int)($_GET['belt'] ?? 0);
// nogi no rankea por cinturon sino por categoria (infantiles/juveniles + los 3 niveles)
$tierKeys = array_keys(nogi_category_colors()); // kids_juvenile, amateur, semipro, pro
$ft = in_array($_GET['tier'] ?? '', $tierKeys, true) ? $_GET['tier'] : '';
$fa = (int)($_GET['age'] ?? 0);
$fw = (int)($_GET['weight'] ?? 0);

$where = 'rp.discipline = ?';
$args = [$fd];
if (in_array($fg, ['M', 'F'])) { $where .= ' AND rp.gender = ?'; $args[] = $fg; }
if ($fd === 'nogi') {
    if ($ft) { $where .= ' AND rp.tier = ?'; $args[] = $ft; }
} elseif ($fb) { $where .= ' AND rp.belt_id = ?'; $args[] = $fb; }
if ($fa) { $where .= ' AND rp.age_division_id = ?'; $args[] = $fa; }
if ($fw) { $where .= ' AND rp.weight_class_id = ?'; $args[] = $fw; }

$ranks = rows("SELECT rp.*, b.name_es b_es, b.name_en b_en, b.name_pt b_pt, b.color_hex,
                      ad.name_es a_es, ad.name_en a_en, ad.name_pt a_pt,
                      wc.name_es w_es, wc.name_en w_en, wc.name_pt w_pt
               FROM ranking_points rp
               JOIN belts b ON b.id = rp.belt_id
               JOIN age_divisions ad ON ad.id = rp.age_division_id
               JOIN weight_classes wc ON wc.id = rp.weight_class_id
               WHERE $where
               ORDER BY rp.points DESC, rp.golds DESC, rp.wins DESC LIMIT 100", $args);

$weights = rows('SELECT * FROM weight_classes ' . (in_array($fg, ['M','F']) ? "WHERE gender IN ('A', ?)" : '') . ' ORDER BY gender, sort',
    in_array($fg, ['M','F']) ? [$fg] : []);
view_header(t('rankings_title'));
?>
<div class="flex spread"><h1><?= icon('chart', 26) ?> <?= t('rankings_title') ?></h1><?= help_link('rankings') ?></div>
<?php // al cambiar de pestaña se descarta el filtro que no aplica en la otra (cinturon en nogi, categoria en gi)
$tabQuery = array_diff_key($_GET, ['belt' => 1, 'tier' => 1]); ?>
<div class="tabs mb">
  <a class="tab <?= $fd === 'gi' ? 'active' : '' ?>" href="?<?= http_build_query(array_merge($tabQuery, ['discipline' => 'gi'])) ?>"><?= t('discipline_gi') ?></a>
  <a class="tab <?= $fd === 'nogi' ? 'active' : '' ?>" href="?<?= http_build_query(array_merge($tabQuery, ['discipline' => 'nogi'])) ?>"><?= t('discipline_nogi') ?></a>
</div>
<div class="card">
  <form method="get" class="flex">
    <input type="hidden" name="discipline" value="<?= e($fd) ?>">
    <div><label><?= t('gender') ?></label>
      <select name="gender"><option value=""><?= t('all') ?></option>
        <option value="M" <?= $fg === 'M' ? 'selected' : '' ?>><?= t('male') ?></option>
        <option value="F" <?= $fg === 'F' ? 'selected' : '' ?>><?= t('female') ?></option></select></div>
    <?php if ($fd === 'nogi'): ?>
    <div><label><?= t('category') ?></label>
      <select name="tier"><option value=""><?= t('all') ?></option>
        <option value="kids_juvenile" <?= $ft === 'kids_juvenile' ? 'selected' : '' ?>><?= t('div_order_kids_juvenile') ?></option>
        <?php foreach (nogi_tier_labels() as $tk => $tl): ?><option value="<?= $tk ?>" <?= $ft === $tk ? 'selected' : '' ?>><?= e($tl) ?></option><?php endforeach; ?></select></div>
    <?php else: ?>
    <div><label><?= t('belt') ?></label>
      <select name="belt"><option value="0"><?= t('all') ?></option>
        <?php foreach ($belts as $b): ?><option value="<?= $b['id'] ?>" <?= $fb === (int)$b['id'] ? 'selected' : '' ?>><?= e(loc_name($b)) ?></option><?php endforeach; ?></select></div>
    <?php endif; ?>
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
      <th><?= t('points_col') ?></th><th><?= icon('award', 14, 'ic-gold') ?></th><th><?= icon('award', 14, 'ic-silver') ?></th><th><?= icon('award', 14, 'ic-bronze') ?></th><th><?= t('wins') ?></th><th><?= t('subs') ?></th></tr>
  <?php foreach ($ranks as $i => $r): ?>
  <tr>
    <td><b><?= $i + 1 ?></b><?= $i === 0 ? ' ' . icon('crown', 14, 'ic-gold') : '' ?></td>
    <td><?php if ($r['photo']): ?><img src="<?= APP_URL . '/' . e($r['photo']) ?>" alt="" class="reg-photo-sm"> <?php endif; ?><b><?= e($r['name']) ?></b></td>
    <td class="muted" style="font-size:.82rem">
      <?php if ($fd === 'nogi'):
          $tierLabel = $r['tier'] === 'kids_juvenile' ? t('div_order_kids_juvenile') : (nogi_tier_labels()[$r['tier']] ?? $r['tier']); ?>
      <?= e($r['gender'] === 'M' ? t('male') : t('female')) ?> · <?= nogi_category_badge($r['tier'], $tierLabel) ?> · <?= e(loc_col($r, 'a')) . ' · ' . e(loc_col($r, 'w')) ?>
      <?php else: ?>
      <span class="belt-chip" style="background:<?= e($r['color_hex']) ?>"></span>
      <?= ($r['gender'] === 'M' ? t('male') : t('female')) . ' · ' . e(loc_col($r, 'a')) . ' · ' . e(loc_col($r, 'b')) . ' · ' . e(loc_col($r, 'w')) ?>
      <?php endif; ?>
    </td>
    <td data-label="<?= t('points_col') ?>"><b style="color:var(--accent2)"><?= (int)$r['points'] ?></b></td>
    <td data-label="<?= t('golds') ?>"><?= (int)$r['golds'] ?></td>
    <td data-label="<?= t('silvers') ?>"><?= (int)$r['silvers'] ?></td>
    <td data-label="<?= t('bronzes') ?>"><?= (int)$r['bronzes'] ?></td>
    <td data-label="<?= t('wins') ?>"><?= (int)$r['wins'] ?></td>
    <td data-label="<?= t('subs') ?>"><?= (int)$r['submissions'] ?></td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
<?php endif;
view_footer();
