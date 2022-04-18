<?php
namespace CoreAdmin\Model;

use function _\map;
use Interop\Container\ContainerInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\ResultSet;

class ScriptModel
{
    private $_container;
    private $_config;
    // private $_auth_service;
    // private $_session_manager;
    private $_db_sys;
    private $_data_cache;

    /**
     * __construct
     *
     * @param mixed $_container Laminas Container
     * @param mixed $_config    Laminas Config
     *
     * @return void
     */
    public function __construct(ContainerInterface $_container, $_config)
    {
        $me             = $this;
        $me->_container = $_container;
        $me->_config    = $_config;
        // $me->auth_service    = $_container->get(AuthenticationService::class);
        // $me->session_manager = $_container->get(SessionManager::class);
        $me->_db_sys  = $_container->get("db-sys");

        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $app_conf = $conf['app-config'];
        if (($app_conf['main_db'] ?? null) === "postgres") {
            $session_conf = $conf['session'];
            $me->dbSys->query(
                'SET search_path TO ' . $session_conf['db_schema_name'],
                Adapter::QUERY_MODE_EXECUTE
            );
        }
        $me->_data_cache = $_container->get("data-file");
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
        if (isset($param['cond'])) {
            $tmp = "";
            foreach ($param['cond'] as $k => $v) {
                $tmp_key = str_replace(" ", "_", $k);
                if ($v['condition'] === "equal") {
                    $tmp = $tmp_key;
                    if (count($v['value']) === 1) {
                        $tmp .= '= :' . $tmp_key;
                        $sql_param[$tmp_key] = $v['value'][0];
                    } elseif (count($v['value']) > 1) {
                        $tmp .= " IN (";
                        $tmp2 = [];
                        foreach ($v['value'] as $k2 => $v2) {
                            $tmp_key2             = $tmp_key . $k2;
                            $tmp2[]               = ":" . $tmp_key2;
                            $sql_param[$tmp_key2] = $v2;
                        }
                        $tmp .= implode(", ", $tmp2) . ")";
                    }
                    $cond[] = $tmp;
                } elseif ($v['condition'] === "notequal") {
                    $tmp = $tmp_key;
                    if (count($v['value']) === 1) {
                        $tmp .= '!= :' . $tmp_key;
                        $sql_param[$tmp_key] = $v['value'][0];
                    } elseif (count($v['value']) > 1) {
                        $tmp .= " NOT IN (";
                        $tmp2 = [];
                        foreach ($v['value'] as $k2 => $v2) {
                            $tmp_key2             = $tmp_key . $k2;
                            $tmp2[]               = ":" . $tmp_key2;
                            $sql_param[$tmp_key2] = $v2;
                        }
                        $tmp .= implode(", ", $tmp2) . ")";
                    }
                    $cond[] = $tmp;
                } elseif ($v['condition'] === "like") {
                    $tmp  = "(";
                    $tmp2 = [];
                    foreach ($v['value'] as $k2 => $v2) {
                        $tmp_key2             = $tmp_key . $k2;
                        $tmp2[]               = $tmp_key . " LIKE :" . $tmp_key2;
                        $sql_param[$tmp_key2] = "%" . $v2 . "%";
                    }
                    $tmp .= implode(" OR ", $tmp2) . ")";
                    $cond[] = $tmp;
                } elseif ($v['condition'] === "notlike") {
                    $tmp  = "(";
                    $tmp2 = [];
                    foreach ($v['value'] as $k2 => $v2) {
                        $tmp_key2             = $tmp_key . $k2;
                        $tmp2[]               = $tmp_key . " NOT LIKE :" . $tmp_key2;
                        $sql_param[$tmp_key2] = "%" . $v2 . "%";
                    }
                    $tmp .= implode(" AND ", $tmp2) . ")";
                    $cond[] = $tmp;
                } elseif ($v['condition'] === "suffix") {
                    $tmp  = "(";
                    $tmp2 = [];
                    foreach ($v['value'] as $k2 => $v2) {
                        $tmp_key2             = $tmp_key . $k2;
                        $tmp2[]               = $tmp_key . " LIKE :" . $tmp_key2;
                        $sql_param[$tmp_key2] = "%" . $v2;
                    }
                    $tmp .= implode(" OR ", $tmp2) . ")";
                    $cond[] = $tmp;
                } elseif ($v['condition'] === "notsuffix") {
                    $tmp  = "(";
                    $tmp2 = [];
                    foreach ($v['value'] as $k2 => $v2) {
                        $tmp_key2             = $tmp_key . $k2;
                        $tmp2[]               = $tmp_key . " NOT LIKE :" . $tmp_key2;
                        $sql_param[$tmp_key2] = "%" . $v2;
                    }
                    $tmp .= implode(" AND ", $tmp2) . ")";
                    $cond[] = $tmp;
                } elseif ($v['condition'] === "prefix") {
                    $tmp  = "(";
                    $tmp2 = [];
                    foreach ($v['value'] as $k2 => $v2) {
                        $tmp_key2             = $tmp_key . $k2;
                        $tmp2[]               = $tmp_key . " LIKE :" . $tmp_key2;
                        $sql_param[$tmp_key2] = $v2 . "%";
                    }
                    $tmp .= implode(" OR ", $tmp2) . ")";
                    $cond[] = $tmp;
                } elseif ($v['condition'] === "notprefix") {
                    $tmp  = "(";
                    $tmp2 = [];
                    foreach ($v['value'] as $k2 => $v2) {
                        $tmp_key2             = $tmp_key . $k2;
                        $tmp2[]               = $tmp_key . " NOT LIKE :" . $tmp_key2;
                        $sql_param[$tmp_key2] = $v2 . "%";
                    }
                    $tmp .= implode(" AND ", $tmp2) . ")";
                    $cond[] = $tmp;
                }
            }
        }
    }

