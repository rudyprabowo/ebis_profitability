<?php

namespace CoreAdmin\Model;

use Interop\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Session\SessionManager;

class UbisModel
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

    public function getAllActiveCompany($par = [],bool $from_cache = true){
        $me = $this;
      $method = str_replace(["\\", "::"], "_", __METHOD__);
      $salt = "cache-data-" . $method;
      // $param = [];
      $param = $par;
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
          $sql = "select * from _business_unit where status = 1 and bu_level=1 order by name,label,code,id";
          $statement = $me->dbSys->createStatement($sql, []);
          /** @var Result $result */
          $result = $statement->execute();
          if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
              $result->getResource()->closeCursor();
              return null;
          } else {
              $resultSet = new ResultSet();
              $result->setFetchMode(\PDO::FETCH_NAMED);
              // !d($result->current());die();
              $resultSet->initialize($result);
              $tmp = $resultSet->toArray();
              $result->getResource()->closeCursor();
              $me->dataCache->removeItem($key);
              $me->dataCache->setItem($key, json_encode($tmp));
              // !d($tmp);die();
              return $tmp;
          }
      }
   }

    public function getAllActiveDivision($par = [],bool $from_cache = true){
        $me = $this;
      $method = str_replace(["\\", "::"], "_", __METHOD__);
      $salt = "cache-data-" . $method;
      // $param = [];
      $param = $par;
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
          $sql = "select * from _business_unit where status = 1 and bu_level=2 order by name,label,code,id";
          $statement = $me->dbSys->createStatement($sql, []);
          /** @var Result $result */
          $result = $statement->execute();
          if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
              $result->getResource()->closeCursor();
              return null;
          } else {
              $resultSet = new ResultSet();
              $result->setFetchMode(\PDO::FETCH_NAMED);
              // !d($result->current());die();
              $resultSet->initialize($result);
              $tmp = $resultSet->toArray();
              $result->getResource()->closeCursor();
              $me->dataCache->removeItem($key);
              $me->dataCache->setItem($key, json_encode($tmp));
              // !d($tmp);die();
              return $tmp;
          }
      }
   }
}