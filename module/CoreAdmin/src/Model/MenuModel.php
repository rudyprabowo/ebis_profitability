<?php

namespace CoreAdmin\Model;

use function _\map;
use Interop\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Session\SessionManager;

class MenuModel
{
    private $container;
    private $config;
    // private $dbSys;
    // private $dataCache;
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

        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $app_conf = $conf['app-config'];
        if (($app_conf['main_db'] ?? null) === "postgres") {
            $session_conf = $conf['session'];
            $me->_db_sys->query(
                'SET search_path TO ' . $session_conf['db_schema_name'],
                Adapter::QUERY_MODE_EXECUTE
            );
        }
    }

    // --------------------------------------------------MENU------------
    public function getAllMenu($param = [], $from_cache = true)
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
                $sql = "select a.id, a.module as module_id, b.name as module, a.layout as layout_id, c.name as layout, a.title, 
                a.route as route_id,
                a.param, a.query, a.url,
                a.icon, 
                a.parent as parent_id, g.title as parent, 
                a.status, a.desc, a.priority
                from _menu a 
                left join _module b on a.module=b.id
                left join _layout c on a.layout=c.id
                left join _menu g on a.parent=g.id
                left join _route d on a.route=d.id";

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

    public function getParentMenu(bool $from_cache = true, $par = [])
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
            $sql = "select id, title from _menu where route is NULL ";
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

    public function checkMenu($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(title) as total from _menu where title=:title and module=:module_id";
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

    public function addMenu($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['icon', 'layout_id', 'module_id', 'param', 'parent_id', 'query', 'route_id', 'status', 'title', 'url', 'desc', 'priority'];
        // $param["icon"] = '<i class="' . $param["icon"] . '"></i>';
        unset($param['layout']);
        unset($param['module']);
        unset($param['route']);
        unset($param['id']);
        unset($param['parent']);
        // zdebug($param);die("masuk sini");
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkMenu(['title' => $param['title'], 'module_id' => $param["module_id"]])) {
                    $sql = "INSERT INTO _menu (module, layout, title, route, param, query, url, icon, parent, status, `desc`, priority) VALUES (:module_id, :layout_id, :title, :route_id, :param, :query, :url, :icon, :parent_id, :status, :desc, :priority)";
                    // zdebug($sql);
                    $statement = $me->_db_sys->createStatement($sql, $param);
                    $result    = $statement->execute();
                    $isResInterface = $result instanceof ResultInterface;
                    $affectedRow = $result->getAffectedRows();
                    if (!$isResInterface || $affectedRow < 1) {
                        $ret = null;
                    } else {
                        rmCacheData("CoreAdmin_Model_ScriptModel_getAllMenu");
                        $ret = [
                            "ret" => true,
                            "code" => 0,
                            "affected_row"    => $result->getAffectedRows(),
                            "generated_value" => $result->getGeneratedValue(),
                            "msg" => "Menu added successfully"
                        ];
                    }
                } else {
                    $ret = [
                        "ret" => false,
                        "code" => 1,
                        "msg" => "Menu name has been exists"
                    ];
                }
            } catch (\Exception $e) {
                // zdebug($e->getMessage());die();
                $ret = [
                    "ret" => false,
                    "code" => 2,
                    "msg" => $e->getMessage()
                ];
            }
        }
        return $ret;
    }

    public function checkMenuNameById($param = [])
    {
        $me = $this;
        try {
            $sql       = "select count(title) as total from _menu where title=:title and module=:module_id and id!=:id";
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

    public function updateMenu($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id', 'icon', 'layout_id', 'module_id', 'param', 'parent_id', 'query', 'route_id', 'status', 'title', 'url', 'desc', 'priority'];
        // $param["module_id"] = (int) $param["module_id"];
        unset($param['layout']);
        unset($param['module']);
        unset($param['route']);
        unset($param['parent']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                if (!$this->checkMenuNameById(['title' => $param['title'], 'module_id' => $param["module_id"], 'id' => $param["id"]])) {
                    $sql = "UPDATE _menu SET module=:module_id, layout=:layout_id, title=:title, route=:route_id, param=:param, query=:query, url=:url, icon=:icon, parent=:parent_id, status=:status, `desc`=:desc, priority=:priority WHERE id=:id";
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
                        rmCacheData("CoreAdmin_Model_ScriptModel_getAllMenu");
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
                        "msg" => "Menu name has been exists"
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

    public function updateMenuAutoIncrement()
    {
        $sql1 = "SET @max_id = (SELECT MAX(id) FROM `_menu` )+1";
        $sql2 = "SET @sql = CONCAT('ALTER TABLE `_menu` AUTO_INCREMENT = ', @max_id)";
        $sql3 = "PREPARE st FROM @sql";
        $sql4 = "EXECUTE st";
        $this->_db_sys->createStatement($sql1, [])->execute();
        $this->_db_sys->createStatement($sql2, [])->execute();
        $this->_db_sys->createStatement($sql3, [])->execute();
        $this->_db_sys->createStatement($sql4, [])->execute();
    }

    public function deleteMenu($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['id'];
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "DELETE FROM _menu WHERE id=:id";
                $statement = $me->_db_sys->createStatement($sql, $param);
                $result    = $statement->execute();
                $isResInterface = $result instanceof ResultInterface;
                $affectedRow = $result->getAffectedRows();
                if (!$isResInterface || $affectedRow < 1) {
                    $ret = null;
                } else {
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllMenu");
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

            $this->updateMenuAutoIncrement();
        }
        return $ret;
    }

    public function updatestatusMenu($param = [])
    {
        $me     = $this;
        $ret    = null;
        $fields = ['status', 'id'];
        unset($param['module_id']);
        unset($param['module']);
        unset($param['layout_id']);
        unset($param['layout']);
        unset($param['title']);
        unset($param['route_id']);
        unset($param['route']);
        unset($param['param']);
        unset($param['query']);
        unset($param['url']);
        unset($param['icon']);
        unset($param['parent_id']);
        unset($param['parent']);
        unset($param['desc']);
        unset($param['priority']);
        if (array_keys_exist($param, $fields)) {
            trim_val($param);
            try {
                $sql = "UPDATE _menu SET status=:status WHERE id=:id";
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
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllMenu");
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

    public function deleteMultiMenu($param = [])
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
                $sql = "DELETE FROM _menu WHERE id IN (" . implode(",", $par) . ")";
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
                    rmCacheData("CoreAdmin_Model_ScriptModel_getAllMenu");
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
                    "msg" => "Menu controller failed"
                ];
            }

            $this->updateMenuAutoIncrement();
        }
        return $ret;
    }

    // public function xgetAllMenu(bool $from_cache = true)
    // {
    //     $me = $this;
    //     $method = str_replace(["\\", "::"], "_", __METHOD__);
    //     $salt = "cache-data-" . $method;
    //     $param = [];
    //     $crypt1 = hash('sha1', $salt);
    //     $crypt2 = hash('sha256', json_encode($param));
    //     $key = $method . '_' . $crypt1 . '_' . $crypt2;

    //     if (($_GET['dbcache'] ?? '1') === '0') {
    //         $from_cache = false;
    //     }

    //     if ($me->dataCache->hasItem($key) && $from_cache) {
    //         $data = $me->dataCache->getItem($key);
    //         return json_decode($data, true);
    //     } else {
    //         $sql = "select * from _route";
    //         $statement = $me->dbSys->createStatement($sql, []);
    //         $result = $statement->execute();
    //         if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
    //             $result->getResource()->closeCursor();
    //             return null;
    //         } else {
    //             $resultSet = new ResultSet();
    //             $result->setFetchMode(\PDO::FETCH_NAMED);
    //             // !d($result->current());die();
    //             $resultSet->initialize($result);
    //             $tmp = $resultSet->toArray();
    //             $result->getResource()->closeCursor();
    //             $me->dataCache->removeItem($key);
    //             $me->dataCache->setItem($key, json_encode($tmp));
    //             // !d($tmp);die();
    //             return $tmp;
    //         }
    //     }
    // }

}
