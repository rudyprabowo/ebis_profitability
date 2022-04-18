<?php

declare(strict_types=1);

namespace CoreAdmin\Controller;

use function _\filter;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

/**
 * RoleController

 *
 * @category Controller
 * @package  CoreAdmin\Controller
 * @author   TMA <info@tma.web.id>
 * @license  Open Source License

 * @link     https://web.tma.id

 */
class RoleController extends AbstractActionController
{
    private $_container;
    private $_config;

    /**
     * __construct
     *
     * @param mixed $container Laminas Container
     * @param mixed $config    Laminas Config
     *
     * @return void
     */
    public function __construct($container, $config)
    {
        $me = $this;
        $me->_container = $container;
        $me->_config = $config;
    }

    private function formatUploadedModuleFile($file, $dir)
    {
        // zdebug($dir."/".$file['new_name']);die();
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($dir . "/" . $file['new_name']);
        $reader->setReadDataOnly(true);
        $sheetnames = ['add', 'update', 'remove'];
        $reader->setLoadSheetsOnly($sheetnames);
        /**  Create an Instance of our Read Filter  **/
        $filterSubset = new MyReadFilter();
        $reader->setReadFilter($filterSubset);
        $spreadsheet = $reader->load($dir . "/" . $file['new_name']);
        $addSheet = $spreadsheet->getSheetByName('add');
        $updateSheet = $spreadsheet->getSheetByName('update');
        $removeSheet = $spreadsheet->getSheetByName('remove');
        // zdebug($addSheet->toArray());
        // zdebug($updateSheet->toArray());
        // zdebug($removeSheet->toArray());
        // die();
        $tmp = [
            'add' => [],
            'update' => [],
            'remove' => []
        ];
        $key = [];
        foreach ($addSheet->toArray() as $k => $v) {
            if ($k === 0) {
                $key = $v;
            } else {
                $tmpx = [];
                $valid = true;
                foreach ($v as $k2 => $v2) {
                    if ($key[$k2] === null || $key[$k2] === 'id') {
                        continue;
                    }
                    if ($key[$k2] === 'code' && $v2 === 'SUPADM') {
                        $valid = false;
                        break;
                    }
                    if ($key[$k2] === 'name' && $v2 === 'Super Admin') {
                        $valid = false;
                        break;
                    }
                    if ($key[$k2] === 'status') {
                        $v2 = (int)$v2;
                    }
                    $keyx = $key[$k2];
                    // if ($keyx === "session_name") {
                    //     $keyx = "session";
                    // }
                    $tmpx[$keyx] = $v2;
                }
                if ($valid) {
                    $tmp['add'][] = $tmpx;
                }
            }
        }
        foreach ($updateSheet->toArray() as $k => $v) {
            if ($k === 0) {
                $key = $v;
            } else {
                $tmpx = [];
                $valid = true;
                foreach ($v as $k2 => $v2) {
                    if ($key[$k2] === null) {
                        continue;
                    }
                    if ($key[$k2] === 'id' && ($v2 === 0 || $v2 === '0')) {
                        $valid = false;
                        break;
                    }
                    if ($key[$k2] === 'code' && $v2 === 'SUPADM') {
                        $valid = false;
                        break;
                    }
                    if ($key[$k2] === 'name' && $v2 === 'Super Admin') {
                        $valid = false;
                        break;
                    }
                    if ($key[$k2] === 'status') {
                        $v2 = (int)$v2;
                    }
                    $tmpx[$key[$k2]] = $v2;
                }
                if ($valid) {
                    if (($tmpx['id'] !== null || $tmpx['old_code'] !== null) && $tmpx['new_code'] !== null) {
                        $tmp['update'][] = $tmpx;
                    }
                }
            }
        }
        foreach ($removeSheet->toArray() as $k => $v) {
            if ($k === 0) {
                $key = $v;
            } else {
                $tmpx = [];
                $valid = true;
                foreach ($v as $k2 => $v2) {
                    if ($key[$k2] === null) {
                        continue;
                    }
                    if ($key[$k2] === 'id' && ($v2 === 0 || $v2 === '0')) {
                        $valid = false;
                        break;
                    }
                    if ($key[$k2] === 'code' && $v2 === 'SUPADM') {
                        $valid = false;
                        break;
                    }
                    if ($key[$k2] === 'name' && $v2 === 'Super Admin') {
                        $valid = false;
                        break;
                    }
                    if ($key[$k2] === 'status') {
                        $v2 = (int)$v2;
                    }
                    $tmpx[$key[$k2]] = $v2;
                }
                if ($valid) {
                    if ($tmpx['id'] !== null || $tmpx['code'] !== null) {
                        $tmp['remove'][] = $tmpx;
                    }
                }
            }
        }
        // zdebug($tmp);
        // die();
        return $tmp;
    }

