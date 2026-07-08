<?php
// Panel del competidor: sus inscripciones, llaves y proximas luchas
$u = require_login();
$regs = rows('SELECT r.*, t.name t_name, t.slug, t.status t_status, t.event_date,
                     b.name_es b_es, b.name_en b_en, ad.name_es a_es, ad.name_en a_en,
                     wc.name_es w_es, wc.name_en w_en
              FROM registrations r
              JOIN tournaments t ON t.id = r.tournament_id
              JOIN belts b ON b.id = r.belt_id
              JOIN age_divisions ad ON ad.id = r.age_division_id
              JOIN weight_classes wc ON wc.id = r.weight_class_id
              WHERE r.user_id = ? OR r.email = ? ORDER BY r.created_at DESC', [$u['id'], $u['email']]);
$isEn = lang() === 'en';
view_header(t('my_panel'));
?>
<h1><?= t('my_panel') ?> · <?= e($u['name']) ?></h1>
<?php if (!$regs): ?>
<div class="card center muted"><?= t('no_registrations') ?></div>
<?php endif; ?>

<?php foreach ($regs as $r):
    $div = row('SELECT * FROM divisions WHERE tournament_id=? AND gender=? AND belt_id=? AND age_division_id=? AND weight_class_id=?',
        [$r['tournament_id'], $r['gender'], $r['belt_id'], $r['age_division_id'], $r['weight_class_id']]);
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
    <?= $r['event_date'] ? '📅 ' . date('d/m/Y', strtotime($r['event_date'])) . ' · ' : '' ?>
    <?= ($r['gender'] === 'M' ? t('male') : t('female')) . ' · ' . e($isEn ? $r['a_en'] : $r['a_es']) . ' · ' . e($isEn ? $r['b_en'] : $r['b_es']) . ' · ' . e($isEn ? $r['w_en'] : $r['w_es']) ?>
    · <?= $r['verified'] ? '<span class="badge green">' . t('verified') . '</span>' : '<span class="badge grey">' . t('pending') . '</span>' ?>
  </p>

  <?php if ($next): ?>
  <div class="flash flash-warning">
    ⚔️ <?= t('next_opponent') ?>: <b><?= e($next['red_reg_id'] == $r['id'] ? $next['blue_name'] : $next['red_name']) ?></b>
    (<?= t('round') ?> <?= (int)$next['round'] ?><?= $next['is_bronze'] ? ' · 🥉' : '' ?>)
  </div>
  <?php endif; ?>

  <?php if ($myMatches): ?>
  <div class="table-wrap"><table>
    <tr><th><?= t('round') ?></th><th><?= t('match') ?></th><th><?= t('result') ?></th></tr>
    <?php foreach ($myMatches as $mm): if (!$mm['red_reg_id'] && !$mm['blue_reg_id']) continue; ?>
    <tr>
      <td><?= $mm['is_bronze'] ? '🥉' : (int)$mm['round'] ?></td>
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

  <?php if ($div): ?>
  <p class="mt"><a class="btn sm secondary" href="<?= APP_URL ?>/division/<?= $div['id'] ?>/view" target="_blank"><?= t('my_position') ?> 📺</a></p>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<div class="card">
  <h3><?= t('my_tournaments') ?> (organizador)</h3>
  <a class="btn" href="<?= APP_URL ?>/tournaments"><?= t('nav_tournaments') ?></a>
  <a class="btn secondary" href="<?= APP_URL ?>/tournaments/create">+ <?= t('create_tournament') ?></a>
</div>
<?php view_footer();
