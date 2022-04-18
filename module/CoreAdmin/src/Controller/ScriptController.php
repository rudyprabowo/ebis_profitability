<?php

declare(strict_types=1);

namespace CoreAdmin\Controller;

use function _\filter;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

/**
 * ScriptController

 *
 * @category Controller
 * @package  CoreAdmin\Controller
 * @author   TMA <info@tma.web.id>
 * @license  Open Source License

 * @link     https://web.tma.id

 */
class ScriptController extends AbstractActionController
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

    /**
     * Action controllerAction
     *
     * @return void
     */
    public function controllerAction()
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
                . '/core-admin/manage-script/controller';
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
                // zdebug($postData);die();

                if ($act === 'getall') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_script->getAllController([], $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->addController($postData);
                } elseif ($act === 'update') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->updateController($postData);
                } elseif ($act === 'delete') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->deleteController($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->deleteMultiController($postData);
                }
            }

            /* ------------------------- Return JSON Data ------------------------ */
            $view_model = new JsonModel();
            $view_model->setVariables($ret);
            return $view_model;
        } else {
            /* --------------------------- Return View --------------------------- */
            $mdl_script = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
            $module = $mdl_script->getModule(false);
            // zdebug($module);die();
            unset($module[0]);
            unset($module[1]);
            return [
                "module" => $module
            ];
            // return [];
        }
    }

    /**
     * Action layoutAction
     *
     * @return void
     */
    public function layoutAction()
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
                . '/core-admin/manage-script/controller';
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
                // zdebug($postData);die();

                if ($act === 'getall') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_script->getAllLayout([], $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->addLayout($postData);
                } elseif ($act === 'update') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->updateLayout($postData);
                } elseif ($act === 'delete') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->deleteLayout($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->deleteMultiLayout($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->updatestatusLayout($postData);
                }
            }

            /* ------------------------- Return JSON Data ------------------------ */
            $view_model = new JsonModel();
            $view_model->setVariables($ret);
            return $view_model;
        } else {
            /* --------------------------- Return View --------------------------- */
            $mdl_script = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
            $module = $mdl_script->getModule(false);
            // zdebug($module);die();
            return [
                "module" => $module
            ];
            // return [];
        }
    }

    /**
     * Action actionAction
     *
     * @return void
     */
    public function actionAction()
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
                . '/core-admin/manage-script/action';
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
                // zdebug($postData);die();

                if ($act === 'getall') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_script->getAllActions([], $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->addAction($postData);
                } elseif ($act === 'update') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->updateAction($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->updateActionStatus($postData);
                } elseif ($act === 'delete') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->deleteAction($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->deleteMultiAction($postData);
                }
            }


            /* ------------------------- Return JSON Data ------------------------ */
            $view_model = new JsonModel();
            $view_model->setVariables($ret);
            return $view_model;
        } else {
            /* ------------------------- query call Controller ------------------------ */
            $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
            $call_controller = $mdl_script->callController();
            // zdebug("$call_controller");die();

            /* --------------------------- Return View --------------------------- */
            return [
                'call_controller' => $call_controller
            ];
        }
    }

    /**
     * Action moduleAction
     *
     * @return void
     */
    public function moduleAction()
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
            if ($request->getHeader('Referer') !== false) {
                $uri_ref = $request->getHeader('Referer')->getUri();
            }
            $uri_serv = $request->getServer()->get('HTTP_ORIGIN')
                . '/core-admin/manage-script/module';
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
                // zdebug($postData);die();

                if ($act === 'getall') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    // zdebug('xxx');die();
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    // zdebug($fromcache);die();
                    $ret['data'] = $mdl_script->getAllModules([], $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->addModule($postData);
                } elseif ($act === 'update') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->updateModule($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->updateModuleStatus($postData);
                } elseif ($act === 'delete') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->deleteModule($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->deleteMultiModule($postData);
                } elseif ($act === 'upload') {
                    $files = $me->params()->fromFiles();
                    $b_file = new \Bulletproof\Image($files);
                    // zdebug($file);
                    // die();
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
                                $mdl_script = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                                // zdebug($data);
                                // die();
                                foreach ($data as $key => $value) {
                                    if ($key === 'add') {
                                        // zdebug($value);die();
                                        foreach ($value as $k => $v) {
                                            // zdebug($v);die();
                                            $mdl_script->addModule($v);
                                        }
                                    } elseif ($key === 'update') {
                                        foreach ($value as $k => $v) {
                                            // zdebug($v);die();
                                            $mdl_script->updateModuleViaUpload($v);
                                        }
                                    } elseif ($key === 'remove') {
                                        foreach ($value as $k => $v) {
                                            // zdebug($v);die();
                                            $mdl_script->deleteModuleViaUpload($v);
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

    /**
     * Route routeAction
     *
     * @return void
     */
    public function routeAction()
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

            /* ------ Referer must be from /core-admin/manage-script/route ------ */
            $BYPASS = true;
            // zdebug($request->getHeader('Referer'));
            $uri_ref = "";
            if ($request->getHeader('Referer') !== false) {
                $uri_ref = $request->getHeader('Referer')->getUri();
            }
            $uri_serv = $request->getServer()->get('HTTP_ORIGIN')
                . '/core-admin/manage-script/route';
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
                // zdebug($postData);die();

                if ($act === 'getall') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_script->getAllRoutes([], $fromcache);

                    $convert_result = [];
                    foreach ($ret['data'] as $k => $v) {
                        if ($v['method'] != null) {
                            $v['method']  = json_decode($v['method']);
                        }
                        $convert_result[] = $v;
                    }
                    $ret['data'] = $convert_result;

                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });

                    // !d($ret['data']);die();
                } elseif ($act === 'create') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->addRoute($postData);
                } elseif ($act === 'update') {
                    // die('test');
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    // zdebug($postData);die();
                    $ret['data'] = $mdl_script->updateRoute($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->updateRouteStatus($postData);
                } elseif ($act === 'updateshow_title') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->updateRouteShow_title($postData);
                } elseif ($act === 'updatemay_terminate') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->updateRouteMay_terminate($postData);
                } elseif ($act === 'updateis_caching') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->updateRouteIs_caching($postData);
                } elseif ($act === 'updateis_logging') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->updateRouteIs_logging($postData);
                } elseif ($act === 'updateis_public') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->updateRouteIs_public($postData);
                } elseif ($act === 'updateis_api') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->updateRouteIs_api($postData);
                } elseif ($act === 'delete') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->deleteRoute($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_script->deleteMultiRoute($postData);
                }
            }

            /* ------------------------- Return JSON Data ------------------------ */
            $view_model = new JsonModel();
            $view_model->setVariables($ret);
            return $view_model;
        } else {
            /* ------------------------- Call data for Select ------------------------ */
            $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
            $call_parent = $mdl_script->callParent();
            $call_action = $mdl_script->callAction();
            $call_layout = $mdl_script->callLayout();

            /* --------------------------- Return View --------------------------- */
            return [
                'call_layout' => $call_layout,
                'call_action' => $call_action,
                'call_parent' => $call_parent,
            ];
        }
    }

    public function apiGetrouteAction()
    {
        $me = $this;
        $request = $me->getRequest();
        $mdl_script    = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
        $ret = $mdl_script->getRoute();
        /* ------------------------- Return JSON Data ------------------------ */
        $view_model = new JsonModel();
        $view_model->setVariables($ret);
        return $view_model;
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
                    if ($key[$k2] === 'name' && ($v2 === 0 || $v2 === '0' || $v2 === 'Core' || $v2 === 'CoreAdmin')) {
                        $valid = false;
                        break;
                    }
                    if ($key[$k2] === 'status') {
                        $v2 = (int)$v2;
                    }
                    $keyx = $key[$k2];
                    if ($keyx === "session_name") {
                        $keyx = "session";
                    }
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
                    if ($key[$k2] === 'id' && ($v2 === 0 || $v2 === '0' || $v2 === 1 || $v2 === '1' || $v2 === 2 || $v2 === '2')) {
                        $valid = false;
                        break;
                    }
                    if ($key[$k2] === 'name' && ($v2 === 0 || $v2 === '0' || $v2 === 'Core' || $v2 === 'CoreAdmin')) {
                        $valid = false;
                        break;
                    }
                    if ($key[$k2] === 'status') {
                        $v2 = (int)$v2;
                    }
                    $tmpx[$key[$k2]] = $v2;
                }
                if ($valid) {
                    if (($tmpx['id'] !== null || $tmpx['old_name'] !== null) && $tmpx['new_name'] !== null) {
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
                    if ($key[$k2] === 'id' && ($v2 === 0 || $v2 === '0' || $v2 === 1 || $v2 === '1' || $v2 === 2 || $v2 === '2')) {
                        $valid = false;
                        break;
                    }
                    if ($key[$k2] === 'name' && ($v2 === 0 || $v2 === '0' || $v2 === 'Core' || $v2 === 'CoreAdmin')) {
                        $valid = false;
                        break;
                    }
                    if ($key[$k2] === 'status') {
                        $v2 = (int)$v2;
                    }
                    $tmpx[$key[$k2]] = $v2;
                }
                if ($valid) {
                    if ($tmpx['id'] !== null || $tmpx['old_name'] !== null) {
                        $tmp['remove'][] = $tmpx;
                    }
                }
            }
        }
        // zdebug($tmp);
        // die();
        return $tmp;
    }
}
class MyReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
{
    public function readCell($column, $row, $worksheet = '')
    {
        if ($worksheet === "add") {
            // if ($row >= 1 && $row <= 7) {
            if (in_array($worksheet, ['add', 'update', 'remove']) && in_array($column, range('A', 'C'))) {
                return true;
            }
            // }
        } elseif ($worksheet === "update") {
            // if ($row >= 1 && $row <= 7) {
            if (in_array($worksheet, ['add', 'update', 'remove']) && in_array($column, range('A', 'E'))) {
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
