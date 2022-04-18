<?php
namespace Core;

use Core\Factory\MainFactory;

return [
    'factories' => [
        Controller\ErrorController::class => MainFactory::class,
        Controller\AjaxController::class => MainFactory::class
    ],
];
