<?php
namespace App;
use Core\Factory\MainFactory;

return [
    'factories' => [
        Controller\IndexController::class => MainFactory::class,
        Controller\LandingController::class => MainFactory::class,
        Controller\ErrorController::class => MainFactory::class,
        Controller\AuthController::class => MainFactory::class
    ],
];