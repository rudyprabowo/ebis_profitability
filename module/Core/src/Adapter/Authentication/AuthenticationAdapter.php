<?php

namespace Core\Adapter\Authentication;

use Core\Model\LayoutModel;
use Core\Model\UserModel;
use function _\each;
use function _\pick;
use function _\reject;
use function _\remove;
use function PHPSTORM_META\map;

use Interop\Container\ContainerInterface;
use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Session\SessionManager;

class AuthenticationAdapter implements AdapterInterface
{
    private $username;
    private $password;
    private $user;
    private $container;
    private $config;
    private $rememberMe;

    public function __construct(ContainerInterface $container, $config)
    {
        $me = $this;
        $me->container = $container;
        $me->config = $config;
        $me->rememberMe = "0";
    }

    /**
     * @inheritDoc
     */
    public function authenticate()
    {
        $me = $this;
        // var_dump(__METHOD__);
        if ($me->username === null || $me->username === "") {
            return new Result(
                Result::FAILURE_CREDENTIAL_INVALID,
                null,
                ['Invalid credentials.']
            );
        } else {
            /** @var UserModel $userModel */
            $userModel = $me->container->get(UserModel::class);
            $user = $userModel->getUserByUsername(['username' => $me->username], false);
            // s($user);
            // die();
            if ($user === null || !is_array($user)) {
                // $auth = $me->authTelkomLdap($me->username,$me->password);
                // if($auth){
                //     $telkomdata = $me->getldap($me->username);
                //     // !d($telkomdata);die();
                //     $crypt = new Bcrypt();
                //     $passwordHash = $crypt->create($me->password);
                //     $user = $userModel->getUserLDAPByUsername([
                //         'username'=>$telkomdata['nik'],
                //         'full_name'=>$telkomdata['fullname'],
                //         'email'=>$telkomdata['mail'],
                //         'password'=>$passwordHash,
                //     ]);
                //     // !d($user);die();
                //     // $user['username'] = $telkomdata['nik'];
                //     // $user['full_name'] = $telkomdata['fullname'];
                //     // $user['email'] = $telkomdata['mail'];
                //     $user['telkomdata'] = $telkomdata;
                //     if($me->rememberMe==="1" || $me->rememberMe==="on"){
                //         /** @var SessionManager $sessionManager */
                //         $sessionManager = $me->container->get(SessionManager::class);
                //         $sessionManager->rememberMe(_REMEMBER_ME_);
                //     }

                //     $roles = $userModel->getUserRolesByUid(['uid'=>$user['id']]);
                //     $permission = $userModel->getUserPermissionByUid(['uid'=>$user['id']]);
                //     // !d($roles,$permission);//die();
                //     $me->restructureSession($user,$roles,$permission);
                //     // !d($user);die();

                //     $me->user = $user;
                //     return new Result(
                //         Result::SUCCESS,
                //         $user,
                //         ['Authenticated successfully.']);
                // }else{
                return new Result(
                    Result::FAILURE_IDENTITY_NOT_FOUND,
                    null,
                    ['Identity not found.']
                );
            // }
            } else {
                // !d($user);die();
                if ((int) $user['status'] === 3) {
                    return new Result(
                        Result::FAILURE_UNCATEGORIZED,
                        null,
                        ['User blocked.']
                    );
                } elseif ((int) $user['status'] !== 1) {
                    return new Result(
                        Result::FAILURE_IDENTITY_NOT_FOUND,
                        null,
                        ['Identity not found.']
                    );
                } else {
                    $auth = false;
                    if ($user['login_method'] === "TELKOM_LDAP") {
                        $auth = $me->authTelkomLdap($me->username, $me->password);
                        // !d($auth);die();
                        if ($auth['result']===true) {
                            $crypt = new Bcrypt();
                            $passwordHash = $crypt->create($me->password);
                            $telkomdata = $auth['telkomdata'];
                            // !d($telkomdata);die();
                            $user = $userModel->getUserLDAPByUsername([
                                'username' => $me->username,
                                'full_name' => $telkomdata['fullname'],
                                'email' => $telkomdata['mail'],
                                'password' => $passwordHash,
                            ]);
                            // !d($user);die();
                            $user['telkomdata'] = $telkomdata;
                        }
                        //else{
                        // $crypt = new Bcrypt();
                        // $passwordHash = $user['password'] ?? "";
                        // $passwordHash2 = $crypt->create($me->password);
                        // $auth = $crypt->verify($me->password, $passwordHash);
                        //}
                        $auth = $auth['result'];
                    } elseif ($user['login_method'] === "TELKOM_API") {
                        $ini_reader = new \Laminas\Config\Reader\Ini();
                        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
                        $login_conf = $conf['login'];
                        $telkom_api_conf = $login_conf['telkom']['api'];
                        $url = $telkom_api_conf['end_point'];
                        $body = [
                            "username"=> $me->username,
                            "password"=> $me->password
                        ];
                        $bodyJSON = json_encode($body);
                        $headers = [
                            'AppsName: '.$telkom_api_conf['name'],
                            'AppsToken: '. $telkom_api_conf['token'],
                            'Content-Type: application/json'
                        ];
                        $ret = postJSON($url, $bodyJSON, $headers);
                        // !d($ret);die();
                        if ($ret!==false) {
                            try {
                                $ret = json_decode($ret, true);
                                // !d($ret);die();
                                if (($ret['code']??0)===200 && ($ret['login']??0)===1 && ($ret['status']??'')==='success') {
                                    $auth = true;
                                } else {
                                    return new Result(
                                        Result::FAILURE_CREDENTIAL_INVALID,
                                        null,
                                        ['Invalid credentials.']
                                    );
                                }
                            } catch (\Exception $e) {
                                // !d($e->getMessage());die();
                                return new Result(
                                    Result::FAILURE,
                                    null,
                                    ['Invalid Auth API response.']
                                );
                            }
                        }
                    } else {
                        $crypt = new Bcrypt();
                        $passwordHash = $user['password'] ?? "";
                        // $passwordHash2 = $crypt->create($me->password);
                        $auth = $crypt->verify($me->password, $passwordHash);
                        // s($auth);
                        // die();
                    }

                    if ($auth) {
                        // $roles = $me->getRoles($user['id']);
                        // $ubis = $me->getUbis($user['id']);
                        // $permission = $me->getPermission($user['id']);
                        // $me->restructureSession($user,$roles,$ubis,$permission);
                        // zdebug($me->rememberMe);die();

                        if ($me->rememberMe === "1" || $me->rememberMe === "on" || $me->rememberMe === "true") {
                            /** @var SessionManager $sessionManager */
                            $sessionManager = $me->container->get(SessionManager::class);
                            $ini_reader = new \Laminas\Config\Reader\Ini();
                            $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
                            $session_conf = $conf['session'];
                            $sessionManager->rememberMe($session_conf['config']['remember_me_seconds']);
                        }

                        // !d($user);die();
                        $roles = $userModel->getUserRolesByUid(['uid' => $user['id']], false);
                        $permission = $userModel->getUserPermissionByUid(['uid' => $user['id']], false);
                        // !d($roles,$permission);//die();
                        // zdebug($roles);//die();
                        // zdebug($permission);
                        // die();
                        // $me->restructureSession($user, $roles, $permission);
                        $me->restructureSimpleSession($user, $roles, $permission);
                        // s($user);
                        // die();

                        $me->user = $user;
                        return new Result(
                            Result::SUCCESS,
                            $user,
                            ['Authenticated successfully.']
                        );
                    } else {
                        return new Result(
                            Result::FAILURE_CREDENTIAL_INVALID,
                            null,
                            ['Invalid credentials.']
                        );
                    }
                }
            }
        }
    }

