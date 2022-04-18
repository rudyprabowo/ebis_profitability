<?php

namespace CoreAdmin\Model;

use Interop\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Session\SessionManager;

class CoreModel
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

    public function getModelAcl(string $module,string $uid, bool $from_cache = true)
    {
        $me = $this;
        $method = str_replace(["\\", "::"], "_", __METHOD__);
        $salt = "cache-data-" . $method;
        $param = [
          'module'=>$module,
          'uid'=>$uid
        ];
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
            $sql = "CALL lamira_sys.get_callmodel_access_by_uid(:module,:uid)";
            $statement = $me->dbSys->createStatement($sql, $param);
            $result = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                $result->getResource()->closeCursor();
                return null;
            } else {
                $tmp = [];
                $data = $result->getResource()->fetchAll();
                foreach ($data as $v){
                    $tmp[$v['model']][] = $v['func'];
                }
                $me->dataCache->removeItem($key);
                $me->dataCache->setItem($key, json_encode($tmp));
                // !d($tmp);die();
                return $tmp;
            }
        }
    }

    public function getAllUserByFilter(bool $from_cache = true,array $fields = ["*"], array $where = [], array $order = [], int $limit = null, int $offset = 0)
    {
        $me = $this;
        $method = str_replace(["\\", "::"], "_", __METHOD__);
        $salt = "cache-data-" . $method;
        $param = [
          'fields'=>$fields,
          'where'=>$where,
          'order'=>$order,
          'limit'=>$limit,
          'offset'=>$offset
        ];
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
            $sql = "select * from _user a
            left join
                (select id as route_id,get_routename_by_rid(id) as redirect_name
                from _route where status = 1 and action is not null) b
                on b.route_id=a.redirect_route
            left join
                (select `user` as pos_user, pos as pos_id, start_dt as pos_start, end_dt as pos_end
                from `_user_pos` where status = 1 and main = 1) c
                on c.pos_user = a.id
            left join
                (select x1.id as pos_id,x1.code as pos_code,x1.name as pos_name,x1.redirect_route as pos_redirect_route,
                get_routename_by_rid(x1.redirect_route) as pos_redirect_name,x1.redirect_param as pos_redirect_param,x1.redirect_query as pos_redirect_query,
                y1.id as pos_parent_id,y1.code as pos_parent_code,y1.name as pos_parent_name from _position_level x1
                left join
                    (select * from _position_level where status = 1) y1
                    on y1.code=x1.parent where x1.status = 1) d
                on d.pos_id=c.pos_id
            left join
                (select `user` as bu_user, bu as bu_id, start_dt as bu_start, end_dt as bu_end
                from `_user_bu` where status = 1 and main = 1) e
                on e.bu_user = a.id
            left join
                (select x2.id as bu_id,x2.code as bu_code,x2.name as bu_name,x2.redirect_route as bu_redirect_route,
                get_routename_by_rid(x2.redirect_route) as bu_redirect_name,x2.redirect_param as bu_redirect_param,x2.redirect_query as bu_redirect_query,z2.*,y2.* from `_business_unit` x2
                left join
                    (select i.id as bu_parent_id,i.code as bu_parent_code,i.name as bu_parent_name,
                    j.id as bulvl_parent_id,j.code as bulvl_parent_code,j.name as bulvl_parent_name from `_business_unit` i
                    left join
                        (select * from `_bu_level` where status  = 1) j
                        on j.id=i.bu_level where i.status = 1) y2
                    on y2.bu_parent_code=x2.parent
                left join
                    (select id as bulvl_id,code as bulvl_code,name as bulvl_name from `_bu_level` where status  = 1) z2
                    on z2.bulvl_id=x2.bu_level where x2.status = 1) f
                on f.bu_id=e.bu_id where a.status!=9";
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

    public function updateUserStatus(int $id,int $status){
        $me = $this;
        $ret = [
            "msg" => "FAILED",
            "ret" => false,
        ];
        if($id!==null && $id!="" && (int)$id>0){
            $param = [
                'status' => $status,
                'id' =>$id
            ];
            // !d($param);die();
            // query
            $sql = "UPDATE _user SET status=:status WHERE id=:id";

            try {
                // create statement
                $statement = $me->dbSys->createStatement($sql, $param);

                //execute statement
                $result = $statement->execute();
                // !d($result->valid());
                if ($result->valid()) { // execute success
                    $row = $result->getAffectedRows(); // get row terdampak
                    $ret = [
                        'ret' => true,
                        'msg' => "Success update data",
                        'affected_row' => $row,
                    ];
                } else {
                    $ret['msg'] = "Failed update data";
                }
            } catch (\Exception $e) {
                $ret['msg'] = $e->getMessage();
            }
        }
        return $ret;
    }

    public function blockUserByUsername(string $uname){
        $me = $this;
        $ret = [
            "msg" => "FAILED",
            "ret" => false,
        ];
        if($uname!==null && $uname!==""){
            $param = [
                'username' => $uname,
                'status' =>3
            ];
            // !d($param);die();
            // query
            $sql = "UPDATE _user SET status=:status WHERE username=:username";

            try {
                // create statement
                $statement = $me->dbSys->createStatement($sql, $param);

                //execute statement
                $result = $statement->execute();
                // !d($result->valid());
                if ($result->valid()) { // execute success
                    $row = $result->getAffectedRows(); // get row terdampak
                    $ret = [
                        'ret' => true,
                        'msg' => "Success update data",
                        'affected_row' => $row,
                    ];
                } else {
                    $ret['msg'] = "Failed update data";
                }
            } catch (\Exception $e) {
                $ret['msg'] = $e->getMessage();
            }
        }
        return $ret;
    }

    public function updateUserLDAP(int $id,int $is_ldap){
        $me = $this;
        $ret = [
            "msg" => "FAILED",
            "ret" => false,
        ];
        if($id!==null && $id!="" && (int)$id>0){
            $param = [
                'is_ldap' => $is_ldap,
                'id' =>$id
            ];
            // !d($param);die();
            // query
            $sql = "UPDATE _user SET is_ldap=:is_ldap WHERE id=:id";

            try {
                // create statement
                $statement = $me->dbSys->createStatement($sql, $param);

                //execute statement
                $result = $statement->execute();
                // !d($result->valid());
                if ($result->valid()) { // execute success
                    $row = $result->getAffectedRows(); // get row terdampak
                    $ret = [
                        'ret' => true,
                        'msg' => "Success update data",
                        'affected_row' => $row,
                    ];
                } else {
                    $ret['msg'] = "Failed update data";
                }
            } catch (\Exception $e) {
                $ret['msg'] = $e->getMessage();
            }
        }
        return $ret;
    }

    public function findUserByUsernameNIKFullname($par = [],bool $from_cache = true){
        $me = $this;
      $method = str_replace(["\\", "::"], "_", __METHOD__);
      $salt = "cache-data-" . $method;
    //   zdebug($par);die();
      $param = $par;
      $param['find'] = "%".$param['find']."%";
    //   zdebug($param);
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
          try{
            $sql = "select * from _user where status = 1 and employ_nik is not null and employ_nik != '' and (username like :find or full_name like :find or employ_nik like :find) limit 10";
            $statement = $me->dbSys->createStatement($sql, $param);
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
        } catch (\Exception $e) {
            if (($_SERVER['APPLICATION_ENV'] ?? "production") === 'development') {
                zdebug($e->getMessage());die();
            }
            return null;
        }
      }
   }

}
