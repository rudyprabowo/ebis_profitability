<?php
namespace Core;

return [
    'services' => [],
    'invokables' => [],
    'factories' => [
        // stdClass::class => InvokableFactory::class
        Model\SysModel::class => Factory\MainFactory::class,
        Model\BUModel::class => Factory\MainFactory::class,
        Model\PositionModel::class => Factory\MainFactory::class,
        Model\UserModel::class => Factory\MainFactory::class,
        Model\MenuModel::class => Factory\MainFactory::class,
        Model\LayoutModel::class => Factory\MainFactory::class,
        Model\SrcModel::class => Factory\MainFactory::class,
        //Model\RouteModel::class => Factory\MainFactory::class,
    ],
    'abstract_factories' => [],
    'delegators' => [],
    'aliases' => [
        // set alias for class
        // 'A' => stdClass::class,
        // 'B' => 'A'
    ],
    'initializers' => [],
    'lazy_services' => [],
    'shared' => [
        //set false for not shared / disable caching ($object1 === $object2) === false
        // stdClass::class => false
    ],
    // set true for not shared / disable caching (all class)
    // 'shared_by_default'  => true,
];