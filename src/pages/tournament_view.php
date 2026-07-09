<?php
// Centro de operacion del torneo: luchas en vivo, proximas, divisiones con acceso rapido
$t = require_tournament_owner((int)$params[0]);
$tid = (int)$t['id'];

$regs = (int)scalar('SELECT COUNT(*) FROM registrations WHERE tournament_id=? AND verified=1', [$tid]);
$fightsDone = (int)scalar('SELECT COUNT(*) FROM matches WHERE tournament_id=? AND status="done" AND red_reg_id IS NOT NULL AND blue_reg_id IS NOT NULL', [$tid]);
$fightsLeft = (int)scalar('SELECT COUNT(*) FROM matches WHERE tournament_id=? AND status!="done" AND red_reg_id IS NOT NULL AND blue_reg_id IS NOT NULL', [$tid]);
$divsDone = (int)scalar('SELECT COUNT(*) FROM divisions WHERE tournament_id=? AND status="done"', [$tid]);
$divsTotal = (int)scalar('SELECT COUNT(*) FROM divisions WHERE tournament_id=?', [$tid]);

$live = rows('SELECT m.*, r1.name red_name, r2.name blue_name FROM matches m
              JOIN registrations r1 ON r1.id=m.red_reg_id JOIN registrations r2 ON r2.id=m.blue_reg_id
              WHERE m.tournament_id=? AND m.status="live" ORDER BY m.updated_at DESC', [$tid]);
$next = rows('SELECT m.*, r1.name red_name, r2.name blue_name, d.id did,
                     b.name_es b_es, b.name_en b_en, ad.name_es ad_es, ad.name_en ad_en, wc.name_es w_es, wc.name_en w_en, d.gender
              FROM matches m
              JOIN divisions d ON d.id=m.division_id
              JOIN belts b ON b.id=d.belt_id JOIN age_divisions ad ON ad.id=d.age_division_id JOIN weight_classes wc ON wc.id=d.weight_class_id
              JOIN registrations r1 ON r1.id=m.red_reg_id JOIN registrations r2 ON r2.id=m.blue_reg_id
              WHERE m.tournament_id=? AND m.status="pending"
              ORDER BY d.id, m.round, m.slot LIMIT 8', [$tid]);
$divs = rows('SELECT d.*, b.name_es b_es, b.name_en b_en, b.color_hex,
                     ad.name_es a_es, ad.name_en a_en, wc.name_es w_es, wc.name_en w_en,
                     (SELECT COUNT(*) FROM matches m WHERE m.division_id=d.id AND m.status!="done" AND m.red_reg_id IS NOT NULL AND m.blue_reg_id IS NOT NULL) left_fights
              FROM divisions d
              JOIN belts b ON b.id=d.belt_id JOIN age_divisions ad ON ad.id=d.age_division_id JOIN weight_classes wc ON wc.id=d.weight_class_id
              WHERE d.tournament_id=? ORDER BY (d.status="done"), d.gender, ad.sort, b.sort, wc.sort', [$tid]);
$isEn = lang() === 'en';

view_header($t['name']);
?>
<div class="flex spread">
  <h1><?php if ($t['logo']): ?><img class="tlogo" src="<?= APP_URL . '/' . e($t['logo']) ?>" alt=""> <?php endif; ?><?= e($t['name']) ?></h1>
  <span class="badge <?= ['draft'=>'grey','open'=>'green','running'=>'blue','finished'=>'gold'][$t['status']] ?>"><?= t('status_' . $t['status']) ?></span>
</div>
<?php tournament_tabs($t, 'overview'); ?>

<div class="grid cols4 mb">
  <div class="stat"><div class="k"><?= icon('users', 14) ?> <?= t('participants') ?></div><div class="v"><?= $regs ?></div></div>
  <div class="stat"><div class="k"><?= icon('swords', 14) ?> <?= t('matches') ?></div><div class="v"><?= $fightsDone ?> <span class="muted" style="font-size:1rem">/ <?= $fightsDone + $fightsLeft ?></span></div><div class="sub"><?= $fightsLeft ?> <?= t('pending') ?></div></div>
  <div class="stat"><div class="k"><?= icon('bracket', 14) ?> <?= t('divisions_progress') ?></div><div class="v"><?= $divsDone ?>/<?= $divsTotal ?></div></div>
  <div class="stat"><div class="k"><?= icon('calendar', 14) ?> <?= t('date') ?></div><div class="v" style="font-size:1.1rem"><?= $t['event_date'] ? date('d/m/Y', strtotime($t['event_date'])) : '—' ?></div></div>
</div>

<?php if ($live): ?>
<div class="card" style="border-color:var(--red)">
  <h3><span class="dot live"></span> <?= t('live_now') ?></h3>
  <?php foreach ($live as $m): ?>
  <div class="flex spread" style="padding:8px 0;border-bottom:1px solid var(--border)">
    <span><b><?= e($m['red_name']) ?></b> <span class="muted"><?= t('vs') ?></span> <b><?= e($m['blue_name']) ?></b>
      <span class="badge red"><?= (int)$m['red_points'] ?>-<?= (int)$m['blue_points'] ?></span></span>
    <span>
      <a class="btn sm" href="<?= APP_URL ?>/match/<?= $m['id'] ?>/operator"><?= icon('timer', 14) ?> <?= t('operator') ?></a>
      <a class="btn sm secondary" href="<?= APP_URL ?>/match/<?= $m['id'] ?>/display" target="_blank"><?= icon('screen', 14) ?></a>
    </span>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($next): ?>
<div class="card">
  <h3><?= icon('play', 15) ?> <?= t('next_fights') ?></h3>
  <div class="table-wrap"><table>
    <?php foreach ($next as $m): ?>
    <tr>
      <td class="muted" style="font-size:.8rem"><?= ($m['gender'] === 'M' ? t('male') : t('female')) . ' · ' . e($isEn ? $m['ad_en'] : $m['ad_es']) . ' · ' . e($isEn ? $m['b_en'] : $m['b_es']) . ' · ' . e($isEn ? $m['w_en'] : $m['w_es']) ?><?= $m['is_bronze'] ? ' ' . icon('award', 12, 'ic-bronze') : '' ?></td>
      <td><b><?= e($m['red_name']) ?></b> <span class="muted"><?= t('vs') ?></span> <b><?= e($m['blue_name']) ?></b></td>
      <td class="right" style="white-space:nowrap">
        <a class="btn sm" href="<?= APP_URL ?>/match/<?= $m['id'] ?>/operator"><?= icon('timer', 14) ?> <?= t('operator') ?></a>
        <a class="btn sm secondary" href="<?= APP_URL ?>/match/<?= $m['id'] ?>/display" target="_blank"><?= icon('screen', 14) ?></a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php endif; ?>

<h3 style="margin:22px 0 10px"><?= icon('trophy', 17) ?> <?= t('divisions') ?></h3>
<?php if (!$divs): ?>
  <div class="card muted center"><?= t('no_competitors') ?> — <a href="<?= APP_URL ?>/tournament/<?= $tid ?>/divisions"><?= t('generate_divisions') ?></a></div>
<?php else: ?>
<div class="grid cols3">
  <?php foreach ($divs as $d): ?>
  <div class="card" style="padding:14px;margin:0">
    <div style="font-size:.86rem">
      <span class="belt-chip" style="background:<?= e($d['color_hex']) ?>"></span>
      <b><?= ($d['gender'] === 'M' ? t('male') : t('female')) ?></b> · <?= e($isEn ? $d['a_en'] : $d['a_es']) ?><br>
      <span class="muted"><?= e($isEn ? $d['b_en'] : $d['b_es']) ?> · <?= e($isEn ? $d['w_en'] : $d['w_es']) ?></span>
    </div>
    <div class="flex spread mt" style="margin-top:10px">
      <?php if ($d['status'] === 'done'): ?>
        <span class="badge gold"><?= icon('check', 11) ?> <?= t('done') ?></span>
      <?php elseif ($d['status'] === 'bracketed'): ?>
        <span class="badge blue"><?= (int)$d['left_fights'] ?> <?= icon('swords', 11) ?></span>
      <?php else: ?>
        <span class="badge grey"><?= t('pending') ?></span>
      <?php endif; ?>
      <span>
        <a class="btn sm" href="<?= APP_URL ?>/division/<?= $d['id'] ?>"><?= icon('bracket', 13) ?> <?= t('bracket') ?></a>
        <a class="btn sm secondary" href="<?= APP_URL ?>/division/<?= $d['id'] ?>/view" target="_blank"><?= icon('screen', 13) ?></a>
      </span>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif;
view_footer();
