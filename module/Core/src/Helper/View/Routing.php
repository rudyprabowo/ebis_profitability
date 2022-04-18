<?php
namespace Core\Helper\View;

use Laminas\Authentication\AuthenticationService;
use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Session\SessionManager;

class Routing extends AbstractHelper
{
    private $authService;
    private $sessionManager;
    private $config;
    private $container;

    public function __construct($container, $config)
    {
        $me = $this;
        $me->container = $container;
        // Debug::dump($container);die();
        $me->config = $config;
        $me->authService = $container->get(AuthenticationService::class);
        $me->sessionManager = $container->get(SessionManager::class);
    }

    /** @return boolean */
    public function hasRoute(string $route_name)
    {
        $me = $this;
//        d($route_name);
        $found = false;
        $tmpname = explode("/", $route_name);
        $routes = $me->config['router']['routes'];
//        d($routes);
        $tmparr = $routes;
        if (is_array($tmpname) && count($tmpname) > 0) {
            if (isset($tmparr[$tmpname[0]])){
                if(count($tmpname)===1) {
                    $found = true;
                }else {
                    $tmparr = $tmparr[$tmpname[0]];
                    if(isset($tmparr['child_routes'])
                        && isset($tmparr['child_routes'][$tmpname[1]])){
                        if(count($tmpname)===2) {
                            $found = true;
                        }else{
//                            d($tmparr,$tmpname);
                            $tmparr = $tmparr['child_routes'][$tmpname[1]];
                            if(isset($tmparr['child_routes'])
                                && isset($tmparr['child_routes'][$tmpname[2]])){
                                if(count($tmpname)===3) {
                                    $found = true;
                                }else{
                                    $tmparr = $tmparr['child_routes'][$tmpname[2]];
                                    if(isset($tmparr['child_routes'])
                                        && isset($tmparr['child_routes'][$tmpname[3]])){
                                        if(count($tmpname)===4) {
                                            $found = true;
                                        }else{

                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $found;
    }
}