    public function rebuildAccessRoute($identity)
    {
        // zdebug($identity);die();
        $me = $this;
        /** @var UserModel $userModel */
        $userModel = $me->container->get(UserModel::class);
        $roles = $userModel->getUserRolesByUid(['uid' => $identity['id']], false);
        $permission = $userModel->getUserPermissionByUid(['uid' => $identity['id']], false);
        // !d($roles,$permission);die();
        $filter_roles = ["id","name","main"];
        $tmp_usr['id'] = $identity['id'];
        $tmp_usr['roles'] = [];
        $tmp_usr['mainrole'] = [];
        foreach ($roles as $v) {
            $tmp = array_filter($v, function ($k) use ($filter_roles) {
                return in_array($k, $filter_roles);
            }, ARRAY_FILTER_USE_KEY);

            $tmp_usr['roles'][$tmp['id']] = $tmp;
            if ((int)($tmp['main']??'0')===1) {
                $tmp_usr['mainrole'] = $tmp;
            }
        }
        // // !d($user,$roles,$permission,$tmp_usr,$tmp_roles);die();
        // !d($tmp_usr);die();

        $accessScript = [];
        $accessRoute = [];
        $accessLayout = [];

        $layoutModel = $me->container->get(LayoutModel::class);
        $_layout = $layoutModel->getActiveLayout(false);
        // !d($_layout);die();
        $layout = [];
        foreach ($_layout as $v) {
            $layout[$v['id']] = $v['name'];
        }

        foreach ($permission as $key => $value) {
            // !d($key,$value);die();
            if ($value['role_id'] !== null && !isset($tmp_usr['roles'][$value['role_id']])) {
                continue;
            }

            $route = $value['route_name'];
            if ($route !== null && $route !== '' && !in_array($route, $accessRoute)) {
                $accessRoute[$route] = $layout[$value['layout']] ?? null;
            }
        }
        // !d($tmp_usr, $accessLayout, $accessRoute,$accessScript);die('ddd');

        $dataCache = $me->container->get("data-file");
        $salt = "cache-data-accessLayout";
        $param = [
            'uid' => $tmp_usr['id'] ?? '',
        ];
        $crypt1 = hash('sha1', $salt);
        $crypt2 = hash('sha256', json_encode($param));
        $key =  'accessLayout_' . $crypt1 . '_' . $crypt2;
        // // zdebug($key);
        if ($dataCache->hasItem($key)) {
            $dataCache->removeItem($key);
        }
        $dataCache->setItem($key, json_encode($accessLayout));

        $salt = "cache-data-accessRoute";
        $crypt1 = hash('sha1', $salt);
        $key =  'accessRoute_' . $crypt1 . '_' . $crypt2;
        // zdebug($key);
        if ($dataCache->hasItem($key)) {
            $dataCache->removeItem($key);
        }
        $dataCache->setItem($key, json_encode($accessRoute));

        return json_encode($accessRoute);
    }

