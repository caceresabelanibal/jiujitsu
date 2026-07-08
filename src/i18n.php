<?php
function i18n_boot(): void {
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en'])) {
        $_SESSION['lang'] = $_GET['lang'];
        setcookie('lang', $_GET['lang'], time() + 86400 * 365, '/');
    }
    if (empty($_SESSION['lang'])) {
        if (!empty($_COOKIE['lang']) && in_array($_COOKIE['lang'], ['es', 'en'])) {
            $_SESSION['lang'] = $_COOKIE['lang'];
        } else {
            // Deteccion por navegador: espanol por defecto para es-*, ingles para el resto
            $accept = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'es');
            $_SESSION['lang'] = str_starts_with($accept, 'es') ? 'es' : 'en';
        }
    }
}

function lang(): string {
    return $_SESSION['lang'] ?? 'es';
}

function t(string $key, array $vars = []): string {
    static $dict = [];
    $l = lang();
    if (!isset($dict[$l])) {
        $file = BASE_PATH . "/src/lang/$l.php";
        $dict[$l] = file_exists($file) ? require $file : [];
    }
    $s = $dict[$l][$key] ?? $key;
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', (string)$v, $s);
    }
    return $s;
}
