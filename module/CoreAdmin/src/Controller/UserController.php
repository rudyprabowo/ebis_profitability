<?php

declare(strict_types=1);

namespace CoreAdmin\Controller;

use CoreAdmin\Model\UserModel;
use function _\filter;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Stdlib\ArrayUtils;
use Laminas\View\Model\JsonModel;

class UserController extends AbstractActionController
{
    private $container;
    private $config;

    public function __construct($container, $config)
    {
        $me = $this;
        $me->_container = $container;
        $me->_config = $config;
    }



    public function listUserAction()
    {
        // die('qqq');
        return [];
    }

    public function processUserAction()
    {
        $me = $this;
        $routeParam = $me->params()->fromRoute();
        // !d($routeParam);die();
        $ret = [
            "ret" => false,
            "process" => false,
            'msg' => 'INVALID REQUEST',
            'data' => []
        ];
        /** @var UserModel $user_mdl */
        $user_mdl = $me->_container->get(UserModel::class);
        // !d(($routeParam['act']??"")==="filter");die();
        /** @var Request $request */
        $request = $me->getRequest();
        if (($routeParam['act'] ?? "") === "activate") {
            if ($request->isPost()) {
                // !d(get_class_methods($me->getRequest()));
                // !d($request->getContent());die();
                $json = $request->getContent();
                if ($json !== null && $json !== "") {
                    $pPost = [];
                    // $pPost = $me->params()->fromPost();
                    try {
                        $pPost = json_decode($json, true);
                    } catch (\Exception $e) {
                    }
                    $ret = [
                        "ret" => true,
                        "process" => false,
                        'msg' => 'FAILED ACTIVATE USER',
                        'data' => []
                    ];
                    // !d($pPost);
                    if (array_keys_exist($pPost, ["id", "row", "status"]) && $pPost['status'] === 0) {
                        $ret = $user_mdl->updateUserStatus((int)$pPost['id'], 1);
                        // !d($ret);die('qqq');
                        $ret["process"] = true;
                    }
                }
            }
        } elseif (($routeParam['act'] ?? "") === "deactivate") {
            // !d($request->isPost());
            if ($request->isPost()) {
                // !d(get_class_methods($me->getRequest()));
                // !d($request->getContent());die();
                $json = $request->getContent();
                // zdebug($json);die();
                if ($json !== null && $json !== "") {
                    $pPost = [];
                    // $pPost = $me->params()->fromPost();
                    try {
                        $pPost = json_decode($json, true);
                    } catch (\Exception $e) {
                        // zdebug($e->getMessage());die();
                    }
                    // zdebug($pPost);die();
                    $ret = [
                        "ret" => true,
                        "process" => false,
                        'msg' => 'FAILED DEACTIVATE USER',
                        'data' => []
                    ];
                    // !d($pPost);
                    if (array_keys_exist($pPost, ["id", "row", "status"]) && $pPost['status'] === 1) {
                        $ret = $user_mdl->updateUserStatus((int)$pPost['id'], 0);
                        // !d($ret);
                        $ret["process"] = true;
                    }
                }
            }
        } elseif (($routeParam['act'] ?? "") === "activate_ldap") {
            if ($request->isPost()) {
                // !d(get_class_methods($me->getRequest()));
                // !d($request->getContent());die();
                $json = $request->getContent();
                if ($json !== null && $json !== "") {
                    $pPost = [];
                    // $pPost = $me->params()->fromPost();
                    try {
                        $pPost = json_decode($json, true);
                    } catch (\Exception $e) {
                    }
                    $ret = [
                        "ret" => true,
                        "process" => false,
                        'msg' => 'FAILED ACTIVATE LDAP',
                        'data' => []
                    ];
                    // !d($pPost);
                    if (array_keys_exist($pPost, ["id", "row", "is_ldap"]) && $pPost['is_ldap'] === 0) {
                        $ret = $user_mdl->updateUserLDAP((int)$pPost['id'], 1);
                        // !d($ret);die('qqq');
                        $ret["process"] = true;
                    }
                }
            }
        } elseif (($routeParam['act'] ?? "") === "deactivate_ldap") {
            // !d($request->isPost());
            if ($request->isPost()) {
                // !d(get_class_methods($me->getRequest()));
                // !d($request->getContent());die();
                $json = $request->getContent();
                if ($json !== null && $json !== "") {
                    $pPost = [];
                    // $pPost = $me->params()->fromPost();
                    try {
                        $pPost = json_decode($json, true);
                    } catch (\Exception $e) {
                    }
                    $ret = [
                        "ret" => true,
                        "process" => false,
                        'msg' => 'FAILED DEACTIVATE LDAP',
                        'data' => []
                    ];
                    // !d($pPost);
                    if (array_keys_exist($pPost, ["id", "row", "is_ldap"]) && $pPost['is_ldap'] === 1) {
                        $ret = $user_mdl->updateUserLDAP((int)$pPost['id'], 0);
                        // !d($ret);
                        $ret["process"] = true;
                    }
                }
            }
        } elseif (($routeParam['act'] ?? "") === "remove") {
            if ($request->isPost()) {
                // !d(get_class_methods($me->getRequest()));
                // !d($request->getContent());die();
                $json = $request->getContent();
                if ($json !== null && $json !== "") {
                    $pPost = [];
                    // $pPost = $me->params()->fromPost();
                    try {
                        $pPost = json_decode($json, true);
                    } catch (\Exception $e) {
                    }
                    $ret = [
                        "ret" => true,
                        "process" => false,
                        'msg' => 'FAILED REMOVE USER',
                        'data' => []
                    ];
                    // !d($pPost);
                    if (array_keys_exist($pPost, ["id", "row"])) {
                        $ret = $user_mdl->updateUserStatus((int)$pPost['id'], 9);
                        // !d($ret);
                        $ret["process"] = true;
                    }
                }
            }
        } elseif (($routeParam['act'] ?? "") === "filter") {
            $ret = [
                "ret" => true,
                'msg' => 'SUCCESS GET DATA',
                'data' => [
                    'total' => $user_mdl->countAllUserByFilter(false),
                    'row' => $user_mdl->getAllUserByFilter(false)
                ]
            ];
        // !d($ret);die();
        } elseif (($routeParam['act']??"")==="add" && ($routeParam['uid']??"") ==="_") {
            // die('test');
            if ($request->isPost()) {
                // !d(get_class_methods($me->getRequest()));
                // !d($request->getContent());die();
                $json = $request->getContent();
                // zdebug($json);die();
                // $dummy = [
                //     "username" => "fajar_tiga",
                //     "full_name" => "dikdik fajar3",
                //     "password" => '$2y$10$fXGyPKdxujgtiCJAYVQIgO3Mg75RnsEbYLeUxGIvTL92rdakJVM3m',
                //     "email" => "fajar_tiga@mail.com",
                //     "status" => 1,
                //     "pass_reset_token" => null,
                //     "pass_reset_date" => null,
                //     "redirect_route" => null,
                //     "redirect_param" => null,
                //     "redirect_query" => null,
                //     "redirect_url" => null,
                //     "is_ldap" => 0,
                //     "mobile_no" => null,
                //     "employ_nik" => null,
                //     "spv_nik" => null,
                //     "telegram_id" => null,
                //     "expired_date" => null
                // ];
                // $json = json_encode($dummy);

                // zdebug($json);die();
                if ($json!==null && $json!=="") {
                    $pPost = [];
                    // $pPost = $me->params()->fromPost();
                    try {
                        $pPost = json_decode($json, true);
                        // die('test');
                        // zdebug($pPost);die();
                    } catch (\Exception $e) {
                    }
                    $ret = [
                        "ret"=>true,
                        "process"=>false,
                        'msg'=>'FAILED ADD USER',
                        'data'=>[]
                    ];
                    if (array_keys_exist($pPost, ["username","full_name","email","status","redirect_route","redirect_param","redirect_query","redirect_url","is_ldap","mobile_no","employ_nik","spv_nik","telegram_id"])) {
                        // !d($pPost);die();
                        $ret = $user_mdl->addUserData($pPost);
                        // zdebug($ret);die();
                        $ret["process"] = true;
                    }
                }
            }

            // zdebug($ret);die();
        } elseif (($routeParam['act'] ?? "") === "edit") {
            if ($request->isPost()) {
                // !d(get_class_methods($me->getRequest()));
                // !d($request->getContent());die();
                $json = $request->getContent();
                // zdebug($json);
                if ($json !== null && $json !== "") {
                    $pPost = [];
                    // $pPost = $me->params()->fromPost();
                    try {
                        $pPost = json_decode($json, true);
                    } catch (\Exception $e) {
                    }
                    // zdebug($pPost);die();
                    $ret = [
                        "ret" => true,
                        "process" => false,
                        'msg' => 'FAILED EDIT USER',
                        'data' => []
                    ];

                    if (array_keys_exist($pPost, ["username", "full_name"])) {
                        if ($pPost["username"] == "") {
                            $ret["process"] = false;
                            $ret["msg"] = "username cannot be null";
                        } elseif ($pPost["status"]>1 or $pPost["is_ldap"]>1) {
                            $ret["process"] = false;
                            $ret["msg"] = "status and is_ldap must be in 1 or 0";
                        } else {
                            $ret = $user_mdl->editUser((int)$routeParam['uid'], $pPost);
                            // !zdebug($ret);die();
                            // $ret["process"] = true;
                        }
                    }
                }
            }
        } elseif (($routeParam['act'] ?? "") === "edit_status") {
            if ($request->isPost()) {
                // !d(get_class_methods($me->getRequest()));
                // !d($request->getContent());die();
                $json = $request->getContent();
                // zdebug($json);
                if ($json !== null && $json !== "") {
                    $pPost = [];
                    // $pPost = $me->params()->fromPost();
                    try {
                        $pPost = json_decode($json, true);
                    } catch (\Exception $e) {
                    }
                    // zdebug($pPost);die();
                    $ret = [
                        "ret" => true,
                        "process" => false,
                        'msg' => 'FAILED EDIT STATUS',
                        'data' => []
                    ];

                    $ret = $user_mdl->editStatus((int)$routeParam['uid'], $pPost);

                    // if (array_keys_exist($pPost, ["username", "full_name"])) {
                    //     if ($pPost["username"] == "") {
                    //         $ret["process"] = false;
                    //         $ret["msg"] = "username cannot be null";
                    //     } else if ($pPost["status"] > 1 or $pPost["is_ldap"] > 1) {
                    //         $ret["process"] = false;
                    //         $ret["msg"] = "status and is_ldap must be in 1 or 0";
                    //     } else {
                    //         $ret = $user_mdl->editUser((int)$routeParam['uid'], $pPost);
                    //         // !zdebug($ret);die();
                    //         // $ret["process"] = true;
                    //     }
                    // }
                }
            }
        }
        // !zdebug($ret);die();
        //ADD COMMENT
        $viewModel = new JsonModel();
        $viewModel->setVariables($ret);
        return $viewModel;
    }

