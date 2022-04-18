<?php // in config/clear-cache-container.php

use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;

$config = require __DIR__ . '/application.config.php';
$devConfig = __DIR__ . '/development.config.php';
if (file_exists($devConfig)) {
    $devConfig = include $devConfig;
    $config = ArrayUtils::merge($config, $devConfig);
}

$configDisableCaches = [
    'modules_listener_options' => [
        'config_cache_enabled' => false,
        'module_map_cache_enabled' => false,
    ]
];
$config = ArrayUtils::merge($config, $configDisableCaches);

$container = new ServiceManager();
(new ServiceManagerConfig($config['service_manager'] ?? []))->configureServiceManager($container);
$container->setService('ApplicationConfig', $config);
$container->get('ModuleManager')->loadModules();

return $container;