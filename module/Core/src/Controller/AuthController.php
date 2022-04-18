<?php

/**
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */

declare (strict_types = 1);

namespace Core\Controller;

use Core\Adapter\Authentication\AuthenticationAdapter;
use Core\Form\CsrfForm;
use Core\Helper\Controller\Logging;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Result;
use Laminas\Form\Element\Hidden as HiddenForm;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\SessionManager;
use Laminas\View\Model\ViewModel;

class AuthController extends AbstractActionController
{
    private $container;
    private $config;

    public function __construct($container, $config)
    {
        $me = $this;
        $me->container = $container;
        $me->config = $config;
    }

    public function errorAction()
    {
        $me = $this;
        $routeParam = $me->params()->fromRoute();
        // !d($routeParam);
        //         die();
        // $layout = $me->layout();
        // $layout->setTemplate('layout/blank');
        $view = new ViewModel([
            'code' => $routeParam['code'],
        ]);

        //        !d(get_class_methods($me->getResponse()->setStatusCode()));die();
        switch ((int) $routeParam['code']) {
            case HTTP_FORBIDDEN:{
                    $view->setTemplate("403");
                    $me->getResponse()->setStatusCode(HTTP_FORBIDDEN);
                    break;
                }
            case HTTP_UNAUTHORIZED:{
                    $view->setTemplate("401");
                    $me->getResponse()->setStatusCode(HTTP_UNAUTHORIZED);
                    break;
                }
            case HTTP_NOTFOUND:{
                    $view->setTemplate("404-A");
                    $me->getResponse()->setStatusCode(HTTP_NOTFOUND);
                    break;
                }
            case HTTP_BADREQUEST:{
                    $me->getResponse()->setStatusCode(HTTP_BADREQUEST);
                    $view->setTemplate("error-A");
                    break;
                }
            default:{
                    $me->getResponse()->setStatusCode((int) $routeParam['code']);
                    $view->setTemplate("error-A");
                    break;
                }
        }
        return $view;
    }
}