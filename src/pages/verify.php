<?php
$token = $_GET['token'] ?? '';
$u = $token ? row('SELECT * FROM users WHERE verify_token = ? AND verified_at IS NULL', [$token]) : null;
if ($u) {
    q('UPDATE users SET verified_at = NOW(), verify_token = NULL WHERE id = ?', [$u['id']]);
    flash('success', t('verify_ok'));
} else {
    flash('error', t('verify_fail'));
}
redirect('/login');