    public function viewUserAction()
    {
        $me = $this;
        // $authService = $me->_container->get(\Laminas\Authentication\AuthenticationService::class);
        // if ($authService->hasIdentity()) {
        //     !d($authService->getIdentity());
        // }
        $vars = $me->layout()->getVariable('_vars_');
        $url = $me->url()->fromRoute("core-admin/manage-user/list-user");
        // !d($url);die();
        $vars['breadcrumb'] = [
            "Manage User" => $me->url()->fromRoute("core-admin/manage-user/list-user"),
            "Add User" => $me->url()->fromRoute("core-admin/manage-user/view-user", ["uid" => 0, "edit" => 0])
        ];
        $vars['route_param']['title'] = "Add New User";
        $me->layout()->setVariable('_vars_', $vars);
        return [
            'form_type' => 'create',
            'uid' => 'null'
        ];
    }

    public function jobPositionAction()
    {
        // die('stest');
        $me = $this;
        $request = $me->getRequest();
        // zdebug($request->isPost());die();
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
            if ($request->getHeader('Referer')!==false) {
                $uri_ref = $request->getHeader('Referer')->getUri();
            }
            $uri_serv = $request->getServer()->get('HTTP_ORIGIN')
            . '/core-admin/mapping-user/job-position';
            if ($uri_ref === $uri_serv || $BYPASS) {
                /* ------------------------- Get Route Param ------------------------- */
                $route_param = $me->params()->fromRoute();
                /* ---------------------- Get 'act' Route Param ---------------------- */
                $act = $route_param['act'] ?? null;
                /* -------------------------- Get POST Data -------------------------- */
                $postData = $me->params()->fromPost();
                // zdebug($postData);die();
                /* ------------------------ Get JSON Body ------------------------ */
                if (count($postData)<=0) {
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
                // zdebug($postData);die();

                if ($act === 'getall') {
                    $user_mdl    = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $user_mdl->getAllJobs([], $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id']!==0;
                    });
                } elseif ($act === 'create') {
                    $user_mdl    = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $user_mdl->addJob($postData);
                } elseif ($act === 'update') {
                    $user_mdl    = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $user_mdl->updateJob($postData);
                } elseif ($act === 'updatestatus') {
                    $user_mdl    = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $user_mdl->updateJobStatus($postData);
                } elseif ($act === 'delete') {
                    $user_mdl    = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $user_mdl->deleteJob($postData);
                } elseif ($act === 'deletemulti') {
                    $user_mdl    = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $user_mdl->deleteMultiJob($postData);
                }
            }


            /* ------------------------- Return JSON Data ------------------------ */
            $view_model = new JsonModel();
            $view_model->setVariables($ret);
            return $view_model;
        } else {
            /* ------------------------- query call Controller ------------------------ */
            $user_mdl    = $me->_container->get(\CoreAdmin\Model\UserModel::class);
            $call_redirectRoute = $user_mdl->call_redirectRoute();
            //    zdebug("$call_redirectRoute");die();

            /* --------------------------- Return View --------------------------- */
            return [
               'call_redirectRoute' => $call_redirectRoute
           ];
        }
    }

    public function testRowchildAction()
    {
        $me = $this;
        // $user_mdl = $me->_container->get(UserModel::class);
        // $route = $user_mdl->getRoute();

        // die('qqq');
        return [

        ];
    }

    public function dataDummyrowchildAction()
    {
    }

    /**
     * Action indexAction
     *
     * @return void
     */
    public function indexAction()
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
            // zdebug($request->getHeader('Referer'));die();
            $uri_ref = "";
            if ($request->getHeader('Referer')!==false) {
                $uri_ref = $request->getHeader('Referer')->getUri();
            }
            $uri_serv = $request->getServer()->get('HTTP_ORIGIN')
            . '/core-admin/manage-user';
            if ($uri_ref === $uri_serv || $BYPASS) {
                /* ------------------------- Get Route Param ------------------------- */
                $route_param = $me->params()->fromRoute();
                /* ---------------------- Get 'act' Route Param ---------------------- */
                $act = $route_param['act'] ?? null;
                /* -------------------------- Get POST Data -------------------------- */
                $postData = $me->params()->fromPost();
                /* ------------------------ Get JSON Body ------------------------ */
                if (count($postData)<=0) {
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
                // zdebug($act);
                // zdebug($postData);
                // die();

                if ($act === 'getall') {
                    $mdl_user = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    // zdebug('xxx');
                    // die();
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    // zdebug($postData);
                    // die();
                    $ret['data'] = $mdl_user->getAllUsersByFilter($postData, $fromcache);
                    $ret['data']['_data'] = filter($ret['data']['_data'], function ($o) {
                        return (int)$o['id']!==0;
                    });
                } elseif ($act === 'create') {
                    $mdl_user = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    // zdebug($postData);die('aaa');
                    $ret['data'] = $mdl_user->addUser($postData);
                } elseif ($act === 'update') {
                    $mdl_user = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->updateUser($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_user = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->updateStatus($postData);
                } elseif ($act === 'updateorganic') {
                    $mdl_user = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->updateOrganic($postData);
                } elseif ($act === 'delete') {
                    $mdl_user = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->deleteUser($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_user = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->deleteMultiUser($postData);
                } elseif ($act === 'upload') {
                    $files = $me->params()->fromFiles();
                    $b_file = new \Bulletproof\Image($files);
                    // zdebug($file);
                    // die();
                    if (isset($files['file']) && $b_file['file']) {
                        $file = $files['file'];
                        if ($file['error']===1) {
                            $ret['ret']  = false;
                            $ret['msg']  = "failed";
                        } else {
                            $r_type = [
                                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                                "application/vnd.ms-excel",
                                "text/csv"
                            ];
                            $r_ext = [
                                "xls","xlsx","csv"
                            ];
                            $r_size = [0,(1048576 * 10)];
                            $method = str_replace(["\\", "::"], "_", __METHOD__);
                            $crypt1  = hash('sha1', $file['name']);
                            $crypt2  = hash('sha1', $method.$uri_ref);
                            $authService = $me->_container->get(AuthenticationService::class);
                            $identity = $authService->getIdentity();
                            // zdebug($identity);die();
                            $crypt3  = hash('sha1', $identity['id'].$identity['username']);
                            $new_name = $crypt1."_".$crypt2."_".$crypt3;
                            // zdebug($new_name);
                            // die();
                            $upload_dir = APP_PATH."data".DS."temp";
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
                                $data = $me->formatUploadedUserFile($check['data'], $upload_dir);
                                $mdl_user = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                                // zdebug($data);die();
                                foreach ($data as $key => $value) {
                                    if ($key === 'add') {
                                        // zdebug($value);die();
                                        foreach ($value as $k=>$v) {
                                            // zdebug($v);die();
                                            $mdl_user->addUser($v);
                                        }
                                    } elseif ($key === 'update') {
                                        foreach ($value as $k=>$v) {
                                            // zdebug($v);die();
                                            $mdl_user->updateUserViaUpload($v);
                                        }
                                    } elseif ($key === 'remove') {
                                        foreach ($value as $k=>$v) {
                                            // zdebug($v);die();
                                            $mdl_user->deleteUserViaUpload($v);
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
            return [];
        }
    }

    public function jobposUserAction()
    {
        // die('stest');
        $me = $this;
        $request = $me->getRequest();
        // zdebug($request->isPost());die();
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
            if ($request->getHeader('Referer')!==false) {
                $uri_ref = $request->getHeader('Referer')->getUri();
            }
            $uri_serv = $request->getServer()->get('HTTP_ORIGIN')
            . '/core-admin/mapping-user/jobpos-user';
            if ($uri_ref === $uri_serv || $BYPASS) {
                /* ------------------------- Get Route Param ------------------------- */
                $route_param = $me->params()->fromRoute();
                /* ---------------------- Get 'act' Route Param ---------------------- */
                $act = $route_param['act'] ?? null;
                /* -------------------------- Get POST Data -------------------------- */
                $postData = $me->params()->fromPost();
                // zdebug($postData);die();
                /* ------------------------ Get JSON Body ------------------------ */
                if (count($postData)<=0) {
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
                // zdebug($postData);die();

                if ($act === 'getall') {
                    $user_mdl    = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $user_mdl->getAllJobposs([], $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id']!==0;
                    });
                } elseif ($act === 'create') {
                    $user_mdl    = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    // zdebug($postData);die();
                    $ret['data'] = $user_mdl->addJobpos($postData);
                } elseif ($act === 'update') {
                    $user_mdl    = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $user_mdl->updateJobpos($postData);
                } elseif ($act === 'updatestatus') {
                    $user_mdl    = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $user_mdl->updateJobposStatus($postData);
                } elseif ($act === 'delete') {
                    $user_mdl    = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $user_mdl->deleteJobpos($postData);
                } elseif ($act === 'deletemulti') {
                    $user_mdl    = $me->_container->get(\CoreAdmin\Model\UserModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $user_mdl->deleteMultiJobpos($postData);
                }
            }


            /* ------------------------- Return JSON Data ------------------------ */
            $view_model = new JsonModel();
            $view_model->setVariables($ret);
            return $view_model;
        } else {
            // die('test')
            /* ------------------------- query call Controller ------------------------ */
            $user_mdl    = $me->_container->get(\CoreAdmin\Model\UserModel::class);
            $call_user = $user_mdl->call_user();
            $call_position_lvl = $user_mdl->call_position_lvl();
            //    zdebug("$call_user");die();

            /* --------------------------- Return View --------------------------- */
            return [
               'call_user' => $call_user,
               'call_position_lvl' => $call_position_lvl,
           ];
        }
    }
}