    /**
     * Action layoutAction
     *
     * @return void
     */
    public function roleAction()
    {
        $me = $this;
        $request = $me->getRequest();
        /* --------------------- Check request method is POST -------------------- */
        if ($request->isPost()) {
            /* -------------------------- Default Return ------------------------- */
            $ret = [
                'ret'  => false,
                'msg'  => 'Invalid Request',
                'data' => [],
            ];

            /* ------ Referer must be from /core-admin/manage-script/module ------ */
            $BYPASS = true;
            // zdebug($request->getHeader('Referer'));
            $uri_ref = "";
            if ($request->getHeader('Referer') !== false) {
                $uri_ref = $request->getHeader('Referer')->getUri();
            }
            $uri_serv = $request->getServer()->get('HTTP_ORIGIN')
                . '/core-admin/manage-role/role';
            if ($uri_ref === $uri_serv || $BYPASS) {
                /* ------------------------- Get Route Param ------------------------- */
                $route_param = $me->params()->fromRoute();
                /* ---------------------- Get 'act' Route Param ---------------------- */
                $act = $route_param['act'] ?? null;
                /* -------------------------- Get POST Data -------------------------- */
                $postData = $me->params()->fromPost();
                // zdebug($postData);die();
                /* ------------------------ Get JSON Body ------------------------ */
                if (count($postData) <= 0) {
                    $json = $request->getContent();
                    // zdebug($json);die();
                    if ($json !== null && $json !== "") {
                        try {
                            $postData = json_decode($json, true);
                        } catch (\Exception $e) {
                            // zdebug($e->getMessage());die();
                        }
                    }
                }

                if ($act === 'getall') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_role->getAllRole([], $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_role   = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->addRole($postData);
                } elseif ($act === 'update') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->updateRole($postData);
                } elseif ($act === 'delete') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->deleteRole($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->deleteMultiRole($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->updatestatusRole($postData);
                } elseif ($act === 'upload') {
                    $files = $me->params()->fromFiles();
                    $b_file = new \Bulletproof\Image($files);
                    // zdebug($files);
                    // die("masuk sini");
                    if (isset($files['file']) && $b_file['file']) {
                        $file = $files['file'];
                        if ($file['error'] === 1) {
                            $ret['ret']  = false;
                            $ret['msg']  = "failed";
                        } else {
                            $r_type = [
                                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                                "application/vnd.ms-excel",
                                "text/csv"
                            ];
                            $r_ext = [
                                "xls", "xlsx", "csv"
                            ];
                            $r_size = [0, (1048576 * 10)];
                            $method = str_replace(["\\", "::"], "_", __METHOD__);
                            $crypt1  = hash('sha1', $file['name']);
                            $crypt2  = hash('sha1', $method . $uri_ref);
                            $authService = $me->_container->get(AuthenticationService::class);
                            $identity = $authService->getIdentity();
                            // zdebug($identity);die();
                            $crypt3  = hash('sha1', $identity['id'] . $identity['username']);
                            $new_name = $crypt1 . "_" . $crypt2 . "_" . $crypt3;
                            // zdebug($new_name);
                            // die();
                            $upload_dir = APP_PATH . "data" . DS . "temp";
                            // zdebug($upload_target);
                            // die();
                            $d_file = new \Delight\FileUpload\FileUpload();
                            $d_file->withTargetFilename($new_name);
                            $d_file->withTargetDirectory($upload_dir);
                            $d_file->from('file');
                            $check = checkUploadedFile($file, $d_file, $r_type, $r_size, $r_ext, $new_name, $upload_dir);
                            // zdebug($check);
                            // die();
                            if (!$check['ret']) {
                                $ret  = $check;
                            } else {
                                $data = $me->formatUploadedModuleFile($check['data'], $upload_dir);
                                $mdl_role = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                                // zdebug($data);
                                // die();
                                foreach ($data as $key => $value) {
                                    if ($key === 'add') {
                                        // zdebug($value);die();
                                        foreach ($value as $k => $v) {
                                            // zdebug($v);die();
                                            $mdl_role->addRole($v);
                                        }
                                    } elseif ($key === 'update') {
                                        foreach ($value as $k => $v) {
                                            // zdebug($v);die();
                                            $mdl_role->updateRoleViaUpload($v);
                                        }
                                    } elseif ($key === 'remove') {
                                        foreach ($value as $k => $v) {
                                            // zdebug($v);die();
                                            $mdl_role->deleteRoleViaUpload($v);
                                        }
                                    }
                                }
                                $ret['ret']  = true;
                                $ret['msg']  = "success";
                            }
                        }
                    }
                }
            }

            /* ------------------------- Return JSON Data ------------------------ */
            $view_model = new JsonModel();
            $view_model->setVariables($ret);
            return $view_model;
        } else {
            /* --------------------------- Return View --------------------------- */
            $mdl_script = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
            $mdl_role = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
            $module = $mdl_script->getModule(false);
            $route = $mdl_role->getRoute(false);
            // zdebug($user);die();
            return [
                "module" => $module,
                "route" => $route,
            ];
            // return [];
        }
    }

