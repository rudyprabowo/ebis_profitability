<?php

declare(strict_types=1);

namespace CoreAdmin\Controller;

use function _\filter;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class MenuController extends AbstractActionController
{
    private $_container;
    private $_config;

    public function __construct($container, $config)
    {
        $me = $this;
        $me->_container = $container;
        $me->_config = $config;
    }

    public function listMenuAction()
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
                . '/core-admin/manage-menu/list-menu';
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
                    $mdl_menu    = $me->_container->get(\CoreAdmin\Model\MenuModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_menu->getAllMenu([], $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_menu    = $me->_container->get(\CoreAdmin\Model\MenuModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $ret['data'] = $mdl_menu->addMenu($postData);
                } elseif ($act === 'update') {
                    $mdl_menu    = $me->_container->get(\CoreAdmin\Model\MenuModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $ret['data'] = $mdl_menu->updateMenu($postData);
                } elseif ($act === 'delete') {
                    $mdl_menu    = $me->_container->get(\CoreAdmin\Model\MenuModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $ret['data'] = $mdl_menu->deleteMenu($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_menu    = $me->_container->get(\CoreAdmin\Model\MenuModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $ret['data'] = $mdl_menu->deleteMultiMenu($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_menu    = $me->_container->get(\CoreAdmin\Model\MenuModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $ret['data'] = $mdl_menu->updatestatusMenu($postData);
                }
            }

            /* ------------------------- Return JSON Data ------------------------ */
            $view_model = new JsonModel();
            $view_model->setVariables($ret);
            return $view_model;
        } else {
            /* --------------------------- Return View --------------------------- */
            $mdl_script = $me->_container->get(\CoreAdmin\Model\ScriptModel::class);
            $mdl_menu = $me->_container->get(\CoreAdmin\Model\MenuModel::class);
            $module = $mdl_script->getModule(false);
            $layout = $mdl_menu->getLayout(false);
            $route = $mdl_menu->getRoute(false);
            $parent = $mdl_menu->getParentMenu(false);
            $icon = getRemixIcon(); //<--- get from public funftion -/function.php
            unset($module[0]);
            unset($module[1]);
            // zdebug($module);die();
            return [
                "module" => $module,
                "layout" => $layout,
                "route" => $route,
                "parent" => $parent,
                "icon" => $icon
            ];
            // return [];
        }

        // $me = $this;
        // $mdl_menu = $me->container->get(MenuModel::class);
        // $route = $mdl_menu->getAllMenu();
        // $ret = [
        //     'a'=>1,
        //     'b'=>2,
        //     'route'=>$route
        // ];

        // return [
        //     'ret1'=>$ret
        // ];
    }


    public function viewMenuAction()
    {
        return [];
    }
}
