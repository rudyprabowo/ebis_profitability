<?php
namespace Core\Factory\Session;

use Core\SaveHandler\Session\MainSaveHandler;
use Core\SaveHandler\Session\MainSaveHandlerOptions;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\Cache\StorageFactory;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\Container as SessionContainer;
use Laminas\Session\SaveHandler\Cache;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\SessionArrayStorage;
use Laminas\Stdlib\ArrayUtils;

class SessionManagerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     *
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // # get config
        $config = $container->get('config');

        // ? global variable SESS_OPT exist
        if (isset($GLOBALS['SESS_OPT'])) {
            $config['session_manager']['config']['options'] = ArrayUtils::merge($config['session_manager']['config']['options'], $GLOBALS['SESS_OPT']);
        }

        // ? session_manager config not exist
        if (!isset($config['session_manager'])) {
            /** @var SessionManager $sessionManager */
            $sessionManager = new SessionManager();
            SessionContainer::setDefaultManager($sessionManager);
            return $sessionManager;
        } else { // ? session_manager config exist
            $session = $config['session_manager'];

            // INFO create session config if exists in global configuration
            $sessionConfig = null;
            if (isset($session['config'])) {
                $class = isset($session['config']['class'])
                ? $session['config']['class']
                : SessionConfig::class;

                $options = isset($session['config']['options'])
                ? $session['config']['options']
                : [];

                $sessionConfig = new $class();
                $sessionConfig->setOptions($options);
            }

            // INFO create session storage if exists in global configuration
            $sessionStorage = null;
            if (isset($session['storage'])) {
                $class = $session['storage'] ?? SessionArrayStorage::class;
                $sessionStorage = new $class();
            }
            $ini_reader = new \Laminas\Config\Reader\Ini();
            $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
            $app_conf = $conf['app-config'];
            $session_conf = $conf['session'];

            // INFO create session save handler
            $sessionSaveHandler = null;
            if (isset($session['save_handler'])) { // ? config session save_handler exist
                // class should be fetched from service manager
                // since it will require constructor arguments
                $sessionSaveHandler = $container->get($session['save_handler']);
            } elseif ($session_conf['save_handler'] === "DB") { // ? session save in DB
                // # get db-admin DB adapter
                $dbAdapter = $container->get('db-admin');
                // zdebug($dbAdapter);
                // die();

                /** @var TableGateway $tableGateway */
                $tableGateway = new TableGateway($session_conf['db_table_name'], $dbAdapter);

                /** @var Core\Factory\Session\MainSaveHandler $sessionSaveHandler */
                $sessionSaveHandler = new MainSaveHandler($container, $tableGateway, new MainSaveHandlerOptions());
            } elseif ($session_conf['save_handler'] === "FILE") { // ? session save in FILE
                $adapter = $config['caches']['session-file']['adapter'];
                $cache = StorageFactory::factory([
                    'adapter' => $adapter,
                ]);

                /** @var Cache $sessionSaveHandler */
                $sessionSaveHandler = new Cache($cache);
            }

            $sessionValidator = isset($session['validators'])
            ? $session['validators']
            : [];

            // INFO set session save handler
            $sessionConfig->setSaveHandler($sessionSaveHandler);

            // INFO initialize session manager
            $sessionManager = new SessionManager(
                $sessionConfig,
                $sessionStorage,
                $sessionSaveHandler,
                $sessionValidator
            );

            // INFO set default session manager
            SessionContainer::setDefaultManager($sessionManager);
            return $sessionManager;
        }
    }
}
