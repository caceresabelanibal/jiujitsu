<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_URL', resolve_app_url());
define('CRON_KEY', getenv('CRON_KEY') ?: 'changeme-cron-key');
define('APP_ENV', getenv('APP_ENV') ?: 'dev');

// En produccion no mostramos errores PHP al visitante (se registran en el log
// de Apache/PHP igual). En dev se muestran para depurar.
if (APP_ENV === 'prod') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

/**
 * URL base de la app. Si APP_URL esta seteada por env (produccion, ej.
 * https://taninzu.com) se usa esa; si no, se detecta del host real de
 * cada request (asi funciona igual desde localhost, una IP de LAN, etc.
 * sin que los links/redirects queden pegados a un dominio fijo).
 * En CLI (scripts, seeders) no hay request, se cae al valor por defecto.
 */
function resolve_app_url(): string {
    $env = getenv('APP_URL');
    if ($env) {
        return rtrim($env, '/');
    }
    if (PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    }
    return 'http://localhost:8080';
}

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/captcha.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/bracket.php';
require_once __DIR__ . '/ranking.php';
require_once __DIR__ . '/certificates.php';
require_once __DIR__ . '/demo.php';
require_once __DIR__ . '/stats.php';
require_once __DIR__ . '/ads.php';
require_once __DIR__ . '/views/icons.php';
require_once __DIR__ . '/views/illustrations.php';
require_once __DIR__ . '/views/layout.php';
require_once __DIR__ . '/views/bracket_render.php';

if (PHP_SAPI !== 'cli') {
    session_start();
    i18n_boot();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
}
