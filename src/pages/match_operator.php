<?php
$m = row('SELECT m.*, r1.name red_name, r2.name blue_name, r1.photo red_photo, r2.photo blue_photo,
                 a1.name red_academy, a2.name blue_academy
          FROM matches m
          LEFT JOIN registrations r1 ON r1.id = m.red_reg_id
          LEFT JOIN registrations r2 ON r2.id = m.blue_reg_id
          LEFT JOIN tournament_academies a1 ON a1.id = r1.academy_id
          LEFT JOIN tournament_academies a2 ON a2.id = r2.academy_id
          WHERE m.id = ?', [(int)$params[0]]);
if (!$m) { http_response_code(404); require BASE_PATH . '/src/pages/_404.php'; exit; }
$t = require_tournament_owner((int)$m['tournament_id']);
$d = row('SELECT * FROM divisions WHERE id = ?', [$m['division_id']]);
$mid = (int)$m['id'];
view_header(t('score_operator'));
?>
<div class="op">
  <div class="flex spread mb">
    <a href="<?= APP_URL ?>/division/<?= $m['division_id'] ?>">← <?= division_label($d, true) ?></a>
    <a class="btn secondary" href="<?= APP_URL ?>/match/<?= $mid ?>/display" target="_blank"><?= icon('screen', 15) ?> <?= t('open_display') ?></a>
  </div>

  <div class="op-timerband">
    <span class="op-clock" data-sb="timer"><?= fmt_time((int)$m['timer_remaining']) ?></span>
    <?php if ($m['status'] !== 'done'): ?>
    <button class="btn green" data-sb="startbtn" data-start="▶ <?= t('start') ?>" data-pause="❚❚ <?= t('pause') ?>" onclick="sbToggleTimer()">▶ <?= t('start') ?></button>
    <button class="btn secondary" onclick="sbAction('reset')"><?= icon('reset', 14) ?> <?= t('reset') ?></button>
    <button class="btn secondary" onclick="sbAction('undo')"><?= icon('undo', 14) ?> <?= t('undo') ?></button>
    <?php endif; ?>
  </div>

  <?php if ($m['status'] === 'pending'): ?>
  <p class="muted center mb" data-sb="lockedhint"><?= icon('timer', 13) ?> <?= t('op_locked_hint') ?></p>
  <?php endif; ?>

  <div class="sb-winnerbar" data-sb="winnerbar" data-label="<?= t('winner') ?>" style="display:none;border-radius:12px;margin-bottom:14px"></div>

  <div class="op-sides">
    <?php foreach ([['red', $m['red_name'], $m['red_academy'], $m['red_photo']], ['blue', $m['blue_name'], $m['blue_academy'], $m['blue_photo']]] as [$side, $name, $academy, $photo]): ?>
    <div class="op-side <?= $side ?>">
      <h3><?php if ($photo): ?><img src="<?= APP_URL . '/' . e($photo) ?>" alt="" class="reg-photo-sm" style="margin-right:6px"><?php endif; ?><?= e($name ?? t('tbd')) ?> <small class="muted"><?= e($academy ?? '') ?></small></h3>
      <div class="flex spread">
        <span class="op-score" data-sb="<?= $side ?>_points">0</span>
        <span><span class="badge gold" style="font-size:1rem">A: <span data-sb="<?= $side ?>_adv">0</span></span>
              <span class="badge red" style="font-size:1rem">P: <span data-sb="<?= $side ?>_pen">0</span></span></span>
      </div>
      <?php if ($m['status'] !== 'done'):
          $locked = $m['status'] === 'pending' ? 'disabled' : ''; ?>
      <div class="op-btns">
        <button class="btn secondary op-scorebtn" <?= $locked ?> onclick="sbAction('score','<?= $side ?>','takedown')"><?= t('takedown') ?> +2</button>
        <button class="btn secondary op-scorebtn" <?= $locked ?> onclick="sbAction('score','<?= $side ?>','sweep')"><?= t('sweep') ?> +2</button>
        <button class="btn secondary op-scorebtn" <?= $locked ?> onclick="sbAction('score','<?= $side ?>','knee_on_belly')"><?= t('knee_on_belly') ?> +2</button>
        <button class="btn secondary op-scorebtn" <?= $locked ?> onclick="sbAction('score','<?= $side ?>','guard_pass')"><?= t('guard_pass') ?> +3</button>
        <button class="btn secondary op-scorebtn" <?= $locked ?> onclick="sbAction('score','<?= $side ?>','mount')"><?= t('mount') ?> +4</button>
        <button class="btn secondary op-scorebtn" <?= $locked ?> onclick="sbAction('score','<?= $side ?>','back_control')"><?= t('back_control') ?> +4</button>
      </div>
      <div class="op-avpen">
        <button class="btn warn sm op-scorebtn" <?= $locked ?> onclick="sbAction('score','<?= $side ?>','advantage')">+ <?= t('advantages') ?></button>
        <button class="btn danger sm op-scorebtn" <?= $locked ?> onclick="sbAction('score','<?= $side ?>','penalty')">+ <?= t('penalties') ?></button>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($m['status'] !== 'done'): ?>
  <div class="card mt">
    <h3><?= t('end_match') ?></h3>
    <p class="muted"><?= t('select_winner') ?> · <?= t('method') ?></p>
    <div class="flex">
      <button class="btn" onclick="sbAction('end', null)"><?= t('by_points') ?> (auto)</button>
      <button class="btn green" onclick="sbAction('end','red','submission')"><?= t('submission') ?> <span class="dot red"></span></button>
      <button class="btn green" onclick="sbAction('end','blue','submission')"><?= t('submission') ?> <span class="dot blue"></span></button>
      <button class="btn secondary" onclick="sbAction('end','red','decision')"><?= t('decision') ?> <span class="dot red"></span></button>
      <button class="btn secondary" onclick="sbAction('end','blue','decision')"><?= t('decision') ?> <span class="dot blue"></span></button>
      <button class="btn danger" onclick="sbAction('end','blue','dq')"><?= t('dq') ?> <span class="dot red"></span></button>
      <button class="btn danger" onclick="sbAction('end','red','dq')"><?= t('dq') ?> <span class="dot blue"></span></button>
      <button class="btn secondary" onclick="sbAction('end','red','wo')">W.O. → <span class="dot red"></span></button>
      <button class="btn secondary" onclick="sbAction('end','blue','wo')">W.O. → <span class="dot blue"></span></button>
    </div>
  </div>
  <?php else: ?>
  <div class="flash flash-success mt"><?= t('match_ended') ?></div>
  <?php if ($m['red_reg_id'] && $m['blue_reg_id']): ?>
  <button class="btn secondary mt" onclick="if(confirm(window.SB.confirmReopen)) sbAction('reopen')"><?= icon('edit', 15) ?> <?= t('edit_result') ?></button>
  <?php endif; ?>
  <?php endif; ?>
</div>
<script>
window.SB = {
  matchId: <?= $mid ?>,
  apiUrl: '<?= APP_URL ?>/api/match/<?= $mid ?>',
  isOperator: true,
  csrf: '<?= e($_SESSION['csrf']) ?>',
  confirmReopen: '<?= e(t('confirm_reopen')) ?>',
  reopenBlocked: '<?= e(t('reopen_blocked')) ?>',
  tournamentFinishedTitle: '<?= e(t('tournament_finished_title')) ?>',
  tournamentFinishedBody: '<?= e(sprintf(t('tournament_finished_body'), $t['name'])) ?>',
  tournamentFinishedClose: '<?= e(t('tournament_finished_close')) ?>'
};
</script>
<script src="<?= asset('/assets/js/scoreboard.js') ?>"></script>
<?php view_footer();
