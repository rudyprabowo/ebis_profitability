<?php
use Laminas\Session\Storage\SessionArrayStorage;
use Laminas\Session\Validator\HttpUserAgent;

$ini_reader = new Laminas\Config\Reader\Ini();
$conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV') . ".conf");
$app_conf = $conf['app-config'];
$session_conf = $conf['session'];
return [
    'session_manager' => [
        'config' => [
            // 'class' => SessionConfig::class,
            // https://www.php.net/manual/en/session.configuration.php
            'options' => [
                'use_trans_sid' => (bool)$session_conf['config']['use_trans_sid'],
                'use_cookies' => (bool)$session_conf['config']['use_cookies'],
                // 'strict' => "off",
                'use_only_cookies' => (bool)$session_conf['config']['use_only_cookies'],
                'name' => $app_conf['app_alias']."_SESSION",
                // 'sid_length' => '',
                'remember_me_seconds' => (int)$session_conf['config']['remember_me_seconds'],
                'cache_expire' => (int)$session_conf['config']['cache_expire'],
                'cookie_lifetime' => (int)$session_conf['config']['cookie_lifetime'],
                // 'cookie_path' => _COOKIE_PATH_,
                'cookie_secure' => (bool)$session_conf['config']['cookie_secure'],
                'cookie_httponly' => (bool)$session_conf['config']['cookie_httponly'],
                "cookie_samesite" => $session_conf['config']['cookie_samesite'],
                'gc_maxlifetime' => (int)$session_conf['config']['gc_maxlifetime'],
                'gc_divisor ' => (int)$session_conf['config']['gc_divisor'],
                'gc_probability  ' => (int)$session_conf['config']['gc_probability'],
                'save_path' => $session_conf['config']['save_path'],
            ],
        ],
        // Session validators (used for security).
        'validators' => [
            // RemoteAddr::class,
            // HttpUserAgent::class,
        ],
        'storage' => SessionArrayStorage::class,
    ],
    "session_containers" => [
        "container_init",
        "container_login",
        "container_data",
    ],
];
