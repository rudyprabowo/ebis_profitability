<?php

return [
    'view_helpers'=> [
        'factories' => [
            Core\Helper\View\Routing::class => Core\Factory\MainFactory::class,
            Core\Helper\View\Crypt::class => Core\Factory\MainFactory::class,
            Core\Helper\Layout\TailwindTopNav::class => Core\Factory\MainFactory::class,
            Core\Helper\Layout\ISMLeftNav::class => Core\Factory\MainFactory::class,
        ],
        'aliases' => [
            'Routing' => Core\Helper\View\Routing::class,
            'Crypt' => Core\Helper\View\Crypt::class,
            'TailwindTopNavLayout' => Core\Helper\Layout\TailwindTopNav::class,
            'ISMLeftNavLayout' => Core\Helper\Layout\ISMLeftNav::class,
        ],
    ]
];
