<?php
const APP_LANGS = ['es' => 'Español', 'en' => 'English', 'pt' => 'Português'];

function i18n_boot(): void {
    if (isset($_GET['lang']) && isset(APP_LANGS[$_GET['lang']])) {
        $_SESSION['lang'] = $_GET['lang'];
        setcookie('lang', $_GET['lang'], time() + 86400 * 365, '/');
    }
    if (empty($_SESSION['lang'])) {
        if (!empty($_COOKIE['lang']) && isset(APP_LANGS[$_COOKIE['lang']])) {
            $_SESSION['lang'] = $_COOKIE['lang'];
        } else {
            // Deteccion por navegador: recorre los idiomas del Accept-Language en
            // orden de preferencia y toma el primero soportado; ingles si ninguno.
            $accept = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'es');
            $_SESSION['lang'] = 'en';
            foreach (explode(',', $accept) as $part) {
                $code = substr(trim(explode(';', $part)[0]), 0, 2);
                if (isset(APP_LANGS[$code])) { $_SESSION['lang'] = $code; break; }
            }
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
