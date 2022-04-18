<?php


namespace Core\Model;

use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\BlockCipher;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Session\SessionManager;

class SysModel
{
    private $config;
    private $container;
    // private $authService;
    // private $sessionManager;
    private $dbSys;
    private $dataCache;
    private $authService;
    private $sessionManager;

    public function __construct($container, $config)
    {
        $me = $this;
        $me->container = $container;
        $me->config = $config;
        try {
            $me->authService = $container->get(AuthenticationService::class);
        } catch (\Exception $e) {
            $me->authService = null;
        }
        try {
            $me->sessionManager = $container->get(SessionManager::class);
        } catch (\Exception $e) {
            $me->sessionManager = null;
        }
        // $me->authService = $container->get(AuthenticationService::class);
        // $me->sessionManager = $container->get(SessionManager::class);
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

    /**
     * @param array $params
     * @param bool $fromcache
     * @return array|mixed
     */
    public function getTableSchema($params = [], $fromcache = true)
    {
        $me = $this;
        $param = [
            'sch'=>$params['sch'] ?? '',
            'tbl'=>$params['tbl'] ?? ''
        ];
        // !d($param);die();
        $method = str_replace(["\\","::"], "_", __METHOD__);
        $salt = "cache-data-".$method;
        $crypted1 = hash('sha1', $salt);
        $crypted2 = hash('sha256', json_encode($param));
        $key = $method.'_'.$crypted1.'_'.$crypted2;
        // !d($salt,$crypted1,$crypted2,$key);die();
        if (isset($_GET['fromcache']) && ($_GET['fromcache']==='0' || $_GET['fromcache']==="false")) {
            $fromcache = false;
        }
        if ($me->dataCache->hasItem($key) && $fromcache) {
            $data = $me->dataCache->getItem($key);
            // Debug::dump($data);die("CACHE");
            return json_decode($data, true);
        } else {
            $sql = "CALL get_table_schema(:sch,:tbl)";
            // die($sql);
            // !d($sql,$param);
            $statement = $me->dbSys->createStatement($sql, $param);
            // !d($statement);die();
            $result    = $statement->execute();
            // Debug::dump($result);die();
            // Debug::dump($result);//die();
            // !d($result);
            if (! $result instanceof ResultInterface || ! $result->isQueryResult()) {
                // Debug::dump($resultSet);die('fff');
                $result->getResource()->closeCursor();
                return [];
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                // Debug::dump($resultSet->toArray());die();
                $tmp = $resultSet->toArray();
                $result->getResource()->closeCursor();
                $me->dataCache->removeItem($key);
                $me->dataCache->setItem($key, json_encode($tmp));
                return $tmp;
            }
        }
    }

    private function extractRouteName(&$data, $parent, $route)
    {
        $me = $this;
        foreach ($route as $k=>$v) {
            $par = $parent.'/'.$k;
            if (isset($v['options']) && isset($v['options']['route']) && isset($v['options']['defaults'])
                && isset($v['options']['defaults']['action'])) {
                if (!in_array($par, $data)) {
                    $data[] = $par;
                }
            }
            if (isset($v['child_routes']) && count($v['child_routes'])>0) {
                $me->extractRouteName($data, $par, $v['child_routes']);
            }
        }
    }

    public function selectAllRoute($fromcache = true)
    {
        $me = $this;
        $param = [
        ];
        $method = str_replace(["\\","::"], "_", __METHOD__);
        $salt = "cache-data-".$method;
        $crypted1 = hash('sha1', $salt);
        $crypted2 = hash('sha256', json_encode($param));
        $key = $method.'_'.$crypted1.'_'.$crypted2;
        // !d($salt,$crypted1,$crypted2,$key);die();
        // !d($me->config['router']['routes']);die();
        if (isset($_GET['fromcache']) && ($_GET['fromcache']==='0' || $_GET['fromcache']==="false")) {
            $fromcache = false;
        }
        if ($me->dataCache->hasItem($key) && $fromcache) {
            $data = $me->dataCache->getItem($key);
            // Debug::dump($data);die("CACHE");
            return json_decode($data, true);
        } else {
            $routes = $me->config['router']['routes'];
            $data = [];
            foreach ($routes as $k=>$v) {
                if (isset($v['options']) && isset($v['options']['route']) && isset($v['options']['defaults'])
                    && isset($v['options']['defaults']['action'])) {
                    if (!in_array($k, $data)) {
                        $data[] = $k;
                    }
                }
                if (isset($v['child_routes']) && count($v['child_routes'])>0) {
                    $me->extractRouteName($data, $k, $v['child_routes']);
                }
            }
            // !d($data);die();

            $res = [];
            $res[] = [
                "val"=>"",
                "label"=>"NULL",
            ];
            foreach ($data as $k=>$v) {
                $res[] = [
                    "val"=>$v,
                    "label"=>$v,
                ];
            }
            // !d($res);die();
            $me->dataCache->removeItem($key);
            $me->dataCache->setItem($key, json_encode($res));
            return $res;
        }
    }

    public function getRows(array $param)
    {
        $me = $this;
        $ret = [
            'ret' => false,
            'msg' => "Invalid Request"
        ];

        if (isset($param['table']) && isset($param['condition'])) {
            $par = [];
            $field = [];
            $field[] = "1=1";
            foreach ($param['condition'] as $k => $v) {
                $field[] = "`" . $k . '` = :'.$k;
                $par[$k] = $v;
            }
            $sql = "SELECT * FROM `".$param['table']."` WHERE " . implode(" AND ", $field);

            $stmt = $me->dbSys->createStatement($sql, $par);
            $result = $stmt->execute();

            if (! $result instanceof ResultInterface || ! $result->isQueryResult()) {
                // Debug::dump($resultSet);die('fff');
                $result->getResource()->closeCursor();
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                // Debug::dump($resultSet->toArray());die();
                $ret = [
                    'ret' => true,
                    'msg' => "Success",
                    'data' => $resultSet->toArray()
                ];
                $result->getResource()->closeCursor();
            }
        }

        return $ret;
    }

    public function addRows(array $param)
    {
        $me = $this;
        $ret = [
            'ret' => false,
            'msg' => "Invalid Request"
        ];

        if (isset($param['table']) && isset($param['values'])) {
            $field1 = [];
            $field2 = [];
            $par = [];
            foreach ($param['values'] as $k => $v) {
                $field1[] = "`" . $k . '`';
                $field2[] = ':'.$k;
                $par[$k] = $v;
            }

            $sql = "INSERT INTO `".$param['table']."` (" . implode(", ", $field1) . ") VALUES (" . implode(",", $field2). ")";

            $stmt = $me->dbSys->createStatement($sql, $par);
            $result = $stmt->execute();
            if ($result->valid()) {
                $row = $result->getGeneratedValue();
                $ret = [
                    'ret' => true,
                    'msg' => "Success add data",
                    'id' => $row
                ];
            } else {
                $ret['msg']="Failed add data";
            }
        }

        return $ret;
    }

    public function updateRows(array $param)
    {
        $me = $this;
        $ret = [
            'ret' => false,
            'msg' => "Invalid Request"
        ];

        if (isset($param['table']) && isset($param['values']) && isset($param['condition'])) {
            $field1 = [];
            $par = [];
            foreach ($param['values'] as $k => $v) {
                $field1[] = "`" . $k . '` = :'.$k;
                $par[$k] = $v;
            }

            $field2 = [];
            $field2[] = "1=1";
            foreach ($param['condition'] as $k => $v) {
                $field2[] = "`" . $k . '` = :'.$k;
                $par[$k] = $v;
            }
            $sql = "UPDATE `".$param['table']."` SET " . implode(", ", $field1) . " WHERE " . implode(" AND ", $field2);

            $stmt = $me->dbSys->createStatement($sql, $par);
            $result = $stmt->execute();
            if ($result->valid()) {
                $row = $result->getAffectedRows();
                $ret = [
                    'ret' => true,
                    'msg' => "Success update data",
                    'affected_row' => $row
                ];
            } else {
                $ret['msg']="Failed update data";
            }
        }

        return $ret;
    }

    public function encrypt($data = [])
    {
        $me = $this;
        // !d($data);die();
        $algo = $data['algo'] ?? "aes";
        $mode = $data['mode'] ?? "gcm";
        $blockCipher = BlockCipher::factory(
            'openssl',
            [
                'algo' => $algo,
                'mode' => $mode
            ]
        );
        $key = $data['key'] ?? "";
        $blockCipher->setKey($key);
        // if(!is_array($data))$data = [$data];
        $txt = $data['txt'] ?? "";
        $res1 = $blockCipher->encrypt(strval($txt));
        $encrypt = $res1;

        $auth = $me->authService;
        $session = $me->sessionManager;
        $bypass = (bool)($data['bypass'] ?? false);
        if (!$bypass && $auth!==null && $auth->hasIdentity() && $session->sessionExists()) {
            $session = $me->sessionManager;
            // $tmp = $auth->getIdentity();
            $sess_id = $session->getId();
            // !d($sess_id);die();
            $blockCipher->setKey($sess_id);
            $res2 = $blockCipher->encrypt($encrypt);
            $encrypt = $res2;
        }
        return $encrypt;
        // $data['txt'] = $encrypt;
        // return [
        //     $encrypt,
        //     $me->decrypt($data)
        // ];
    }

    public function decrypt($data = [])
    {
        $me = $this;
        // !d($data);
        $algo = $data['algo'] ?? "aes";
        $mode = $data['mode'] ?? "gcm";
        $blockCipher = BlockCipher::factory(
            'openssl',
            [
                'algo' => $algo,
                'mode' => $mode
            ]
        );
        $txt = $data['txt'] ?? "";
        $decrypt = strval($txt);
        $auth = $me->authService;
        $session = $me->sessionManager;
        $bypass = (bool)($data['bypass'] ?? false);
        if (!$bypass && $auth!==null && $auth->hasIdentity() && $session->sessionExists()) {
            // $tmp = $auth->getIdentity();
            $sess_id = $session->getId();

            $blockCipher->setKey($sess_id);
            try {
                $res1 = $blockCipher->decrypt($decrypt);
            } catch (\Exception $e) {
                return null;
            }
            $decrypt = $res1;
        }

        $key = $data['key'] ?? "";
        $blockCipher->setKey($key);
        try {
            $ret = $blockCipher->decrypt($decrypt);
        } catch (\Exception $e) {
            return null;
        }
        // die($ret);
        return $ret;
    }

    public function deleteRows(array $param)
    {
        $me = $this;
        $ret = [
            'ret' => false,
            'msg' => "Invalid Request"
        ];

        if (isset($param['table']) && isset($param['condition'])) {
            $par = [];
            $field = [];
            $field[] = "1=1";
            foreach ($param['condition'] as $k => $v) {
                $field[] = "`" . $k . '` = :'.$k;
                $par[$k] = $v;
            }
            $sql = "DELETE FROM `".$param['table']."` WHERE " . implode(" AND ", $field);

            $stmt = $me->dbSys->createStatement($sql, $par);
            $result = $stmt->execute();
            if ($result->valid()) {
                $row = $result->getAffectedRows();
                $ret = [
                    'ret' => true,
                    'msg' => "Success delete data",
                    'affected_row' => $row
                ];
            } else {
                $ret['msg']="Failed delete data";
            }
        }

        return $ret;
    }
}
