<?php

return [
    'service_manager' => [
        'factories' => [
            Laminas\Session\ManagerInterface::class => Core\Factory\Session\SessionManagerFactory::class,
            Laminas\Authentication\AuthenticationService::class => Core\Factory\Authentication\AuthenticationServiceFactory::class,
            Core\Adapter\Authentication\AuthenticationAdapter::class => Core\Factory\MainFactory::class,
        ],
        'invokables' => [
            'csrfForm' => Core\Form\CsrfForm::class
        ]
    ],
];