    public function rebuildAccessScript($identity)
    {
        // zdebug($identity);die();
        $me = $this;
        /** @var UserModel $userModel */
        $userModel = $me->container->get(UserModel::class);
        $roles = $userModel->getUserRolesByUid(['uid' => $identity['id']], false);
        $permission = $userModel->getUserPermissionByUid(['uid' => $identity['id']], false);
        // !d($roles,$permission);die();
        $filter_roles = ["id","name","main"];
        $tmp_usr['id'] = $identity['id'];
        $tmp_usr['roles'] = [];
        $tmp_usr['mainrole'] = [];
        foreach ($roles as $v) {
            $tmp = array_filter($v, function ($k) use ($filter_roles) {
                return in_array($k, $filter_roles);
            }, ARRAY_FILTER_USE_KEY);

            $tmp_usr['roles'][$tmp['id']] = $tmp;
            if ((int)($tmp['main']??'0')===1) {
                $tmp_usr['mainrole'] = $tmp;
            }
        }
        // // !d($user,$roles,$permission,$tmp_usr,$tmp_roles);die();
        // !d($tmp_usr);die();

        $accessScript = [];
        $accessLayout = [];

        $layoutModel = $me->container->get(LayoutModel::class);
        $_layout = $layoutModel->getActiveLayout(false);
        // !d($_layout);die();
        $layout = [];
        foreach ($_layout as $v) {
            $layout[$v['id']] = $v['name'];
        }

        foreach ($permission as $key => $value) {
            // !d($key,$value);die();
            if ($value['role_id'] !== null && !isset($tmp_usr['roles'][$value['role_id']])) {
                continue;
            }

            $module = $value['module_name'] === null || $value['module_name'] === "0" ? '*' : $value['module_name'];
            if (!isset($accessScript[$module])) {
                $accessScript[$module] = [];
                $accessLayout[$module] = [];
            }

            $controller = $value['control_name'] === null || $value['control_name'] === "0" ? '*' : $value['control_name'];
            if (!isset($accessScript[$module][$controller])) {
                $accessScript[$module][$controller] = [];
                $accessLayout[$module][$controller] = [];
            }

            $action = $value['act_name'] === null || $value['act_name'] === "0" ? '*' : $value['act_name'];
            if (!in_array($action, $accessScript[$module][$controller])) {
                $accessScript[$module][$controller][$action] = $layout[$value['layout']] ?? null;
            }
        }
        // !d($tmp_usr, $accessLayout, $accessRoute,$accessScript);die('ddd');

        $dataCache = $me->container->get("data-file");
        $salt = "cache-data-accessLayout";
        $param = [
            'uid' => $tmp_usr['id'] ?? '',
        ];
        $crypt1 = hash('sha1', $salt);
        $crypt2 = hash('sha256', json_encode($param));
        $key =  'accessLayout_' . $crypt1 . '_' . $crypt2;
        // // zdebug($key);
        if ($dataCache->hasItem($key)) {
            $dataCache->removeItem($key);
        }
        $dataCache->setItem($key, json_encode($accessLayout));

        $salt = "cache-data-accessScript";
        $crypt1 = hash('sha1', $salt);
        $key =  'accessScript_' . $crypt1 . '_' . $crypt2;
        // zdebug($key);
        if ($dataCache->hasItem($key)) {
            $dataCache->removeItem($key);
        }
        $dataCache->setItem($key, json_encode($accessScript));

        return json_encode($accessScript);
    }

