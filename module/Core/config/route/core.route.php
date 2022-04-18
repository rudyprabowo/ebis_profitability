<?php
namespace Core;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

//api-tools = /api-tools/ui
return [
    'access-error' => [
        'type'    => Segment::class,
        'options' => [
            'route'    => '/access-error/:code',
            'defaults' => [
                'controller' => Controller\ErrorController::class,
                'action'     => 'error',
                'is_caching' => false,
                'layout' => 'blank',
                'is_public' => true
            ],
        ],
    ]
];