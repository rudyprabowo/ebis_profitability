<?php
namespace Core;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
    'ajax' => [
        'type' => Literal::class,
        'options' => [
            'route' => '/ajax',
            'defaults' => [
                'controller' => Controller\AjaxController::class,
            ],
        ],
        'may_terminate' => true,
        'child_routes' => [
            'call-model' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/call-model/:app/:model/:func',
                    'defaults' => [
                        'action' => 'callmodel',
                        'is_public' => true,
                    ],
                ],
            ],
            'call-model-upload' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/call-model-upload/:app/:func',
                    'defaults' => [
                        'action' => 'callmodelupload',
                        'is_public' => true,
                    ],
                ],
            ],
            'data-generator' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/data-generator',
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'deleteSQL' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/deleteSQL',
                            'defaults' => [
                                'action' => 'generatorDeleteSQL',
                                'is_public' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];