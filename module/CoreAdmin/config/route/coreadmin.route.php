<?php
namespace CoreAdmin;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
    'coreadmin' => [
        'type'    => Literal::class,
        'options' => [
            'route'    => '/core-admin',
            'defaults' => [
                'controller' => Controller\IndexController::class,
                'action' => 'home',
                'layout' => 'tailwind-topnav'
            ],
        ],
        'may_terminate' => true,
        'child_routes'=>[
            'xhr' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/xhr',
                    'defaults' => [
                        'controller' => Controller\AjaxController::class,
                        'layout' => 'blank',
                        'is_public' => false
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'login' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/auth-login',
                            'defaults' => [
                                'action'=>'login',
                                'layout' => 'blank',
                                'is_public' => true,
                                "method" => ["POST"]
                            ],
                        ],
                    ],
                    'call-model' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/call-model/:app/:model/:func',
                            'defaults' => [
                                'action'=>'call-model',
                                'layout' => 'blank',
                                'is_public' => true,
                                "method" => ["POST"]
                            ],
                        ],
                    ],
                ]
            ],
            'auth' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/auth',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                    ],
                ],
                'may_terminate' => true,
                'child_routes'=>[
                    'login' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/login',
                            'defaults' => [
                                'action'=>'login',
                                'layout' => 'blank',
                                'is_public' => true
                            ],
                        ],
                    ],
                    'logout' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/logout',
                            'defaults' => [
                                'action'=>'logout',
                                'layout' => 'blank',
                                'is_public' => true
                            ],
                        ],
                    ],
                ]
            ],
        ]
    ],
];