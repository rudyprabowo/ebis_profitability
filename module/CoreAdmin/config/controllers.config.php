<?php
namespace CoreAdmin;
use Core\Factory\MainFactory;

return [
    'factories' => [
        ConsoleController\SchedulerController::class => MainFactory::class,
        ConsoleController\ScriptController::class => MainFactory::class,
        Controller\IndexController::class => MainFactory::class,
        Controller\ExampleController::class => MainFactory::class,
        Controller\AuthController::class => MainFactory::class,
        Controller\AjaxController::class => MainFactory::class,
    ],
];