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

class RoleModel
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

    // --------------------------------------------------LIST ROLE------------
    public function getAllRole($param = [], $from_cache = true)
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
                $sql = "select * from _role";
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

    public function checkRole($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(code) as total from _role where code=:code";
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

    public function checkRoleNameById($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(code) as total from _role where code=:code and id!=:id";
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

    public function addRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['code', 'name', 'status', 'redirect_route', 'redirect_param', 'redirect_query', 'redirect_url'];
        unset($param['id']);
        unset($param['route']);
        // zdebug($param);die();
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkRole(['code' => $param['code']])) {
                    $sql = "INSERT INTO _role (code, name, status, redirect_route, redirect_param, redirect_query, redirect_url)
                    VALUES(:code, :name, :status, :redirect_route, :redirect_param, :redirect_query, :redirect_url)";
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Role added successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Role name has been exists"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e);
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Role added failed"
                ];
            }
        }
        return $ret;
    }

    public function updateRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'code', 'name', 'status', 'redirect_route', 'redirect_param', 'redirect_query', 'redirect_url'];
        unset($param['route']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkRoleNameById(['code' => $param['code'], 'id' => $param['id']])) {
                    $sql = "UPDATE _role SET code=:code, name=:name, status=:status,
                    redirect_route=:redirect_route, redirect_param=:redirect_param,
                    redirect_query=:redirect_query, redirect_url=:redirect_url WHERE id=:id";

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
                        rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Role updated successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Role name has been exists"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Role updated failed"
                ];
            }
        }
        return $ret;
    }

    public function updateRoleAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_role` )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE `_role` AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function deleteRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _role WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Role deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Role deleted failed"
                ];
            }

            $this->updateRoleAutoIncrement();
        }
        return $ret;
    }

    public function deleteMultiRole($param = [])
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
                $sql = "DELETE FROM _role WHERE id IN (" . implode(",", $par) . ")";
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
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Role deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Role controller failed"
                ];
            }

            $this->updateRoleAutoIncrement();
        }
        return $ret;
    }

    public function updatestatusRole($param = [])
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

        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "UPDATE _role SET status=:status WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Status Role updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                // zdebug($e->getMessage());
                // die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Status Role updated failed"
                ];
            }
        }
        return $ret;
    }

    public function updateRoleViaUpload($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'old_code', 'new_code', 'name', 'status', 'redirect_route', 'redirect_param', 'redirect_query', 'redirect_url'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                $validname = false;
                $sql = "";
                $par = $param;
                if ($param['id'] !== null) {
                    $validname = !$this->checkRoleNameById(['code' => $param['new_code'], 'id' => $param['id']]);
                    $sql = "UPDATE _role SET code=:new_code, name=:name, status=:status,
                    redirect_route=:redirect_route, redirect_param=:redirect_param,
                    redirect_query=:redirect_query, redirect_url=:redirect_url WHERE id=:id";
                    unset($par['old_code']);
                } elseif ($param['old_code'] !== null) {
                    if ($param['old_code'] == $param['new_code']) {
                        $validname = true;
                    } else {
                        $validname = !$this->checkRole(['code' => $param['new_code']]);
                    }
                    // zdebug($validname);die();
                    $sql = "UPDATE _role SET code=:new_code, name=:name, status=:status,
                    redirect_route=:redirect_route, redirect_param=:redirect_param,
                    redirect_query=:redirect_query, redirect_url=:redirect_url WHERE code=:old_code";
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
                        rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Role updated successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Role name has been exists"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Role updated failed"
                ];
            }
        }
        return $ret;
    }

    public function deleteRoleViaUpload($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'code'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            // zdebug($param);die();
            try {
                $validname = false;
                $sql = "";
                $par = $param;
                if ($param['id'] !== null) {
                    $sql = "DELETE FROM _role WHERE id=:id";
                    unset($par['code']);
                } elseif ($param['code'] !== null) {
                    $sql = "DELETE FROM _role WHERE code=:code";
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
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRoles");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Role deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Role deleted failed"
                ];
            }

            $this->updateRoleAutoIncrement();
        }
        return $ret;
    }

    // --------------------------------------------------USER ROLE------------
    public function getAllUserRole($param = [], $from_cache = true)
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
                $sql = "select a.id, a.user as user_id, a.role as role_id, c.name as role, a.status, a.main
                from _user_role a
                left join _user b on b.id=a.user
                left join _role c on c.id=a.role
                where a.user = :id ";
                // zdebug($sql);
                // die();
                /* ----------------------- Create Statement ---------------------- */
                $statement      = $me->_db_sys->createStatement($sql, $param);
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

    public function getUser(bool $from_cache = true, $par = [])
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
            $sql = "select * from _user";
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

    public function getRole(bool $from_cache = true, $par = [])
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
            $sql = "select * from _role";
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

    public function setNewRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['user', 'role', 'status', 'main'];
        unset($param['id']);
        // zdebug($param);die();
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkUserRole(['role' => $param['role'], 'user' => $param['user']])) {
                    $sql = "INSERT INTO _user_role (user, role, status, main) VALUES(:user, :role, :status, :main)";
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_RoleModel_getAllUserRole");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Role set successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Role has been exists"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Role added failed"
                ];
            }
        }
        return $ret;
    }

    public function checkUserRole($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(role) as total from _user_role where role=:role and user=:user";
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

    public function updateUserRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'user', 'role', 'status', 'main'];
        // unset($param['route']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkUserRoleNameById(['role' => $param['role'], 'user' => $param['user'], 'id' => $param['id']])) {
                    unset($param['user']);
                    $sql = "UPDATE _user_role SET role=:role, status=:status,
                    main=:main WHERE id=:id";
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
                        rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Role updated successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Role has been exists on this user"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Role updated failed"
                ];
            }
        }
        return $ret;
    }

    public function checkUserRoleNameById($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(role) as total from _user_role where role=:role and user=:user and id!=:id";
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

    public function deleteUserRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _user_role WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Role deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Role deleted failed"
                ];
            }

            $this->updateUserRoleAutoIncrement();
        }
        return $ret;
    }

    public function updateUserRoleAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_user_role` )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE `_user_role` AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function updatestatusUserRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['status', 'id'];
        // $param["status"] = (int) $param["status"];
        // $param["id"] = (int) $param["id"];
        unset($param['main']);
        unset($param['role']);
        unset($param['role_id']);
        unset($param['user_id']);

        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "UPDATE _user_role SET status=:status WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Status Role updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                // zdebug($e->getMessage());
                // die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Status Role updated failed"
                ];
            }
        }
        return $ret;
    }

    public function deleteMultiUserRole($param = [])
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
                $sql = "DELETE FROM _user_role WHERE id IN (" . implode(",", $par) . ")";
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
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Role deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Role controller failed"
                ];
            }

            $this->updateUserRoleAutoIncrement();
        }
        return $ret;
    }

    // --------------------------------------------------MENU ROLE------------
    public function getAllMenuRole($param = [], $from_cache = true)
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
                $sql = "select a.id, a.role as role_id, b.name as role, a.menu as menu_id, c.title as menu_name, a.status
                from _role_menu a
                left join _role b on b.id=a.role
                left join _menu c on c.id=a.menu
                where role=:role_id";
                // zdebug($sql);
                // die();
                /* ----------------------- Create Statement ---------------------- */
                $statement      = $me->_db_sys->createStatement($sql, $param);
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

    public function getMenu(bool $from_cache = true, $par = [])
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
            a.id,CONCAT_WS('/',c.title,b.title, a.title) as title,
            a.status
            from _menu a
            left join _menu b on a.parent=b.id
            left join _menu c on b.parent=c.id";
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

    public function setNewMenu($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['menu_id', 'role_id', 'status'];
        unset($param['menu_name']);
        unset($param['role']);
        // zdebug($param);die();
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkMenuRole(['menu' => $param['menu_id'], 'role' => $param['role_id']])) {
                    $sql = "INSERT INTO _role_menu (role, menu, status) VALUES(:role_id, :menu_id, :status)";
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_RoleModel_getAllUserRole");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Menu set successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Menu has been exists"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Menu added failed"
                ];
            }
        }
        return $ret;
    }

    public function checkMenuRole($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(menu) as total from _role_menu where menu=:menu and role=:role";
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

    public function updateMenuRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['menu_id', 'role_id', 'status'];
        unset($param['menu_name']);
        unset($param['role']);
        // unset($param['route']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkMenuRoleNameById(['menu' => $param['menu_id'], 'role' => $param['role_id'], 'id' => $param['id']])) {
                    unset($param['role_id']);
                    $sql = "UPDATE _role_menu SET menu=:menu_id, status=:status
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
                        rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Menu updated successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Menu has been exists on this role"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Menu updated failed"
                ];
            }
        }
        return $ret;
    }

    public function checkMenuRoleNameById($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(menu) as total from _role_menu where menu=:menu and role=:role and id!=:id";
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

    public function deleteMenuRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _role_menu WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Menu deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Menu deleted failed"
                ];
            }

            $this->updateMenuRoleAutoIncrement();
        }
        return $ret;
    }

    public function updateMenuRoleAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_role_menu` )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE `_role_menu` AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function updatestatusMenuRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['status', 'id'];
        // $param["status"] = (int) $param["status"];
        // $param["id"] = (int) $param["id"];
        unset($param['menu_id']);
        unset($param['menu_name']);
        unset($param['role']);
        unset($param['role_id']);

        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "UPDATE _role_menu SET status=:status WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Status Menu updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                // zdebug($e->getMessage());
                // die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Status Menu updated failed"
                ];
            }
        }
        return $ret;
    }

    public function deleteMultiMenuRole($param = [])
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
                $sql = "DELETE FROM _role_menu WHERE id IN (" . implode(",", $par) . ")";
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
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Menu deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Menu controller failed"
                ];
            }

            $this->updateMenuRoleAutoIncrement();
        }
        return $ret;
    }

    // --------------------------------------------------SCRIPT ROLE------------
    public function getAllScriptRole($param = [], $from_cache = true)
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
                $sql = "select a.id, a.role as role_id, b.code, b.name as role_name, a.module as module_id, c.name as module,
                a.controller as controller_id, d.name as controller,
                a.action as action_id, e.name as action,
                a.layout as layout_id, f.name as layout,
                a.status
                from `_role_script` a
                left join `_role` b on a.role=b.id
                left join `_module` c on a.module=c.id
                left join `_controller` d on a.controller=d.id
                left join `_action` e on a.action=e.id
                left join `_layout` f on a.layout=f.id
                where a.role=:role_id";
                // zdebug($sql);
                // die();
                /* ----------------------- Create Statement ---------------------- */
                $statement      = $me->_db_sys->createStatement($sql, $param);
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

    public function getController(bool $from_cache = true, $par = [])
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
            $sql = "select * from _controller";
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

    public function getAction(bool $from_cache = true, $par = [])
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
            $sql = "select * from _action";
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

    public function getLayout(bool $from_cache = true, $par = [])
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
            $sql = "select * from _layout";
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

    public function setNewScript($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['role_id', 'module_id', 'controller_id', 'action_id', 'layout_id', 'status'];
        unset($param['code']);
        unset($param['controller']);
        unset($param['id']);
        unset($param['layout']);
        unset($param['module']);
        unset($param['role_name']);
        unset($param['action']);

        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkScriptRole(['module' => $param['module_id'], 'controller' => $param['controller_id'], 'action' => $param['action_id'], 'role' => $param['role_id']])) {
                    $sql = "INSERT INTO _role_script (role, module, controller, action, layout, status)
                    VALUES (:role_id, :module_id, :controller_id, :action_id, :layout_id, :status)";
                    // zdebug($param);
                    // zdebug($sql);
                    // die();
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_RoleModel_getAllUserRole");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Script set successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Script has been exists"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Script added failed"
                ];
            }
        }
        return $ret;
    }

    public function checkScriptRole($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(*) as total from _role_script where module=:module and controller=:controller and action=:action
            and role=:role";
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

    public function checkScriptRolebyID($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(*) as total from _role_script where module=:module and controller=:controller and action=:action
            and role=:role and id!=:id";
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

    public function updateScriptRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['role_id', 'module_id', 'controller_id', 'action_id', 'layout_id', 'status', 'id'];
        unset($param['code']);
        unset($param['controller']);
        // unset($param['id']);
        unset($param['layout']);
        unset($param['module']);
        unset($param['role_name']);
        unset($param['action']);
        // unset($param['route']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkScriptRolebyID(['module' => $param['module_id'], 'controller' => $param['controller_id'], 'action' => $param['action_id'], 'role' => $param['role_id'], 'id' => $param['id']])) {
                    unset($param['role_id']);
                    $sql = "UPDATE _role_script SET module=:module_id, controller=:controller_id,
                    action=:action_id, layout=:layout_id, status=:status
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
                        rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Script updated successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Script has been exists on this role"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Script updated failed"
                ];
            }
        }
        return $ret;
    }

    public function deleteScriptRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _role_script WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Script deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Script deleted failed"
                ];
            }

            $this->updateScriptRoleAutoIncrement();
        }
        return $ret;
    }

    public function updateScriptRoleAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_role_script` )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE `_role_script` AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function updatestatusScriptRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['status', 'id'];
        // $param["status"] = (int) $param["status"];
        // $param["id"] = (int) $param["id"];
        unset($param['action']);
        unset($param['action_id']);
        unset($param['code']);
        unset($param['controller']);
        unset($param['controller_id']);
        unset($param['layout']);
        unset($param['layout_id']);
        unset($param['module']);
        unset($param['module_id']);
        unset($param['role_id']);
        unset($param['role_name']);

        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "UPDATE _role_script SET status=:status WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Status Script updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                // zdebug($e->getMessage());
                // die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Status Script updated failed"
                ];
            }
        }
        return $ret;
    }

    public function deleteMultiScriptRole($param = [])
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
                $sql = "DELETE FROM _role_script WHERE id IN (" . implode(",", $par) . ")";
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
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Script deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Script controller failed"
                ];
            }

            $this->updateScriptRoleAutoIncrement();
        }
        return $ret;
    }

    // --------------------------------------------------ROUTE ROLE------------
    public function getAllrouteRole($param = [], $from_cache = true)
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
                $sql = "select a.id, a.role as role_id, b.code, b.name as role, a.route as route_id,
                a.layout as layout_id, c.name as layout,
                a.status
                from `_role_route` a
                left join `_role` b on a.role=b.id
                left join `_layout` c on a.layout=c.id
                where a.role=:role_id";
                // zdebug($sql);
                // die();
                /* ----------------------- Create Statement ---------------------- */
                $statement      = $me->_db_sys->createStatement($sql, $param);
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
                        $key = array_search($v["route_id"], array_column($route, 'id'));
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

    public function setNewRoute($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['role_id', 'route_id', 'layout_id', 'status'];
        unset($param['code']);
        unset($param['id']);
        unset($param['layout']);
        unset($param['role']);
        unset($param['route']);

        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkRouteRole(['route' => $param['route_id'], 'role' => $param['role_id']])) {
                    $sql = "INSERT INTO _role_route (role, route, layout, status)
                    VALUES (:role_id, :route_id, :layout_id, :status)";
                    // zdebug($param);
                    // zdebug($sql);
                    // die();
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_RoleModel_getAllUserRole");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Route set successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Route has been exists"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Route added failed"
                ];
            }
        }
        return $ret;
    }

    public function checkRouteRole($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(*) as total from _role_route where route=:route and role=:role";
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

    public function updateRouteRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['role_id', 'route_id', 'layout_id', 'status', 'id'];
        unset($param['code']);
        unset($param['layout']);
        unset($param['role']);
        unset($param['route']);

        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkRouteRolebyID(['route' => $param['route_id'], 'role' => $param['role_id'], 'id' => $param['id']])) {
                    unset($param['role_id']);
                    $sql = "UPDATE _role_route SET route=:route_id, layout=:layout_id, status=:status
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
                        rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
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
                        "msg" => "Route has been exists on this role"
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

    public function checkRouteRolebyID($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(*) as total from _role_route where route=:route and role=:role and id!=:id";
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

    public function updatestatusRouteRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['status', 'id'];
        unset($param['code']);
        unset($param['layout']);
        unset($param['layout_id']);
        unset($param['role']);
        unset($param['role_id']);
        unset($param['route']);
        unset($param['route_id']);

        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "UPDATE _role_route SET status=:status WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Status Route updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                // zdebug($e->getMessage());
                // die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Status Route updated failed"
                ];
            }
        }
        return $ret;
    }

    public function deleteRouteRole($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _role_route WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
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

            $this->updateRouteRoleAutoIncrement();
        }
        return $ret;
    }

    public function updateRouteRoleAutoIncrement()
    {
        try {
            $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_role_route` )+1";
            $sql2 = "SET @sql = CONCAT('ALTER TABLE `_role_route` AUTO_INCREMENT = ', @max_id)";
            $sql3 = "PREPARE st FROM @sql";
            $sql4 = "EXECUTE st";
            $this->_db_sys->createStatement($sql1, [])->execute();
            $this->_db_sys->createStatement($sql2, [])->execute();
            $this->_db_sys->createStatement($sql3, [])->execute();
            $this->_db_sys->createStatement($sql4, [])->execute();
        } catch (\Exception $e) {
            zdebug($e->getMessage());
            die();
        }
    }

    public function deleteMultiRouteRole($param = [])
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
                $sql = "DELETE FROM _role_route WHERE id IN (" . implode(",", $par) . ")";
                $statement = $me->_db_sys->createStatement($sql, $val);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                // zdebug($isResInterface);
                // zdebug($affectedRow);
                // die();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_RoleModel_getAllRole");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Route deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Route controller failed"
                ];
            }
            $this->updateRouteRoleAutoIncrement();
        }
        return $ret;
    }
}
