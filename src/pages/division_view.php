<?php
// Vista proyector (publica, auto-refresca cada 15s)
$d = row('SELECT * FROM divisions WHERE id = ?', [(int)$params[0]]);
if (!$d) { http_response_code(404); require BASE_PATH . '/src/pages/_404.php'; exit; }
$t = row('SELECT * FROM tournaments WHERE id = ?', [$d['tournament_id']]);

header('Refresh: 15');
view_header(division_label($d), true);
?>
<div class="projector-header">
  <?php if ($t['logo']): ?><img class="tlogo" src="<?= APP_URL . '/' . e($t['logo']) ?>" alt=""><?php endif; ?>
  <h1><?= e($t['name']) ?></h1>
  <h2 class="muted"><?= e(division_label($d)) ?></h2>
</div>
<div style="padding:0 24px 24px">
  <?php render_bracket((int)$d['id']); ?>
</div>
<?php render_ads_bar((int)$t['id'], true);
view_footer(true);
