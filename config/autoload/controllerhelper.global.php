<?php

return [
    'controller_plugins' => array(
        'factories' => [
            Core\Helper\Controller\DataGenerator::class => Core\Factory\MainFactory::class,
            Core\Helper\Controller\Routing::class => Core\Factory\MainFactory::class,
            Core\Helper\Controller\Email::class => Core\Factory\MainFactory::class,
            Core\Helper\Controller\Crypt::class => Core\Factory\MainFactory::class,
            Core\Helper\Controller\Logging::class => Core\Factory\MainFactory::class,
            Core\Helper\Controller\Secure::class => Core\Factory\MainFactory::class
        ],
        'aliases' => array(
            'DataGenerator' => Core\Helper\Controller\DataGenerator::class,
            'Routing' => Core\Helper\Controller\Routing::class,
            'Email' => Core\Helper\Controller\Email::class,
            'Crypt' => Core\Helper\Controller\Crypt::class,
            'Logging' => Core\Helper\Controller\Logging::class,
            'Secure' => Core\Helper\Controller\Secure::class
        )
    ),
];