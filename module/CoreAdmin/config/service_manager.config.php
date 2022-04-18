<?php
namespace CoreAdmin;
use Core\Factory\MainFactory;

return [
    'services'           => [],
    'invokables'         => [],
    'factories'          => [
        // Model\ExampleModel::class => MainFactory::class,
        // Model\UploadModel::class => MainFactory::class,
        // Model\RouteModel::class => MainFactory::class,
        // Model\ScriptModel::class => MainFactory::class,
        Model\NzModel::class => MainFactory::class,
        Model\UserModel::class => MainFactory::class,
        Model\MenuModel::class => MainFactory::class,
        Model\RoleModel::class => MainFactory::class,
        Model\UbisModel::class => MainFactory::class,
        Model\CoreModel::class => MainFactory::class,
        Model\ScriptModel::class => MainFactory::class,
        Model\BusinessModel::class => MainFactory::class,
        Model\UsermappingModel::class => MainFactory::class,
    ],
    'abstract_factories' => [],
    'delegators'         => [],
    'aliases'            => [],
    'initializers'       => [],
    'lazy_services'      => [],
    'shared'             => [],
    // 'shared_by_default'  => true,
];
