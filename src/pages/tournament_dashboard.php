<?php
$t = require_tournament_owner((int)$params[0]);
$tid = (int)$t['id'];
$s = tournament_stats($tid);
view_header(t('dashboard'));

function stat_tile(string $label, ?array $data, string $valueKey, string $suffix = '', string $iconName = 'award'): void {
    echo '<div class="stat"><div class="k">' . icon($iconName, 14) . ' ' . e($label) . '</div>';
    if ($data && isset($data['name'])) {
        $v = $data[$valueKey] ?? '';
        echo '<div class="v" style="font-size:1.15rem">' . e($data['name']) . '</div><div class="sub">' . e((string)$v) . $suffix . '</div>';
    } else {
        echo '<div class="v muted">—</div>';
    }
    echo '</div>';
}
?>
<h1><?= e($t['name']) ?></h1>
<?php tournament_tabs($t, 'dashboard'); ?>

<div class="grid cols4 mb">
  <div class="stat"><div class="k"><?= icon('users', 14) ?> <?= t('participants') ?></div><div class="v"><?= (int)$s['participants'] ?></div></div>
  <div class="stat"><div class="k"><?= icon('swords', 14) ?> <?= t('total_fights') ?></div><div class="v"><?= (int)($s['totals']['fights'] ?? 0) ?></div></div>
  <div class="stat"><div class="k"><?= icon('clock', 14) ?> <?= t('total_mat_time') ?></div><div class="v"><?= fmt_time((int)($s['totals']['mat_seconds'] ?? 0)) ?></div></div>
  <div class="stat"><div class="k"><?= icon('bracket', 14) ?> <?= t('divisions_progress') ?></div><div class="v"><?= (int)$s['divisions_done'] ?>/<?= (int)$s['divisions_total'] ?></div></div>
</div>

<div class="grid cols4 mb">
  <div class="stat"><div class="k"><?= icon('zap', 14) ?> <?= t('by_submission') ?></div><div class="v"><?= (int)($s['totals']['submissions'] ?? 0) ?></div></div>
  <div class="stat"><div class="k"><?= icon('target', 14) ?> <?= t('by_points') ?></div><div class="v"><?= (int)($s['totals']['by_points'] ?? 0) ?></div></div>
  <div class="stat"><div class="k"><?= icon('user-check', 14) ?> <?= t('by_decision') ?></div><div class="v"><?= (int)($s['totals']['by_decision'] ?? 0) ?></div></div>
  <div class="stat"><div class="k"><?= icon('plus-circle', 14) ?> <?= t('by_advantages') ?></div><div class="v"><?= (int)($s['totals']['by_advantages'] ?? 0) ?></div></div>
</div>

<?php if ($s['winning_academy']): ?>
<div class="card center" style="border-color:var(--gold)">
  <h2><?= icon('trophy', 22, 'ic-gold') ?> <?= t('stats_winning_academy') ?></h2>
  <h1 style="color:var(--gold)"><?= e($s['winning_academy']['name']) ?></h1>
  <p><?= icon('award', 15, 'ic-gold') ?> <?= (int)$s['winning_academy']['gold'] ?> · <?= icon('award', 15, 'ic-silver') ?> <?= (int)$s['winning_academy']['silver'] ?> · <?= icon('award', 15, 'ic-bronze') ?> <?= (int)$s['winning_academy']['bronze'] ?></p>
</div>
<?php endif; ?>

<div class="grid cols3 mb">
  <?php
  stat_tile(t('stats_most_fights'), $s['most_fights'], 'c', ' ' . strtolower(t('matches')), 'swords');
  stat_tile(t('stats_mat_time'), $s['most_mat_time'] ? ['name' => $s['most_mat_time']['name'], 'v' => fmt_time((int)$s['most_mat_time']['sec'])] : null, 'v', '', 'clock');
  stat_tile(t('stats_most_subs'), $s['most_submissions'], 'c', '', 'zap');
  stat_tile(t('stats_wins_by_points'), $s['most_wins_points'], 'c', '', 'target');
  stat_tile(t('stats_most_losses'), $s['most_losses'], 'c', '', 'down-trend');
  stat_tile(t('stats_fastest_sub'), $s['fastest_submission'] ? ['name' => $s['fastest_submission']['name'], 'v' => fmt_time((int)$s['fastest_submission']['sec'])] : null, 'v', '', 'zap');
  stat_tile(t('stats_most_points'), $s['most_points_scored'], 'pts', ' pts', 'target');
  stat_tile(t('stats_most_adv'), $s['most_advantages'], 'adv', ' adv', 'plus-circle');
  ?>
</div>

<div class="card">
  <h3><?= t('medal_table') ?></h3>
  <div class="table-wrap"><table>
    <tr><th>#</th><th><?= t('academy') ?></th><th><?= icon('award', 13, 'ic-gold') ?> <?= t('gold') ?></th><th><?= icon('award', 13, 'ic-silver') ?> <?= t('silver') ?></th><th><?= icon('award', 13, 'ic-bronze') ?> <?= t('bronze') ?></th></tr>
    <?php foreach ($s['medals_by_academy'] as $i => $a): ?>
    <tr><td><?= $i + 1 ?></td><td><b><?= e($a['name']) ?></b></td>
      <td data-label="<?= t('gold') ?>"><?= (int)$a['gold'] ?></td>
      <td data-label="<?= t('silver') ?>"><?= (int)$a['silver'] ?></td>
      <td data-label="<?= t('bronze') ?>"><?= (int)$a['bronze'] ?></td></tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php view_footer();
