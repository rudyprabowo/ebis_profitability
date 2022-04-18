<?php

namespace CoreAdmin\Model;

use Interop\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Session\SessionManager;

class NzModel
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
        $me->dbNzFrames = $container->get("db-nzframes");
        $me->dataCache = $container->get("data-file");
    }

    public function getFramesCollection($param = [], $from_cache = true){
      $me = $this;
      $method = str_replace(["\\", "::"], "_", __METHOD__);
      $salt = "cache-data-" . $method;
      $par = $param;
      $crypt1 = hash('sha1', $salt);
      $crypt2 = hash('sha256', json_encode($param));
      $key = $method . '_' . $crypt1 . '_' . $crypt2;

      if ($_GET['dbcache'] ?? '1' === '0') {
          $from_cache = false;
      }

      if ($me->dataCache->hasItem($key) && $from_cache) {
          $data = $me->dataCache->getItem($key);
          return json_decode($data, true);
      } else {
          $sql = "SELECT * FROM TELKOM_CDR.ADMIN.CDR_COLLECTION LIMIT 10";
          $statement = $me->dbNzFrames->createStatement($sql, []);
          $result = $statement->execute();
          if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
              return null;
          } else {
              $resultSet = new ResultSet();
              $resultSet->initialize($result);
              $tmp = $resultSet->toArray();
              $me->dataCache->removeItem($key);
              $me->dataCache->setItem($key, json_encode($tmp));
              // !d($tmp);die();
              return $tmp;
          }
      }
    }
}