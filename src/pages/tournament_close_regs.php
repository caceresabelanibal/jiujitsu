<?php
// Cerrar / reabrir inscripciones a mano (dueño, staff o admin).
// El cierre automático por fecha lo hace el cron "registration_close".
$t = require_tournament_owner((int)$params[0]);
$tid = (int)$t['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['do'] ?? '') === 'reopen') {
        // Reabrir: además de limpiar el cierre, se borra la fecha configurada
        // si ya pasó (si no, el cron las volvería a cerrar al instante).
        q('UPDATE tournaments SET regs_closed_at = NULL,
               reg_close_date = IF(reg_close_date IS NOT NULL AND reg_close_date <= CURDATE(), NULL, reg_close_date)
           WHERE id = ?', [$tid]);
        flash('success', t('regs_reopened'));
    } else {
        q('UPDATE tournaments SET regs_closed_at = NOW() WHERE id = ?', [$tid]);
        // Generar las divisiones que falten, así el aviso de "divisiones con
        // un solo competidor" refleja a todos los inscriptos verificados.
        ensure_divisions($tid);
        flash('success', t('regs_closed_ok'));
    }
}
redirect('/tournament/' . $tid);
