<?php
namespace App;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
    'app' => [
        'type'    => Literal::class,
        'options' => [
            'route'    => '/',
            'defaults' => [
                'controller' => Controller\LandingController::class,
                'action' => 'welcome',
                'layout' => 'blank',
                'is_public' => true
            ],
        ],
        'may_terminate' => true,
        'child_routes'=>[
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => 'home',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'home',
                        'layout' => 'tailwind-blank',
                        'is_public' => false
                    ],
                ],
            ],
            'term-condition' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => 'term-condition',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'termcond',
                        'layout' => 'tailwind-blank',
                        'is_public' => true
                    ],
                ],
            ],
            'auth' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => 'auth',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'method'=>['GET','POST']
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
                                'layout' => 'tailwind-blank',
                                'is_caching' => false,
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
                                'layout' => 'tailwind-blank',
                                'is_public' => true
                            ],
                        ],
                    ],
                ]
            ],
            'access-error' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => 'access-error/:code',
                    'defaults' => [
                        'controller' => Controller\ErrorController::class,
                        'action'     => 'error',
                        'is_caching' => false,
                        'layout' => 'blank',
                        'is_public' => true
                    ],
                ],
            ]
        ]
    ],
];