    private function restructureSimpleSession(&$user, $roles, $permission)
    {
        $me = $this;
        // $filter_user = ["id","username","full_name","email","is_ldap","mobile_no","employ_nik","spv_nik","telegram_id","pos_id","pos_code","pos_name","bu_id","bu_code","bu_name","bulvl_id","bulvl_code","bulvl_name","bu_parent_id","bu_parent_code","bu_parent_name","bulvlparent_id","bulvlparent_code","bulvlparent_name","loc_id","loc_type","loca_name"];
        $filter_user = ["id","username","full_name","email","is_organic","login_method","mobile_no","employ_nik","spv_nik","telegram_id","pos_id","pos_name","bu_id","bu_name","bulvl_id","bulvl_name","bu_parent_id","bu_parent_name","bulvlparent_id","bulvlparent_name","loc_id","loc_type","loc_name"];
        $tmp_usr = array_filter($user, function ($k) use ($filter_user) {
            return in_array($k, $filter_user);
        }, ARRAY_FILTER_USE_KEY);
        // $filter_roles = ["id","code","name","main"];
        $filter_roles = ["id","name","main"];
        $tmp_usr['roles'] = [];
        $tmp_usr['mainrole'] = [];
        foreach ($roles as $v) {
            $tmp = array_filter($v, function ($k) use ($filter_roles) {
                return in_array($k, $filter_roles);
            }, ARRAY_FILTER_USE_KEY);

            $tmp_usr['roles'][$tmp['id']] = $tmp;
            if ((int)($tmp['main']??'0')===1) {
                $tmp_usr['mainrole'] = $tmp;
            }
        }
        // !d($user,$roles,$permission,$tmp_usr,$tmp_roles);die();
        // !d($tmp_usr);die();

        $accessScript = [];
        $accessRoute = [];
        $accessLayout = [];

        $layoutModel = $me->container->get(LayoutModel::class);
        $_layout = $layoutModel->getActiveLayout(false);
        $layout = [];
        foreach ($_layout as $v) {
            $layout[$v['id']] = $v['name'];
        }

        foreach ($permission as $key => $value) {
            // !d($key,$value);die();
            if ($value['role_id'] !== null && !isset($tmp_usr['roles'][$value['role_id']])) {
                continue;
            }

            $module = $value['module_name'] === null || $value['module_name'] === "0" ? '*' : $value['module_name'];
            if (!isset($accessScript[$module])) {
                $accessScript[$module] = [];
                $accessLayout[$module] = [];
            }

            $controller = $value['control_name'] === null || $value['control_name'] === "0" ? '*' : $value['control_name'];
            if (!isset($accessScript[$module][$controller])) {
                $accessScript[$module][$controller] = [];
                $accessLayout[$module][$controller] = [];
            }

            $action = $value['act_name'] === null || $value['act_name'] === "0" ? '*' : $value['act_name'];
            if (!in_array($action, $accessScript[$module][$controller])) {
                $accessScript[$module][$controller][$action] = $layout[$value['layout']] ?? null;
            }

            $route = $value['route_name'];
            if ($route !== null && $route !== '' && !in_array($route, $accessRoute)) {
                $accessRoute[$route] = $layout[$value['layout']] ?? null;
            }
        }
        // !d($user,$tmp_usr, $accessLayout, $accessRoute,$accessScript);die('ddd');

        $dataCache = $me->container->get("data-file");
        $salt = "cache-data-accessLayout";
        $param = [
            'uid' => $tmp_usr['id'] ?? '',
        ];
        $crypt1 = hash('sha1', $salt);
        $crypt2 = hash('sha256', json_encode($param));
        $key =  'accessLayout_' . $crypt1 . '_' . $crypt2;
        // zdebug($key);
        if ($dataCache->hasItem($key)) {
            $dataCache->removeItem($key);
        }
        $dataCache->setItem($key, json_encode($accessLayout));

        $salt = "cache-data-accessRoute";
        $crypt1 = hash('sha1', $salt);
        $key =  'accessRoute_' . $crypt1 . '_' . $crypt2;
        // zdebug($key);
        if ($dataCache->hasItem($key)) {
            $dataCache->removeItem($key);
        }
        $dataCache->setItem($key, json_encode($accessRoute));

        $salt = "cache-data-accessScript";
        $crypt1 = hash('sha1', $salt);
        $key =  'accessScript_' . $crypt1 . '_' . $crypt2;
        // zdebug($key);
        if ($dataCache->hasItem($key)) {
            $dataCache->removeItem($key);
        }
        $dataCache->setItem($key, json_encode($accessScript));

        $salt = "cache-data-userData";
        $crypt1 = hash('sha1', $salt);
        $key =  'userData_' . $crypt1 . '_' . $crypt2;
        // zdebug($key);
        if ($dataCache->hasItem($key)) {
            $dataCache->removeItem($key);
        }
        $dataCache->setItem($key, json_encode($user));

        $user = $tmp_usr;
    }

