<?php
declare(strict_types = 1);
namespace Core;

use Core\Adapter\Authentication\AuthenticationAdapter;
use Laminas\Authentication\AuthenticationService;
// use Laminas\Http\Request as HttpRequest;
// use Laminas\Http\Response as HttpResponse;
use Laminas\Http\PhpEnvironment\Request as HttpRequest;
use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use Laminas\ServiceManager\ServiceManager;
use voku\helper\AntiXSS;

class Dispatch
{

    /**
     * bootstrapSession function
     *
     * @param MvcEvent $mvcEvent
     * @return void
     */
    // SECTION initSession
    public function initSession(MvcEvent $mvcEvent)
    {
        // # get matched route
        $routeMatch = $mvcEvent->getRouteMatch();
        /**
         * # get controller name
         * @var string $controller_name
         */
        $controller_name = $routeMatch->getParam('controller', null);
        /**
         * # get module name
         * @var string $module_name
         */
        $module_name = substr($controller_name, 0, strpos($controller_name, '\\'));
        // # get service manager
        $serviceManager = $mvcEvent->getApplication()->getServiceManager();
        // # get config
        $config = $serviceManager->get('Config');
        // ? module session name not null AND not empty string
        if (($config['modules'][$module_name]['session_name'] ?? null) !== null && ($config['modules'][$module_name]['session_name'] ?? "") !== "") {
            // INFO change global session name
            $GLOBALS['SESS_OPT']['name'] = $config['modules'][$module_name]['session_name'] . "_session";
        }
        /**
         * # get session manager
         * @var \Laminas\Session\SessionManager $sessionManager
         */
        $sessionManager = $serviceManager->get(\Laminas\Session\SessionManager::class);
        // INFO session start
        try {
            $sessionManager->start();
        } catch (\Exception $e) {
        }
        // # get session container init
        $containInit = $serviceManager->get("container_init");
        /**
         * # get request
         * @var Request $request
         */
        $request = $mvcEvent->getRequest();
        // zdebug(get_class($request));die();
        /**
         * @var bool $session_exist
         */
        $session_exist = false;
        // ? session exist AND valid
        if ($sessionManager->sessionExists() && $sessionManager->isValid()) {
            /**
             * # get authentication service
             * @var \Laminas\Authentication\AuthenticationService $authService
             */
            // $authService = $serviceManager->get(AuthenticationService::class);
            // ? variable init exist AND has identity (has been login)
            if ($containInit->offsetExists("init")) {
                // INFO set session variable
                $containInit->offsetSet("sess_id", $sessionManager->getId());
                $containInit->offsetSet("remote_addr", $request->getServer()->get('REMOTE_ADDR'));
                $containInit->offsetSet("http_user_agent", $request->getServer()->get('HTTP_USER_AGENT'));
                $containInit->offsetSet("last_request", date("Y-m-d H:i:s"));
                $session_exist = true;
            }
        }
        // zdebug($session_exist);
        // ? session not exist
        if (!$session_exist) {
            $ini_reader = new \Laminas\Config\Reader\Ini();
            $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
            $app_conf = $conf['app-config'];
            $session_conf = $conf['session'];
            if ($session_conf['save_handler'] === "DB") {
                $ini_reader = new \Laminas\Config\Reader\Ini();
                $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
                $app_conf = $conf['app-config'];
                $session_conf = $conf['session'];
                if (($app_conf['main_db']??null)==="postgres") {
                    $postgres_conf = $conf['db-postgres'];
                    /**
                     * @var \Medoo\Medoo $medooDb
                     */
                    $medooDb = new \Medoo\Medoo([
                        'database_type' => 'pgsql',
                        'database_name' => $postgres_conf['admin']['database'],
                        'server' => $postgres_conf['admin']['hostname'],
                        'username' => $postgres_conf['admin']['username'],
                        'password' => $postgres_conf['admin']['password'],
                        'port' => $postgres_conf['admin']['port'],
                        'command' => [
                            'SET search_path TO '.$session_conf['db_schema_name']
                        ]
                    ]);
                    $qry = "EXTRACT(EPOCH FROM INTERVAL '-".($session_conf['config']['remember_me_seconds']/3600)." hour')";
                    $qry2 = "EXTRACT(EPOCH FROM INTERVAL '-".($session_conf['expire']/60)." minute')";
                } else {
                    $mysql_conf = $conf['db-mysql'];
                    /**
                     * @var \Medoo\Medoo $medooDb
                     */
                    $medooDb = new \Medoo\Medoo([
                        'database_type' => 'mysql',
                        'database_name' => $mysql_conf['admin']['database'],
                        'server' => $mysql_conf['admin']['hostname'],
                        'username' => $mysql_conf['admin']['username'],
                        'password' => $mysql_conf['admin']['password'],
                        'port' => $mysql_conf['admin']['port']
                    ]);
                    $qry = 'UNIX_TIMESTAMP(NOW() - INTERVAL '.($session_conf['config']['remember_me_seconds']/3600).' HOUR)';
                    $qry2 = 'UNIX_TIMESTAMP(NOW() - INTERVAL '.($session_conf['expire']/60).' MINUTE)';
                }
                $session_validator = [];
                if (isset($GLOBALS['SESS_VALIDATOR'])) {
                    $session_validator = $GLOBALS['SESS_VALIDATOR'];
                }
                $delete_param = [
                    "name" => $sessionManager->getName(),
                    // "uid"=>null,
                    "modified[<]"=>\Medoo\Medoo::raw($qry)
                ];
                $delete_param = [
                    "name" => $sessionManager->getName(),
                    "uid"=>null,
                    "modified[<]"=>\Medoo\Medoo::raw($qry2)
                ];

                if ($session_validator['ip'] ?? false) {
                    $delete_param['ip'] = $request->getServer()->get('REMOTE_ADDR');
                }
                if ($session_validator['uag'] ?? false) {
                    $delete_param['uag'] = $request->getServer()->get('HTTP_USER_AGENT');
                }
                // INFO delete old session
                $medooDb->delete($session_conf['db_table_name'], [
                    "AND" => $delete_param,
                ]);
            }
            // INFO generate new session id
            $sessionManager->regenerateId(true);
            $containInit->offsetSet("init", 1);
            $containInit->offsetSet("sess_id", $sessionManager->getId());
            $containInit->offsetSet("remote_addr", $request->getServer()->get('REMOTE_ADDR'));
            $containInit->offsetSet("http_user_agent", $request->getServer()->get('HTTP_USER_AGENT'));
            $containInit->offsetSet("last_request", date("Y-m-d H:i:s"));
            /** @var array $config_validator */
            $config_validator = [];
            // ? session_manager config exist AND session validator config exist
            if (isset($config['session_manager']) && isset($config['session_manager']['validators'])) {
                $config_validator = $config['session_manager']['validators'];
            } elseif (isset($config['session_validators'])) { // ? session validator config exist
                $config_validator = $config['session_validators'];
            }
            $validatorChain = $sessionManager->getValidatorChain();
            foreach ($config_validator as $validator) {
                switch ($validator) {
                    case \Laminas\Session\Validator\HttpUserAgent::class:
                        $validator = new $validator($containInit->http_user_agent);
                        break;
                    case \Laminas\Session\Validator\RemoteAddr::class:
                        $validator = new $validator($containInit->remote_addr);
                        break;
                    default:
                        $validator = new $validator();
                        break;
                }
                $validatorChain->attach('session.validate', array($validator, 'isValid'));
            }
        }
    }
    // !SECTION initSession
    /**
     * checkXSS function
     *
     * @param MvcEvent $event
     * @return bool
     */
    // SECTION checkXSS
    public function checkXSS(MvcEvent $event)
    {
        /** @var bool $found_xss */
        $found_xss = false;
        /** @var AntiXSS $antiXss */
        $antiXss = new AntiXSS();
        $antiXss->addNeverAllowedStrAfterwards(["'();}]",'"();}]','<','>']);
        // # get request
        /** @var Request $request */
        $request = $event->getRequest();
        // # get request uri
        $par_path = $request->getUri()->getPath();
        // INFO cleansing request uri
        $antiXss->xss_clean($par_path);
        // INFO check request uri xss
        $found_xss = $antiXss->isXssFound();
        if (!$found_xss) {
            $found_xss = !checkXSS($par_path);
        }
        // # get request query param
        $par_query = $request->getQuery()->toArray();
        if (!$found_xss) { // ? XSS not found
            // # loop request query param
            foreach ($par_query as $key => $value) {
                // INFO cleansing request query param key
                $antiXss->xss_clean($key);
                // INFO check request query param key
                $found_xss = $antiXss->isXssFound();
                if ($found_xss) { // ? XSS found
                    break;
                }
                // INFO cleansing request query param value
                $antiXss->xss_clean($value);
                // INFO check request query param value
                $found_xss = $antiXss->isXssFound();
                if (!$found_xss) {
                    $found_xss = !checkXSS($value);
                }
                if ($found_xss) { // ? XSS found
                    break;
                }
            }
        }
        // # get request post param
        $par_post = $request->getPost()->toArray();
        if (!$found_xss) { // ? XSS not found
            foreach ($par_post as $key => $value) {
                // INFO cleansing request post param value
                $antiXss->xss_clean($key);
                // INFO cleansing request post param value
                $found_xss = $antiXss->isXssFound();
                if ($found_xss) { // ? XSS found
                    break;
                }
                // INFO cleansing request post param value
                $antiXss->xss_clean($value);
                // INFO cleansing request post param value
                $found_xss = $antiXss->isXssFound();
                if (!$found_xss) {
                    $found_xss = !checkXSS($value);
                }
                if ($found_xss) { // ? XSS found
                    break;
                }
            }
        }

        $uag = $request->getServer()->get('HTTP_USER_AGENT');
        if (!$found_xss) {
            $antiXss = new AntiXSS();
            $harmless_string = $antiXss->xss_clean($uag);
            $found_xss = $antiXss->isXssFound();
            if (!$found_xss) {
                $found_xss = !checkXSS($uag);
            }
        }

        // !d($found_xss);
        if ($found_xss) { // INFO XSS found
            // # get service manager
            $servicesManager = $event->getApplication()->getServiceManager();
            /** @var AuthenticationService $authService */
            $authService = $servicesManager->get(AuthenticationService::class);
            /** @var array $user */
            $user = [];
            if ($authService->hasIdentity()) { // ? has identity (has been login)
                /** @var SessionManager $sessionManager */
                $sessionManager = $servicesManager->get(\Laminas\Session\SessionManager::class);
                // # get identity
                $identity = $authService->getIdentity();
                /** @var string $sess_id */
                $sess_id = null;
                if ($sessionManager->sessionExists()) { // ? session exist
                    // # get session id
                    $sess_id = $sessionManager->getId();
                }
                $user = [
                    'id' => $identity['id'] ?? null,
                    'username' => $identity['username'] ?? null,
                    'full_name' => $identity['full_name'] ?? null,
                    'session_id' => $sess_id,
                ];
            }
            /**
             * # get request header
             * @var array $par_header
             */
            $par_header = $request->getHeaders()->toArray();
            /**
             * # get request server
             * @var array $par_server
             */
            $par_server = $request->getServer()->toArray();
            /**
             * # get request environment
             * @var array $par_env
             */
            $par_env = $request->getEnv()->toArray();
            /** @var array $data */
            $data = [
                'path' => $par_path,
                'query' => $par_query,
                'post' => $par_post,
                'header' => $par_header,
                'server' => $par_server,
                'env' => $par_env,
                'user' => $user,
            ];
            /** @var string $format */
            $format = '%timestamp%|%priorityName%|%message%';
            /** @var Simple $formatter */
            $formatter = new \Laminas\Log\Formatter\Simple($format);
            $ini_reader = new \Laminas\Config\Reader\Ini();
            $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
            $log_dir = $conf['app-config']['log_dir'];
            /** @var \Laminas\Log\Writer\Stream $writer */
            $writer = new \Laminas\Log\Writer\Stream($log_dir . 'xss/' . date('Ymd') . '-xss.log');
            // # set formatter
            $writer->setFormatter($formatter);
            /** @var \Laminas\Log\Logger $logger */
            $logger = new \Laminas\Log\Logger();
            // # add writer
            $logger->addWriter($writer);
            // # log alert
            $logger->alert(json_encode($data));
            // # set null
            $logger = null;
        }
        // # return
        return !$found_xss;
    }
    // !SECTION checkXSS
    /**
     * setTemplate function
     *
     * @param MvcEvent $event
     * @return void
     */
    // SECTION setTemplate
    public function setTemplate(MvcEvent $event)
    {
        $routeMatch = $event->getRouteMatch();
        $routeParams = $routeMatch->getParams();
        // !d($routeParams);die();
        $viewModel = $event->getViewModel();
        // INFO set layout to route layout param if exist if not use default layout
        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $layout_conf = $conf['layout'];
        $viewModel->setTemplate('layout/' . ($routeParams['layout'] ?? $layout_conf['default']));
    }
    // !SECTION setTemplate
    /**
     * setResponseHeader function
     *
     * @param MvcEvent $event
     * @return void
     */
    // SECTION setResponseHeader
    public function setResponseHeader(MvcEvent $event)
    {
        /** @var HttpResponse $response */
        $response = $event->getResponse();
        // INFO set X-Frame-Options HTTP header
        $response->getHeaders()->addHeaderLine('X-Frame-Options', 'sameorigin');
        // INFO set X-XSS-Protection HTTP header
        $response->getHeaders()->addHeaderLine('X-XSS-Protection', '1');
        // INFO set X-Content-Type-Options HTTP header
        $response->getHeaders()->addHeaderLine('X-Content-Type-Options', 'nosniff');
        $ini_reader = new \Laminas\Config\Reader\Ini();
        $ini_reader->setProcessSections(false);
        $conf = $ini_reader->fromFile(ini_path() . "content-security-policy.ini");
        // !d($conf);die();
        $csp = [];
        foreach ($conf as $k=>$v) {
            $tmp = implode(" ", $v).";";
            $csp[] = $k." ".$tmp;
        }
        // !d($csp);die();
        // INFO set Content-Security-Policy HTTP header
        $response->getHeaders()->addHeaderLine('Content-Security-Policy', implode(" ", $csp));
    }
    // !SECTION setResponseHeader
    /**
     * setViewVariable function
     *
     * @param MvcEvent $event
     * @return void
     */
    // SECTION setViewVariable
    public function setViewVariable(MvcEvent $event)
    {
        // # get matched route
        $routeMatch = $event->getRouteMatch();
        // # get HTTP request
        /** @var Request $request */
        $request = $event->getRequest();
        // # get controller name
        $control_name = $routeMatch->getParam('controller', null);
        $controlName = explode("\\", $control_name);
        $controlName = $controlName[count($controlName) - 1];
        // # get module name
        $module_name = substr($control_name, 0, strpos($control_name, '\\'));
        // # get action name
        $actionName = $routeMatch->getParam('action', null);
        // # get viewModel
        $viewModel = $event->getViewModel();
        // # get view layout
        $layout = $viewModel->getTemplate();
        // !d($layout);
        // # get layout name
        $layout = explode("/", $layout);
        $layout = $layout[count($layout) - 1];
        // !d($_SERVER);die();
        // INFO set view variables
        $vars = [
            'route_name' => $routeMatch->getMatchedRouteName(),
            'route_param' => $routeMatch->getParams(),
            'route_query' => $request->getQuery()->toArray(),
            'module' => $module_name,
            'controller' => $controlName,
            'action' => $actionName,
            'layout' => $layout,
            'is_xhr' => $request->isXmlHttpRequest()
        ];
        // !d($vars);//die();
        $viewModel->setVariable('_vars_', $vars);
    }
    // !SECTION setViewVariable
    /**
     * setLayoutMeta function
     *
     * @param MvcEvent $event
     * @return void
     */
    // SECTION setLayoutMeta
    public function setLayoutMeta(MvcEvent $event)
    {
        // # get viewHelperManager
        $viewHelperManager = $event->getApplication()->getServiceManager()->get('ViewHelperManager');
        // # get matched route
        $routeMatch = $event->getRouteMatch();
        // !d($routeMatch);die();
        // # get route title param
        $title = $routeMatch->getParam('title', null);
        // # get viewModel
        $viewModel = $event->getViewModel();
        // INFO set title variable
        $viewModel->setVariable('_menu_title', $title);
        // SECTION set meta title
        /** @var HeadTitle $headTitleHelper */
        $headTitleHelper = $viewHelperManager->get('headTitle');
        $headTitleHelper->setSeparator(' | ');
        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $app_conf = $conf['app-config'];
        if ((bool)$app_conf['show_app_name']) { // ? if show app name
            // # append app name
            $headTitleHelper->append($app_conf['app_name']);
        }
        // # get controller name
        $control_name = $routeMatch->getParam('controller', null);
        // # get module name
        $module_name = substr($control_name, 0, strpos($control_name, '\\'));
        // # append module name
        $headTitleHelper->append(($title != null) ? $title : $module_name);
        // !SECTION set meta title
        $ini_reader = new \Laminas\Config\Reader\Ini();
        $ini_reader->setProcessSections(false);
        $conf = $ini_reader->fromFile(ini_path() . "content-security-policy.ini");
        // !d($conf);die();
        $csp = [];
        foreach ($conf as $k=>$v) {
            $tmp = implode(" ", $v).";";
            $csp[] = $k." ".$tmp;
        }
        // !d($csp);die();
        $conf = $ini_reader->fromFile(ini_path() . "view-port.ini");
        // !d($conf);die();
        $vport = [];
        foreach ($conf as $k=>$v) {
            $vport[] = $k."=".$v;
        }
        // INFO get headMeta helper
        $headMetaHelper = $viewHelperManager->get('headMeta');
        $headMetaHelper
        // INFO set viewport meta
        ->appendName('viewport', implode(" ", $vport))
        // INFO set X-UA-Compatible HttpEquiv meta
            ->appendHttpEquiv('X-UA-Compatible', 'IE=edge')
        // INFO set Content-Security-Policy HttpEquiv meta
            ->appendHttpEquiv('Content-Security-Policy', implode(" ", $csp));
        
        $headLinkHelper = $viewHelperManager->get('headLink');
        $headLinkHelper->setAlternate('/img/fav.ico', 'image/gif', null, $extras = ['rel'=>'icon']);
        /**
         * # get request
         * @var HttpRequest $request
         */
        $request = $event->getRequest();
        // # get ServiceManager
        $serviceManager = $event->getApplication()->getServiceManager();
        // # get authentication service
        $authService = $serviceManager->get(AuthenticationService::class);
        $sessionManager = $serviceManager->get(\Laminas\Session\SessionManager::class);
        if (!$request->isXmlHttpRequest()) { // ? not ajax
            // $par_header = $request->getHeaders()->toArray();
            // zdebug($request->getUriString());
            // !d(get_class_methods($request));die();
            // # get request uri
            // $uri = $request->getRequestUri();
            $uri = $request->getUriString();
            $req_tmp = time();
            // !d($par_header);die();
            $md5_uri = md5($uri);
            $viewModel->setVariable('_md5_uri', $md5_uri);
            // zdebug($md5_uri);//die();
            if ($sessionManager->sessionExists() && $sessionManager->isValid()) {
                // if ($authService->hasIdentity()) { // ? has identity (has been login)
                // # get data container
                $container_data = $serviceManager->get('container_data');
                // # generate random hex
                $csrf = bin2hex(random_bytes(32));
                // # unset csrf session data
                $container_data->offsetUnset($md5_uri . "-CSRF");
                // # set csrf session data
                $container_data->offsetSet($md5_uri . "-CSRF", $csrf);
                // # set csrf token meta
                $headMetaHelper->prependName($md5_uri . '-Csrf-Token', $container_data->offsetGet($md5_uri . "-CSRF"));
            } else { // ? not login
                // # generate random hex
                $csrf = bin2hex(random_bytes(32));
                // # unset csrf session data
                unset($_SESSION[$md5_uri . '-CSRF']);
                // # set csrf session data
                $_SESSION[$md5_uri . '-CSRF'] = $csrf;
                // # set csrf token meta
                $headMetaHelper->prependName($md5_uri . '-Csrf-Token', $_SESSION[$md5_uri . '-CSRF']);
            }
        } elseif ($request->isXmlHttpRequest()) { // ? is ajax
        }
    }
    // SECTION setLayoutMeta
    /**
     * checkAccess function
     *
     * @param MvcEvent $event
     * @param AuthenticationService $authService
     * @param RouteMatch $routematch
     * @return boolean $ret
     */
    // SECTION checkAccess
    public function checkAccess(MvcEvent $event, $authService, RouteMatch $routematch)
    {
        $identity = $authService->getIdentity();
        /** @var AuthenticationAdapter $authAdapter */
        $authAdapter = $authService->getAdapter();
        /**
         * # init return variable
         * @var bool $ret
         */
        $ret = false;
        // # get route name
        $route_name = $routematch->getMatchedRouteName();
        // zdebug($route_name);die();
        // # user route access
        // zdebug($identity);die();
        $serviceManager = $event->getApplication()->getServiceManager();
        $dataCache = $serviceManager->get("data-file");
        $salt = "cache-data-accessRoute";
        $param = [
            'uid' => $identity['id'] ?? '',
        ];
        // zdebug($param);
        $crypt1 = hash('sha1', $salt);
        $crypt2 = hash('sha256', json_encode($param));
        $key =  'accessRoute_' . $crypt1 . '_' . $crypt2;
        // zdebug($key);die();
        // !d(!$dataCache->hasItem($key));die();
        $v = null;
        if (!$dataCache->hasItem($key)) {
            $v = $authAdapter->rebuildAccessRoute($identity);
        } else {
            $v = $dataCache->getItem($key);
            if ($v===null) {
                $v = $authAdapter->rebuildAccessRoute($identity);
            }
        }
        // !d($key,$v);die();
        $access_route = [];
        if ($v!==null) {
            try {
                $access_route = json_decode($v, true);
            } catch (\Exception $e) {
            }
        }
        // !d($access_route);die();
        // $access_route = $identity['accessRoute'];
        // !d($access_route);die();
        // # get viewModel
        $viewModel = $event->getViewModel();
        // # get view variable
        $vars = $viewModel->getVariable('_vars_');
        if (array_key_exists($route_name, $access_route)) { // INFO user has access (check by route name)
            $ret = true;
            if ($access_route[$route_name] ?? null !== null) { // ? route access param not null
                // # set view layout by config from route param
                $viewModel->setTemplate('layout/' . $access_route[$route_name]);
                // # set view variable
                $vars['layout'] = $access_route[$route_name];
                $viewModel->setVariable('_vars_', $vars);
            }
        } else { // INFO user doesn't have  access (check by route name)
            // # get controller name
            $controller_name = $routematch->getParam('controller', null);
            // zdebug($controller_name);die();
            $control_name = explode("\\", $controller_name);
            $control_name = $control_name[count($control_name) - 1];
            $control_name = str_replace("Controller", "", $control_name);
            // zdebug($control_name);die();
            // # get module name
            $module_name = substr($controller_name, 0, strpos($controller_name, '\\'));
            // zdebug($module_name);die();
            // # get action name
            $action_name = $routematch->getParam('action', null);
            // # user script access
            $salt = "cache-data-accessScript";
            $param = [
                'uid' => $identity['id'] ?? '',
            ];
            // zdebug($param);
            $crypt1 = hash('sha1', $salt);
            $crypt2 = hash('sha256', json_encode($param));
            $key =  'accessScript_' . $crypt1 . '_' . $crypt2;
            // zdebug($key);
            $v = null;
            if (!$dataCache->hasItem($key)) {
                $v = $authAdapter->rebuildAccessScript($identity);
            } else {
                $v = $dataCache->getItem($key);
                if ($v===null) {
                    $v = $authAdapter->rebuildAccessScript($identity);
                }
            }
            $access_script = [];
            if ($v!==null) {
                try {
                    $access_script = json_decode($v, true);
                } catch (\Exception $e) {
                }
            }
            // $access_script = $identity['accessScript'];
            // zdebug($module_name);
            // zdebug($control_name);
            // zdebug($action_name);
            // zdebug($access_script);die();
            if (
                array_key_exists($module_name, $access_script)
                && array_key_exists($control_name, $access_script[$module_name])
                && (
                    array_key_exists($action_name, $access_script[$module_name][$control_name])
                    || array_key_exists("*", $access_script[$module_name][$control_name])
                )
            ) { // INFO module has access + controller has access + action has access
                $ret = true;
            } elseif (
                array_key_exists($module_name, $access_script)
                && array_key_exists("*", $access_script[$module_name])
                && (
                    array_key_exists($action_name, $access_script[$module_name]["*"])
                    || array_key_exists("*", $access_script[$module_name]["*"])
                )
            ) { // INFO module has access + controller * + action has access
                $ret = true;
            } elseif (
                array_key_exists("*", $access_script)
                && array_key_exists($control_name, $access_script["*"])
                && (
                    array_key_exists($action_name, $access_script["*"][$control_name])
                    || array_key_exists("*", $access_script["*"][$control_name])
                )
            ) { // INFO module * + controller has access + action has access
                $ret = true;
            } elseif (
                array_key_exists("*", $access_script)
                && array_key_exists("*", $access_script["*"])
                && (
                    array_key_exists($action_name, $access_script["*"]["*"])
                    || array_key_exists("*", $access_script["*"]["*"])
                )
            ) { // INFO module * + controller * + action has access
                $ret = true;
            }
            // zdebug($access_script[$module_name][$control_name][$action_name]);die();
            if ((($access_script[$module_name][$control_name][$action_name])??null) !== null) { // ? action param not null
                // # set view layout by config from route param
                $viewModel->setTemplate('layout/' . $access_script[$module_name][$control_name][$action_name]);
                // # set view variable
                $vars['layout'] = $access_script[$module_name][$control_name][$action_name];
                $viewModel->setVariable('_vars_', $vars);
            }
        }
        return $ret;
    }
    // !SECTION checkAccess
    /**
     * redirectGuest function
     *
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @param ServiceManager $serviceManager
     * @param AbstractActionController $controller
     * @return void
     */
    // !SECTION redirectGuest
    public function redirectGuest(HttpRequest $request, HttpResponse $response, ServiceManager $serviceManager, $controller, $routeParams)
    {
        $uri = $request->getUri();
        $uri->setScheme(null)->setHost(null)->setPort(null)->setUserInfo(null);
        $redirect_url = $uri->toString();
        $controller_name = $routeParams['controller'] ?? null;
        // !d($controller_name);die();
        $query = $request->getQuery('module', '');
        $module = null;
        if ($query === '' && $controller_name !== null) {
            $module = substr($controller_name, 0, strpos($controller_name, '\\'));
        } else {
            $module = $query;
        }
        $lowmodule = strtolower($module);
        /** @var HelperPluginManager $viewHelperManager */
        $viewHelperManager = $serviceManager->get('ViewHelperManager');
        /** @var Routing routing */
        $routing = $viewHelperManager->get('Routing');
        // !d($lowmodule,$routing->hasRoute($lowmodule . '/auth/login'));die();
        // !d($routing->hasRoute($lowmodule));die();
        if ($routing->hasRoute($lowmodule . '/auth/login')) {
            $redirectRoute = $lowmodule . '/auth/login';
            // die($redirectRoute);
            return $controller->redirect()->toRoute($redirectRoute, [], ['query' => ['redirect' => $redirect_url, 'module' => $module]]);
        } elseif ($routing->hasRoute($lowmodule)) {
            return $controller->redirect()->toRoute($lowmodule);
        } elseif ($routing->hasRoute('auth/login')) {
            $redirectRoute = 'auth/login';
            return $controller->redirect()->toRoute($redirectRoute, [], ['query' => ['redirect' => $redirect_url, 'module' => $module]]);
        } elseif ($routing->hasRoute('landing')) {
            return $controller->redirect()->toRoute('landing');
        } else {
            $response->setStatusCode(HTTP_UNAUTHORIZED);
            $uri = $request->getUri();
            $uri->setScheme(null)
                ->setHost(null)
                ->setPort(null)
                ->setUserInfo(null);
            $redirect_url = $uri->toString();
            return $controller->redirect()->toRoute('auth/error', ['code' => HTTP_UNAUTHORIZED], ['query' => ['ref' => $redirect_url]]);
        }
    }
    // !SECTION redirectGuest
}
