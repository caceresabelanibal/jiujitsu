<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';

$routes = [
    '#^/$#'                              => 'home',
    '#^/login$#'                         => 'login',
    '#^/logout$#'                        => 'logout',
    '#^/register$#'                      => 'register',
    '#^/verify$#'                        => 'verify',
    '#^/dashboard$#'                     => 'dashboard',
    '#^/tournaments$#'                   => 'tournaments',
    '#^/tournaments/create$#'            => 'tournament_create',
    '#^/tournament/(\d+)$#'              => 'tournament_view',
    '#^/tournament/(\d+)/edit$#'         => 'tournament_edit',
    '#^/tournament/(\d+)/settings$#'     => 'tournament_settings',
    '#^/tournament/(\d+)/clone$#'        => 'tournament_clone',
    '#^/tournament/(\d+)/delete$#'       => 'tournament_delete',
    '#^/tournament/(\d+)/academies$#'    => 'tournament_academies',
    '#^/tournament/(\d+)/registrations$#'=> 'tournament_registrations',
    '#^/registration/(\d+)/edit$#'       => 'registration_edit',
    '#^/tournament/(\d+)/divisions$#'    => 'tournament_divisions',
    '#^/tournament/(\d+)/dashboard$#'    => 'tournament_dashboard',
    '#^/tournament/(\d+)/certificates$#' => 'tournament_certificates',
    '#^/tournament/(\d+)/matches$#'      => 'tournament_matches',
    '#^/division/(\d+)$#'                => 'division_manage',
    '#^/division/(\d+)/view$#'           => 'division_view',
    '#^/match/(\d+)/operator$#'          => 'match_operator',
    '#^/match/(\d+)/display$#'           => 'match_display',
    '#^/api/match/(\d+)$#'               => 'api_match',
    '#^/t/([a-zA-Z0-9]+)$#'              => 'public_register',
    '#^/reg-verify$#'                    => 'reg_verify',
    '#^/rankings$#'                      => 'rankings',
    '#^/help$#'                          => 'help',
    '#^/admin$#'                         => 'admin_home',
    '#^/admin/users$#'                   => 'admin_users',
    '#^/admin/settings$#'                => 'admin_settings',
    '#^/admin/ads$#'                     => 'admin_ads',
    '#^/admin/scheduler$#'               => 'admin_scheduler',
    '#^/certificate/(\d+)/download$#'    => 'certificate_download',
];

foreach ($routes as $pattern => $page) {
    if (preg_match($pattern, $path, $m)) {
        $params = array_slice($m, 1);
        $file = BASE_PATH . "/src/pages/$page.php";
        if (file_exists($file)) {
            require $file;
            exit;
        }
    }
}

http_response_code(404);
require BASE_PATH . '/src/pages/_404.php';
