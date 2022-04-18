<?php

namespace Core\Model;

use Interop\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Session\SessionManager;

class MenuModel
{
    private $container;
    private $config;
    private $authService;
    private $sessionManager;
    private $dbSys;
    private $dataCache;

    public function __construct(ContainerInterface $container, $config)
    {
        $me = $this;
        $me->container = $container;
        $me->config = $config;
        $me->authService = $container->get(AuthenticationService::class);
        $me->sessionManager = $container->get(SessionManager::class);
        $me->dbSys = $container->get("db-sys");

        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $app_conf = $conf['app-config'];
        if (($app_conf['main_db']??null)==="postgres") {
            $session_conf = $conf['session'];
            $me->dbSys->query(
                'SET search_path TO '.$session_conf['db_schema_name'],
                Adapter::QUERY_MODE_EXECUTE
            );
        }
        $me->dataCache = $container->get("data-file");
    }

    public function getMenuByUidByLayoutByModule(array $param = [], bool $from_cache = true)
    {
        $me = $this;
        $method = str_replace(["\\", "::"], "_", __METHOD__);
        $salt = "cache-data-" . $method;
        $param = [
            'uid' => $param['uid'] ?? null,
            'layout' => $param['layout'] ?? null,
            'module' => $param['module'] ?? null,
        ];
        // zdebug($param);die();
        $crypt1 = hash('sha1', $salt);
        $crypt2 = hash('sha256', json_encode($param));
        $key = $method . '_' . $crypt1 . '_' . $crypt2;

        if (($_GET['dbcache'] ?? '1') === '0') {
            $from_cache = false;
        }
        if ($me->dataCache->hasItem($key) && $from_cache) {
            $data = $me->dataCache->getItem($key);
            return json_decode($data, true);
        } else {
            $ini_reader = new \Laminas\Config\Reader\Ini();
            $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
            $app_conf = $conf['app-config'];
            if (($app_conf['main_db']??null)==="postgres") {
                $sql = "select * from get_menu_by_uid_layout_module(:uid,:layout,:module)";
            } else {
                $sql = "call get_menu_by_uid_layout_module(:uid,:layout,:module)";
            }
            $statement = $me->dbSys->createStatement($sql, $param);
            // zdebug($sql);
            // zdebug($param);
            // die();
            $result = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                $result->getResource()->closeCursor();
                return null;
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                // zdebug($tmp);
                // die();
                $result->getResource()->closeCursor();
                return $tmp;
            }
        }
    }
}
