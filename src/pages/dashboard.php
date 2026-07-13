<?php
// Panel unificado: torneos que organizo/administro (gestion completa) + mis inscripciones como competidor
$u = require_login();

// Propios (o todos si admin) + torneos donde soy personal (arbitro/mesa)
$mine = is_admin()
    ? rows('SELECT t.*, u.name owner, (SELECT COUNT(*) FROM registrations r WHERE r.tournament_id=t.id AND r.verified=1) regs FROM tournaments t JOIN users u ON u.id=t.user_id ORDER BY t.created_at DESC')
    : rows('SELECT DISTINCT t.*, (SELECT COUNT(*) FROM registrations r WHERE r.tournament_id=t.id AND r.verified=1) regs
            FROM tournaments t LEFT JOIN tournament_staff s ON s.tournament_id = t.id
            WHERE t.user_id = ? OR s.user_id = ? ORDER BY t.created_at DESC', [$u['id'], $u['id']]);

$regs = rows('SELECT r.*, t.name t_name, t.slug, t.status t_status, t.event_date,
                     b.name_es b_es, b.name_en b_en, b.name_pt b_pt,
                     ad.name_es a_es, ad.name_en a_en, ad.name_pt a_pt,
                     wc.name_es w_es, wc.name_en w_en, wc.name_pt w_pt
              FROM registrations r
              JOIN tournaments t ON t.id = r.tournament_id
              JOIN belts b ON b.id = r.belt_id
              JOIN age_divisions ad ON ad.id = r.age_division_id
              JOIN weight_classes wc ON wc.id = r.weight_class_id
              WHERE r.user_id = ? OR r.email = ? ORDER BY r.created_at DESC', [$u['id'], $u['email']]);

view_header(t('my_panel'));
?>
<div class="flex spread"><h1><?= t('my_panel') ?> · <?= e($u['name']) ?></h1><?= help_link('mi-panel') ?></div>

<div class="flex spread mb">
  <h2 style="margin:0"><?= icon('trophy', 18) ?> <?= t('my_tournaments') ?></h2>
  <a class="btn" href="<?= APP_URL ?>/tournaments/create">+ <?= t('create_tournament') ?></a>
</div>
<?php if (!$mine): ?>
  <div class="card center muted mb"><?= t('no_tournaments') ?></div>
<?php else: ?>
<div class="card table-wrap mb">
<table>
  <tr><th><?= t('tournament') ?></th><th><?= t('date') ?></th><th><?= t('tournament_type') ?></th><th><?= t('participants') ?></th><th><?= t('status') ?></th><?= is_admin() ? '<th>Owner</th>' : '' ?><th></th></tr>
  <?php foreach ($mine as $tt): ?>
  <tr>
    <td><?php if ($tt['logo']): ?><img class="logo-sm" src="<?= APP_URL . '/' . e($tt['logo']) ?>" alt=""> <?php endif; ?><b><?= e($tt['name']) ?></b></td>
    <td><?= $tt['event_date'] ? date('d/m/Y', strtotime($tt['event_date'])) : '—' ?></td>
    <td><?= $tt['type'] === 'open' ? 'Open' : t('type_internal') ?></td>
    <td><?= (int)$tt['regs'] ?></td>
    <td><span class="badge <?= ['draft'=>'grey','open'=>'green','running'=>'blue','finished'=>'gold'][$tt['status']] ?>"><?= t('status_' . $tt['status']) ?></span></td>
    <?= is_admin() ? '<td>' . e($tt['owner'] ?? '') . '</td>' : '' ?>
    <td style="white-space:nowrap">
      <a class="btn sm" href="<?= APP_URL ?>/tournament/<?= $tt['id'] ?>"><?= icon('play', 12) ?> <?= t('go_to_tournament') ?></a>
      <a class="btn sm secondary" href="<?= APP_URL ?>/tournament/<?= $tt['id'] ?>/settings" title="<?= t('settings') ?>"><?= icon('settings', 14) ?></a>
      <?php if (is_admin() || (int)$tt['user_id'] === (int)$u['id']): ?>
      <a class="btn sm secondary" href="<?= APP_URL ?>/tournament/<?= $tt['id'] ?>/clone" title="<?= t('clone_tournament') ?>"><?= icon('shuffle', 14) ?></a>
      <a class="btn sm danger" href="<?= APP_URL ?>/tournament/<?= $tt['id'] ?>/delete" title="<?= t('delete_tournament') ?>"><?= icon('trash', 14) ?></a>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
<?php endif; ?>

<h2><?= icon('user', 18) ?> <?= t('my_registrations') ?></h2>
<?php if (!$regs): ?>
<div class="card center muted mb"><?= t('no_registrations') ?></div>
<?php endif; ?>

<?php $tournamentsById = []; foreach ($regs as $r):
    $tid2 = (int)$r['tournament_id'];
    if (!array_key_exists($tid2, $tournamentsById)) {
        $tournamentsById[$tid2] = row('SELECT * FROM tournaments WHERE id=?', [$tid2]);
    }
    $myDivs = find_registrant_divisions($r, $tournamentsById[$tid2]);
    $myMatches = rows('SELECT m.*, r1.name red_name, r2.name blue_name FROM matches m
                       LEFT JOIN registrations r1 ON r1.id=m.red_reg_id
                       LEFT JOIN registrations r2 ON r2.id=m.blue_reg_id
                       WHERE (m.red_reg_id=? OR m.blue_reg_id=?) ORDER BY m.round', [$r['id'], $r['id']]);
    $next = null;
    foreach ($myMatches as $mm) if ($mm['status'] !== 'done' && $mm['red_reg_id'] && $mm['blue_reg_id']) { $next = $mm; break; }
?>
<div class="card">
  <div class="flex spread">
    <h2><?= e($r['t_name']) ?></h2>
    <span class="badge <?= ['draft'=>'grey','open'=>'green','running'=>'blue','finished'=>'gold'][$r['t_status']] ?>"><?= t('status_' . $r['t_status']) ?></span>
  </div>
  <p class="muted">
    <?= $r['event_date'] ? icon('calendar', 13) . ' ' . date('d/m/Y', strtotime($r['event_date'])) . ' · ' : '' ?>
    <?= ($r['gender'] === 'M' ? t('male') : t('female')) . ' · ' . e(loc_col($r, 'a')) . ' · ' . e(loc_col($r, 'b')) . ' · ' . e(loc_col($r, 'w')) ?>
    · <?= $r['verified'] ? '<span class="badge green">' . t('verified') . '</span>' : '<span class="badge grey">' . t('pending') . '</span>' ?>
  </p>

  <?php if ($next): ?>
  <div class="flash flash-warning">
    <?= icon('swords', 15) ?> <?= t('next_opponent') ?>: <b><?= e($next['red_reg_id'] == $r['id'] ? $next['blue_name'] : $next['red_name']) ?></b>
    (<?= t('round') ?> <?= (int)$next['round'] ?><?= $next['is_bronze'] ? ' · ' . icon('award', 12, 'ic-bronze') : '' ?>)
  </div>
  <?php endif; ?>

  <?php if ($myMatches): ?>
  <div class="table-wrap"><table>
    <tr><th><?= t('round') ?></th><th><?= t('match') ?></th><th><?= t('result') ?></th></tr>
    <?php foreach ($myMatches as $mm): if (!$mm['red_reg_id'] && !$mm['blue_reg_id']) continue; ?>
    <tr>
      <td><?= $mm['is_bronze'] ? icon('award', 13, 'ic-bronze') : (int)$mm['round'] ?></td>
      <td><?= e($mm['red_name'] ?? t('tbd')) ?> <span class="muted"><?= t('vs') ?></span> <?= e($mm['blue_name'] ?? t('tbd')) ?></td>
      <td>
        <?php if ($mm['status'] === 'done' && $mm['winner_reg_id']): ?>
          <?= $mm['winner_reg_id'] == $r['id'] ? '<span class="badge green">' . t('won') . '</span>' : '<span class="badge red">' . t('lost') . '</span>' ?>
          <span class="muted"><?= (int)$mm['red_points'] ?>-<?= (int)$mm['blue_points'] ?></span>
        <?php else: ?><span class="badge grey"><?= t('upcoming') ?></span><?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>

  <?php if ($myDivs): ?>
  <p class="mt">
    <?php foreach ($myDivs as $div): ?>
    <a class="btn sm secondary" href="<?= APP_URL ?>/division/<?= $div['id'] ?>/view" target="_blank"><?= icon('screen', 13) ?> <?= t('my_position') ?><?= $div['kind'] === 'absolute' ? ' (' . t('absolute_category') . ')' : '' ?></a>
    <?php endforeach; ?>
  </p>
  <?php endif; ?>
</div>
<?php endforeach;
view_footer();