    public function getRoute(bool $from_cache = true, $par = [])
    {
        $me = $this;
        // die('qqq');
        $method = str_replace(["\\", "::"], "_", __METHOD__);
        $salt = "cache-data-" . $method;
        $param = [
            //   'fields'=>$fields,
            //   'where'=>$where,
            //   'order'=>$order,
            //   'limit'=>$limit,
            //   'offset'=>$offset
        ];
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
            CONCAT_WS('',c.route,b.route, a.route) as route, a.status
            from _route a
            left join _route b on a.parent=b.id
            left join _route c on b.parent=c.id";
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
     * Public getAllRules
     *
     * @param mixed $param      Query Parameter
     * @param mixed $from_cache Data From Cache
     *
     * @return Array|null
     */
    public function getAllModules($param = [], $from_cache = true)
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

        /* ------------------------- Get Data From Cache ------------------------- */
        if ($me->_data_cache->hasItem($key) && $from_cache) {
            $data = $me->_data_cache->getItem($key);
            return json_decode($data, true);
        } else {
            /* ------------------------- Get Data From DB ------------------------ */
            try {
                $sql = "SELECT * from _module where id >2";
                // zdebug($sql);
                // die();
                /* ----------------------- Create Statement ---------------------- */
                $statement      = $me->_db_sys->createStatement($sql, []);
                /* ------------------------ Execute Query ------------------------ */
                $result         = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $isQryResult    = $result->isQueryResult();
                if (!$isResInterface || !$isQryResult) {
                    return null;
                } else {
                    $resultSet = new ResultSet();
                    $resultSet->initialize($result);
                    /* ---------------- Convert Resultset to Array --------------- */
                    $tmp = $resultSet->toArray();
                    /* ------------------ Remove Existing Cache ------------------ */
                    $me->_data_cache->removeItem($key);
                    /* --------------------- Write New Cache --------------------- */
                    $me->_data_cache->setItem($key, json_encode($tmp));
                    return $tmp;
                }
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    public function updateModuleAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_module` )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE `_module` AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function checkModuleNameById($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(name) as total from _module where name=:name and id!=:id";
            $statement = $me->_db_sys->createStatement($sql, $param);
            $result    = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                return false;
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                $tot = (int) $tmp[0]['total'];
                return $tot > 0;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function checkModuleName($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(name) as total from _module where name=:name";
            $statement = $me->_db_sys->createStatement($sql, $param);
            $result    = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                return false;
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                $tot = (int) $tmp[0]['total'];
                return $tot > 0;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function addModule($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['name', 'status', 'session'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkModuleName(['name' => $param['name']])) {
                    $sql = "INSERT INTO _module (name, status, session_name) VALUES(:name, :status, :session)";
                    // zdebug($sql);zdebug($param);die();
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_ScriptModel_getAllModules");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Module added successfully"
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
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Module added failed"
                ];
            }
        }
        return $ret;
    }

    public function updateModule($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'name', 'status', 'session'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                if (!$this->checkModuleNameById(['name' => $param['name'], 'id' => $param['id']])) {
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
                        rmCacheData("CoreAdmin_Model_ScriptModel_getAllModules");
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

    public function updateModuleViaUpload($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'old_name', 'new_name', 'status', 'session_name'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                $validname = false;
                $sql = "";
                $par = $param;
                if ($param['id'] !== null) {
                    $validname = !$this->checkModuleNameById(['name' => $param['new_name'], 'id' => $param['id']]);
                    $sql = "UPDATE _module SET name=:new_name, status=:status, session_name=:session_name WHERE id=:id";
                    unset($par['old_name']);
                } elseif ($param['old_name'] !== null) {
                    $validname = !$this->checkModuleName(['name' => $param['new_name']]);
                    $sql = "UPDATE _module SET name=:new_name, status=:status, session_name=:session_name WHERE name=:old_name";
                    unset($par['id']);
                }
                if ($validname) {
                    // zdebug($sql);zdebug($par); die();
                    $statement = $me->_db_sys->createStatement($sql, $par);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    // zdebug($isResInterface);
                    // zdebug($affectedRow);
                    // die();
                    if (!$isResInterface) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_ScriptModel_getAllModules");
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
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Module updated failed"
                ];
            }
        }
        return $ret;
    }

    public function deleteModuleViaUpload($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'name'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                $validname = false;
                $sql = "";
                $par = $param;
                if ($param['id'] !== null) {
                    $sql = "DELETE FROM _module WHERE id=:id";
                    unset($par['name']);
                } elseif ($param['name'] !== null) {
                    $sql = "DELETE FROM _module WHERE name=:name";
                    unset($par['id']);
                }

                // zdebug($sql);zdebug($par); die();
                $statement = $me->_db_sys->createStatement($sql, $par);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllModules");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Module deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Module deleted failed"
                ];
            }

            $this->updateModuleAutoIncrement();
        }
        return $ret;
    }

    public function updateModuleStatus($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'status'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                $sql = "UPDATE _module SET status=:status WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllModules");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Module updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Module updated failed"
                ];
            }
        }
        return $ret;
    }

    public function deleteModule($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _module WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllModules");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Module deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Module deleted failed"
                ];
            }

            $this->updateModuleAutoIncrement();
        }
        return $ret;
    }

    public function deleteMultiModule($param = [])
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
            foreach ($param['id'] as $k => $v) {
                $par[] = ':id' . $k;
                $val['id' . $k] = $v;
            }
            // zdebug($param);
            // die();
            try {
                $sql = "DELETE FROM _module WHERE id IN (" . implode(",", $par) . ")";
                $statement = $me->_db_sys->createStatement($sql, $val);
                // zdebug($sql);
                // zdebug($val);
                // die();
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllModules");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Module deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Module deleted failed"
                ];
            }

            $this->updateModuleAutoIncrement();
        }
        return $ret;
    }
    public function getAllActions($param = [], $from_cache = true)
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

        /* ------------------------- Get Data From Cache ------------------------- */
        if ($me->_data_cache->hasItem($key) && $from_cache) {
            $data = $me->_data_cache->getItem($key);
            return json_decode($data, true);
        } else {
            /* ------------------------- Get Data From DB ------------------------ */
            try {
                $sql = " SELECT a.id, a.name, c.name as controller , a.controller as controller_id, a.status from `_action` a LEFT JOIN `_controller` c ON a.controller = c.id
                ";
                // zdebug($sql);
                // die();
                /* ----------------------- Create Statement ---------------------- */
                $statement      = $me->_db_sys->createStatement($sql, []);
                /* ------------------------ Execute Query ------------------------ */
                $result         = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $isQryResult    = $result->isQueryResult();
                if (!$isResInterface || !$isQryResult) {
                    return null;
                } else {
                    $resultSet = new ResultSet();
                    $resultSet->initialize($result);
                    /* ---------------- Convert Resultset to Array --------------- */
                    $tmp = $resultSet->toArray();
                    /* ------------------ Remove Existing Cache ------------------ */
                    $me->_data_cache->removeItem($key);
                    /* --------------------- Write New Cache --------------------- */
                    $me->_data_cache->setItem($key, json_encode($tmp));
                    return $tmp;
                }
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    public function checkActionNameById($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(name) as total from _action where name=:name and id!=:id";
            $statement = $me->_db_sys->createStatement($sql, $param);
            $result    = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                return false;
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                $tot = (int) $tmp[0]['total'];
                return $tot > 0;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function checkActionName($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(name) as total from _action where name=:name";
            $statement = $me->_db_sys->createStatement($sql, $param);
            $result    = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                return false;
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                $tot = (int) $tmp[0]['total'];
                return $tot > 0;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function addAction($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['name', 'controller_id', 'status'];
        unset($param['controller']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkActionName(['name' => $param['name']])) {
                    $sql = "INSERT INTO _action (name, controller, status) VALUES(:name, :controller_id, :status)";
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_ScriptModel_getAllActions");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Action added successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Action name has been exists"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Action added failed"
                ];
            }
        }
        return $ret;
    }

    public function updateAction($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'name', 'controller_id', 'status'];
        unset($param['controller']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                if (!$this->checkActionNameById(['name' => $param['name'], 'id' => $param['id']])) {
                    $sql = "UPDATE _action SET name=:name, controller=:controller_id, status=:status WHERE id=:id";
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
                        rmCacheData("CoreAdmin_Model_ScriptModel_getAllActions");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Action updated successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Action name has been exists"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Action updated failed"
                ];
            }
        }
        return $ret;
    }

    public function updateActionStatus($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'status'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                $sql = "UPDATE _action SET status=:status WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllActions");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Action updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Action updated failed"
                ];
            }
        }
        return $ret;
    }

    public function updateActionAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_action` )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE `_action` AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function deleteAction($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _action WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllActions");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Action deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Action deleted failed"
                ];
            }

            $this->updateActionAutoIncrement();
        }
        return $ret;
    }

    public function deleteMultiAction($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            $param['id'] = map($param['id'], function ($v) {
                // zdebug($v);die();
                return (int)$v;
            });
            $val = [];
            $par = [];
            foreach ($param['id'] as $k => $v) {
                $par[] = ':id' . $k;
                $val['id' . $k] = $v;
            }
            // zdebug($param);
            // die();
            try {
                $sql = "DELETE FROM _action WHERE id IN (" . implode(",", $par) . ")";
                $statement = $me->_db_sys->createStatement($sql, $val);
                // zdebug($sql);
                // zdebug($val);
                // die();
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllActions");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Action deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Action deleted failed"
                ];
            }

            $this->updateActionAutoIncrement();
        }
        return $ret;
    }
    public function callController($param = [], $from_cache = true)
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

        /* ------------------------- Get Data From Cache ------------------------- */
        if ($me->_data_cache->hasItem($key) && $from_cache) {
            $data = $me->_data_cache->getItem($key);
            return json_decode($data, true);
        } else {
            /* ------------------------- Get Data From DB ------------------------ */
            try {
                $sql = "SELECT * from _controller";
                // zdebug($sql);
                // die();
                /* ----------------------- Create Statement ---------------------- */
                $statement      = $me->_db_sys->createStatement($sql, []);
                /* ------------------------ Execute Query ------------------------ */
                $result         = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $isQryResult    = $result->isQueryResult();
                if (!$isResInterface || !$isQryResult) {
                    return null;
                } else {
                    $resultSet = new ResultSet();
                    $resultSet->initialize($result);
                    /* ---------------- Convert Resultset to Array --------------- */
                    $tmp = $resultSet->toArray();
                    /* ------------------ Remove Existing Cache ------------------ */
                    $me->_data_cache->removeItem($key);
                    /* --------------------- Write New Cache --------------------- */
                    $me->_data_cache->setItem($key, json_encode($tmp));
                    return $tmp;
                }
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    public function getAllRoutes($param = [], $from_cache = true)
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

        /* ------------------------- Get Data From Cache ------------------------- */
        if ($me->_data_cache->hasItem($key) && $from_cache) {
            $data = $me->_data_cache->getItem($key);
            return json_decode($data, true);
        } else {
            /* ------------------------- Get Data From DB ------------------------ */
            try {
                $sql = "SELECT
                r.id,
                r.name,
                r2.name as parent ,
                r.parent as parent_id,
                r.status ,
                r.`type` ,
                r.route,
                a.name as action,
                r.action as action_id,
                r.title ,
                r.show_title ,
                r.may_terminate ,
                r.is_logging ,
                r.`method` ,
                r.is_caching ,
                l.name as layout ,
                r.layout as layout_id,
                r.is_public ,
                r.is_api
            FROM
                `_route` r
            left join `_route` r2 on
                r.parent = r2.id
            left JOIN `_action` a on
                r.action = a.id
            LEFT JOIN `_layout` l on
                r.layout = l.id
                where r.action is not null";
                // zdebug($sql);
                // die();
                /* ----------------------- Create Statement ---------------------- */
                $statement      = $me->_db_sys->createStatement($sql, []);
                /* ------------------------ Execute Query ------------------------ */
                $result         = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $isQryResult    = $result->isQueryResult();
                if (!$isResInterface || !$isQryResult) {
                    return null;
                } else {
                    $resultSet = new ResultSet();
                    $resultSet->initialize($result);
                    /* ---------------- Convert Resultset to Array --------------- */
                    $tmp = $resultSet->toArray();
                    // !d($tmp);die();
                    /* ------------------ Remove Existing Cache ------------------ */
                    $me->_data_cache->removeItem($key);
                    /* --------------------- Write New Cache --------------------- */
                    $me->_data_cache->setItem($key, json_encode($tmp));
                    return $tmp;
                }
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    public function updateRouteAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_route` )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE `_route` AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function checkRouteNameById($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(name) as total from _route where name=:name and id!=:id";
            $statement = $me->_db_sys->createStatement($sql, $param);
            $result    = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                return false;
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                $tot = (int) $tmp[0]['total'];
                return $tot > 0;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function checkRouteName($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(name) as total from _route where name=:name";
            $statement = $me->_db_sys->createStatement($sql, $param);
            $result    = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                return false;
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                $tot = (int) $tmp[0]['total'];
                return $tot > 0;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function updateRouteStatus($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'status'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                $sql = "UPDATE _route SET status=:status WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllRoutes");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Route updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Route updated failed"
                ];
            }
        }
        return $ret;
    }

    public function addRoute($param = [])
    {
        $param['method'] = json_encode($param['method']);
        $me     = $this;
        $ret    = null;
        unset($param['parent']);
        unset($param['action']);
        unset($param['layout']);
        $fields = ['name', 'parent_id', 'status', 'type', 'route', 'action_id', 'title', 'show_title', 'may_terminate', 'is_logging', 'method', 'is_caching', 'layout_id', 'is_public', 'is_api'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkRouteName(['name' => $param['name']])) {
                    $sql = "INSERT INTO _route (name, parent, status, type, route, action, title, show_title, may_terminate, is_logging, method, is_caching, layout, is_public, is_api) VALUES(:name, :parent_id, :status, :type, :route, :action_id, :title, :show_title, :may_terminate, :is_logging, :method, :is_caching, :layout_id, :is_public, :is_api)";
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_ScriptModel_getAllRoutes");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Route added successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Route name has been exists"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Route added failed"
                ];
            }
        }
        return $ret;
    }

    public function updateRoute($param = [])
    {
        $param['method'] = json_encode($param['method']);
        $me     = $this;
        $ret    = null;
        unset($param['parent']);
        unset($param['action']);
        unset($param['layout']);
        // zdebug($param);die();
        $fields = ['id', 'name', 'parent_id', 'status', 'type', 'route', 'action_id', 'title', 'show_title', 'may_terminate', 'is_logging', 'method', 'is_caching', 'layout_id', 'is_public', 'is_api'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                if (!$this->checkRouteNameById(['name' => $param['name'], 'id' => $param['id']])) {
                    $sql = "UPDATE _route SET name=:name, parent=:parent_id, status=:status, type=:type, route=:route, action=:action_id, title=:title, show_title=:show_title, may_terminate=:may_terminate, is_logging=:is_logging, method=:method, is_caching=:is_caching, layout=:layout_id, is_public=:is_public, is_api=:is_api WHERE id=:id";
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
                        rmCacheData("CoreAdmin_Model_ScriptModel_getAllRoutes");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Route updated successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Route name has been exists"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Action updated failed"
                ];
            }
        }
        return $ret;
    }

    public function updateRouteShow_title($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'show_title'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                $sql = "UPDATE _route SET show_title=:show_title WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllRoutes");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Route updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Route updated failed"
                ];
            }
        }
        return $ret;
    }

    public function updateRouteMay_terminate($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'may_terminate'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                $sql = "UPDATE _route SET may_terminate=:may_terminate WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllRoutes");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Route updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Route updated failed"
                ];
            }
        }
        return $ret;
    }

    public function updateRouteIs_caching($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'is_caching'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                $sql = "UPDATE _route SET is_caching=:is_caching WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllRoutes");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Route updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Route updated failed"
                ];
            }
        }
        return $ret;
    }

    public function updateRouteIs_logging($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'is_logging'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                $sql = "UPDATE _route SET is_logging=:is_logging WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllRoutes");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Route updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Route updated failed"
                ];
            }
        }
        return $ret;
    }

    public function updateRouteIs_public($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'is_public'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                $sql = "UPDATE _route SET is_public=:is_public WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllRoutes");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Route updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Route updated failed"
                ];
            }
        }
        return $ret;
    }
    public function updateRouteIs_api($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'is_api'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                $sql = "UPDATE _route SET is_api=:is_api WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllRoutes");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Route updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Route updated failed"
                ];
            }
        }
        return $ret;
    }

    public function callLayout($param = [], $from_cache = true)
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

        /* ------------------------- Get Data From Cache ------------------------- */
        if ($me->_data_cache->hasItem($key) && $from_cache) {
            $data = $me->_data_cache->getItem($key);
            return json_decode($data, true);
        } else {
            /* ------------------------- Get Data From DB ------------------------ */
            try {
                $sql = "SELECT id, name from _layout";
                // zdebug($sql);
                // die();
                /* ----------------------- Create Statement ---------------------- */
                $statement      = $me->_db_sys->createStatement($sql, []);
                /* ------------------------ Execute Query ------------------------ */
                $result         = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $isQryResult    = $result->isQueryResult();
                if (!$isResInterface || !$isQryResult) {
                    return null;
                } else {
                    $resultSet = new ResultSet();
                    $resultSet->initialize($result);
                    /* ---------------- Convert Resultset to Array --------------- */
                    $tmp = $resultSet->toArray();
                    /* ------------------ Remove Existing Cache ------------------ */
                    $me->_data_cache->removeItem($key);
                    /* --------------------- Write New Cache --------------------- */
                    $me->_data_cache->setItem($key, json_encode($tmp));
                    return $tmp;
                }
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    public function callAction($param = [], $from_cache = true)
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

        /* ------------------------- Get Data From Cache ------------------------- */
        if ($me->_data_cache->hasItem($key) && $from_cache) {
            $data = $me->_data_cache->getItem($key);
            return json_decode($data, true);
        } else {
            /* ------------------------- Get Data From DB ------------------------ */
            try {
                $sql = "SELECT id, name from _action";
                // zdebug($sql);
                // die();
                /* ----------------------- Create Statement ---------------------- */
                $statement      = $me->_db_sys->createStatement($sql, []);
                /* ------------------------ Execute Query ------------------------ */
                $result         = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $isQryResult    = $result->isQueryResult();
                if (!$isResInterface || !$isQryResult) {
                    return null;
                } else {
                    $resultSet = new ResultSet();
                    $resultSet->initialize($result);
                    /* ---------------- Convert Resultset to Array --------------- */
                    $tmp = $resultSet->toArray();
                    /* ------------------ Remove Existing Cache ------------------ */
                    $me->_data_cache->removeItem($key);
                    /* --------------------- Write New Cache --------------------- */
                    $me->_data_cache->setItem($key, json_encode($tmp));
                    return $tmp;
                }
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    public function callParent($param = [], $from_cache = true)
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

        /* ------------------------- Get Data From Cache ------------------------- */
        if ($me->_data_cache->hasItem($key) && $from_cache) {
            $data = $me->_data_cache->getItem($key);
            return json_decode($data, true);
        } else {
            /* ------------------------- Get Data From DB ------------------------ */
            try {
                $sql = "SELECT id, name from `_route`  WHERE action is null";
                // zdebug($sql);
                // die();
                /* ----------------------- Create Statement ---------------------- */
                $statement      = $me->_db_sys->createStatement($sql, []);
                /* ------------------------ Execute Query ------------------------ */
                $result         = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $isQryResult    = $result->isQueryResult();
                if (!$isResInterface || !$isQryResult) {
                    return null;
                } else {
                    $resultSet = new ResultSet();
                    $resultSet->initialize($result);
                    /* ---------------- Convert Resultset to Array --------------- */
                    $tmp = $resultSet->toArray();
                    /* ------------------ Remove Existing Cache ------------------ */
                    $me->_data_cache->removeItem($key);
                    /* --------------------- Write New Cache --------------------- */
                    $me->_data_cache->setItem($key, json_encode($tmp));
                    return $tmp;
                }
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    public function deleteRoute($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _route WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllRoutes");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Route deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Route deleted failed"
                ];
            }

            $this->updateRouteAutoIncrement();
        }
        return $ret;
    }

    public function deleteMultiRoute($param = [])
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
            foreach ($param['id'] as $k => $v) {
                $par[] = ':id' . $k;
                $val['id' . $k] = $v;
            }
            // zdebug($param);
            // die();
            try {
                $sql = "DELETE FROM _route WHERE id IN (" . implode(",", $par) . ")";
                $statement = $me->_db_sys->createStatement($sql, $val);
                // zdebug($sql);
                // zdebug($val);
                // die();
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllRoutes");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Route deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Route deleted failed"
                ];
            }

            $this->updateRouteAutoIncrement();
        }
        return $ret;
    }

    public function getAllController($param = [], $from_cache = true)
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

        /* ------------------------- Get Data From Cache ------------------------- */
        if ($me->_data_cache->hasItem($key) && $from_cache) {
            $data = $me->_data_cache->getItem($key);
            return json_decode($data, true);
        } else {
            /* ------------------------- Get Data From DB ------------------------ */
            try {
                $sql = "select _controller.id, _controller.name, _module.name as module, _controller.module as module_id, _controller.status, _controller.factory
                from `_controller` left join `_module` on _controller.module=_module.id";
                // zdebug($sql);
                // die();
                /* ----------------------- Create Statement ---------------------- */
                $statement      = $me->_db_sys->createStatement($sql, []);
                /* ------------------------ Execute Query ------------------------ */
                $result         = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $isQryResult    = $result->isQueryResult();
                if (!$isResInterface || !$isQryResult) {
                    return null;
                } else {
                    $resultSet = new ResultSet();
                    $resultSet->initialize($result);
                    /* ---------------- Convert Resultset to Array --------------- */
                    $tmp = $resultSet->toArray();
                    /* ------------------ Remove Existing Cache ------------------ */
                    $me->_data_cache->removeItem($key);
                    /* --------------------- Write New Cache --------------------- */
                    $me->_data_cache->setItem($key, json_encode($tmp));
                    return $tmp;
                }
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    public function checkController($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(name) as total from _controller where name=:name and module=:module_id";
            $statement = $me->_db_sys->createStatement($sql, $param);
            $result    = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                return false;
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                $tot = (int) $tmp[0]['total'];
                return $tot > 0;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function checkControllerNameById($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(name) as total from _controller where name=:name and module=:module_id and id!=:id";
            $statement = $me->_db_sys->createStatement($sql, $param);
            $result    = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                return false;
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                $tot = (int) $tmp[0]['total'];
                return $tot > 0;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function addController($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['name', 'module_id', 'status', 'factory'];
        $param["module_id"] = (int) $param["module_id"];
        $param["factory"] = str_replace('_', '\\', $param["factory"]);
        unset($param['module']);
        // zdebug($param);die();
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkController(['name' => $param['name'], 'module_id' => $param["module_id"]])) {
                    $sql = "INSERT INTO _controller (name, module, status, factory) VALUES(:name, :module_id, :status, :factory)";
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_ScriptModel_getAllController");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Controller added successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Controller name has been exists"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Controller added failed"
                ];
            }
        }
        return $ret;
    }

    public function updateController($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['name', 'module_id', 'status', 'factory', 'id'];
        $param["module_id"] = (int) $param["module_id"];
        $param["factory"] = str_replace('_', '\\', $param["factory"]);
        $param["id"] = (int) $param["id"];
        unset($param['module']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkControllerNameById(['name' => $param['name'], 'module_id' => $param['module_id'], 'id' => $param['id']])) {
                    $sql = "UPDATE _controller SET name=:name, module=:module_id, status=:status, factory=:factory WHERE id=:id";
                    // die($sql);
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    // zdebug($sql); zdebug($param); die();
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    // zdebug($isResInterface);
                    // zdebug($affectedRow);
                    // die();
                    if (!$isResInterface) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_ScriptModel_getAllController");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Controller updated successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Controller name has been exists"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Controller updated failed"
                ];
            }
        }
        return $ret;
    }

    public function updateControllerAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_controller` )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE `_controller` AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function deleteController($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _controller WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllController");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Controller deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Controller deleted failed"
                ];
            }

            $this->updateControllerAutoIncrement();
        }
        return $ret;
    }

    public function deleteMultiController($param = [])
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
            foreach ($param['id'] as $k => $v) {
                $par[] = ':id' . $k;
                $val['id' . $k] = $v;
            }
            // zdebug($param);
            // die();
            try {
                $sql = "DELETE FROM _controller WHERE id IN (" . implode(",", $par) . ")";
                $statement = $me->_db_sys->createStatement($sql, $val);
                // zdebug($sql);
                // zdebug($val);
                // die();
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllController");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Controller deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Module controller failed"
                ];
            }

            $this->updateControllerAutoIncrement();
        }
        return $ret;
    }

    public function getModule(bool $from_cache = true, $par = [])
    {
        $me = $this;
        // die('qqq');
        $method = str_replace(["\\", "::"], "_", __METHOD__);
        $salt = "cache-data-" . $method;
        $param = [
            //   'fields'=>$fields,
            //   'where'=>$where,
            //   'order'=>$order,
            //   'limit'=>$limit,
            //   'offset'=>$offset
        ];
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
            $sql = "select * from _module";
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
    // --------------------------------------------------LAYOUT------------
    public function getAllLayout($param = [], $from_cache = true)
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

        /* ------------------------- Get Data From Cache ------------------------- */
        if ($me->_data_cache->hasItem($key) && $from_cache) {
            $data = $me->_data_cache->getItem($key);
            return json_decode($data, true);
        } else {
            /* ------------------------- Get Data From DB ------------------------ */
            try {
                $sql = "select * from _layout";
                // zdebug($sql);
                // die();
                /* ----------------------- Create Statement ---------------------- */
                $statement      = $me->_db_sys->createStatement($sql, []);
                /* ------------------------ Execute Query ------------------------ */
                $result         = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $isQryResult    = $result->isQueryResult();
                if (!$isResInterface || !$isQryResult) {
                    return null;
                } else {
                    $resultSet = new ResultSet();
                    $resultSet->initialize($result);
                    /* ---------------- Convert Resultset to Array --------------- */
                    $tmp = $resultSet->toArray();
                    /* ------------------ Remove Existing Cache ------------------ */
                    $me->_data_cache->removeItem($key);
                    /* --------------------- Write New Cache --------------------- */
                    $me->_data_cache->setItem($key, json_encode($tmp));
                    return $tmp;
                }
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    public function checkLayout($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(name) as total from _layout where name=:name";
            $statement = $me->_db_sys->createStatement($sql, $param);
            $result    = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                return false;
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                $tot = (int) $tmp[0]['total'];
                return $tot > 0;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function checkLayoutNameById($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(name) as total from _layout where name=:name and id!=:id";
            $statement = $me->_db_sys->createStatement($sql, $param);
            $result    = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                return false;
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                $tot = (int) $tmp[0]['total'];
                return $tot > 0;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function addLayout($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['name', 'status'];
        $param["status"] = (int) $param["status"];
        // unset($param['module']);
        // zdebug($param);die();
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkLayout(['name' => $param['name']])) {
                    $sql = "INSERT INTO _layout (name, status) VALUES(:name, :status)";
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_ScriptModel_getAllLayout");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Controller added successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Controller name has been exists"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Controller added failed"
                ];
            }
        }
        return $ret;
    }

    public function updateLayout($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['name', 'status', 'id'];
        $param["status"] = (int) $param["status"];
        $param["id"] = (int) $param["id"];
        // unset($param['module']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkLayoutNameById(['name' => $param['name'], 'id' => $param['id']])) {
                    $sql = "UPDATE _layout SET name=:name, status=:status WHERE id=:id";
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    // zdebug($sql); zdebug($param); die();
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    // zdebug($isResInterface);
                    // zdebug($affectedRow);
                    // die();
                    if (!$isResInterface) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_ScriptModel_getAllLayout");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Layout updated successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Layout name has been exists"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Layout updated failed"
                ];
            }
        }
        return $ret;
    }

    public function updateLayoutAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_layout` )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE `_layout` AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function deleteLayout($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _layout WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllLayout");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Layout deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Layout deleted failed"
                ];
            }

            $this->updateLayoutAutoIncrement();
        }
        return $ret;
    }

    public function deleteMultiLayout($param = [])
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
            foreach ($param['id'] as $k => $v) {
                $par[] = ':id' . $k;
                $val['id' . $k] = $v;
            }
            // zdebug($param);
            // die();
            try {
                $sql = "DELETE FROM _layout WHERE id IN (" . implode(",", $par) . ")";
                $statement = $me->_db_sys->createStatement($sql, $val);
                // zdebug($sql);
                // zdebug($val);
                // die();
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllLayout");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Layout deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Layout controller failed"
                ];
            }

            $this->updateLayoutAutoIncrement();
        }
        return $ret;
    }

    public function updatestatusLayout($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['status', 'id'];
        $param["status"] = (int) $param["status"];
        $param["id"] = (int) $param["id"];
        unset($param['name']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "UPDATE _layout SET status=:status WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                // zdebug($sql); zdebug($param); die();
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                // zdebug($isResInterface);
                // zdebug($affectedRow);
                // die();
                if (!$isResInterface) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllLayout");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Status Layout updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Status Layout updated failed"
                ];
            }
        }
        return $ret;
    }
}