    /**
     * Action userRoleAction
     *
     * @return void
     */
    public function userRoleAction()
    {
        $me = $this;
        $request = $me->getRequest();
        /* --------------------- Check request method is POST -------------------- */
        if ($request->isPost()) {
            /* -------------------------- Default Return ------------------------- */
            $ret = [
                'ret'  => false,
                'msg'  => 'Invalid Request',
                'data' => [],
            ];

            /* ------ Referer must be from /core-admin/manage-script/module ------ */
            $BYPASS = true;
            // zdebug($request->getHeader('Referer'));
            $uri_ref = "";
            if ($request->getHeader('Referer') !== false) {
                $uri_ref = $request->getHeader('Referer')->getUri();
            }
            $uri_serv = $request->getServer()->get('HTTP_ORIGIN')
                . '/core-admin/manage-role/role';
            if ($uri_ref === $uri_serv || $BYPASS) {
                /* ------------------------- Get Route Param ------------------------- */
                $route_param = $me->params()->fromRoute();
                /* ---------------------- Get 'act' Route Param ---------------------- */
                $act = $route_param['act'] ?? null;
                /* -------------------------- Get POST Data -------------------------- */
                $postData = $me->params()->fromPost();
                // zdebug($postData);die();
                /* ------------------------ Get JSON Body ------------------------ */
                if (count($postData) <= 0) {
                    $json = $request->getContent();
                    // zdebug($json);die();
                    if ($json !== null && $json !== "") {
                        try {
                            $postData = json_decode($json, true);
                        } catch (\Exception $e) {
                            // zdebug($e->getMessage());die();
                        }
                    }
                }

                if ($act === 'getall') {
                    // zdebug($postData);die();
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_role->getAllUserRole($postData, $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_role   = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->setNewRole($postData);
                } elseif ($act === 'update') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->updateUserRole($postData);
                } elseif ($act === 'delete') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->deleteUserRole($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->deleteMultiUserRole($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->updatestatusUserRole($postData);
                } else {
                    // $ret['uid'] = $postData['uid'];
                    $mdl_script = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $mdl_role = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $module = $mdl_script->getModule(false);
                    $route = $mdl_role->getRoute(false);
                    $user = $mdl_role->getUser(false);
                    $role = $mdl_role->getRole(false);
                    return [
                        "uid" => $postData['uid'],
                        "module" => $module,
                        "route" => $route,
                        "user" => $user,
                        "role" => $role,
                    ];
                    // zdebug($postData);die();
                }
            }

            /* ------------------------- Return JSON Data ------------------------ */
            $view_model = new JsonModel();
            $view_model->setVariables($ret);
            return $view_model;
        } else {
            /* --------------------------- Return View --------------------------- */
            $mdl_script = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
            $mdl_role = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
            $module = $mdl_script->getModule(false);
            $route = $mdl_role->getRoute(false);
            $user = $mdl_role->getUser(false);
            $role = $mdl_role->getRole(false);
            // zdebug($route);die();
            return [
                "module" => $module,
                "route" => $route,
                "user" => $user,
                "role" => $role,
            ];
            // return [];
        }
    }

    /**
     * Action menuRoleAction
     *
     * @return void
     */
    public function menuRoleAction()
    {
        $me = $this;
        $request = $me->getRequest();
        /* --------------------- Check request method is POST -------------------- */
        if ($request->isPost()) {
            /* -------------------------- Default Return ------------------------- */
            $ret = [
                'ret'  => false,
                'msg'  => 'Invalid Request',
                'data' => [],
            ];

            /* ------ Referer must be from /core-admin/manage-script/module ------ */
            $BYPASS = true;
            // zdebug($request->getHeader('Referer'));
            $uri_ref = "";
            if ($request->getHeader('Referer') !== false) {
                $uri_ref = $request->getHeader('Referer')->getUri();
            }
            $uri_serv = $request->getServer()->get('HTTP_ORIGIN')
                . '/core-admin/manage-role/role';
            if ($uri_ref === $uri_serv || $BYPASS) {
                /* ------------------------- Get Route Param ------------------------- */
                $route_param = $me->params()->fromRoute();
                /* ---------------------- Get 'act' Route Param ---------------------- */
                $act = $route_param['act'] ?? null;
                /* -------------------------- Get POST Data -------------------------- */
                $postData = $me->params()->fromPost();
                // zdebug($postData);die();
                /* ------------------------ Get JSON Body ------------------------ */
                if (count($postData) <= 0) {
                    $json = $request->getContent();
                    // zdebug($json);die();
                    if ($json !== null && $json !== "") {
                        try {
                            $postData = json_decode($json, true);
                        } catch (\Exception $e) {
                            // zdebug($e->getMessage());die();
                        }
                    }
                }

                if ($act === 'getall') {
                    // zdebug($postData);die();
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_role->getAllMenuRole($postData, $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_role   = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->setNewMenu($postData);
                } elseif ($act === 'update') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->updateMenuRole($postData);
                } elseif ($act === 'delete') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->deleteMenuRole($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->deleteMultiMenuRole($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->updatestatusMenuRole($postData);
                } elseif ($act === 'upload') {
                    $files = $me->params()->fromFiles();
                    $b_file = new \Bulletproof\Image($files);
                    zdebug($files);
                    zdebug($postData);
                    die("masuk sini");
                    if (isset($files['file']) && $b_file['file']) {
                        $file = $files['file'];
                        if ($file['error'] === 1) {
                            $ret['ret']  = false;
                            $ret['msg']  = "failed";
                        } else {
                            $r_type = [
                                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                                "application/vnd.ms-excel",
                                "text/csv"
                            ];
                            $r_ext = [
                                "xls", "xlsx", "csv"
                            ];
                            $r_size = [0, (1048576 * 10)];
                            $method = str_replace(["\\", "::"], "_", __METHOD__);
                            $crypt1  = hash('sha1', $file['name']);
                            $crypt2  = hash('sha1', $method . $uri_ref);
                            $authService = $me->_container->get(AuthenticationService::class);
                            $identity = $authService->getIdentity();
                            // zdebug($identity);die();
                            $crypt3  = hash('sha1', $identity['id'] . $identity['username']);
                            $new_name = $crypt1 . "_" . $crypt2 . "_" . $crypt3;
                            // zdebug($new_name);
                            // die();
                            $upload_dir = APP_PATH . "data" . DS . "temp";
                            // zdebug($upload_target);
                            // die();
                            $d_file = new \Delight\FileUpload\FileUpload();
                            $d_file->withTargetFilename($new_name);
                            $d_file->withTargetDirectory($upload_dir);
                            $d_file->from('file');
                            $check = checkUploadedFile($file, $d_file, $r_type, $r_size, $r_ext, $new_name, $upload_dir);
                            // zdebug($check);
                            // die();
                            if (!$check['ret']) {
                                $ret  = $check;
                            } else {
                                $data = $me->formatUploadedModuleFile($check['data'], $upload_dir);
                                $mdl_role = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                                // zdebug($data);
                                // die();
                                foreach ($data as $key => $value) {
                                    if ($key === 'add') {
                                        // zdebug($value);die();
                                        foreach ($value as $k => $v) {
                                            // zdebug($v);die();
                                            $mdl_role->addRole($v);
                                        }
                                    } elseif ($key === 'update') {
                                        foreach ($value as $k => $v) {
                                            // zdebug($v);die();
                                            $mdl_role->updateRoleViaUpload($v);
                                        }
                                    } elseif ($key === 'remove') {
                                        foreach ($value as $k => $v) {
                                            // zdebug($v);die();
                                            $mdl_role->deleteRoleViaUpload($v);
                                        }
                                    }
                                }
                                $ret['ret']  = true;
                                $ret['msg']  = "success";
                            }
                        }
                    }
                } else {
                    $rid = $postData["rid"];
                    $mdl_script = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $mdl_role = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $module = $mdl_script->getModule(false);
                    $route = $mdl_role->getRoute(false);
                    $user = $mdl_role->getUser(false);
                    $role = $mdl_role->getRole(false);
                    $menu =  $mdl_role->getMenu(false);
                    return [
                        "rid" => $rid,
                        "module" => $module,
                        "route" => $route,
                        "user" => $user,
                        "role" => $role,
                        "menu" => $menu,
                    ];
                }
            }

            /* ------------------------- Return JSON Data ------------------------ */
            $view_model = new JsonModel();
            $view_model->setVariables($ret);
            return $view_model;
        } else {
            /* --------------------------- Return View --------------------------- */
            $mdl_script = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
            $mdl_role = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
            $module = $mdl_script->getModule(false);
            $route = $mdl_role->getRoute(false);
            $user = $mdl_role->getUser(false);
            $role = $mdl_role->getRole(false);
            $menu =  $mdl_role->getMenu(false);
            // zdebug($route);die();
            return [
                "module" => $module,
                "route" => $route,
                "user" => $user,
                "role" => $role,
                "menu" => $menu,
            ];
            // return [];
        }
    }

    /**
     * Action scriptRoleAction
     *
     * @return void
     */
    public function scriptRoleAction()
    {
        $me = $this;
        $request = $me->getRequest();
        /* --------------------- Check request method is POST -------------------- */
        if ($request->isPost()) {
            /* -------------------------- Default Return ------------------------- */
            $ret = [
                'ret'  => false,
                'msg'  => 'Invalid Request',
                'data' => [],
            ];

            /* ------ Referer must be from /core-admin/manage-script/module ------ */
            $BYPASS = true;
            // zdebug($request->getHeader('Referer'));
            $uri_ref = "";
            if ($request->getHeader('Referer') !== false) {
                $uri_ref = $request->getHeader('Referer')->getUri();
            }
            $uri_serv = $request->getServer()->get('HTTP_ORIGIN')
                . '/core-admin/manage-role/role';
            if ($uri_ref === $uri_serv || $BYPASS) {
                /* ------------------------- Get Route Param ------------------------- */
                $route_param = $me->params()->fromRoute();
                /* ---------------------- Get 'act' Route Param ---------------------- */
                $act = $route_param['act'] ?? null;
                /* -------------------------- Get POST Data -------------------------- */
                $postData = $me->params()->fromPost();
                // zdebug($postData);die();
                /* ------------------------ Get JSON Body ------------------------ */
                if (count($postData) <= 0) {
                    $json = $request->getContent();
                    // zdebug($json);die();
                    if ($json !== null && $json !== "") {
                        try {
                            $postData = json_decode($json, true);
                        } catch (\Exception $e) {
                            // zdebug($e->getMessage());die();
                        }
                    }
                }

                if ($act === 'getall') {
                    // zdebug($postData);die();
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_role->getAllScriptRole($postData, $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_role   = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->setNewScript($postData);
                } elseif ($act === 'update') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->updateScriptRole($postData);
                } elseif ($act === 'delete') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->deleteScriptRole($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->deleteMultiScriptRole($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->updatestatusScriptRole($postData);
                } else {
                    $rid = $postData["rid"];
                    $mdl_script = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $mdl_role = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $module = $mdl_script->getModule(false);
                    $controller = $mdl_role->getController(false);
                    $action = $mdl_role->getAction(false);
                    $layout = $mdl_role->getLayout(false);
                    $role = $mdl_role->getRole(false);
                    unset($module[0]);
                    unset($module[1]);
                    unset($controller[0]);
                    unset($action[0]);
                    // zdebug($route);die();
                    return [
                        "rid" => $rid,
                        "module" => $module,
                        "controller" => $controller,
                        "action" => $action,
                        "layout" => $layout,
                        "role" => $role,
                    ];
                }
            }

            /* ------------------------- Return JSON Data ------------------------ */
            $view_model = new JsonModel();
            $view_model->setVariables($ret);
            return $view_model;
        } else {
            /* --------------------------- Return View --------------------------- */
            $mdl_script = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
            $mdl_role = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
            $module = $mdl_script->getModule(false);
            $controller = $mdl_role->getController(false);
            $action = $mdl_role->getAction(false);
            $layout = $mdl_role->getLayout(false);
            $role = $mdl_role->getRole(false);
            unset($module[0]);
            unset($module[1]);
            unset($controller[0]);
            unset($action[0]);
            // zdebug($route);die();
            return [
                "module" => $module,
                "controller" => $controller,
                "action" => $action,
                "layout" => $layout,
                "role" => $role,
            ];
            // return [];
        }
    }

    /**
     * Action routeRoleAction
     *
     * @return void
     */
    public function routeRoleAction()
    {
        $me = $this;
        $request = $me->getRequest();
        /* --------------------- Check request method is POST -------------------- */
        if ($request->isPost()) {
            /* -------------------------- Default Return ------------------------- */
            $ret = [
                'ret'  => false,
                'msg'  => 'Invalid Request',
                'data' => [],
            ];

            /* ------ Referer must be from /core-admin/manage-script/module ------ */
            $BYPASS = true;
            // zdebug($request->getHeader('Referer'));
            $uri_ref = "";
            if ($request->getHeader('Referer') !== false) {
                $uri_ref = $request->getHeader('Referer')->getUri();
            }
            $uri_serv = $request->getServer()->get('HTTP_ORIGIN')
                . '/core-admin/manage-role/route-role';
            if ($uri_ref === $uri_serv || $BYPASS) {
                /* ------------------------- Get Route Param ------------------------- */
                $route_param = $me->params()->fromRoute();
                /* ---------------------- Get 'act' Route Param ---------------------- */
                $act = $route_param['act'] ?? null;
                /* -------------------------- Get POST Data -------------------------- */
                $postData = $me->params()->fromPost();
                // zdebug($postData);die();
                /* ------------------------ Get JSON Body ------------------------ */
                if (count($postData) <= 0) {
                    $json = $request->getContent();
                    // zdebug($json);die();
                    if ($json !== null && $json !== "") {
                        try {
                            $postData = json_decode($json, true);
                        } catch (\Exception $e) {
                            // zdebug($e->getMessage());die();
                        }
                    }
                }

                if ($act === 'getall') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_role->getAllrouteRole($postData, $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_role   = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->setNewRoute($postData);
                } elseif ($act === 'update') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->updateRouteRole($postData);
                } elseif ($act === 'delete') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->deleteRouteRole($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->deleteMultiRouteRole($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_role    = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_role->updatestatusRouteRole($postData);
                } else {
                    $rid = $postData["rid"];
                    $mdl_script = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $mdl_role = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
                    $module = $mdl_script->getModule(false);
                    $route = $mdl_role->getRoute(false);
                    $role = $mdl_role->getRole(false);
                    $layout = $mdl_role->getLayout(false);
                    return [
                        "rid" => $rid,
                        "module" => $module,
                        "route" => $route,
                        "role" => $role,
                        "layout" => $layout,
                    ];
                }
            }

            /* ------------------------- Return JSON Data ------------------------ */
            $view_model = new JsonModel();
            $view_model->setVariables($ret);
            return $view_model;
        } else {
            /* --------------------------- Return View --------------------------- */
            $mdl_script = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
            $mdl_role = $me->_container->get(\CoreAdmin\Model\RoleModel::class);
            $module = $mdl_script->getModule(false);
            $route = $mdl_role->getRoute(false);
            $role = $mdl_role->getRole(false);
            $layout = $mdl_role->getLayout(false);
            // zdebug($route);die();
            return [
                "module" => $module,
                "route" => $route,
                "role" => $role,
                "layout" => $layout,
            ];
            // return [];
        }
    }
}
class MyReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
{
    public function readCell($column, $row, $worksheet = '')
    {
        if ($worksheet === "add") {
            // if ($row >= 1 && $row <= 7) {
            if (in_array($worksheet, ['add', 'update', 'remove']) && in_array($column, range('A', 'G'))) {
                return true;
            }
            // }
        } elseif ($worksheet === "update") {
            // if ($row >= 1 && $row <= 7) {
            if (in_array($worksheet, ['add', 'update', 'remove']) && in_array($column, range('A', 'I'))) {
                return true;
            }
            // }
        } elseif ($worksheet === "remove") {
            // if ($row >= 1 && $row <= 7) {
            if (in_array($worksheet, ['add', 'update', 'remove']) && in_array($column, range('A', 'B'))) {
                return true;
            }
            // }
        }
        return false;
    }
}
