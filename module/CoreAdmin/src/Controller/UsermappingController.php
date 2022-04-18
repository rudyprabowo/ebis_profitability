<?php

declare(strict_types=1);

namespace CoreAdmin\Controller;

use function _\filter;
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
class UsermappingController extends AbstractActionController
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
     * Action routeUserAction
     *
     * @return void
     */
    public function routeUserAction()
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
                . '/core-admin/manage-user/user';
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
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_user->getAllRouteUser($postData, $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_user   = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->setNewRoute($postData);
                } elseif ($act === 'update') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->updateRouteUSer($postData);
                } elseif ($act === 'delete') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->deleteRouteUser($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->deleteMultiRouteUser($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->updatestatusRouteUser($postData);
                } else {
                    // $ret['uid'] = $postData['uid'];
                    $mdl_user = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $route = $mdl_user->getRoute(false);
                    $user = $mdl_user->getUser(false);
                    $layout = $mdl_user->getLayout(false);
                    return [
                        "uid" => $postData['uid'],
                        "route" => $route,
                        "user" => $user,
                        "layout" => $layout,
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
            $mdl_user = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
            $route = $mdl_user->getRoute(false);
            $user = $mdl_user->getUser(false);
            $layout = $mdl_user->getLayout(false);
            // zdebug($route);die();
            return [
                "route" => $route,
                "user" => $user,
                "layout" => $layout,
            ];
            // return [];
        }
    }

    /**
     * Action menuUserAction
     *
     * @return void
     */
    public function menuUserAction()
    {
        // die();
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
                . '/core-admin/manage-user/user';
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
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_user->getAllMenuUser($postData, $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_user   = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->setNewMenu($postData);
                } elseif ($act === 'update') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->updateMenuUSer($postData);
                } elseif ($act === 'delete') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->deleteMenuUser($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->deleteMultiMenuUser($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->updatestatusMenuUser($postData);
                } else {
                    // $ret['uid'] = $postData['uid'];
                    $mdl_user = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $route = $mdl_user->getRoute(false);
                    $user = $mdl_user->getUser(false);
                    $layout = $mdl_user->getLayout(false);
                    $menu = $mdl_user->getAllMenu(false);
                    return [
                        "uid" => $postData['uid'],
                        "route" => $route,
                        "user" => $user,
                        "layout" => $layout,
                        "menu" => $menu,
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
            $mdl_user = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
            $route = $mdl_user->getRoute(false);
            $user = $mdl_user->getUser(false);
            $layout = $mdl_user->getLayout(false);
            $menu = $mdl_user->getAllMenu(false);
            // zdebug($route);die();
            return [
                "route" => $route,
                "user" => $user,
                "layout" => $layout,
                "menu" => $menu,
            ];
            // return [];
        }
    }

    /**
     * Action scriptUserAction
     *
     * @return void
     */
    public function scriptUserAction()
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
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_user->getAllScriptMenu($postData, $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_user   = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->setNewScript($postData);
                } elseif ($act === 'update') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->updateScriptMenu($postData);
                } elseif ($act === 'delete') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->deleteScriptUser($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->deleteMultiScriptUser($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->updatestatusScriptUser($postData);
                } else {
                    // $ret['uid'] = $postData['uid'];
                    $mdl_script = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
                    $mdl_user = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $module = $mdl_script->getModule(false);
                    $controller = $mdl_user->getController(false);
                    $action = $mdl_user->getAction(false);
                    $layout = $mdl_user->getLayout(false);
                    $role = $mdl_user->getRole(false);
                    $user = $mdl_user->getUser(false);
                    unset($module[0]);
                    unset($module[1]);
                    unset($controller[0]);
                    unset($action[0]);
                    return [
                        "uid" => $postData['uid'],
                        "module" => $module,
                        "controller" => $controller,
                        "action" => $action,
                        "layout" => $layout,
                        "role" => $role,
                        "user" => $user,
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
            $mdl_user = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
            $module = $mdl_script->getModule(false);
            $controller = $mdl_user->getController(false);
            $action = $mdl_user->getAction(false);
            $layout = $mdl_user->getLayout(false);
            $role = $mdl_user->getRole(false);
            $user = $mdl_user->getUser(false);
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
                "user" => $user,
            ];
            // return [];
        }
    }

    /**
     * Action buUserAction
     *
     * @return void
     */
    public function buUserAction()
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
                // zdebug($postData);
                if ($act === 'getall') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_user->getAllBuUser($postData, $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_user   = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->setNewBU($postData);
                } elseif ($act === 'update') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->updateBuMenu($postData);
                } elseif ($act === 'delete') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->deleteBuUser($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->deleteMultiBuUser($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_user    = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_user->updatestatusBuUser($postData);
                } else {
                    // $ret['uid'] = $postData['uid'];
                    $mdl_user = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
                    $bu = $mdl_user->getBU(false);
                    $user = $mdl_user->getUser(false);
                    return [
                        "uid" => $postData['uid'],
                        "bu" => $bu,
                        "user" => $user,
                    ];
                    // zdebug($postData);die();
                }
            }

            /* ------------------------- Return JSON Data ------------------------ */
            $postData2 = $me->params()->fromPost();
            // zdebug($postData2 != null);
            $view_model = new JsonModel();
            $view_model->setVariables($ret);
            // zdebug($view_model);die();
            return $view_model;
        } else {
            $route_param = $me->params()->fromRoute();
            // zdebug($route_param['act']);
            // zdebug($me->params()->fromPost());die();
            /* --------------------------- Return View --------------------------- */
            $mdl_script = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
            $mdl_user = $me->_container->get(\CoreAdmin\Model\UsermappingModel::class);
            $module = $mdl_script->getModule(false);
            $bu = $mdl_user->getBU(false);
            $user = $mdl_user->getUser(false);
            // zdebug($route);die();
            return [
                "bu" => $bu,
                "user" => $user,
            ];
            // return [];
        }
    }
}
