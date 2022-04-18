<?php

namespace CoreAdmin\Model;

use function _\map;
use Interop\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Session\SessionManager;

class UserModel
{
    private $container;
    private $config;
    private $authService;
    private $sessionManager;
    private $dbSys;
    private $dataCache;
    private $_db_sys;
    private $_data_cache;

    public function __construct(ContainerInterface $container, $config)
    {
        $me = $this;
        $me->container = $container;
        $me->config = $config;
        $me->authService = $container->get(AuthenticationService::class);
        $me->sessionManager = $container->get(SessionManager::class);
        $me->_db_sys = $container->get("db-sys");

        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $app_conf = $conf['app-config'];
        if (($app_conf['main_db']??null)==="postgres") {
            $session_conf = $conf['session'];
            $me->_db_sys->query(
                'SET search_path TO '.$session_conf['db_schema_name'],
                Adapter::QUERY_MODE_EXECUTE
            );
        }
        $me->_data_cache = $container->get("data-file");
    }

    public function findUserByUsernameNIKFullname($par = [], bool $from_cache = true)
    {
        $me = $this;
        $method = str_replace(["\\", "::"], "_", __METHOD__);
        $salt = "cache-data-" . $method;
        //   zdebug($par);die();
        $param = $par;
        $param['find'] = "%" . $param['find'] . "%";
        //   zdebug($param);
        $crypt1 = hash('sha1', $salt);
        $crypt2 = hash('sha256', json_encode($param));
        $key = $method . '_' . $crypt1 . '_' . $crypt2;

        if (($_GET['dbcache'] ?? '1') === '0') {
            $from_cache = false;
        }

        if ($me->_data_cache->hasItem($key) && $from_cache) {
            $data = $me->_data_cache->getItem($key);
            return json_decode($data, true);
        } else {
            try {
                $sql = "select * from _user where status = 1 and employ_nik is not null and employ_nik != '' and (username like :find or full_name like :find or employ_nik like :find) limit 10";
                $statement = $me->_db_sys->createStatement($sql, $param);
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
                    $me->_data_cache->removeItem($key);
                    $me->_data_cache->setItem($key, json_encode($tmp));
                    // !d($tmp);die();
                    return $tmp;
                }
            } catch (\Exception $e) {
                if (($_SERVER['APPLICATION_ENV'] ?? "production") === 'development') {
                    zdebug($e->getMessage());
                    die();
                }
                return null;
            }
        }
    }

    public function getRoute($par = [], bool $from_cache = true)
    {
        $me = $this;
        // die('qqq');
        $method = str_replace(["\\", "::"], "_", __METHOD__);
        $salt = "cache-data-" . $method;
        $param = [];
        $crypt1 = hash('sha1', $salt);
        $crypt2 = hash('sha256', json_encode($param));
        $key = $method . '_' . $crypt1 . '_' . $crypt2;

        if ($_GET['dbcache'] ?? '1' === '0') {
            $from_cache = false;
        }

        if ($me->_data_cache->hasItem($key) && $from_cache) {
            $data = $me->_data_cache->getItem($key);
            return json_decode($data, true);
        } else {
            $sql = "select
            a.id,CONCAT_WS('/',c.name,b.name, a.name) as name,
            CONCAT_WS('',c.route,b.route, a.route) as route,a.status
            from _route a
            left join _route b on a.parent=b.id
            left join _route c on b.parent=c.id
            where a.action is not null";

            $statement = $me->_db_sys->createStatement($sql, []);
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
                $me->_data_cache->removeItem($key);
                $me->_data_cache->setItem($key, json_encode($tmp));
                // !d($tmp);die();
                return $tmp;
            }
        }
    }

    /**
     * Private _buildFilterData
     *
     * @param mixed $param     Filter Parameter
     * @param mixed $cond      Condition Parameter
     * @param mixed $sql_param SQL Parameter
     *
     * @return void
     */
    private function _buildFilterData($param, &$cond, &$sql_param)
    {
        if (isset($param['filter'])) {
            $tmp = "";
            foreach ($param['filter'] as $k => $v) {
                $tmp_key = str_replace(" ", "_", $k);
                if ($v['cond'] === "equal") {
                    $tmp = $tmp_key;
                    if (count($v['val']) === 1) {
                        $tmp .= '= :' . $tmp_key;
                        $sql_param[$tmp_key] = $v['val'][0];
                    } elseif (count($v['val']) > 1) {
                        $tmp .= " IN (";
                        $tmp2 = [];
                        foreach ($v['val'] as $k2 => $v2) {
                            $tmp_key2             = $tmp_key . $k2;
                            $tmp2[]               = ":" . $tmp_key2;
                            $sql_param[$tmp_key2] = $v2;
                        }
                        $tmp .= implode(", ", $tmp2) . ")";
                    }
                    $cond[] = $tmp;
                } elseif ($v['cond'] === "notequal") {
                    $tmp = $tmp_key;
                    if (count($v['val']) === 1) {
                        $tmp .= '!= :' . $tmp_key;
                        $sql_param[$tmp_key] = $v['val'][0];
                    } elseif (count($v['val']) > 1) {
                        $tmp .= " NOT IN (";
                        $tmp2 = [];
                        foreach ($v['val'] as $k2 => $v2) {
                            $tmp_key2             = $tmp_key . $k2;
                            $tmp2[]               = ":" . $tmp_key2;
                            $sql_param[$tmp_key2] = $v2;
                        }
                        $tmp .= implode(", ", $tmp2) . ")";
                    }
                    $cond[] = $tmp;
                } elseif ($v['cond'] === "like") {
                    $tmp = $tmp_key;
                    if (count($v['val']) === 1) {
                        $tmp .= ' LIKE :' . $tmp_key;
                        $sql_param[$tmp_key] = "%" . $v['val'][0] . "%";
                    } else {
                        $tmp  = "(";
                        $tmp2 = [];
                        foreach ($v['val'] as $k2 => $v2) {
                            $tmp_key2             = $tmp_key . $k2;
                            $tmp2[]               = $tmp_key . " LIKE :" . $tmp_key2;
                            $sql_param[$tmp_key2] = "%" . $v2 . "%";
                        }
                        $tmp .= implode(" OR ", $tmp2) . ")";
                    }
                    $cond[] = $tmp;
                } elseif ($v['cond'] === "notlike") {
                    $tmp = $tmp_key;
                    if (count($v['val']) === 1) {
                        $tmp .= ' NOT LIKE :' . $tmp_key;
                        $sql_param[$tmp_key] = "%" . $v['val'][0] . "%";
                    } else {
                        $tmp  = "(";
                        $tmp2 = [];
                        foreach ($v['val'] as $k2 => $v2) {
                            $tmp_key2             = $tmp_key . $k2;
                            $tmp2[]               = $tmp_key . " NOT LIKE :" . $tmp_key2;
                            $sql_param[$tmp_key2] = "%" . $v2 . "%";
                        }
                        $tmp .= implode(" AND ", $tmp2) . ")";
                    }
                    $cond[] = $tmp;
                } elseif ($v['cond'] === "suffix") {
                    $tmp = $tmp_key;
                    if (count($v['val']) === 1) {
                        $tmp .= ' LIKE :' . $tmp_key;
                        $sql_param[$tmp_key] = "%" . $v['val'][0];
                    } else {
                        $tmp  = "(";
                        $tmp2 = [];
                        foreach ($v['val'] as $k2 => $v2) {
                            $tmp_key2             = $tmp_key . $k2;
                            $tmp2[]               = $tmp_key . " LIKE :" . $tmp_key2;
                            $sql_param[$tmp_key2] = "%" . $v2;
                        }
                        $tmp .= implode(" OR ", $tmp2) . ")";
                    }
                    $cond[] = $tmp;
                } elseif ($v['cond'] === "notsuffix") {
                    $tmp = $tmp_key;
                    if (count($v['val']) === 1) {
                        $tmp .= ' NOT LIKE :' . $tmp_key;
                        $sql_param[$tmp_key] = "%" . $v['val'][0];
                    } else {
                        $tmp  = "(";
                        $tmp2 = [];
                        foreach ($v['val'] as $k2 => $v2) {
                            $tmp_key2             = $tmp_key . $k2;
                            $tmp2[]               = $tmp_key . " NOT LIKE :" . $tmp_key2;
                            $sql_param[$tmp_key2] = "%" . $v2;
                        }
                        $tmp .= implode(" AND ", $tmp2) . ")";
                    }
                    $cond[] = $tmp;
                } elseif ($v['cond'] === "prefix") {
                    $tmp = $tmp_key;
                    if (count($v['val']) === 1) {
                        $tmp .= ' LIKE :' . $tmp_key;
                        $sql_param[$tmp_key] = $v['val'][0] . "%";
                    } else {
                        $tmp  = "(";
                        $tmp2 = [];
                        foreach ($v['val'] as $k2 => $v2) {
                            $tmp_key2             = $tmp_key . $k2;
                            $tmp2[]               = $tmp_key . " LIKE :" . $tmp_key2;
                            $sql_param[$tmp_key2] = $v2 . "%";
                        }
                        $tmp .= implode(" OR ", $tmp2) . ")";
                    }
                    $cond[] = $tmp;
                } elseif ($v['cond'] === "notprefix") {
                    $tmp = $tmp_key;
                    if (count($v['val']) === 1) {
                        $tmp .= ' NOT LIKE :' . $tmp_key;
                        $sql_param[$tmp_key] = $v['val'][0] . "%";
                    } else {
                        $tmp  = "(";
                        $tmp2 = [];
                        foreach ($v['val'] as $k2 => $v2) {
                            $tmp_key2             = $tmp_key . $k2;
                            $tmp2[]               = $tmp_key . " NOT LIKE :" . $tmp_key2;
                            $sql_param[$tmp_key2] = $v2 . "%";
                        }
                        $tmp .= implode(" AND ", $tmp2) . ")";
                    }
                    $cond[] = $tmp;
                } elseif ($v['cond'] === "more") {
                    $tmp = $tmp_key;
                    if (count($v['val']) === 1) {
                        $tmp .= ' > :' . $tmp_key;
                        $sql_param[$tmp_key] = $v['val'][0];
                    } else {
                        $tmp  = "(";
                        $tmp2 = [];
                        foreach ($v['val'] as $k2 => $v2) {
                            $tmp_key2             = $tmp_key . $k2;
                            $tmp2[]               = $tmp_key . " > :" . $tmp_key2;
                            $sql_param[$tmp_key2] = $v2;
                        }
                        $tmp .= implode(" OR ", $tmp2) . ")";
                    }
                    $cond[] = $tmp;
                } elseif ($v['cond'] === "less") {
                    $tmp = $tmp_key;
                    if (count($v['val']) === 1) {
                        $tmp .= ' < :' . $tmp_key;
                        $sql_param[$tmp_key] = $v['val'][0];
                    } else {
                        $tmp  = "(";
                        $tmp2 = [];
                        foreach ($v['val'] as $k2 => $v2) {
                            $tmp_key2             = $tmp_key . $k2;
                            $tmp2[]               = $tmp_key . " < :" . $tmp_key2;
                            $sql_param[$tmp_key2] = $v2;
                        }
                        $tmp .= implode(" OR ", $tmp2) . ")";
                    }
                    $cond[] = $tmp;
                } elseif ($v['cond'] === "moreequal") {
                    $tmp = $tmp_key;
                    if (count($v['val']) === 1) {
                        $tmp .= ' >= :' . $tmp_key;
                        $sql_param[$tmp_key] = $v['val'][0];
                    } else {
                        $tmp  = "(";
                        $tmp2 = [];
                        foreach ($v['val'] as $k2 => $v2) {
                            $tmp_key2             = $tmp_key . $k2;
                            $tmp2[]               = $tmp_key . " >= :" . $tmp_key2;
                            $sql_param[$tmp_key2] = $v2;
                        }
                        $tmp .= implode(" OR ", $tmp2) . ")";
                    }
                    $cond[] = $tmp;
                } elseif ($v['cond'] === "lessequal") {
                    $tmp = $tmp_key;
                    if (count($v['val']) === 1) {
                        $tmp .= ' <= :' . $tmp_key;
                        $sql_param[$tmp_key] = $v['val'][0];
                    } else {
                        $tmp  = "(";
                        $tmp2 = [];
                        foreach ($v['val'] as $k2 => $v2) {
                            $tmp_key2             = $tmp_key . $k2;
                            $tmp2[]               = $tmp_key . " <= :" . $tmp_key2;
                            $sql_param[$tmp_key2] = $v2;
                        }
                        $tmp .= implode(" OR ", $tmp2) . ")";
                    }
                    $cond[] = $tmp;
                }
            }
        }
    }

    public function getAllUsersByFilter($param = [], $from_cache = true)
    {
        $me     = $this;
        $method = str_replace(["\\", "::"], "_", __METHOD__);
        $salt   = "cache-data-" . $method;
        $par    = $param;
        if (isset($param['dbcache'])) {
            unset($param['dbcache']);
        }
        $crypt1  = hash('sha1', $salt);
        $crypt2  = hash('sha256', json_encode($param));
        $key     = $method . '_' . $crypt1 . '_' . $crypt2;
        $dbcache = $_GET['dbcache'] ?? ($par['dbcache'] ?? "1");
        if ($dbcache === '0') {
            $from_cache = false;
        }
        // die('aaa');

        /* ------------------------- Get Data From Cache ------------------------- */
        if ($me->_data_cache->hasItem($key) && $from_cache) {
            $data = $me->_data_cache->getItem($key);
            return json_decode($data, true);
        } else {
            /* ------------------------- Get Data From DB ------------------------ */
            try {
                $cond = ['1=1'];
                $par = [];
                // die('qqq');
                $me->_buildFilterData($param, $cond, $par);
                // zdebug($param);
                // zdebug($cond);
                // zdebug($par);
                // die();
                $sql = "SELECT * from _user where id >0 AND ".implode(' AND ', $cond);
                $sort = $param['sorting']??null;
                if ($sort!==null && isset($sort['field']) && ($sort['field']!=="none" || $sort['field']!==null)
                && ($sort['order']!=="none" || $sort['order']!==null)) {
                    $sql .= " ORDER BY " . str_replace(" ", "_", $sort['field']) . " " . (($sort['order']==="desc")?"desc":"asc");
                }
                if (isset($param['limit']) && isset($param['current_page']) && (int)$param['current_page']>0) {
                    if (((int)$param['current_page'])>1) {
                        $sql .= " LIMIT ". ((((int)$param['current_page']-1)*(int)$param['limit'])-1).", ".((int)$param['limit']+2);
                    } else {
                        $sql .= " LIMIT ". (((int)$param['current_page']-1)*(int)$param['limit']).", ".((int)$param['limit']+1);
                    }
                }
                // zdebug($sql);
                // zdebug($par);
                // die();
                /* ----------------------- Create Statement ---------------------- */
                $statement      = $me->_db_sys->createStatement($sql, $par);
                /* ------------------------ Execute Query ------------------------ */
                $result         = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $isQryResult    = $result->isQueryResult();
                if (!$isResInterface || !$isQryResult) {
                    return [
                        'hasNext' => false,
                        '_data' => null
                    ];
                } else {
                    $resultSet = new ResultSet();
                    $resultSet->initialize($result);
                    /* ---------------- Convert Resultset to Array --------------- */
                    $tmp = $resultSet->toArray();
                    /* ------------------ Remove Existing Cache ------------------ */
                    $me->_data_cache->removeItem($key);
                    /* --------------------- Write New Cache --------------------- */
                    $hNext = false;
                    // zdebug($tmp);
                    if (((int)$param['current_page'])>1) {
                        if (count($tmp) > ((int)$param['limit']+1)) {
                            $hNext = true;
                            array_pop($tmp);
                            array_shift($tmp);
                        }
                    } else {
                        if (count($tmp) > ((int)$param['limit'])) {
                            $hNext = true;
                            array_pop($tmp);
                        }
                    }
                    // zdebug($tmp);
                    // die();
                    $tmp =  [
                        'hasNext' => $hNext,
                        '_data' => $tmp
                    ];
                    $me->_data_cache->setItem($key, json_encode($tmp));
                    return $tmp;
                }
            } catch (\Exception$e) {
                // zdebug($e->getMessage());
                // die();
                return [
                    'hasNext' => false,
                    '_data' => null
                ];
            }
        }
    }

    public function checkUserExist($param = [])
    {
        $me = $this;
        try {
            $cond = ["email =:email"];
            if ($param['nik']!==null) {
                $cond[] = 'employ_nik=:nik';
            }
            if ($param['mobile_no']!==null) {
                $cond[] = 'mobile_no=:mobile_no';
            }
            if ($param['telegram_id']!==null) {
                $cond[] = 'telegram_id=:telegram_id';
            }
            // die('ss');
            // query check username
            $sql = "SELECT count(username) as total FROM _user WHERE username=:username OR ".implode(' OR ',$cond);
            // zdebug($sql);zdebug($param);die();
            $statement = $me->_db_sys->createStatement($sql, $param);
            $result    = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                return false;
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                // zdebug($tmp);die();
                $tot = (int) $tmp[0]['total'];
                // zdebug($tot > 0);die();
                return $tot > 0;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function addUser($param = [])
    {
        // die('ADD USER');
        $me     = $this;
        $ret    = null;
        $fields = ['username', 'email', 'full_name','pass','status','is_organic','login_method','mobile_no',
        'telegram_id','nik','spv','redirect_route','redirect_url','redirect_param','redirect_query'];
        // zdebug($fields);zdebug($param);die();
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkUserExist(['username'=>$param['username'],'email'=>$param['email'],'nik'=>$param['nik'],'mobile_no'=>$param['mobile_no'],'telegram_id'=>$param['telegram_id']])) {
                    $sql = "INSERT INTO lamira_sys._user
                    (username, full_name, password, email, status, pass_reset_token, created_date, updated_date,
                    pass_reset_date, redirect_route, redirect_param, redirect_query, redirect_url,
                    is_organic, mobile_no, employ_nik, spv_nik, telegram_id, expired_date, login_method)
                    VALUES(:username, :full_name, :pass, :email, :status, NULL, current_timestamp(), current_timestamp(),
                    NULL, :redirect_route, :redirect_param, :redirect_query, :redirect_url,
                    :is_organic, :mobile_no, :nik, :spv, :telegram_id, NULL, :login_method)";
                    unset($param['confirm_pass']);
                    $bcrypt = new Bcrypt();
                    $param['pass'] = $bcrypt->create($param['pass']);
                    $param['status'] = (int)$param['status'];
                    $param['is_organic'] = (int)$param['is_organic'];
                    // zdebug($sql);zdebug($param);die();
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_UserModel_getAllUsersByFilter");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "User added successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "User has been exists"
                    ];
                }
            } catch (\Exception $e) {
                // zdebug($e->getMessage());die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "User added failed"
                ];
            }
        }
        return $ret;
    }

    public function updateUser($param = [])
    {
        die("UPDATE USER");
        $me     = $this;
        $ret    = null;
        $fields = ['id','name', 'status', 'session'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                if (!$this->checkModuleNameById(['name'=>$param['name'],'id'=>$param['id']])) {
                    $sql = "UPDATE _module SET name=:name, status=:status, session_name=:session WHERE id=:id";
                    // die($sql);
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    // zdebug($isResInterface);
                    // zdebug($affectedRow);
                    // die();
                    if (!$isResInterface) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_UserModel_getAllUsersByFilter");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Module updated successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Module name has been exists"
                    ];
                }
            } catch (\Exception $e) {
                // zdebug($e->getMessage());
                // die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Module updated failed"
                ];
            }
        }
        return $ret;
    }

    public function updateStatus($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'status'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                $sql = "UPDATE _user SET status=:status WHERE id=:id";
                // die($sql);
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                // zdebug($isResInterface);
                // zdebug($affectedRow);
                // die();
                if (!$isResInterface) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_UserModel_getAllUsersByFilter");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "User updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "User updated failed"
                ];
            }
        }
        return $ret;
    }

    public function updateOrganic($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'is_organic'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                $sql = "UPDATE _user SET is_organic=:is_organic WHERE id=:id";
                // die($sql);
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                // zdebug($isResInterface);
                // zdebug($affectedRow);
                // die();
                if (!$isResInterface) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_UserModel_getAllUsersByFilter");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "User updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "User updated failed"
                ];
            }
        }
        return $ret;
    }

    public function updateUserAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM _user )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE _user AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function deleteUser($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _user WHERE id=:id";
                // zdebug($sql);
                // zdebug($param);
                // die();
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_UserModel_getAllUsersByFilter");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "User deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "User deleted failed"
                ];
            }

            $this->updateUserAutoIncrement();
        }
        return $ret;
    }

    public function deleteMultiUser($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param['id']);
            $param['id'] = map($param['id'], function ($v) {
                return (int)$v;
            });
            $val = [];
            $par = [];
            foreach ($param['id'] as $k=>$v) {
                $par[] = ':id'.$k;
                $val['id'.$k] = $v;
            }
            // zdebug($param);
            // die();
            try {
                $sql = "DELETE FROM _user WHERE id IN (".implode(",", $par).")";
                // zdebug($sql);
                // zdebug($val);
                // die();
                $statement = $me->_db_sys->createStatement($sql, $val);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_UserModel_getAllUsersByFilter");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "User deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "User deleted failed"
                ];
            }

            $this->updateUserAutoIncrement();
        }
        return $ret;
    }
}
