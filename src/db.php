<?php
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = getenv('DB_HOST') ?: 'db';
        $name = getenv('DB_NAME') ?: 'bjj';
        $user = getenv('DB_USER') ?: 'bjj';
        $pass = getenv('DB_PASS') ?: 'bjjsecret';
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function row(string $sql, array $params = []): ?array {
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

function rows(string $sql, array $params = []): array {
    return q($sql, $params)->fetchAll();
}

function scalar(string $sql, array $params = []) {
    return q($sql, $params)->fetchColumn();
}

function setting(string $key, $default = null) {
    $v = scalar('SELECT v FROM settings WHERE k = ?', [$key]);
    if ($v === false) return $default;
    $decoded = json_decode((string)$v, true);
    return $decoded === null && $v !== 'null' ? $v : $decoded;
}

function set_setting(string $key, $value): void {
    $v = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$key, $v]);
}