    private function restructureSession(&$user, $roles, $permission)
    {
        $me = $this;
        //   Debug::dump($user);//die('ddd');
        //   Debug::dump($roles);//die('ddd');
        // Debug::dump($permission);//die('ddd');

        $layoutModel = $me->container->get(LayoutModel::class);
        $_layout = $layoutModel->getActiveLayout();
        $layout = [];
        foreach ($_layout as $v) {
            $layout[$v['id']] = $v['name'];
        }
        // !d($layout);

        $role = [];
        $accessScript = [];
        $accessRoute = [];
        $accessLayout = [];
        $user['mainrole'] = [];
        foreach ($roles as $key => $value) {
            if ($value['main'] === "1") {
                $user['mainrole'] = $value;
            }
            $role[$value['id']] = $value;
        }

        foreach ($permission as $key => $value) {
            // !d($key,$value);die();
            if ($value['role_id'] !== null && !isset($role[$value['role_id']])) {
                continue;
            }

            $module = $value['module_name'] === null || $value['module_name'] === "0" ? '*' : $value['module_name'];
            if (!isset($accessScript[$module])) {
                $accessScript[$module] = [];
                $accessLayout[$module] = [];
            }

            $controller = $value['control_name'] === null || $value['control_name'] === "0" ? '*' : $value['control_name'];
            if (!isset($accessScript[$module][$controller])) {
                $accessScript[$module][$controller] = [];
                $accessLayout[$module][$controller] = [];
            }

            $action = $value['act_name'] === null || $value['act_name'] === "0" ? '*' : $value['act_name'];
            if (!in_array($action, $accessScript[$module][$controller])) {
                $accessScript[$module][$controller][$action] = $layout[$value['layout']] ?? null;
            }

            $route = $value['route_name'];
            if ($route !== null && $route !== '' && !in_array($route, $accessRoute)) {
                $accessRoute[$route] = $layout[$value['layout']] ?? null;
            }
        }
        // !d($role, $accessLayout, $accessRoute);die('ddd');
        $user['roles'] = $role;
        $user['accessRoute'] = $accessRoute;
        $user['accessScript'] = $accessScript;
        // Debug::dump($user);die('ddd');
    }

