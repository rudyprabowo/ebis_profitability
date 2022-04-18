<?php


namespace Core\Model;


use Laminas\Authentication\AuthenticationService;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Session\SessionManager;
use Laminas\Stdlib\ArrayUtils;

class PositionModel
{
    private $config;
    private $container;
    private $authService;
    private $sessionManager;
    private $dbSys;
    private $dataCache;

    public function __construct($container,$config){
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

    public function selectAllPosition($fromcache = true){
        $me = $this;
        $param = [
        ];
        $method = str_replace(["\\","::"],"_",__METHOD__);
        $salt = "cache-data-".$method;
        $crypted1 = hash('sha1', $salt);
        $crypted2 = hash('sha256', json_encode($param));
        $key = $method.'_'.$crypted1.'_'.$crypted2;
        // !d($salt,$crypted1,$crypted2,$key);die();
        if(isset($_GET['fromcache']) && ($_GET['fromcache']==='0' || $_GET['fromcache']==="false"))
            $fromcache = false;
        if ($me->dataCache->hasItem($key) && $fromcache){
            $data = $me->dataCache->getItem($key);
            // Debug::dump($data);die("CACHE");
            return json_decode($data,true);
        }else{
            $sql = "SELECT id as val,name as label from _position_level where status=1";
            // die($sql);
            $statement = $me->dbSys->createStatement($sql, $param);
            // Debug::dump($statement);die();
            $result    = $statement->execute();
            // Debug::dump($result);die();
            // Debug::dump($result);//die();
            if (! $result instanceof ResultInterface || ! $result->isQueryResult()) {
                // Debug::dump($resultSet);die('fff');
                $result->getResource()->closeCursor();
                return [];
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                // Debug::dump($resultSet->toArray());die();
                $res = $resultSet->toArray();
                $result->getResource()->closeCursor();
                $tmp[] = [
                    "val"=>"null",
                    "label"=>"NULL",
                ];
                $tmp = ArrayUtils::merge($tmp, $res);
                $me->dataCache->removeItem($key);
                $me->dataCache->setItem($key, json_encode($tmp));
                // Debug::dump($tmp);die('qqq');
                return $tmp;
            }
        }
    }
}