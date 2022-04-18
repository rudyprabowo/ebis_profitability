<?php
namespace CoreAdmin;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
    'example' => [
        'type'    => Segment::class,
        'options' => [
            'route'    => '/example[/:action]',
            'constraints' => [
                'action'     => '[a-zA-Z][a-zA-Z0-9_-]+',
            ],
            'defaults' => [
                'controller' => Controller\ExampleController::class,
                'layout' => 'blank',
                'action'     => 'index'
            ],
        ],
        'may_terminate' => true
    ],
];