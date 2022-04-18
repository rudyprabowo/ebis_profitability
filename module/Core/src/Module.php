<?php
declare(strict_types = 1);
namespace Core;

use Laminas\Authentication\AuthenticationService;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\ModuleManager\ModuleEvent;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\Console\Controller\AbstractConsoleController;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceManager;

class Module
{
    /**
     * init function
     *
     * # Load Every Request (1)
     * @param ModuleManager $moduleManager
     */
    // SECTION init
    public function init(ModuleManager $moduleManager)
    {
        $moduleEvent = $moduleManager->getEventManager();
        $me = $this;
        // # attach MergeConfig Listener
        $moduleEvent->attach(ModuleEvent::EVENT_MERGE_CONFIG, [$me, 'onMergeConfig']);
    }
    // !SECTION init
    /**
     * getConfig function
     *
     * # Load Every Request (2)
     * @return array
     */
    // SECTION getConfig
    public function getConfig(): array
    {
        // # load config
        /** @var array $config */
        $config = include __DIR__ . '/../config/module.php';
        // !d($config);die();
        return $config;
    }
    // !SECTION getConfig
    /**
     * onMergeConfig function
     *
     * # Load Every Request (3)
     * @param ModuleEvent $moduleEvent
     */
    // SECTION onMergeConfig
    public function onMergeConfig(ModuleEvent $moduleEvent)
    {
        // !d($_REQUEST,$_SERVER);
        // !d(get_class_methods($moduleEvent));//die();
        // $target = $moduleEvent->getTarget();
        // !d(get_class($target));
        // $module = $moduleEvent->getModule();
        // !d(get_class($module));
        // $modname = $moduleEvent->getModuleName();
        // !d($modname);
        // $param = $moduleEvent->getParams();
        // !d($param);
        // $name = $moduleEvent->getName();
        // !d($name);
        // zdebug(get_class($moduleEvent->getTarget()));
        // zdebug($request);
        // die();
        $me = new MergeConfig();
        $configListener = $moduleEvent->getConfigListener();
        $config = $configListener->getMergedConfig(false);
        // INFO append router config
        $routes = $config['router']['routes'] ?? [];
        $config['router']['routes'] = $me->appendRoutes($routes);
        // !d($config['router']['routes']);die();
        // INFO append controller config
        $controllers = $config['controllers']['factories'] ?? [];
        $config['controllers']['factories'] = $me->appendControllers($controllers);
        // !d($config['controllers']['factories']);die();
        // INFO append module config
        $modules = $config['modules'] ?? [];
        $config['modules'] = $me->appendModules($modules);
        // !d($config['modules']);die();
        $configListener->setMergedConfig($config);
    }
    // !SECTION onMergeConfig
    /**
     * onBootstrap function
     *
     * # Load Every Request (4)
     * @param MvcEvent $mvcEvent
     * @return void
     */
    // SECTION onBootstrap
    public function onBootstrap(MvcEvent $mvcEvent)
    {
        $me = $this;
        $eventManager = $mvcEvent->getApplication()->getEventManager();
        // $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($me,'onError'), 100);
        // $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, array($me,'onError'), 100);
        // $eventManager->attach(
        //     '*', // all events
        //     array($me,'onError')
        // );

        $sharedEventManager = $eventManager->getSharedManager();
        // # attach onRoute Listener
        // - disable OnRoute listener
        // $sharedEventManager->attach(
        //     '*',
        //     MvcEvent::EVENT_ROUTE,
        //     [$me, 'onRoute'], 100);
        // # attach onDispatch Listener
        $sharedEventManager->attach(
            '*',
            MvcEvent::EVENT_DISPATCH,
            [$me, 'onDispatch'],
            100
        );
        // # attach onDispatchError Listener
        $sharedEventManager->attach(
            '*',
            MvcEvent::EVENT_DISPATCH_ERROR,
            [$this, 'onDispatchError'],
            100
        );
        // # attach onRender Listener
        $sharedEventManager->attach(
            '*',
            MvcEvent::EVENT_RENDER,
            [$this, 'onRender'],
            100
        );
        // # attach onRenderError Listener
        $sharedEventManager->attach(
            '*',
            MvcEvent::EVENT_RENDER_ERROR,
            [$this, 'onRenderError'],
            100
        );
        $sharedEventManager->attach(
            '*',
            MvcEvent::EVENT_FINISH,
            [$this, 'onFinish'],
            100
        );
    }
    // !SECTION onBootstrap

