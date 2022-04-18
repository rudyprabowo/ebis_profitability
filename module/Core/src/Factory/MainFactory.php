<?php
namespace Core\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MainFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // !d($requestedName);die();
        $config = $container->get('Config');
        $class = $requestedName;
        return new $class($container, $config);
    }
}
