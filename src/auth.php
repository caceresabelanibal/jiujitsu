<?php
function current_user(): ?array {
    static $u = false;
    if ($u === false) {
        $u = isset($_SESSION['uid']) ? row('SELECT * FROM users WHERE id = ?', [$_SESSION['uid']]) : null;
    }
    return $u;
}

function is_admin(): bool {
    return (current_user()['role'] ?? '') === 'admin';
}

function require_login(): array {
    $u = current_user();
    if (!$u) {
        flash('warning', t('login_required'));
        redirect('/login');
    }
    return $u;
}

function require_admin(): array {
    $u = require_login();
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        die(t('forbidden'));
    }
    return $u;
}

/** El dueño del torneo o un admin */
function require_tournament_owner(int $tournamentId): array {
    $u = require_login();
    $t = row('SELECT * FROM tournaments WHERE id = ?', [$tournamentId]);
    if (!$t || ($t['user_id'] != $u['id'] && $u['role'] !== 'admin')) {
        http_response_code(403);
        die(t('forbidden'));
    }
    return $t;
}

function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['id'];
}

/** Limite: 1 torneo por semana para usuarios no admin */
function can_create_tournament(array $user): bool {
    if ($user['role'] === 'admin') return true;
    $limit = (int)(setting('tournament_weekly_limit', 1));
    $count = (int)scalar('SELECT COUNT(*) FROM tournaments WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)', [$user['id']]);
    return $count < $limit;
}
