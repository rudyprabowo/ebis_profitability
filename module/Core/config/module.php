<?php
declare (strict_types = 1);

namespace Core;

use Laminas\Stdlib\ArrayUtils;

$routes = [];
foreach (glob(__DIR__ . '/route/*.route.php') as $filename) {
    $routes = ArrayUtils::merge($routes, include $filename);
}
// !d($routes);die();

$console_routes = [];
foreach (glob(__DIR__ . '/route/*.console.php') as $filename) {
    $console_routes = ArrayUtils::merge($console_routes, include $filename);
}

return [
    'modules' => [
        "Core" => ["session_name" => null],
        "CoreAdmin" => ["session_name" => "Core"],
        "App" => ["session_name" => null]
    ],
    'service_manager' => include __DIR__ . '/service_manager.config.php',
    'controllers' => include __DIR__ . '/controllers.config.php',
    'router' => [
        'routes' => $routes,
    ],
    'console' => [
        'router' => [
            'routes' => $console_routes,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'layout' => 'layout/blank',
        'not_found_template' => 'error/notfound',
        'exception_template' => 'error/except',
        'default_template_suffix' => 'phtml',
        'template_map' => [
            'layout/blank' => APP_PATH . '/views/templates/layout/blank.phtml',
            'layout/tailwind-topnav' => APP_PATH . '/views/templates/layout/tailwind-topnav.phtml',
            'layout/tailwind-blank' => APP_PATH . '/views/templates/layout/tailwind-blank.phtml',
            'layout/cork' => APP_PATH . '/views/templates/layout/cork.phtml',

            'error/notfound' => APP_PATH . '/views/pages/error/notfound.phtml',
            'error/except' => APP_PATH . '/views/pages/error/except.phtml',
        ],
        'template_path_stack' => [
            "View" => APP_PATH . '/views',
            "View/Page" => APP_PATH . '/views/pages',
            "View/Page/Error" => APP_PATH . '/views/pages/error',
            "View/Page/Login" => APP_PATH . '/views/pages/login',
            "View/Template" => APP_PATH . '/views/templates',
            "View/Template/Layout" => APP_PATH . '/views/templates/layout',
            "View/Template/Email" => APP_PATH . '/views/templates/email',
            "Core" => __DIR__ . '/../' . (env("APPLICATION_ENV","development")==="production"?"prod-view":"view"),
        ],
        'strategies' => [
            'ViewJsonStrategy', // register JSON renderer strategy
            'ViewFeedStrategy', // register Feed renderer strategy
        ],
    ],
];
