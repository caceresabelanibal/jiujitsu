<?php
// Eliminar torneo: solo el creador real (dueño) o un admin -- el personal del torneo no puede.
// Las FK de tournaments ya cascadean en el schema (registraciones, divisiones, llaves, staff,
// academias, profesores, publicidad, certificados); despues de borrar recalculamos el ranking
// para que no queden puntos de este torneo colgados.
$t = require_tournament_creator((int)$params[0]);
$tid = (int)$t['id'];

$stats = [
    'regs' => (int)scalar('SELECT COUNT(*) FROM registrations WHERE tournament_id=?', [$tid]),
    'divs' => (int)scalar('SELECT COUNT(*) FROM divisions WHERE tournament_id=?', [$tid]),
    'matches' => (int)scalar('SELECT COUNT(*) FROM matches WHERE tournament_id=? AND status="done"', [$tid]),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (trim($_POST['confirm_name'] ?? '') === $t['name']) {
        q('DELETE FROM tournaments WHERE id = ?', [$tid]);
        recompute_rankings();
        flash('success', t('tournament_deleted'));
        redirect('/dashboard');
    }
    flash('error', t('tournament_delete_mismatch'));
    redirect("/tournament/$tid/delete");
}

view_header(t('delete_tournament'));
?>
<div class="card" style="max-width:560px;margin:0 auto;border-color:var(--red)">
  <h2><?= icon('trash', 20) ?> <?= t('delete_tournament') ?></h2>
  <p><?= sprintf(t('delete_tournament_warning'), '<b>' . e($t['name']) . '</b>') ?></p>
  <ul>
    <li><?= sprintf(t('delete_stat_regs'), $stats['regs']) ?></li>
    <li><?= sprintf(t('delete_stat_divs'), $stats['divs']) ?></li>
    <li><?= sprintf(t('delete_stat_matches'), $stats['matches']) ?></li>
  </ul>
  <form method="post">
    <?= csrf_field() ?>
    <label><?= sprintf(t('delete_type_name'), '<b>' . e($t['name']) . '</b>') ?></label>
    <input type="text" name="confirm_name" required autofocus autocomplete="off">
    <button class="btn danger mt" style="width:100%"><?= icon('trash', 15) ?> <?= t('delete_tournament') ?></button>
  </form>
</div>
<?php view_footer();
