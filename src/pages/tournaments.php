<?php
$u = require_login();
// Propios + torneos donde el usuario es personal (arbitro/mesa)
$mine = is_admin()
    ? rows('SELECT t.*, u.name owner, (SELECT COUNT(*) FROM registrations r WHERE r.tournament_id=t.id AND r.verified=1) regs FROM tournaments t JOIN users u ON u.id=t.user_id ORDER BY t.created_at DESC')
    : rows('SELECT DISTINCT t.*, (SELECT COUNT(*) FROM registrations r WHERE r.tournament_id=t.id AND r.verified=1) regs
            FROM tournaments t LEFT JOIN tournament_staff s ON s.tournament_id = t.id
            WHERE t.user_id = ? OR s.user_id = ? ORDER BY t.created_at DESC', [$u['id'], $u['id']]);
view_header(t('my_tournaments'));
?>
<div class="flex spread mb">
  <h1><?= t('my_tournaments') ?></h1>
  <a class="btn" href="<?= APP_URL ?>/tournaments/create">+ <?= t('create_tournament') ?></a>
</div>
<?php if (!$mine): ?>
  <div class="card center muted"><?= t('no_tournaments') ?></div>
<?php else: ?>
<div class="card table-wrap">
<table>
  <tr><th><?= t('tournament') ?></th><th><?= t('date') ?></th><th><?= t('tournament_type') ?></th><th><?= t('participants') ?></th><th><?= t('status') ?></th><?= is_admin() ? '<th>Owner</th>' : '' ?><th></th></tr>
  <?php foreach ($mine as $tt): ?>
  <tr>
    <td><b><?= e($tt['name']) ?></b></td>
    <td><?= $tt['event_date'] ? date('d/m/Y', strtotime($tt['event_date'])) : '—' ?></td>
    <td><?= $tt['type'] === 'open' ? 'Open' : t('type_internal') ?></td>
    <td><?= (int)$tt['regs'] ?></td>
    <td><span class="badge <?= ['draft'=>'grey','open'=>'green','running'=>'blue','finished'=>'gold'][$tt['status']] ?>"><?= t('status_' . $tt['status']) ?></span></td>
    <?= is_admin() ? '<td>' . e($tt['owner'] ?? '') . '</td>' : '' ?>
    <td style="white-space:nowrap">
      <a class="btn sm" href="<?= APP_URL ?>/tournament/<?= $tt['id'] ?>">▶ <?= t('go_to_tournament') ?></a>
      <a class="btn sm secondary" href="<?= APP_URL ?>/tournament/<?= $tt['id'] ?>/settings" title="<?= t('settings') ?>">⚙️</a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
<?php endif;
view_footer();
