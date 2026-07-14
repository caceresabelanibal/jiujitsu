<?php
// Resetea un torneo de muestra al "momento cero" (mitad de luchas + certificados).
// Solo admin, y solo si el torneo es de muestra (is_demo).
require_admin();
$tid = (int)$params[0];
$t = row('SELECT * FROM tournaments WHERE id = ? AND is_demo = 1', [$tid]);
if (!$t) { http_response_code(404); die(t('not_found')); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $n = reset_demo_tournament($tid);
    flash('success', sprintf(t('demo_reset_done'), $n));
}
redirect('/tournament/' . $tid);
