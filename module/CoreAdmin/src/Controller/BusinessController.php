<?php

declare(strict_types=1);

namespace CoreAdmin\Controller;

use function _\filter;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

/**
 * BusinessController

 *
 * @category Controller
 * @package  CoreAdmin\Controller
 * @author   TMA <info@tma.web.id>
 * @license  Open Source License

 * @link     https://web.tma.id

 */
class BusinessController extends AbstractActionController
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
     * Action businesstAction
     *
     * @return void
     */
    public function businessAction()
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

            /* ------ Referer must be from /core-admin/manage-business/business ------ */
            $BYPASS = true;
            // zdebug($request->getHeader('Referer'));
            $uri_ref = "";
            if ($request->getHeader('Referer') !== false) {
                $uri_ref = $request->getHeader('Referer')->getUri();
            }
            $uri_serv = $request->getServer()->get('HTTP_ORIGIN')
                . '/core-admin/manage-business/business';
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
                    $mdl_business = $me->_container->get(\CoreAdmin\Model\BusinessModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_business->getAllBusniness([], $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_business    = $me->_container->get(\CoreAdmin\Model\BusinessModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_business->addBusiness($postData);
                } elseif ($act === 'update') {
                    $mdl_business    = $me->_container->get(\CoreAdmin\Model\BusinessModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_business->updateBusiness($postData);
                } elseif ($act === 'delete') {
                    $mdl_business    = $me->_container->get(\CoreAdmin\Model\BusinessModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_business->deleteBusiness($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_business    = $me->_container->get(\CoreAdmin\Model\BusinessModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_business->deleteMultiBusiness($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_business    = $me->_container->get(\CoreAdmin\Model\BusinessModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_business->updatestatusBusiness($postData);
                }
            }

            /* ------------------------- Return JSON Data ------------------------ */
            $view_model = new JsonModel();
            $view_model->setVariables($ret);
            return $view_model;
        } else {
            /* --------------------------- Return View --------------------------- */
            $mdl_business = $me->_container->get(\CoreAdmin\Model\BusinessModel::class);
            $parent = $mdl_business->getParent(false);
            $route = $mdl_business->getRoute(false);
            $bu_level = $mdl_business->getBulevel(false);
            // zdebug($route);die();
            return [
                "parent" => $parent,
                "route" => $route,
                "bu_level" => $bu_level,
            ];
            // return [];
        }
    }

    /**
     * Action buleveltAction
     *
     * @return void
     */
    public function bulevelAction()
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

            /* ------ Referer must be from /core-admin/manage-business/business ------ */
            $BYPASS = true;
            // zdebug($request->getHeader('Referer'));
            $uri_ref = "";
            if ($request->getHeader('Referer') !== false) {
                $uri_ref = $request->getHeader('Referer')->getUri();
            }
            $uri_serv = $request->getServer()->get('HTTP_ORIGIN')
                . '/core-admin/manage-business/business';
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
                    $mdl_business = $me->_container->get(\CoreAdmin\Model\BusinessModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";
                    $fromcache   = ($postData['dbcache'] ?? "1") === "1";
                    $ret['data'] = $mdl_business->getAllBusninessLevel([], $fromcache);
                    $ret['data'] = filter($ret['data'], function ($o) {
                        return (int)$o['id'] !== 0;
                    });
                } elseif ($act === 'create') {
                    $mdl_business    = $me->_container->get(\CoreAdmin\Model\BusinessModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_business->addBusinesslevel($postData);
                } elseif ($act === 'update') {
                    $mdl_business    = $me->_container->get(\CoreAdmin\Model\BusinessModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_business->updateBusinesslevel($postData);
                } elseif ($act === 'delete') {
                    $mdl_business    = $me->_container->get(\CoreAdmin\Model\BusinessModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_business->deleteBusinesslevel($postData);
                } elseif ($act === 'deletemulti') {
                    $mdl_business    = $me->_container->get(\CoreAdmin\Model\BusinessModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_business->deleteMultiBusinesslevel($postData);
                } elseif ($act === 'updatestatus') {
                    $mdl_business    = $me->_container->get(\CoreAdmin\Model\BusinessModel::class);
                    $ret['ret']  = true;
                    $ret['msg']  = "success";

                    $ret['data'] = $mdl_business->updatestatusBusinesslevel($postData);
                }
            }

            /* ------------------------- Return JSON Data ------------------------ */
            $view_model = new JsonModel();
            $view_model->setVariables($ret);
            return $view_model;
        } else {
            /* --------------------------- Return View --------------------------- */
            $mdl_business = $me->_container->get(\CoreAdmin\Model\BusinessModel::class);
            $parent = $mdl_business->getParentbulevel(false);
            $route = $mdl_business->getRoute(false);
            $bu_level = $mdl_business->getBulevel(false);
            // zdebug($route);die();
            return [
                "parent" => $parent,
                "route" => $route,
                "bu_level" => $bu_level,
            ];
            // return [];
        }
    }
}
