<?php
namespace Core\Helper\Controller;

use Laminas\Authentication\AuthenticationService;
use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Session\SessionManager;

class Routing extends AbstractPlugin
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

    public function checkCSRF($name, $value, $referer){
        $me = $this;
        $md5_uri = md5($referer);
        !d($name, $value, $referer, $md5_uri);//die();
        if($name===$md5_uri."-Csrf-Token"){
            $tmp_token = null;
            if ($me->sessionManager->sessionExists() && $me->sessionManager->isValid()) {
                // # get data container
                $container_data = $me->container->get('container_data');
                if ($container_data->offsetExists($md5_uri."-CSRF")) {
                    $tmp_token = $container_data->offsetGet($md5_uri . "-CSRF");
                }
                // !d($tmp_token);die();
            } else { // ? not login
                if (isset($_SESSION[$md5_uri."-CSRF"])) {
                    $tmp_token = $_SESSION[$md5_uri."-CSRF"];
                }
            }
            // !d($value,$tmp_token);die();
            if ($value===null || $value!==$tmp_token) {
                return false;
            }else{
                return true;
            }
        }else{
            return false;
        }
    }

    public function defaultRedirectUrl($identity,$default = 'landing',$level = "all")
    { 
        $me = $this;
        /** @var AbstractActionController $controller */
        $controller = $me->getController();
        // zdebug(get_class($controller));die();
        // !d($identity);
        if($level==="all" || $level==="user") {
            // zdebug($identity);
            $dataCache = $me->container->get("data-file");
            $salt = "cache-data-userData";
            $param = [
                'uid' => $identity['id'] ?? '',
            ];
            // zdebug($param);
            $crypt1 = hash('sha1', $salt);
            $crypt2 = hash('sha256', json_encode($param));
            $key =  'userData_' . $crypt1 . '_' . $crypt2;
            // zdebug($key);
            $v = $dataCache->getItem($key);
            $v = json_decode($v,true);
            // zdebug($v);die();

            if (($v['redirect_name'] ?? null) !== null) {
                if ($me->hasRoute($v['redirect_name'])) {
                    $routeParam = [];
                    if (($v['param'] ?? null) !== null) {
                        $routeParam = json_decode($v['redirect_param']);
                        if ($routeParam === false || $routeParam === null) {
                            $routeParam = [];
                        }
                    }
                    $routeQuery = [];
                    if (($v['param'] ?? null) !== null) {
                        $routeQuery = json_decode($v['redirect_query']);
                        if ($routeQuery === false || $routeQuery === null) {
                            $routeQuery = [];
                        }
                    }
                    return $controller->url()->fromRoute($v['redirect_name'], $routeParam, ['query' => $routeQuery]);
                } else if (($v['redirect_url'] ?? null) !== null) {
                    return $v['redirect_url'];
                }
            } else if (($v['redirect_url'] ?? null) !== null) {
                return $v['redirect_url'];
            }
        }

        if($level==="all" || $level==="mainrole") {
            if (array_key_exists('mainrole', $identity)) {
                $v = $identity['mainrole'];
                if (($v['route_name'] ?? null) !== null) {
                    if ($me->hasRoute($v['route_name'])) {
                        $routeParam = [];
                        if (($v['param'] ?? null) !== null) {
                            $routeParam = json_decode($v['redirect_param']);
                            if ($routeParam === false || $routeParam === null) {
                                $routeParam = [];
                            }
                        }
                        $routeQuery = [];
                        if (($v['param'] ?? null) !== null) {
                            $routeQuery = json_decode($v['redirect_query']);
                            if ($routeQuery === false || $routeQuery === null) {
                                $routeQuery = [];
                            }
                        }
                        return $controller->url()->fromRoute($v['route_name'], $routeParam, ['query' => $routeQuery]);
                    } else if (($v['redirect_url'] ?? null) !== null) {
                        return $v['redirect_url'];
                    }
                } else if (($v['redirect_url'] ?? null) !== null) {
                    return $v['redirect_url'];
                }
            }
        }
//        die('ttt');

        $v = $identity;
        if($level==="all" || $level==="bu") {
            if (($v['bu_redirect_name'] ?? null) !== null) {
                if ($me->hasRoute($v['bu_redirect_name'])) {
                    $routeParam = [];
                    if (($v['param'] ?? null) !== null) {
                        $routeParam = json_decode($v['bu_redirect_param']);
                        if ($routeParam === false || $routeParam === null) {
                            $routeParam = [];
                        }
                    }
                    $routeQuery = [];
                    if (($v['param'] ?? null) !== null) {
                        $routeQuery = json_decode($v['bu_redirect_query']);
                        if ($routeQuery === false || $routeQuery === null) {
                            $routeQuery = [];
                        }
                    }
                    return $controller->url()->fromRoute($v['bu_redirect_name'], $routeParam, ['query' => $routeQuery]);
                } else if (($v['redirect_url'] ?? null) !== null) {
                    return $v['bu_redirect_url'];
                }
            } else if (($v['redirect_url'] ?? null) !== null) {
                return $v['bu_redirect_url'];
            }
        }

        if($level==="all" || $level==="pos") {
            if (($v['pos_redirect_name'] ?? null) !== null) {
                if ($me->hasRoute($v['pos_redirect_name'])) {
                    $routeParam = [];
                    if (($v['param'] ?? null) !== null) {
                        $routeParam = json_decode($v['pos_redirect_param']);
                        if ($routeParam === false || $routeParam === null) {
                            $routeParam = [];
                        }
                    }
                    $routeQuery = [];
                    if (($v['param'] ?? null) !== null) {
                        $routeQuery = json_decode($v['pos_redirect_query']);
                        if ($routeQuery === false || $routeQuery === null) {
                            $routeQuery = [];
                        }
                    }
                    return $controller->url()->fromRoute($v['pos_redirect_name'], $routeParam, ['query' => $routeQuery]);
                } else if (($v['redirect_url'] ?? null) !== null) {
                    return $v['pos_redirect_url'];
                }
            } else if (($v['redirect_url'] ?? null) !== null) {
                return $v['pos_redirect_url'];
            }
        }

        if($level==="all" || $level==="role") {
            foreach ($identity['roles'] as $v) {
                if (($v['route_name'] ?? null) !== null) {
                    if ($me->hasRoute($v['route_name'])) {
                        $routeParam = [];
                        if (($v['param'] ?? null) !== null) {
                            $routeParam = json_decode($v['redirect_param']);
                            if ($routeParam === false || $routeParam === null) {
                                $routeParam = [];
                            }
                        }
                        $routeQuery = [];
                        if (($v['param'] ?? null) !== null) {
                            $routeQuery = json_decode($v['redirect_query']);
                            if ($routeQuery === false || $routeQuery === null) {
                                $routeQuery = [];
                            }
                        }
                        return $controller->url()->fromRoute($v['route_name'], $routeParam, ['query' => $routeQuery]);
                    } else if (($v['redirect_url'] ?? null) !== null) {
                        return $v['redirect_url'];
                    }
                } else if (($v['redirect_url'] ?? null) !== null) {
                    return $v['redirect_url'];
                }
            }
        }

        return $controller->url()->fromRoute($default);
    }

    public function defaultRedirect($identity,$default = 'landing',$level = "all")
    {
        $routing = $this;
        /** @var AbstractActionController $controller */
        $controller = $routing->getController();
    //    !d($identity);
        if($level==="all" || $level==="user") {
            $v = $identity;
            if (($v['redirect_name'] ?? null) !== null) {
                if ($routing->hasRoute($v['redirect_name'])) {
                    $routeParam = [];
                    if (($v['param'] ?? null) !== null) {
                        $routeParam = json_decode($v['redirect_param']);
                        if ($routeParam === false || $routeParam === null) {
                            $routeParam = [];
                        }
                    }
                    $routeQuery = [];
                    if (($v['param'] ?? null) !== null) {
                        $routeQuery = json_decode($v['redirect_query']);
                        if ($routeQuery === false || $routeQuery === null) {
                            $routeQuery = [];
                        }
                    }
                    return $controller->redirect()->toRoute($v['redirect_name'], $routeParam, ['query' => $routeQuery]);
                } else if (($v['redirect_url'] ?? null) !== null) {
                    return $controller->redirect()->toUrl($v['redirect_url']);
                }
            } else if (($v['redirect_url'] ?? null) !== null) {
                return $controller->redirect()->toUrl($v['redirect_url']);
            }
        }

        if($level==="all" || $level==="mainrole") {
            if (array_key_exists('mainrole', $identity)) {
                $v = $identity['mainrole'];
                if (($v['route_name'] ?? null) !== null) {
                    if ($routing->hasRoute($v['route_name'])) {
                        $routeParam = [];
                        if (($v['param'] ?? null) !== null) {
                            $routeParam = json_decode($v['redirect_param']);
                            if ($routeParam === false || $routeParam === null) {
                                $routeParam = [];
                            }
                        }
                        $routeQuery = [];
                        if (($v['param'] ?? null) !== null) {
                            $routeQuery = json_decode($v['redirect_query']);
                            if ($routeQuery === false || $routeQuery === null) {
                                $routeQuery = [];
                            }
                        }
                        return $controller->redirect()->toRoute($v['route_name'], $routeParam, ['query' => $routeQuery]);
                    } else if (($v['redirect_url'] ?? null) !== null) {
                        return $controller->redirect()->toUrl($v['redirect_url']);
                    }
                } else if (($v['redirect_url'] ?? null) !== null) {
                    return $controller->redirect()->toUrl($v['redirect_url']);
                }
            }
        }
//        die('ttt');

        $v = $identity;
        if($level==="all" || $level==="bu") {
            if (($v['bu_redirect_name'] ?? null) !== null) {
                if ($routing->hasRoute($v['bu_redirect_name'])) {
                    $routeParam = [];
                    if (($v['param'] ?? null) !== null) {
                        $routeParam = json_decode($v['bu_redirect_param']);
                        if ($routeParam === false || $routeParam === null) {
                            $routeParam = [];
                        }
                    }
                    $routeQuery = [];
                    if (($v['param'] ?? null) !== null) {
                        $routeQuery = json_decode($v['bu_redirect_query']);
                        if ($routeQuery === false || $routeQuery === null) {
                            $routeQuery = [];
                        }
                    }
                    return $controller->redirect()->toRoute($v['bu_redirect_name'], $routeParam, ['query' => $routeQuery]);
                } else if (($v['redirect_url'] ?? null) !== null) {
                    return $controller->redirect()->toUrl($v['bu_redirect_url']);
                }
            } else if (($v['redirect_url'] ?? null) !== null) {
                return $controller->redirect()->toUrl($v['bu_redirect_url']);
            }
        }

        if($level==="all" || $level==="pos") {
            if (($v['pos_redirect_name'] ?? null) !== null) {
                if ($routing->hasRoute($v['pos_redirect_name'])) {
                    $routeParam = [];
                    if (($v['param'] ?? null) !== null) {
                        $routeParam = json_decode($v['pos_redirect_param']);
                        if ($routeParam === false || $routeParam === null) {
                            $routeParam = [];
                        }
                    }
                    $routeQuery = [];
                    if (($v['param'] ?? null) !== null) {
                        $routeQuery = json_decode($v['pos_redirect_query']);
                        if ($routeQuery === false || $routeQuery === null) {
                            $routeQuery = [];
                        }
                    }
                    return $controller->redirect()->toRoute($v['pos_redirect_name'], $routeParam, ['query' => $routeQuery]);
                } else if (($v['redirect_url'] ?? null) !== null) {
                    return $controller->redirect()->toUrl($v['pos_redirect_url']);
                }
            } else if (($v['redirect_url'] ?? null) !== null) {
                return $controller->redirect()->toUrl($v['pos_redirect_url']);
            }
        }

        if($level==="all" || $level==="role") {
            foreach ($identity['roles'] as $v) {
                if (($v['route_name'] ?? null) !== null) {
                    if ($routing->hasRoute($v['route_name'])) {
                        $routeParam = [];
                        if (($v['param'] ?? null) !== null) {
                            $routeParam = json_decode($v['redirect_param']);
                            if ($routeParam === false || $routeParam === null) {
                                $routeParam = [];
                            }
                        }
                        $routeQuery = [];
                        if (($v['param'] ?? null) !== null) {
                            $routeQuery = json_decode($v['redirect_query']);
                            if ($routeQuery === false || $routeQuery === null) {
                                $routeQuery = [];
                            }
                        }
                        return $controller->redirect()->toRoute($v['route_name'], $routeParam, ['query' => $routeQuery]);
                    } else if (($v['redirect_url'] ?? null) !== null) {
                        return $controller->redirect()->toUrl($v['redirect_url']);
                    }
                } else if (($v['redirect_url'] ?? null) !== null) {
                    return $controller->redirect()->toUrl($v['redirect_url']);
                }
            }
        }

        return $controller->redirect()->toRoute($default);
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