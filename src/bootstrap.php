<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_URL', rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/'));
define('CRON_KEY', getenv('CRON_KEY') ?: 'changeme-cron-key');

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/bracket.php';
require_once __DIR__ . '/ranking.php';
require_once __DIR__ . '/certificates.php';
require_once __DIR__ . '/stats.php';
require_once __DIR__ . '/ads.php';
require_once __DIR__ . '/views/layout.php';
require_once __DIR__ . '/views/bracket_render.php';

if (PHP_SAPI !== 'cli') {
    session_start();
    i18n_boot();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
}
