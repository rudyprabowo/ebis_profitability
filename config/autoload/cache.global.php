<?php
$ini_reader = new Laminas\Config\Reader\Ini();
$conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV') . ".conf");
$app_conf = $conf['app-config'];
$cache_conf = $conf['cache'];
return [
    'caches' => [
        'session-file' => [
            // 'adapter' => [
            //     'name' => 'filesystem',
            //     'options' => [
            //         'ttl' => $cache_conf['session']['ttl'],
            //         'namespace' => $app_conf['app_alias'] . '_SESSIONCACHE',
            //         'cache_dir' => $app_conf['cache_dir'] . 'session',
            //         'no_atime' => false,
            //         'no_ctime' => false,
            //         'suffix' => 'session',
            //         'dir_permission' => 0770,
            //         'file_permission' => 0660,
            //     ],
            // ],
            'adapter' => 'filesystem',
            'options' => [
                'ttl' => $cache_conf['session']['ttl'],
                'namespace' => $app_conf['app_alias'] . '_SESSIONCACHE',
                'cache_dir' => $app_conf['cache_dir'] . 'session',
                'no_atime' => false,
                'no_ctime' => false,
                'suffix' => 'session',
                'dir_permission' => 0770,
                'file_permission' => 0660,
            ],
            'plugins' => [
                [
                    'name' => 'exception_handler',
                    'options' => [
                        'throw_exceptions' => env("DEBUG"),
                        ],
                ],
            ],
        ],
        'view-file' => [
            // 'adapter' => [
            //     'name' => 'filesystem',
            //     'options' => [
            //         'ttl' => $cache_conf['view']['ttl'],
            //         'namespace' => $app_conf['app_alias'] . '_VIEWCACHE',
            //         'cache_dir' => $app_conf['cache_dir'] . 'view',
            //         'no_atime' => false,
            //         'no_ctime' => false,
            //         'suffix' => 'view',
            //         'dir_permission' => 0770,
            //         'file_permission' => 0660,
            //     ],
            // ],
            'adapter' => 'filesystem',
            'options' => [
                'ttl' => $cache_conf['session']['ttl'],
                'namespace' => $app_conf['app_alias'] . '_VIEWCACHE',
                'cache_dir' => $app_conf['cache_dir'] . 'view',
                'no_atime' => false,
                'no_ctime' => false,
                'suffix' => 'view',
                'dir_permission' => 0770,
                'file_permission' => 0660,
            ],
            'plugins' => [
                [
                    'name' => 'exception_handler',
                    'options' => [
                        'throw_exceptions' => env("DEBUG"),
                        ],
                ],
            ],
        ],
        'data-file' => [
            // 'adapter' => [
            //     'name' => 'filesystem',
            //     'options' => [
            //         'ttl' => $cache_conf['data']['ttl'],
            //         'namespace' => $app_conf['app_alias'] . '_DATACACHE',
            //         'cache_dir' => $app_conf['cache_dir'] . 'data',
            //         'no_atime' => false,
            //         'no_ctime' => false,
            //         'suffix' => 'data',
            //         'dir_permission' => 0770,
            //         'file_permission' => 0660,
            //     ],
            // ],
            'adapter' => 'filesystem',
            'options' => [
                'ttl' => $cache_conf['session']['ttl'],
                'namespace' => $app_conf['app_alias'] . '_DATACACHE',
                'cache_dir' => $app_conf['cache_dir'] . 'data',
                'no_atime' => false,
                'no_ctime' => false,
                'suffix' => 'data',
                'dir_permission' => 0770,
                'file_permission' => 0660,
            ],
            'plugins' => [
                [
                    'name' => 'exception_handler',
                    'options' => [
                        'throw_exceptions' => env("DEBUG"),
                        ],
                ],
            ],
        ],
    ],
];
