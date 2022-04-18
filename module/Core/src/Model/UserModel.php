<?php

namespace Core\Model;

use Interop\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Db\Adapter\Adapter;
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

    public function getUserByUsername(array $param, bool $from_cache = true)
    {
        $me = $this;
        $method = str_replace(["\\", "::"], "_", __METHOD__);
        $salt = "cache-data-" . $method;
        $param = [
            'uname' => $param['username'] ?? '',
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
            // zdebug($param);
            // die();
            $ini_reader = new \Laminas\Config\Reader\Ini();
            $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
            $app_conf = $conf['app-config'];
            if (($app_conf['main_db']??null)==="postgres") {
                $sql = "select * from get_user_by_uname(:uname)";
            } else {
                $sql = "call get_user_by_uname(:uname)";
            }
            $statement = $me->dbSys->createStatement($sql, $param);
            $result = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                $result->getResource()->closeCursor();
                return null;
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                $result->getResource()->closeCursor();
                // !d($tmp);
                if (count($tmp) <= 0) {
                    return null;
                } else {
                    return $tmp[0];
                }
            }
        }
    }

    public function getUserPermissionByUid(array $param = [], bool $from_cache = true)
    {
        $me = $this;
        $method = str_replace(["\\", "::"], "_", __METHOD__);
        $salt = "cache-data-" . $method;
        $param = [
            'uid' => $param['uid'] ?? '',
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
                $sql = "select * from get_userpermission_byuid(:uid)";
            } else {
                $sql = "call get_userpermission_byuid(:uid)";
            }
            $statement = $me->dbSys->createStatement($sql, $param);
            $result = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                $result->getResource()->closeCursor();
                return [];
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                $result->getResource()->closeCursor();
                $me->dataCache->removeItem($key);
                $me->dataCache->setItem($key, json_encode($tmp));
                // !d($tmp);=
                return $tmp;
            }
        }
    }

    public function getUserRolesByUid(array $param = [], bool $from_cache = true)
    {
        $me = $this;
        $method = str_replace(["\\", "::"], "_", __METHOD__);
        $salt = "cache-data-" . $method;
        $param = [
            'uid' => $param['uid'] ?? '',
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
            $sql = "select a.main,b.*,get_routename_by_rid(b.redirect_route) as route_name from _user_role a left join _role b on b.id=a.role where user=:uid and a.status = 1 and b.status = 1";
            $statement = $me->dbSys->createStatement($sql, $param);
            $result = $statement->execute();
            if (!$result instanceof ResultInterface || !$result->isQueryResult()) {
                $result->getResource()->closeCursor();
                return [];
            } else {
                $resultSet = new ResultSet();
                $resultSet->initialize($result);
                $tmp = $resultSet->toArray();
                $result->getResource()->closeCursor();
                $me->dataCache->removeItem($key);
                $me->dataCache->setItem($key, json_encode($tmp));
                // !d($tmp);
                return $tmp;
            }
        }
    }

    public function getUserLDAPByUsername(array $param = [], bool $from_cache = true)
    {
        $me = $this;
        // !d($param);die();
        $param['username2'] = $param['username'];
        try {
            $sql = "INSERT INTO _user (username,full_name,`password`,email,`status`,is_ldap
            ,created_date,updated_date)
            SELECT :username,:full_name,:password, :email, 1, 1, now(), now() from DUAL
            WHERE NOT EXISTS(
                SELECT username from _user WHERE username=:username2 LIMIT 1
            )";
            $statement1 = $me->dbSys->createStatement($sql, $param);
            // !d($sql,$param);//die();
            $result1 = $statement1->execute();
            // !d(get_class_methods($result1));die();
            $result1->getResource()->closeCursor();
        } catch (\Exception $e) {
            // !d($e->getMessage());die();
        }
        $sql = "call get_user_by_uname(:username)";
        $statement2 = $me->dbSys->createStatement($sql, ['username' => $param['username']]);
        $result2 = $statement2->execute();
        if (!$result2 instanceof ResultInterface || !$result2->isQueryResult()) {
            $result2->getResource()->closeCursor();
            return null;
        } else {
            $resultSet = new ResultSet();
            $resultSet->initialize($result2);
            $tmp = $resultSet->toArray();
            // !d(get_class_methods($resultSet));die();
            $result2->getResource()->closeCursor();
            // !d($tmp);
            if (count($tmp) <= 0) {
                return null;
            } else {
                try {
                    $sql = "INSERT IGNORE INTO _user_role (user,role,`status`,main)
                    VALUES (:uid,:rid,1, 1)";
                    $statement3 = $me->dbSys->createStatement($sql, [
                        'uid' => $tmp[0]['id'],
                        'rid' => 7,
                    ]);
                    // !d($sql,$tmp);die();
                    $result3 = $statement3->execute();
                    $result3->getResource()->closeCursor();

                    // !d($result);die();
                } catch (\Exception $e) {
                    // !d($e->getMessage());die();
                }
                return $tmp[0];
            }
        }
    }

    public function addUserFromFile(array $param)
    {
        $me = $this;
        $file = $param['file'] ?? '';
        $ret = [
            'ret' => false,
            'success' => 0,
            'fail' => 0,
            'total' => 0,
        ];
        if ($file !== '' && file_exists($file)) {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly(["user", "user_role"]);
            $reader->setReadFilter(new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
                public function readCell($column, $row, $worksheetName = '')
                {
                    $cols = [];
                    $maxrow = 0;
                    if ($worksheetName == "user") {
                        $cols = range('A', 'L');
                        $maxrow = 1000;
                    } elseif ($worksheetName == "user_role") {
                        $cols = range('A', 'D');
                        $maxrow = 10000;
                    }

                    if ($row <= $maxrow && in_array($column, $cols)) {
                        return true;
                    } else {
                        return false;
                    }
                }
            });
            try {
                // !d($file);
                $spreadsheet = $reader->load($file);
                $spreadsheet->setActiveSheetIndexByName('user');
                $worksheet = $spreadsheet->getActiveSheet();
                $field = [];
                // foreach ($worksheet->getRowIterator() as $row) {
                //     !d(get_class_methods($row));
                //     $cellIterator = $row->getCellIterator();
                //     // $cellIterator->setIterateOnlyExistingCells(true);
                //     foreach ($cellIterator as $cell) {
                //         // !d(get_class_methods($cell));
                //         !d($cell->getValue(),$cell->getRow(),$cell->getColumn(),$cell->getCoordinate());
                //     }
                // }
                $data = $spreadsheet->getActiveSheet()->toArray();
                // !d($data);
                // !d($spreadsheet->getActiveSheet()->toArray());
                $ret['ret'] = true;
                if (count($data) > 1) {
                    $spreadsheet->setActiveSheetIndexByName('user_role');
                    $worksheet = $spreadsheet->getActiveSheet();
                    $data2 = $spreadsheet->getActiveSheet()->toArray();
                    unset($data2[0]);
                    // !d($data2);//die();
                    $user_role = [];
                    $list_role = [];
                    foreach ($data2 as $v) {
                        $user_role[$v[0]][] = $v;
                        if (!in_array("'" . $v['1'] . "'", $list_role)) {
                            $list_role[] = "'" . $v['1'] . "'";
                        }
                    }
                    if (count($list_role) > 0) {
                        $sql = "select code,id from _role where code in (" . implode(",", $list_role) . ")";
                        // die($sql);
                        $stmt = $me->dbSys->createStatement($sql);
                        $result = $stmt->execute();
                        if ($result->valid()) {
                            $resultSet = new ResultSet();
                            $resultSet->initialize($result);
                            $tmp = $resultSet->toArray();
                            $result->getResource()->closeCursor();
                            $list_role = [];
                            foreach ($tmp as $v) {
                                $list_role[$v['code']] = $v['id'];
                            }
                        }
                    }
                    // !d($user_role,$list_role);die();

                    $field = [];
                    $field2 = [];
                    $field3 = $data[0];
                    foreach ($data[0] as $k => $v) {
                        $field2[$k] = ":" . $v;
                        $field[$k] = "`" . $v . '`';
                    }
                    unset($data[0]);
                    $sql = "INSERT INTO _user (" . implode(", ", $field) . ") VALUES (" . implode(", ", $field2) . ")";

                    foreach ($data as $k => $v) {
                        $ret['total']++;
                        if (($v[1] ?? null) === null || ($v[1] ?? '') === '') {
                            $ret['fail']++;
                            continue;
                        }
                        // die($sql);
                        $pass = $v[2] ?? $v[1];
                        $pass = ifNullEmpty($pass, $v[1]);
                        $crypt = new Bcrypt();
                        $passwordHash = $crypt->create($pass);
                        $v[2] = $passwordHash;
                        $is_ldap = $v[3] ?? 0;
                        $is_ldap = ifNullEmpty($is_ldap, 0);
                        $v[3] = $is_ldap;
                        $status = $v[5] ?? 0;
                        $status = ifNullEmpty($status, 0);
                        $v[5] = $status;
                        try {
                            $par = array_combine($field3, $v);
                            // !d($sql,$par);//die();
                            $stmt = $me->dbSys->createStatement($sql, $par);
                            // !d($stmt);die();
                            $result = $stmt->execute(); // die("ok");
                            // !d($sql,$par,$result);die();
                            // !d($result->valid());die();
                            // !d($user_role);
                            // !d($par['username']);
                            if ($result->valid()) {
                                $result->getResource()->closeCursor();
                                $ret['success']++;
                                // $uid = 0;
                                $uid = $result->getGeneratedValue();
                                // !d($uid);
                                $sql2 = "INSERT INTO _user_role (`user`,`role`,`status`,`main`) VALUES
                                (" . $uid . ",:role,:status,:main)";
                                if (isset($user_role[$par['username']]) && count($user_role[$par['username']]) > 0) {
                                    $foundmain = false;
                                    foreach ($user_role[$par['username']] as $k2 => $v2) {
                                        // !d($v2); //die();
                                        if (isset($list_role[$v2[1]])) {
                                            $status = $v2[2] ?? 0;
                                            $status = ifNullEmpty($status, 0);
                                            $main = $v2[3] ?? 0;
                                            // !d($main); //die();
                                            $main = ifNullEmpty($main, 0);
                                            // !d($main); //die();

                                            if ($foundmain) {
                                                $main = 0;
                                            }

                                            if ((int) $main === 1) {
                                                $foundmain = true;
                                            }

                                            $par2 = [
                                                'role' => $list_role[$v2[1]],
                                                'status' => $status,
                                                'main' => $main,
                                            ];
                                            // !d($par2);die();
                                            try {
                                                // !d($sql,$par2);//die();
                                                $stmt = $me->dbSys->createStatement($sql2, $par2);
                                                $result = $stmt->execute(); // die("ok");
                                            } catch (\Exception $e) {
                                                // !d($e->getMessage());die();
                                                // $ret['fail']++;
                                            }
                                            $result->getResource()->closeCursor();
                                        }
                                    }
                                }
                                // !d($sql,$par,$result);die();
                                //insert _user_role
                            } else {
                                $ret['fail']++;
                            }
                            $result->getResource()->closeCursor();
                        } catch (\Exception $e) {
                            // !d($e->getMessage());die();
                            $ret['fail']++;
                        }
                    }
                }
            } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            }
        }

        return $ret;
    }

    public function blockUserByUsername(string $uname)
    {
        $me = $this;
        $ret = [
            "msg" => "FAILED",
            "ret" => false,
        ];
        if ($uname!==null && $uname!=="") {
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
}
