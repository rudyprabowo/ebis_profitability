<?php
use Laminas\Stdlib\ArrayUtils;

$ini_reader = new Laminas\Config\Reader\Ini();
$conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV') . ".conf");
$app_conf = $conf['app-config'];
$mysql_conf = $conf['db-mysql'];
$postgres_conf = $conf['db-postgres'];
// $nz_conf = $conf['db-netezza'];
// zdebug($nz_conf);die();
$db_main = [];
if (($app_conf['main_db']??null)==='postgres') {
    $db_main = [
        'db' => [
            'driver' => $postgres_conf['admin']['driver'],
            'hostname' => $postgres_conf['admin']['hostname'],
            'port' => $postgres_conf['admin']['port'],
            'username' => $postgres_conf['admin']['username'],
            'password' => $postgres_conf['admin']['password'],
            'database' => $postgres_conf['admin']['database'],
            'options' => [
                // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ],
            'adapters' => [
                'db-admin' => [
                    'driver' => $postgres_conf['admin']['driver'],
                    'hostname' => $postgres_conf['admin']['hostname'],
                    'port' => $postgres_conf['admin']['port'],
                    'username' => $postgres_conf['admin']['username'],
                    'password' => $postgres_conf['admin']['password'],
                    'database' => $postgres_conf['admin']['database'],
                    'options' => [
                        // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        // PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    ],
                ],
                'db-app' => [
                    'driver' => $postgres_conf['app']['driver'],
                    'hostname' => $postgres_conf['app']['hostname'],
                    'port' => $postgres_conf['app']['port'],
                    'username' => $postgres_conf['app']['username'],
                    'password' => $postgres_conf['app']['password'],
                    'database' => $postgres_conf['app']['database'],
                    'options' => [
                        // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        // PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    ],
                ],
                'db-sys' => [
                    'driver' => $postgres_conf['sys']['driver'],
                    'hostname' => $postgres_conf['sys']['hostname'],
                    'port' => $postgres_conf['sys']['port'],
                    'username' => $postgres_conf['sys']['username'],
                    'password' => $postgres_conf['sys']['password'],
                    'database' => $postgres_conf['sys']['database'],
                    'options' => [
                        // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        // PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    ],
                ]
            ]
        ]
    ];
} else {
    $db_main = [
        'db' => [
            'driver' => $mysql_conf['admin']['driver'],
            'hostname' => $mysql_conf['admin']['hostname'],
            'port' => $mysql_conf['admin']['port'],
            'username' => $mysql_conf['admin']['username'],
            'password' => $mysql_conf['admin']['password'],
            'database' => $mysql_conf['admin']['database'],
            'options' => [
                // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ],
            'adapters' => [
                'db-admin' => [
                    'driver' => $mysql_conf['admin']['driver'],
                    'hostname' => $mysql_conf['admin']['hostname'],
                    'port' => $mysql_conf['admin']['port'],
                    'username' => $mysql_conf['admin']['username'],
                    'password' => $mysql_conf['admin']['password'],
                    'database' => $mysql_conf['admin']['database'],
                    'options' => [
                        // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        // PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    ],
                ],
                'db-app' => [
                    'driver' => $mysql_conf['app']['driver'],
                    'hostname' => $mysql_conf['app']['hostname'],
                    'port' => $mysql_conf['app']['port'],
                    'username' => $mysql_conf['app']['username'],
                    'password' => $mysql_conf['app']['password'],
                    'database' => $mysql_conf['app']['database'],
                    'options' => [
                        // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        // PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    ],
                ],
                'db-sys' => [
                    'driver' => $mysql_conf['sys']['driver'],
                    'hostname' => $mysql_conf['sys']['hostname'],
                    'port' => $mysql_conf['sys']['port'],
                    'username' => $mysql_conf['sys']['username'],
                    'password' => $mysql_conf['sys']['password'],
                    'database' => $mysql_conf['sys']['database'],
                    'options' => [
                        // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        // PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    ],
                ]
            ]
        ]
    ];
}

$db_conf = [
    'db' => [
        'adapters' => [
//             'db-app-frames' => [
//                 'driver' => $mysql_conf['app']['driver'],
//                 'hostname' => $mysql_conf['app']['hostname'],
//                 'port' => $mysql_conf['app']['port'],
//                 'username' => $mysql_conf['app']['username'],
//                 'password' => $mysql_conf['app']['password'],
//                 'database' => $mysql_conf['app']['database'],
//                 'options' => [
//                     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//                     PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
//                 ],
//             ],
//             'db-frames' => [
//                 'driver' => $oracle_conf['frames']['driver'],
//                 'hostname' => $oracle_conf['frames']['hostname'],
//                 'port' => $oracle_conf['frames']['port'],
//                 'username' => $oracle_conf['frames']['username'],
//                 'password' => $oracle_conf['frames']['password'],
//                 'database' => $oracle_conf['frames']['database'],
//                 'connection_string' => $oracle_conf['frames']['connection_string'],
//                 // 'character_set' => $oracle_conf['frames']['character_set']
//             ],
//             'db-nzframes' => [
//                 'dsn' => $nz_conf['frames']['dsn'],
//                 'dsn' => $nz_conf['frames']['connection_string'],
//                 'driver' => 'pdo',
//                 'driver_options' => [
//                     // PDO::ATTR_PERSISTENT => true,
//                     PDO::ATTR_AUTOCOMMIT => true,
//                     // PDO::I5_ATTR_DBC_SYS_NAMING => true,
//                     // PDO::I5_ATTR_DBC_CURLIB => '',
//                     // PDO::I5_ATTR_DBC_LIBL => 'SCHEMA1 SCHEMA2 SCHEMA3',
//                 ],
//                 'username' => $nz_conf['frames']['username'],
//                 'password' => $nz_conf['frames']['password'],
//                 'hostname' => $nz_conf['frames']['hostname'],
//                 'port' => $nz_conf['frames']['port'],
//                 // 'platform' => $nz_conf['frames']['dsn'],
//                 'platform_options' => ['quote_identifiers' => false],
//             ],
        ],
    ],
];

return ArrayUtils::merge($db_main, $db_conf);
