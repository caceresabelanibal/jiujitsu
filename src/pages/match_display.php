<?php
// Marcador publico a pantalla completa (para monitor/proyector)
$m = row('SELECT m.*, r1.name red_name, r2.name blue_name, a1.name red_academy, a2.name blue_academy
          FROM matches m
          LEFT JOIN registrations r1 ON r1.id = m.red_reg_id
          LEFT JOIN registrations r2 ON r2.id = m.blue_reg_id
          LEFT JOIN tournament_academies a1 ON a1.id = r1.academy_id
          LEFT JOIN tournament_academies a2 ON a2.id = r2.academy_id
          WHERE m.id = ?', [(int)$params[0]]);
if (!$m) { http_response_code(404); exit('Not found'); }
$mid = (int)$m['id'];
view_header('Scoreboard', true, 'sbpage');
?>
<div class="sb">
  <div class="sb-timer" data-sb="timer"><?= fmt_time((int)$m['timer_remaining']) ?></div>
  <div class="sb-sides">
    <div class="sb-side red">
      <div class="sb-name"><?= e($m['red_name'] ?? '—') ?></div>
      <div class="sb-academy"><?= e($m['red_academy'] ?? '') ?></div>
      <div class="sb-points" data-sb="red_points">0</div>
      <div class="sb-extras">
        <span class="adv">ADV <span data-sb="red_adv">0</span></span>
        <span class="pen">PEN <span data-sb="red_pen">0</span></span>
      </div>
    </div>
    <div class="sb-side blue">
      <div class="sb-name"><?= e($m['blue_name'] ?? '—') ?></div>
      <div class="sb-academy"><?= e($m['blue_academy'] ?? '') ?></div>
      <div class="sb-points" data-sb="blue_points">0</div>
      <div class="sb-extras">
        <span class="adv">ADV <span data-sb="blue_adv">0</span></span>
        <span class="pen">PEN <span data-sb="blue_pen">0</span></span>
      </div>
    </div>
  </div>
  <div class="sb-winnerbar" data-sb="winnerbar" data-label="<?= t('winner') ?>" style="display:none"></div>
</div>
<script>
window.SB = { matchId: <?= $mid ?>, apiUrl: '<?= APP_URL ?>/api/match/<?= $mid ?>', isOperator: false, csrf: '' };
</script>
<script src="<?= asset('/assets/js/scoreboard.js') ?>"></script>
<?php render_ads_bar((int)$m['tournament_id'], true);
view_footer(true);
