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

class BusinessModel
{
    private $container;
    private $config;
    private $authService;
    private $sessionManager;
    private $dbSys;
    private $dataCache;
    private $_db_sys;
    private $_data_cache;

    public function __construct(ContainerInterface $_container, $_config)
    {
        $me             = $this;
        $me->_container = $_container;
        $me->_config    = $_config;
        // $me->auth_service    = $_container->get(AuthenticationService::class);
        // $me->session_manager = $_container->get(SessionManager::class);
        $me->_db_sys  = $_container->get("db-sys");
        $me->_data_cache = $_container->get("data-file");
    }

    // --------------------------------------------------LIST BUSNIESS UNIT------------
    public function getAllBusniness($param = [], $from_cache = true)
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
                $sql = "select a.id, a.code, a.name, a.parent as parent_id, b.name as parent, a.status, 
                a.redirect_route, a.redirect_param, a.redirect_query, a.redirect_url, a.bu_level, a.label
                from `_business_unit` a 
                left join `_business_unit` b on a.parent=b.id";

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

                    $route = $me->getRoute();
                    foreach ($tmp as $k => $v) {
                        $key = array_search($v["redirect_route"], array_column($route, 'id'));
                        if ($key !== false) {
                            $tmp[$k]["route"] = $route[$key]["name"];
                        } else {
                            $tmp[$k]["route"] = null;
                        }
                    }
                    return $tmp;
                }
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    public function getParent(bool $from_cache = true, $par = [])
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
            $sql = "select * from `_business_unit`";
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

    public function checkBusiness($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(code) as total from _business_unit where code=:code";
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

    public function checkBusinessNameById($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(code) as total from _business_unit where code=:code and id!=:id";
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

    public function addBusiness($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['code', 'name', 'status', 'parent_id', 'redirect_route', 'redirect_param', 'redirect_query', 'redirect_url', 'bu_level', 'label'];
        unset($param['id']);
        unset($param['parent']);
        // zdebug($param);die();
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkBusiness(['code' => $param['code']])) {
                    $sql = "INSERT INTO _business_unit (code, name, status, parent, redirect_route, redirect_param, redirect_query, redirect_url, bu_level, label) VALUES(:code, :name, :status, :parent_id, :redirect_route, :redirect_param, :redirect_query, :redirect_url, :bu_level, :label)";
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_BusinessModel_getAllBusiness");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Business added successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Business name has been exists"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Business added failed"
                ];
            }
        }
        return $ret;
    }

    public function updateBusiness($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'code', 'name', 'status', 'parent_id', 'redirect_route', 'redirect_param', 'redirect_query', 'redirect_url', 'bu_level', 'label'];
        unset($param['parent']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkBusinessNameById(['code' => $param['code'], 'id' => $param['id']])) {
                    $sql = "UPDATE _business_unit SET code=:code, name=:name, status=:status, 
                    parent=:parent_id, 
                    redirect_route=:redirect_route, redirect_param=:redirect_param, 
                    redirect_query=:redirect_query, redirect_url=:redirect_url,
                    bu_level=:bu_level, 
                    label=:label
                     WHERE id=:id";

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
                        rmCacheData("CoreAdmin_Model_BusinessModel_getAllBusiness");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Business updated successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Business name has been exists"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Business updated failed"
                ];
            }
        }
        return $ret;
    }

    public function updateBusinessAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_business_unit` )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE `_business_unit` AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function deleteBusiness($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _business_unit WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_BusinessModel_getAllBusiness");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Business deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Business deleted failed"
                ];
            }

            $this->updateBusinessAutoIncrement();
        }
        return $ret;
    }

    public function deleteMultiBusiness($param = [])
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
                $sql = "DELETE FROM _business_unit WHERE id IN (" . implode(",", $par) . ")";
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
                    rmCacheData("CoreAdmin_Model_BusinessModel_getAllBusiness");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Business deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Business controller failed"
                ];
            }

            $this->updateBusinessAutoIncrement();
        }
        return $ret;
    }

    public function updatestatusBusiness($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['status', 'id'];
        // $param["status"] = (int) $param["status"];
        // $param["id"] = (int) $param["id"];
        unset($param['name']);
        unset($param['code']);
        unset($param['redirect_route']);
        unset($param['redirect_param']);
        unset($param['redirect_query']);
        unset($param['redirect_url']);
        unset($param['route']);
        unset($param['parent']);
        unset($param['parent_id']);
        unset($param['bu_level']);
        unset($param['label']);

        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "UPDATE _business_unit SET status=:status WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_BusinessModel_getAllBusiness");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Status Business updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                // zdebug($e->getMessage());
                // die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Status Business updated failed"
                ];
            }
        }
        return $ret;
    }

    public function getBulevel(bool $from_cache = true, $par = [])
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
            $sql = "select * from `_bu_level`";
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

    // --------------------------------------------------BUSNIESS UNIT LEVEL BU LEVEL------------
    public function getAllBusninessLevel($param = [], $from_cache = true)
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
                $sql = "select a.id, a.code, a.name, a.parent as parent_id, b.name as parent, a.status
                from `_bu_level` a 
                left join `_bu_level` b on a.parent=b.id";

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

    public function addBusinesslevel($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['code', 'name', 'status', 'parent_id'];
        unset($param['id']);
        unset($param['parent']);
        // zdebug($param);die();
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkBusinesslevel(['code' => $param['code']])) {
                    $sql = "INSERT INTO _bu_level (code, name, status, parent) VALUES(:code, :name, :status, :parent_id)";
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_BusinessModel_getAllBusiness");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Business level added successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Business level has been exists"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Business level added failed"
                ];
            }
        }
        return $ret;
    }

    public function checkBusinesslevel($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(code) as total from _bu_level where code=:code";
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

    public function getParentbulevel(bool $from_cache = true, $par = [])
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
            $sql = "select * from `_bu_level`";
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

    public function updateBusinesslevel($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['code', 'name', 'status', 'parent_id'];
        unset($param['parent']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkBusinesslevelByid(['code' => $param['code'], 'id' => $param['id']])) {
                    $sql = "UPDATE _bu_level SET code=:code, name=:name, status=:status, 
                    parent=:parent_id 
                    WHERE id=:id";

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
                        rmCacheData("CoreAdmin_Model_BusinessModel_getAllBusiness");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Business level updated successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Business level has been exists"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Business level updated failed"
                ];
            }
        }
        return $ret;
    }

    public function checkBusinesslevelByid($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(code) as total from _bu_level where code=:code and id!=:id";
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

    public function deleteBusinesslevel($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _bu_level WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_BusinessModel_getAllBusiness");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Business level deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Business level deleted failed"
                ];
            }

            $this->updateBusinessLevelAutoIncrement();
        }
        return $ret;
    }

    public function updateBusinessLevelAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_bu_level` )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE `_bu_level` AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function updatestatusBusinesslevel($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['status', 'id'];
        // $param["status"] = (int) $param["status"];
        // $param["id"] = (int) $param["id"];
        unset($param['name']);
        unset($param['code']);
        unset($param['parent']);
        unset($param['parent_id']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "UPDATE _bu_level SET status=:status WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_BusinessModel_getAllBusiness");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Status Business level updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                // zdebug($e->getMessage());
                // die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Status Business level updated failed"
                ];
            }
        }
        return $ret;
    }

    public function deleteMultiBusinesslevel($param = [])
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
                $sql = "DELETE FROM _bu_level WHERE id IN (" . implode(",", $par) . ")";
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
                    rmCacheData("CoreAdmin_Model_BusinessModel_getAllBusiness");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Business level deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Business level deleted failed"
                ];
            }

            $this->updateBusinessLevelAutoIncrement();
        }
        return $ret;
    }
}
