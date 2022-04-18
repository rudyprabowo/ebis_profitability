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

class UsermappingModel
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

    // --------------------------------------------------ROUTE USER------------
    public function getAllRouteUser($param = [], $from_cache = true)
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
                $sql = "select a.id, a.user as user_id, a.route as route_id,
                a.layout as layout_id, c.name as layout, a.status
                from `_user_route` a
                left join _user b on a.user=b.id
                left join `_layout` c on a.layout=c.id
                where user=:user_id";
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
            $sql = "select * from _menu";
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

    public function setNewRoute($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['user_id', 'route_id', 'layout_id', 'status'];
        unset($param['layout']);
        unset($param['route']);
        // zdebug($param);die();
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkRouteUser(['route' => $param['route_id'], 'user' => $param['user_id']])) {
                    $sql = "INSERT INTO _user_route (user, route, layout, status) VALUES(:user_id, :route_id, :layout_id, :status)";
                    // zdebug($sql);zdebug($param);die();
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_UsermappingModel_getAllRouteUser");
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

    public function checkRouteUser($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(*) as total from _user_route where route=:route and user=:user";
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

    public function updateRouteUSer($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['user_id', 'route_id', 'layout_id', 'status'];
        unset($param['layout']);
        unset($param['route']);
        // unset($param['route']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkRouteUserNameById(['route' => $param['route_id'], 'user' => $param['user_id'], 'id' => $param['id']])) {
                    unset($param['user_id']);
                    $sql = "UPDATE _user_route SET route=:route_id, layout=:layout_id, status=:status
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
                        rmCacheData("CoreAdmin_Model_UsermappingModel_getAllRouteUser");
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

    public function checkRouteUserNameById($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(*) as total from _user_route where route=:route and user=:user and id!=:id";
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

    public function deleteRouteUser($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _user_route WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_UsermappingModel_getAllRouteUser");
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

            $this->updateUserRouteAutoIncrement();
        }
        return $ret;
    }

    public function updateUserRouteAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_user_route` )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE `_user_route` AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function updatestatusRouteUser($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['status', 'id'];
        // $param["status"] = (int) $param["status"];
        // $param["id"] = (int) $param["id"];
        unset($param['layout_id']);
        unset($param['layout']);
        unset($param['route']);
        unset($param['route_id']);
        unset($param['user_id']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "UPDATE _user_route SET status=:status WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_UsermappingModel_getAllUserRoute");
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

    public function deleteMultiRouteUser($param = [])
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
                $sql = "DELETE FROM _user_route WHERE id IN (" . implode(",", $par) . ")";
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
                    rmCacheData("CoreAdmin_Model_UsermappingModel_getAllRouteUser");
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

            $this->updateUserRouteAutoIncrement();
        }
        return $ret;
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

    // --------------------------------------------------MENU USER------------
    public function getAllMenuUser($param = [], $from_cache = true)
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
                $sql = "select a.id, a.user as user_id, a.menu as menu_id, c.title as menu,
                a.status
                from `_user_menu` a
                left join _user b on a.user=b.id
                left join `_menu` c on a.menu=c.id
                 where user=:user_id";
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

                    // $route = $me->getRoute();
                    // foreach ($tmp as $k => $v) {
                    //     $key = array_search($v["route_id"], array_column($route, 'id'));
                    //     if ($key !== false) {
                    //         $tmp[$k]["route"] = $route[$key]["name"];
                    //     } else {
                    //         $tmp[$k]["route"] = null;
                    //     }
                    // }

                    return $tmp;
                }
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    public function getAllMenu(bool $from_cache = true, $par = [])
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
        $fields = ['user_id', 'menu_id', 'status'];
        unset($param['id']);
        unset($param['menu']);
        // zdebug($param);die();
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkMenuUser(['menu' => $param['menu_id'], 'user' => $param['user_id']])) {
                    $sql = "INSERT INTO _user_menu (user, menu, status) VALUES(:user_id, :menu_id, :status)";
                    // zdebug($sql);zdebug($param);die();
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_UsermappingModel_getAllRouteUser");
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
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Menu added failed"
                ];
            }
        }
        return $ret;
    }

    public function checkMenuUser($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(*) as total from _user_menu where menu=:menu and user=:user";
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

    public function updateMenuUSer($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['user_id', 'menu_id', 'status'];
        unset($param['menu']);
        // unset($param['route']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkMenuUserNameById(['menu' => $param['menu_id'], 'user' => $param['user_id'], 'id' => $param['id']])) {
                    unset($param['user_id']);
                    $sql = "UPDATE _user_menu SET menu=:menu_id, status=:status
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
                        rmCacheData("CoreAdmin_Model_UsermappingModel_getAllRouteUser");
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

    public function checkMenuUserNameById($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(*) as total from _user_menu where menu=:menu and user=:user and id!=:id";
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

    public function deleteMenuUser($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _user_menu WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_UsermappingModel_getAllRouteUser");
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

            $this->updateUserMenuAutoIncrement();
        }
        return $ret;
    }

    public function updateUserMenuAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_user_menu` )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE `_user_menu` AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function updatestatusMenuUser($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['status', 'id'];
        // $param["status"] = (int) $param["status"];
        // $param["id"] = (int) $param["id"];
        unset($param['menu']);
        unset($param['menu_id']);
        unset($param['user_id']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "UPDATE _user_menu SET status=:status WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_UsermappingModel_getAllUserRoute");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Status Menu updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Status Menu updated failed"
                ];
            }
        }
        return $ret;
    }

    public function deleteMultiMenuUser($param = [])
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
                $sql = "DELETE FROM _user_menu WHERE id IN (" . implode(",", $par) . ")";
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
                    rmCacheData("CoreAdmin_Model_UsermappingModel_getAllRouteUser");
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

            $this->updateUserMenuAutoIncrement();
        }
        return $ret;
    }

    ///----------------------SCRIPT USER ---------------------
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

    public function getAllScriptMenu($param = [], $from_cache = true)
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
                $sql = "select a.id, a.user as user_id, b.username , a.module as module_id, c.name as module,
                a.controller as controller_id, d.name as controller,
                a.action as action_id, e.name as action,
                a.layout as layout_id, f.name as layout,
                a.status
                from `_user_script` a
                left join _user b on a.user=b.id
                left join `_module` c on a.module=c.id
                left join `_controller` d on a.controller=d.id
                left join `_action` e on a.action=e.id
                left join `_layout` f on a.layout=f.id
                where a.user=:user_id";
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

    public function setNewScript($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['user_id', 'module_id', 'controller_id', 'action_id', 'layout_id', 'status'];
        unset($param['code']);
        unset($param['controller']);
        unset($param['id']);
        unset($param['layout']);
        unset($param['module']);
        unset($param['username']);
        unset($param['action']);

        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkScriptUser(['module' => $param['module_id'], 'controller' => $param['controller_id'], 'action' => $param['action_id'], 'user' => $param['user_id']])) {
                    $sql = "INSERT INTO _user_script (user, module, controller, action, layout, status)
                    VALUES (:user_id, :module_id, :controller_id, :action_id, :layout_id, :status)";
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
                        rmCacheData("CoreAdmin_Model_UsermappingModel_getAllUser");
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

    public function checkScriptUser($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(*) as total from _user_script where module=:module and controller=:controller and action=:action
            and role=:role where user=:user";
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

    public function updateScriptMenu($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['user_id', 'module_id', 'controller_id', 'action_id', 'layout_id', 'status', 'id'];
        unset($param['code']);
        unset($param['controller']);
        // unset($param['id']);
        unset($param['layout']);
        unset($param['module']);
        unset($param['username']);
        unset($param['action']);
        // unset($param['route']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkScriptUserbyID(['module' => $param['module_id'], 'controller' => $param['controller_id'], 'action' => $param['action_id'], 'role' => $param['user_id'], 'id' => $param['id']])) {
                    unset($param['user_id']);
                    $sql = "UPDATE _user_script SET module=:module_id, controller=:controller_id,
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

    public function checkScriptUserbyID($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(*) as total from _menu_script where module=:module and controller=:controller and action=:action
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

    public function updatestatusScriptUser($param = [])
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
        unset($param['user_id']);
        unset($param['username']);

        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "UPDATE _user_script SET status=:status WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_UsermappingModel_getAllUser");
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

    public function deleteScriptUser($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _user_script WHERE id=:id";
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

            $this->updateScriptUserAutoIncrement();
        }
        return $ret;
    }

    public function updateScriptUserAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_user_script` )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE `_user_script` AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function deleteMultiScriptUser($param = [])
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
                $sql = "DELETE FROM _user_script WHERE id IN (" . implode(",", $par) . ")";
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

            $this->updateScriptUserAutoIncrement();
        }
        return $ret;
    }

    //----------------------BU USER -----//
    public function getAllBuUser($param = [], $from_cache = true)
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
                $sql = "select a.id, a.user as user_id, b.username, b.full_name, a.bu as bu_id, c.code as code_bu, c.name as name_bu,
                a.start_dt, a.end_dt, a.status, a.main
                from `_user_bu` a
                left join _user b on a.user=b.id
                left join `_business_unit` c on a.bu=c.id
                where a.user=:user_id";
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

    public function getBU(bool $from_cache = true, $par = [])
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
            $sql = "select * from _business_unit";
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

    public function checkBuUser($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(*) as total from _user_bu where bu=:bu where user=:user";
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

    public function setNewBU($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['bu_id', 'user_id', 'start_dt', 'end_dt', 'status', 'main'];
        unset($param['code_bu']);
        unset($param['full_name']);
        unset($param['name_bu']);
        unset($param['username']);

        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkBuUser(['bu' => $param['bu_id'], 'user' => $param['user_id']])) {
                    $sql = "INSERT INTO _user_bu (user, bu, start_dt, end_dt, status, main)
                    VALUES (:user_id, :bu_id, :start_dt, :end_dt, :status, :main)";
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
                        rmCacheData("CoreAdmin_Model_UsermappingModel_getAllUser");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Business Unit set successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Business Unit has been exists"
                    ];
                }
            } catch (\Exception $e) {
                // zdebug($e->getMessage());
                // die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Business Unit added failed"
                ];
            }
        }
        return $ret;
    }

    public function updateBuMenu($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'bu_id', 'user_id', 'start_dt', 'end_dt', 'status', 'main'];
        unset($param['code_bu']);
        unset($param['full_name']);
        unset($param['name_bu']);
        unset($param['username']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkBuUserbyID(['bu' => $param['bu_id'], 'user' => $param['user_id'], 'id' => $param['id']])) {
                    unset($param['user_id']);
                    $sql = "UPDATE _user_bu SET bu=:bu_id, start_dt=:start_dt,
                    end_dt=:end_dt, main=:main, status=:status
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
                            "msg" => "Business Unit updated successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Business Unit has been exists on this role"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Business Unit updated failed"
                ];
            }
        }
        return $ret;
    }

    public function checkBuUserbyID($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(*) as total from _user_bu where bu=:bu where user=:user and id!=:id";
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

    public function deleteBuUser($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _user_bu WHERE id=:id";
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
                        "msg" => "Business Unit deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Business Unit deleted failed"
                ];
            }

            $this->updateBUUserAutoIncrement();
        }
        return $ret;
    }

    public function updateBUUserAutoIncrement()
    {
        try {
            $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_user_bu` )+1";
            $sql2 = "SET @sql = CONCAT('ALTER TABLE `_user_bu` AUTO_INCREMENT = ', @max_id)";
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

    public function updatestatusBuUser($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'status'];
        unset($param['code_bu']);
        unset($param['full_name']);
        unset($param['name_bu']);
        unset($param['username']);
        unset($param['bu_id']);
        unset($param['user_id']);
        unset($param['start_dt']);
        unset($param['end_dt']);
        unset($param['main']);

        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "UPDATE _user_bu SET status=:status WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_UsermappingModel_getAllUser");
                    $ret = [
                        "ret" => true,
                        "code" => 0,
                        "affected_row"    => $result->getAffectedRows(),
                        "generated_value" => $result->getGeneratedValue(),
                        "msg" => "Status Business Unit updated successfully"
                    ];
                }
            } catch (\Exception $e) {
                // zdebug($e->getMessage());
                // die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Status Business Unit updated failed"
                ];
            }
        }
        return $ret;
    }

    public function deleteMultiBuUser($param = [])
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
                $sql = "DELETE FROM _user_bu WHERE id IN (" . implode(",", $par) . ")";
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
                        "msg" => "Business Unit deleted successfully"
                    ];
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => "Business Unit controller failed"
                ];
            }

            $this->updateBUUserAutoIncrement();
        }
        return $ret;
    }
}
