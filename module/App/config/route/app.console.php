<?php
namespace App;

use Laminas\Mvc\Console\Router\Simple;

return [
    //php ./public/index.php scheduler-app-5min
    'scheduler-app-5min' => [
        'type' => Simple::class,
        'options' => [
            'route' => 'scheduler-app-5min',
            'defaults' => [
                'controller' => ConsoleController\SchedulerController::class,
                'action' => 'scheduler5min',
            ],
        ],
    ],
];