    // public function onError(MvcEvent $e)
    // {
    //     // zdebug(get_class_methods($e));
    //     zdebug($e->getName());//die();
    //     // $viewModel = $e->getViewModel();
    //     // Debug::dump($viewModel);die();
    //     // $viewModel->setTemplate(_DEFAULT_LAYOUT_);
    // }

    /**
     * onDispatch function
     *
     * @param MvcEvent $event
     * @return void
     */
    // SECTION onDispatch
    public function onDispatch(MvcEvent $event)
    {
        $me = new Dispatch();
        // !d(get_class($event->getTarget()));//die();
        // !d($event->getTarget() instanceof AbstractConsoleController);
        // !d($event->getTarget() instanceof AbstractActionController);//die();
        if (($event->getTarget() instanceof AbstractActionController) && !($event->getTarget() instanceof AbstractConsoleController)) { // ? check request not from console
            // # get matched route
            $routeMatch = $event->getRouteMatch();
            // zdebug($routeMatch::class);die();
            // zdebug($routeMatch->getParams());die();
            // # get matched route method
            $route_method = $routeMatch->getParam("method", []);
            /**
             * # get request
             * @var \Laminas\Http\Request $request
             */
            $request = $event->getRequest();
            // zdebug($request->isXmlHttpRequest());//die('aaa');
            // # get request method
            $req_method = $request->getMethod();
            // zdebug($req_method);//
            /**
             * # get request
             * @var \Laminas\Http\Response $response
             */
            $response = $event->getResponse();
            /**
             * # get controller
             * @var AbstractActionController $controller
             */
            $controller = $event->getTarget();
            // zdebug(get_class_methods($controller));die();
            $ini_reader = new \Laminas\Config\Reader\Ini();
            $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
            $app_conf = $conf['app-config'];
            // !d($app_conf);die();
            if ((int)$app_conf['is_secure']===1 && ($request instanceof HttpRequest) && $request->getUri()->getScheme()!=="https") {
                // !d($request->getUriString(),$request->getUri()->getScheme());die();
                $url = $request->getUriString();
                $url = str_replace("http://", "https://", $url);
                // !d($request->getUriString(),$url);die();
                return $controller->redirect()->toUrl($url);
            }

            /**
             * ? have route method AND request method not in route method
             * INFO request method not allow
             */
            // !d($req_method, $route_method);die();
            if (count($route_method) > 0 && !in_array($req_method, $route_method)) {
                // # set response status code as forbidden
                $response->setStatusCode(HTTP_FORBIDDEN);
                if (!$request->isXmlHttpRequest()) {
                    // INFO redirect to Forbidden Page
                    // zdebug(HTTP_FORBIDDEN);die();
                    return $controller->redirect()->toRoute('access-error', ['code' => HTTP_FORBIDDEN]);
                } else {
                    // die('aaaa');
                    $response->setContent("{FORBIDDEN}");
                    // # get app
                    $app = $event->getTarget();
                    // # get ServiceManager
                    $serviceManager = $app->getServiceManager();
                    // # get config
                    $config = $serviceManager->get('config');
                    $finish = new Finish();
                    $finish->disconnectDB($config['db']['adapters'], $serviceManager);
                    $response->send();
                    exit;
                    // return $response;
                }
            } elseif ($request instanceof HttpRequest) { // ? request is HTTP Request
                // INFO initialize session
                $me->initSession($event);
                // INFO check XSS
                $cleanXSS = $me->checkXSS($event);
                // zdebug($cleanXSS);die();
                if (!$cleanXSS) {
                    // SECTION XSS not clean
                    $response->setStatusCode(HTTP_FORBIDDEN);
                    if (!$request->isXmlHttpRequest()) {
                        // INFO redirect to Forbidden Page
                        // return $controller->redirect()->toRoute('auth/error', ['code' => HTTP_FORBIDDEN]);
                        return $controller->redirect()->toRoute('access-error', ['code' => HTTP_FORBIDDEN]);
                    } else {
                        $response->setContent("FORBIDDEN");
                        // # get app
                        $app = $event->getTarget();
                        // # get ServiceManager
                        $serviceManager = $app->getServiceManager();
                        // # get config
                        $config = $serviceManager->get('config');
                        $finish = new Finish();
                        $finish->disconnectDB($config['db']['adapters'], $serviceManager);
                        $response->send();
                        exit;
                        // return $response;
                    }
                    // !SECTION XSS not clean
                } else {
                    // SECTION XSS clean
                    // INFO set template
                    $me->setTemplate($event);
                    // INFO set response header
                    $me->setResponseHeader($event);
                    // INFO set view param
                    $me->setViewVariable($event);
                    // INFO set layout meta
                    $me->setLayoutMeta($event);
                    /**
                     * # get service manager
                     * @var ServiceManager $serviceManager
                     */
                    $serviceManager = $event->getApplication()->getServiceManager();
                    /**
                     * # get authentication service
                     * @var AuthenticationService $authService
                     */
                    $authService = $serviceManager->get(AuthenticationService::class);
                    // # get route params
                    $routeParams = $routeMatch->getParams();
                    // zdebug($routeParams);
                    // die();
                    // # get is public route param (default not public)
                    $public_route = $routeParams['is_public'] ?? false;
                    // zdebug($public_route);
                    // die();
                    $have_access = false;
                    // zdebug($authService->hasIdentity());die();
                    if ($authService->hasIdentity()) { // ? has identity (has been login)
                        // SECTION has identity process
                        if ($public_route) { // ? public route
                            // SECTION has identity + public route process
                            $have_access = true;
                        // !SECTION has identity + public route  process
                        } else {
                            // zdebug("qqq");
                            $routeMatch->setParam("user_id", $authService->getIdentity()['id']);
                            $routeMatch->setParam("user_name", $authService->getIdentity()['username']);
                            // zdebug($authService->getIdentity());
                            // zdebug($routeMatch);
                            $have_access = $me->checkAccess($event, $authService, $routeMatch);
                            // zdebug($have_access);die();
                        }
                        // !SECTION has identity process
                    } else { // ? guest
                        // SECTION guest process
                        if ($public_route) { // ? public route
                            // SECTION guest + public route process
                            $have_access = true;
                            // !SECTION guest + public route  process
                        }
                        // !SECTION guest process
                    }
                    // zdebug($have_access);die('eee');
                    if (!$have_access) { // INFO doesnt have access
                        if ($request->isXmlHttpRequest()) {
                            $response->setStatusCode(HTTP_UNAUTHORIZED);
                            $response->setContent("UNAUTHORIZED");
                            // # get ServiceManager
                            $serviceManager = $event->getApplication()->getServiceManager();
                            // # get config
                            $config = $serviceManager->get('config');
                            $finish = new Finish();
                            $finish->disconnectDB($config['db']['adapters'], $serviceManager);
                            $response->send();
                            exit;
                        // return $response;
                        } else {
                            if ($authService->hasIdentity()) { // ? has identity (has been login)
                                // INFO redirect to Unauthorize Page
                                $response->setStatusCode(HTTP_UNAUTHORIZED);
                                $uri = $request->getUri();
                                $uri->setScheme(null)
                                    ->setHost(null)
                                    ->setPort(null)
                                    ->setUserInfo(null);
                                $redirect_url = $uri->toString();
                                return $controller->redirect()->toRoute('access-error', ['code' => HTTP_UNAUTHORIZED], ['query' => ['ref' => $redirect_url]]);
                            } else { // ? guest
                                $me->redirectGuest($request, $response, $serviceManager, $controller, $routeParams);
                            }
                        }
                    } elseif ($have_access && $request->isXmlHttpRequest()) {
                        $par_header = $request->getHeaders()->toArray();
                        // !d(getallheaders(),$par_header);die();
                        // !d(get_class_methods($request));die();
                        $md5_uri = md5($par_header['Referer']);
                        // !d($par_header,$par_header['Referer'],$md5_uri);die();
                        // zdebug($par_header['Referer']);
                        // zdebug($md5_uri);//die();
                        // zdebug($par_header);//die();
                        // zdebug($md5_uri."-Csrf-Token");//die();
                        $token = $par_header[$md5_uri."-Csrf-Token"]??null;
                        // zdebug(ucfirst($md5_uri)."-Csrf-Token");//die();
                        if ($token===null) {
                            $token = $par_header[ucfirst($md5_uri)."-Csrf-Token"]??null;
                        }
                        // zdebug($token);die();
                        $serviceManager = $event->getApplication()->getServiceManager();
                        $sessionManager = $serviceManager->get(\Laminas\Session\SessionManager::class);
                        // $authService = $serviceManager->get(AuthenticationService::class);
                        $tmp_token = null;
                        if ($sessionManager->sessionExists() && $sessionManager->isValid()) {
                            // if ($authService->hasIdentity()) { // ? has identity (has been login)
                            // # get data container
                            $container_data = $serviceManager->get('container_data');
                            if ($container_data->offsetExists($md5_uri."-CSRF")) {
                                $tmp_token = $container_data->offsetGet($md5_uri . "-CSRF");
                            }
                        } else { // ? not login
                            if (isset($_SESSION[$md5_uri."-CSRF"])) {
                                $tmp_token = $_SESSION[$md5_uri."-CSRF"];
                            }
                        }
                        // !d($token,$tmp_token);die();
                        // !d(($token===null || $token!==$tmp_token));die();
                        $BYPASS = true;
                        if (!$BYPASS && ($token===null || $token!==$tmp_token)) {
                            // # get config
                            $config = $serviceManager->get('config');
                            $finish = new Finish();
                            $finish->disconnectDB($config['db']['adapters'], $serviceManager);
                            // die('qqq');
                            $response->setStatusCode(HTTP_UNAUTHORIZED);
                            // zdebug(HTTP_UNAUTHORIZED);die();
                            // echo "XXX";
                            $response->setContent("UNAUTHORIZED");
                            $response->send();
                            exit;
                        }
                    }
                    // !SECTION XSS clean
                }
            }
        }
    }
    // !SECTION onDispatch
    /**
     * onDispatchError function
     *
     * @param MvcEvent $event
     * @return void
     */
    // SECTION onDispatchError
    public function onDispatchError(MvcEvent $event)
    {
        // zdebug('sss');
        // zdebug($event->getError());die();
        // zdebug(get_class_methods($event));
        // zdebug(get_class_methods($event->getViewModel()));
        // zdebug($event->getViewModel()->getChildren());
        // zdebug($event->getViewModel()->getVariables());
        // zdebug($event->getViewModel()->getIterator());die();
        // # get request
        $request = $event->getRequest();
        // zdebug($request);die();
        if ($request instanceof HttpRequest) { // is HTTP Request
            if ($request->isXmlHttpRequest() && $event->getError()!=="error-exception") {
                /** @var HttpResponse $response */
                $response = $event->getResponse();
                $response->setStatusCode(HTTP_BADREQUEST);
                $response->setContent("BAD REQUEST");
                // # get app
                $app = $event->getTarget();
                // # get ServiceManager
                $serviceManager = $event->getApplication()->getServiceManager();
                // # get config
                $config = $serviceManager->get('config');
                $finish = new Finish();
                $finish->disconnectDB($config['db']['adapters'], $serviceManager);
                $response->send();
                exit;
            // return $response;
            } else {
                // $routeMatch = $event->getRouteMatch();
                // $viewModel = $event->getViewModel();
                // $viewModel->setTemplate('layout/blank');
                // !d($viewModel);
            }
            // !d($event->getError());die('aaa');
        }
    }
    // !SECTION onDispatchError
    /**
     * onRender function
     *
     * @param MvcEvent $event
     * @return void
     */
    // SECTION onRender
    public function onRender(MvcEvent $event)
    {
        if ($event->getTarget() instanceof \Laminas\Mvc\Application) {
            // # get request
            $request = $event->getRequest();
            $routeMatch = $event->getRouteMatch();
            // zdebug($routeMatch);die();
            $route_param['action'] = "not-found";
            if ($routeMatch!==null) {
                $route_param = $routeMatch->getParams();
            }
            if ($request instanceof HttpRequest) { // ? requets is HTTP request
                if ($route_param['action']==="not-found") {
                    if ($request->isXmlHttpRequest()) {
                        /** @var HttpResponse $response */
                        $response = $event->getResponse();
                        $response->setStatusCode(HTTP_BADREQUEST);
                        $response->setContent("BAD REQUEST");
                        // # get app
                        $app = $event->getTarget();
                        // # get ServiceManager
                        $serviceManager = $app->getServiceManager();
                        // # get config
                        $config = $serviceManager->get('config');
                        $finish = new Finish();
                        $finish->disconnectDB($config['db']['adapters'], $serviceManager);
                        $response->send();
                        exit;
                    } else {
                        $me = new Render();
                        $me->setViewContent($event);
                    }
                } else {
                    $me = new Render();
                    $me->setViewContent($event);
                }
            }
        }
    }
    // !SECTION onRender
    /**
     * onRenderError function
     *
     * @param MvcEvent $event
     * @return void
     */
    // SECTION onRenderError
    public function onRenderError(MvcEvent $event)
    {
        // # get request
        $request = $event->getRequest();
        // !d($request);die();
        // $target = $event->getTarget();
        // !d(get_class($target));
        if ($request instanceof HttpRequest) { // is HTTP Request
            /** @var HttpResponse $response */
            $response = $event->getResponse();
            $response->setStatusCode(HTTP_INTERNALSERVERERROR);
            if ($request->isXmlHttpRequest()) {
                $response->setContent("INTERNAL SERVER ERROR");
                // # get app
                $app = $event->getTarget();
                // # get ServiceManager
                $serviceManager = $app->getServiceManager();
                // # get config
                $config = $serviceManager->get('config');
                $finish = new Finish();
                $finish->disconnectDB($config['db']['adapters'], $serviceManager);
                $response->send();
                exit;
            // return $response;
            } else {
                // $routeMatch = $event->getRouteMatch();
                $viewModel = $event->getViewModel();
                $viewModel->setTemplate('layout/blank');
            }
            // !d($event->getError());die('bbb');
        }
    }
    // !SECTION onRenderError
    /**
     * onFinish function
     *
     * @param MvcEvent $event
     * @return void
     */
    // SECTION onFinish
    public function onFinish(MvcEvent $event)
    {
        // !d($event->getError());die('aaa');
        $me = new Finish();
        if ($event->getRequest() instanceof HttpRequest) { // ? request is HTTP request
            // # get matche route
            $routeMatch = $event->getRouteMatch();
            // # get app
            $app = $event->getTarget();
            /** @var HttpResponse $response */
            $response = $app->getResponse();
            if ($routeMatch != null) { // ? matched route not null
                // # get ServiceManager
                $serviceManager = $app->getServiceManager();
                // # get config
                $config = $serviceManager->get('config');
                // # disconnect DB
                $me->disconnectDB($config['db']['adapters'], $serviceManager);
                // # get logging route param
                $is_logging = $routeMatch->getParam("is_logging", false);
                if ($is_logging) { // INFO is logging route
                    $me->routeLogging($event);
                }
                // INFO set cache view
                $me->setCacheView($event);
                // # get render query param
                $render = $event->getRequest()->getQuery('render', '');
                if ($render === "text") { // INFO render is text
                    // # get matched route
                    $route = $routeMatch->getMatchedRouteName();
                    // # replace / with -
                    $route = str_replace('/', '-', $route);
                    // # set header filename
                    $response->getHeaders()->addHeaderLine('Content-disposition', 'inline; filename="' . $route . '.txt"');
                    // # set header mime-type to text
                    $response->getHeaders()->addHeaderLine('Content-Type', 'plain/text; charset=utf-8');
                    // # get content
                    $html = $response->getContent();
                    // # set content
                    $response->setContent($html);
                    // INFO send response
                    $response->send();
                }
            }
        }
    }
    // !SECTION onFinish
}
