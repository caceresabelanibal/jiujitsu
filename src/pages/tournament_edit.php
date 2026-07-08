<?php
$t = require_tournament_owner((int)$params[0]);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $logo = upload_image('logo', 'tournament') ?? $t['logo'];
    q('UPDATE tournaments SET name=?, event_date=?, max_participants=?, status=?, default_duration_sec=?, logo=? WHERE id=?',
        [trim($_POST['name'] ?? $t['name']), $_POST['event_date'] ?: null,
         max(2, (int)($_POST['max_participants'] ?? 200)),
         in_array($_POST['status'] ?? '', ['draft','open','running','finished']) ? $_POST['status'] : $t['status'],
         max(60, (int)($_POST['duration_min'] ?? 5) * 60), $logo, $t['id']]);
    flash('success', t('settings_saved'));
}
redirect('/tournament/' . $t['id'] . '/settings');
