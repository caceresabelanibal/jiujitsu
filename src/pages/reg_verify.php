<?php
$token = $_GET['token'] ?? '';
$r = $token ? row('SELECT * FROM registrations WHERE verify_token = ? AND verified = 0', [$token]) : null;
if ($r) {
    q('UPDATE registrations SET verified = 1, verify_token = NULL WHERE id = ?', [$r['id']]);
    // La verificacion de la inscripcion tambien verifica la cuenta de usuario asociada
    if ($r['user_id']) {
        q('UPDATE users SET verified_at = COALESCE(verified_at, NOW()) WHERE id = ?', [$r['user_id']]);
    } else {
        q('UPDATE users SET verified_at = COALESCE(verified_at, NOW()) WHERE email = ?', [$r['email']]);
        $u = row('SELECT id FROM users WHERE email = ?', [$r['email']]);
        if ($u) q('UPDATE registrations SET user_id = ? WHERE id = ?', [$u['id'], $r['id']]);
    }
    flash('success', t('reg_verified_ok'));
    $t = row('SELECT slug FROM tournaments WHERE id = ?', [$r['tournament_id']]);
    redirect('/t/' . $t['slug']);
}
flash('error', t('verify_fail'));
redirect('/');
