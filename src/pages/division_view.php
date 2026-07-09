<?php
// Vista proyector (publica). Se actualiza sola cada 15s sin recargar la
// pagina (fetch + reemplazo del contenido), asi no pierde la posicion de
// scroll ni te vuelve a arrancar arriba en cada actualizacion.
$d = row('SELECT * FROM divisions WHERE id = ?', [(int)$params[0]]);
if (!$d) { http_response_code(404); require BASE_PATH . '/src/pages/_404.php'; exit; }
$t = row('SELECT * FROM tournaments WHERE id = ?', [$d['tournament_id']]);

if (isset($_GET['_fragment'])) {
    // Pedido del poller: devuelve solo el contenido de la llave, sin layout.
    render_bracket((int)$d['id']);
    exit;
}

view_header(division_label($d), true, 'proj');
?>
<script src="<?= APP_URL ?>/assets/js/bracket.js"></script>
<div class="proj-page">
  <div class="projector-header">
    <?php if ($t['logo']): ?><img class="tlogo" src="<?= APP_URL . '/' . e($t['logo']) ?>" alt=""><?php endif; ?>
    <h1><?= e($t['name']) ?></h1>
    <h2 class="muted"><?= e(division_label($d)) ?></h2>
  </div>
  <div class="bracket-region" id="bracket-region">
    <?php render_bracket((int)$d['id']); ?>
  </div>
</div>
<script>
(function () {
  const region = document.getElementById('bracket-region');
  async function refresh() {
    try {
      const html = await (await fetch(location.pathname + '?_fragment=1')).text();
      region.innerHTML = html;
      if (window.fitBracket) fitBracket();
    } catch (e) { /* reintenta en el proximo ciclo */ }
  }
  setInterval(refresh, 15000);
})();
</script>
<?php render_ads_bar((int)$t['id'], true);
view_footer(true);