    public function authTelkomLdap($user, $pass)
    {
        $auth = [
            "result"=>false,
            "telkomdata"=>[]
        ];
        // Debug::dump($user);
        // Debug::dump($pass);//die();
        // $ldaphost = "ldapnas1.telkom.co.id";
        // $ldaphost = "10.2.12.86";
        $ldapconfig['host'] = 'merahputih.telkom.co.id';
        // $ldapconfig['host']='ldap01a.telkom.co.id';
        // $ldapconfig['host']='ldap.telkom.co.id';
        $ldapconfig['authrealm'] = 'User Intranet Telkom ND';
        try {
            $ds = @ldap_connect($ldapconfig['host']);
            // zdebug($ds);die();
            if ($ds) {
                try {
                    $r = @ldap_search($ds, "", 'uid=' . $user);
                    // $r= @ldap_search($ds, "o=Telkom", "uid=".$user);
                    // zdebug($r);die();
                    if ($r) {
                        $result = @ldap_get_entries($ds, $r);
                        // !d($result,isset($result[0]));die();
                        if (isset($result[0])) {
                            // !d($ds,$user,$pass,$result[0]['dn']);
                            $login = @ldap_bind($ds, $result[0]['dn'], $pass);
                            // !d($login);die();
                            // $login = true; //BYPASS
                            if ($login) {
                                $auth['result'] = true;

                                $info = ldap_get_entries($ds, $r);

                                for ($i = 0; $i < $info["count"]; $i++) {
                                    $dn = $info[$i]["dn"];
                                    $miniemp = $info[$i];
                                }

                                if (empty($miniemp["telkomdivision"][0])) {
                                    $miniemp["telkomdivision"][0] = $miniemp["essdivision"][0] ?? "";
                                }

                                if (empty($miniemp["location"][0])) {
                                    $miniemp["location"][0] = $miniemp["telkomofficecity"][0] ?? "";
                                }
                                $auth["telkomdata"] = array(
                                    'display_name' => $miniemp["displayname"][0] ?? "",
                                    'fullname' => $miniemp["cn"][0] ?? "",
                                    'nik' => $miniemp["employeeid"][0] ?? "",
                                    'religion' => $miniemp["religion"][0] ?? "",
                                    'position' => $miniemp["title"][0] ?? "",
                                    'mail' => $miniemp["mail"][0] ?? "",
                                    'divisi' => $miniemp["telkomdivision"][0] ?? "",
                                    'department' => $miniemp["department"][0] ?? "",
                                    'telkomofficecity' => $miniemp["location"][0] ?? "",
                                    'homepostaladdress' => $miniemp["homepostaladdress"][0] ?? "",
                                    'perusahaan' => 'PT Telkom Indonesia',
                                    'company' => $miniemp["companyname"][0] ?? "",
                                );
                            }
                        }
                    }
                    ldap_close($ds);
                } catch (\Exception $e) {
                    ldap_close($ds);
                }
            }
        } catch (\Exception $e) {
        }

        // Debug::dump($auth);die();
        return $auth;
    }

    public function getldap($nik)
    {
        $userLDAP = trim('401599');
        $passLDAP = trim('R4d1us2020');
        $hostLDAP = "ldap.telkom.co.id";
        $ds = ldap_connect($hostLDAP);

        // $r = ldap_bind($ds, "CN=Minitool,OU=401599,O=Telkom", "R4d1us2020");
        // $sr            =ldap_search($ds, "o=Telkom", "telkomnik=$nik");
        $sr = ldap_search($ds, "o=Telkom", "uid=" . $nik);
        $info = ldap_get_entries($ds, $sr);
        $usercount = ldap_count_entries($ds, $sr);

        for ($i = 0; $i < $info["count"]; $i++) {
            $dn = $info[$i]["dn"];
            $miniemp = $info[$i];
            ldap_close($ds);
        }

        if (empty($miniemp["telkomdivision"][0])) {
            $miniemp["telkomdivision"][0] = $miniemp["essdivision"][0] ?? "";
        }

        if (empty($miniemp["location"][0])) {
            $miniemp["location"][0] = $miniemp["telkomofficecity"][0] ?? "";
        }
        $finemp = array(
            'display_name' => $miniemp["displayname"][0] ?? "",
            'fullname' => $miniemp["cn"][0] ?? "",
            'nik' => $miniemp["employeeid"][0] ?? "",
            'religion' => $miniemp["religion"][0] ?? "",
            'position' => $miniemp["title"][0] ?? "",
            'mail' => $miniemp["mail"][0] ?? "",
            'divisi' => $miniemp["telkomdivision"][0] ?? "",
            'department' => $miniemp["department"][0] ?? "",
            'telkomofficecity' => $miniemp["location"][0] ?? "",
            'homepostaladdress' => $miniemp["homepostaladdress"][0] ?? "",
            'perusahaan' => 'PT Telkom Indonesia',
            'company' => $miniemp["companyname"][0] ?? "",
        );
        // var_dump($finemp);die();

        return $finemp;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        $me = $this;
        return $me->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username): void
    {
        $me = $this;
        $me->username = $username;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        $me = $this;
        return $me->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password): void
    {
        $me = $this;
        $me->password = $password;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        $me = $this;
        return $me->user;
    }

    public function setRememberMe($remember_me)
    {
        $me = $this;
        $me->rememberMe = $remember_me;
    }
}
