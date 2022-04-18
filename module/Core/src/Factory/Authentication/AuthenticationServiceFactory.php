<?php


namespace Core\Factory\Authentication;


use Core\Adapter\Authentication\AuthenticationAdapter;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\SessionManager;
use Laminas\Authentication\Storage\Session as AuthSessionStorage;

class AuthenticationServiceFactory implements FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
//        var_dump($requestedName);
//        var_dump(__METHOD__);
        $sessionManager = $container->get(SessionManager::class);
        // !d($sessionManager->getName());die();
        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV','development') . ".conf");
        $app_conf = $conf['app-config'];
        $authStorage = new AuthSessionStorage($app_conf['app_alias'].'_AUTH', 'authentication', $sessionManager);
//        var_dump(get_class($authStorage));die();
        $authAdapter = $container->get(AuthenticationAdapter::class);
//         var_dump(get_class($authAdapter));die();
        // d($sessionManager,$authAdapter);die();
        // Debug::dump($authAdapter);die();
        // Create the service and inject dependencies into its constructor.
        $class = $requestedName;
        return new $class($authStorage, $authAdapter);
    }
}