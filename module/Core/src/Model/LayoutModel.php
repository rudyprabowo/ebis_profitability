<?php

namespace Core\Model;

use Interop\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Session\SessionManager;

class LayoutModel
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

    public function getActiveLayout(bool $from_cache = true)
    {
        $me = $this;
        $method = str_replace(["\\", "::"], "_", __METHOD__);
        $salt = "cache-data-" . $method;
        $param = [
        ];
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
                $sql = "select * from  get_all_active_layout()";
            } else {
                $sql = "call get_all_active_layout()";
            }
            $statement = $me->dbSys->createStatement($sql, $param);
            // var_dump($statement, true);die();
            $result = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                $result->getResource()->closeCursor();
                return null;
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                // !d($tmp);
                $result->getResource()->closeCursor();
                $me->dataCache->removeItem($key);
                $me->dataCache->setItem($key, json_encode($tmp));
                return $tmp;
            }
        }
    }

    public function getAllLayout(bool $from_cache = true)
    {
        $me = $this;
        $method = str_replace(["\\", "::"], "_", __METHOD__);
        $salt = "cache-data-" . $method;
        $param = [
        ];
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
            $sql = "SELECT * FROM _layout order by id, name";
            $statement = $me->dbSys->createStatement($sql, $param);
            // var_dump($statement, true);die();
            $result = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                $result->getResource()->closeCursor();
                return null;
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                // !d($tmp);
                $me->dataCache->removeItem($key);
                $me->dataCache->setItem($key, json_encode($tmp));
                $result->getResource()->closeCursor();
                return $tmp;
            }
        }
    }

    public function insertLayout($par)
    {
        $me = $this;
        $method = str_replace(["\\", "::"], "_", __METHOD__);
        $salt = "cache-data-" . $method;
        $param = [
        ];
        $crypt1 = hash('sha1', $salt);
        $crypt2 = hash('sha256', json_encode($param));
        $key = $method . '_' . $crypt1 . '_' . $crypt2;

        // !d($par );die();

        try {
            $sql = "INSERT INTO _layout (name, status)
                values
                (:name, :status)
            ";

            $param = [
                'name' => $par['addLayoutName'],
                'status' => $par['addLayoutStatus']
                ];

            $stmt = $me->dbSys->createStatement($sql, $param);
            // !d($stmt );die();

            $result = $stmt->execute();

            if (! $result instanceof ResultInterface || $result->getAffectedRows()<1) {
                return [
                    "ret"=>false,
                    "affected_row"=>0,
                    "generated_value"=>0,
                    "code"=>1,
                    "msg"=>"Insert data Failed !"
                ];
            } else {
                return [
                    "ret"=>true,
                    "code"=>0,
                    "affected_row"=>$result->getAffectedRows(),
                    "generated_value"=>$result->getGeneratedValue(),
                    "msg"=>"Data Layout Inserted !"
                ];
            }
        } catch (\Exception $e) {
            return [
            "ret"=>false,
            "code"=>1,
            "affected_row"=>0,
            "generated_value"=>0,
            "msg"=>$e->getMessage()
            ];
        }
    }

    public function updateLayout($par)
    {
        $me = $this;
        $method = str_replace(["\\", "::"], "_", __METHOD__);
        $salt = "cache-data-" . $method;
        $param = [
        ];
        $crypt1 = hash('sha1', $salt);
        $crypt2 = hash('sha256', json_encode($param));
        $key = $method . '_' . $crypt1 . '_' . $crypt2;

        // !d($par );die();
        try {
            $sql = "UPDATE _layout set name =:name, status =:status
                    WHERE id =:id
            ";

            $param = [
                'name' => $par['editLayoutName'],
                'status' => $par['editLayoutStatus'],
                'id' => $par['editLayoutID']
                ];

            $stmt = $me->dbSys->createStatement($sql, $param);
            $result = $stmt->execute();

            if (! $result instanceof ResultInterface || $result->getAffectedRows()<1) {
                return [
                    "ret"=>false,
                    "affected_row"=>0,
                    "generated_value"=>0,
                    "code"=>1,
                    "msg"=>"Update data Failed !"
                ];
            } else {
                return [
                    "ret"=>true,
                    "code"=>0,
                    "affected_row"=>$result->getAffectedRows(),
                    "generated_value"=>$result->getGeneratedValue(),
                    "msg"=>"Data Route Updated !"
                ];
            }
        } catch (\Exception $e) {
            return [
            "ret"=>false,
            "code"=>1,
            "affected_row"=>0,
            "generated_value"=>0,
            "msg"=>$e->getMessage()
            ];
        }
    }
}
