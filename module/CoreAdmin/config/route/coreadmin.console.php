<?php
namespace CoreAdmin;

use Laminas\Mvc\Console\Router\Simple;

return [
    //php ./public/index.php scheduler-1min
    'scheduler-1min' => [
        'type' => Simple::class,
        'options' => [
            'route' => 'scheduler-1min',
            'defaults' => [
                'controller' => ConsoleController\SchedulerController::class,
                'action' => 'scheduler1min',
            ],
        ],
    ],
    //php ./public/index.php job-param 'paramA' 'paramB' 'paramC'
    'job-param' => [
        'type' => Simple::class,
        'options' => [
            'route' => 'job-param <paramA> <paramB> <paramC>',
            'defaults' => [
                'controller' => ConsoleController\SchedulerController::class,
                'action' => 'job-param',
            ],
        ],
    ],
    //php ./public/index.php gearman-upload-module
    'gearman-upload-module' => [
        'type' => Simple::class,
        'options' => [
            'route' => 'gearman-upload-module',
            'defaults' => [
                'controller' => ConsoleController\ScriptController::class,
                'action' => 'gearman-upload-module',
            ],
        ],
    ],
    //php ./public/index.php gearman-upload-module-client
    'gearman-upload-module-client' => [
        'type' => Simple::class,
        'options' => [
            'route' => 'gearman-upload-module-client',
            'defaults' => [
                'controller' => ConsoleController\ScriptController::class,
                'action' => 'gearman-upload-module-client',
            ],
        ],
    ],
];
