<?php
$t = require_tournament_owner((int)$params[0]);
$tid = (int)$t['id'];
$s = tournament_stats($tid);
view_header(t('dashboard'));

function stat_tile(string $label, ?array $data, string $valueKey, string $suffix = '', string $icon = '🏅'): void {
    echo '<div class="stat"><div class="k">' . $icon . ' ' . e($label) . '</div>';
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
  <div class="stat"><div class="k">👥 <?= t('participants') ?></div><div class="v"><?= (int)$s['participants'] ?></div></div>
  <div class="stat"><div class="k">⚔️ <?= t('total_fights') ?></div><div class="v"><?= (int)($s['totals']['fights'] ?? 0) ?></div></div>
  <div class="stat"><div class="k">⏱ <?= t('total_mat_time') ?></div><div class="v"><?= fmt_time((int)($s['totals']['mat_seconds'] ?? 0)) ?></div></div>
  <div class="stat"><div class="k">📋 <?= t('divisions_progress') ?></div><div class="v"><?= (int)$s['divisions_done'] ?>/<?= (int)$s['divisions_total'] ?></div></div>
</div>

<div class="grid cols4 mb">
  <div class="stat"><div class="k">🥋 <?= t('by_submission') ?></div><div class="v"><?= (int)($s['totals']['submissions'] ?? 0) ?></div></div>
  <div class="stat"><div class="k">🔢 <?= t('by_points') ?></div><div class="v"><?= (int)($s['totals']['by_points'] ?? 0) ?></div></div>
  <div class="stat"><div class="k">⚖️ <?= t('by_decision') ?></div><div class="v"><?= (int)($s['totals']['by_decision'] ?? 0) ?></div></div>
  <div class="stat"><div class="k">➕ <?= t('by_advantages') ?></div><div class="v"><?= (int)($s['totals']['by_advantages'] ?? 0) ?></div></div>
</div>

<?php if ($s['winning_academy']): ?>
<div class="card center" style="border-color:var(--gold)">
  <h2>🏆 <?= t('stats_winning_academy') ?></h2>
  <h1 style="color:var(--gold)"><?= e($s['winning_academy']['name']) ?></h1>
  <p>🥇 <?= (int)$s['winning_academy']['gold'] ?> · 🥈 <?= (int)$s['winning_academy']['silver'] ?> · 🥉 <?= (int)$s['winning_academy']['bronze'] ?></p>
</div>
<?php endif; ?>

<div class="grid cols3 mb">
  <?php
  stat_tile(t('stats_most_fights'), $s['most_fights'], 'c', ' ' . strtolower(t('matches')), '⚔️');
  stat_tile(t('stats_mat_time'), $s['most_mat_time'] ? ['name' => $s['most_mat_time']['name'], 'v' => fmt_time((int)$s['most_mat_time']['sec'])] : null, 'v', '', '⏱');
  stat_tile(t('stats_most_subs'), $s['most_submissions'], 'c', '', '🥋');
  stat_tile(t('stats_wins_by_points'), $s['most_wins_points'], 'c', '', '🔢');
  stat_tile(t('stats_most_losses'), $s['most_losses'], 'c', '', '📉');
  stat_tile(t('stats_fastest_sub'), $s['fastest_submission'] ? ['name' => $s['fastest_submission']['name'], 'v' => fmt_time((int)$s['fastest_submission']['sec'])] : null, 'v', '', '⚡');
  stat_tile(t('stats_most_points'), $s['most_points_scored'], 'pts', ' pts', '🎯');
  stat_tile(t('stats_most_adv'), $s['most_advantages'], 'adv', ' adv', '➕');
  ?>
</div>

<div class="card">
  <h3><?= t('medal_table') ?></h3>
  <div class="table-wrap"><table>
    <tr><th>#</th><th><?= t('academy') ?></th><th>🥇 <?= t('gold') ?></th><th>🥈 <?= t('silver') ?></th><th>🥉 <?= t('bronze') ?></th></tr>
    <?php foreach ($s['medals_by_academy'] as $i => $a): ?>
    <tr><td><?= $i + 1 ?></td><td><b><?= e($a['name']) ?></b></td><td><?= (int)$a['gold'] ?></td><td><?= (int)$a['silver'] ?></td><td><?= (int)$a['bronze'] ?></td></tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php view_footer();
