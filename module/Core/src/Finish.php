<?php
declare(strict_types = 1);
namespace Core;

use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ArrayUtils;

class Finish
{
    /**
     * disconnectDB function
     *
     * @param array $db
     * @param ServiceManager $serviceLocator
     * @return void
     */
    // SECTION disconnectDB
    public function disconnectDB($db = array(), $serviceManager)
    {
        if (count($db) > 0) {
            foreach ($db as $key => $value) {
                /** @var Adapter $dbadapter */
                $dbadapter = $serviceManager->get($key);
                if ($dbadapter->getDriver()->getConnection()->isConnected()) {
                    $dbadapter->getDriver()->getConnection()->disconnect();
                }
            }
        }
        /** @var Adapter $db */
        $db = $serviceManager->get(\Laminas\Db\Adapter\Adapter::class);
        if ($db->getDriver()->getConnection()->isConnected()) {
            $db->getDriver()->getConnection()->disconnect();
        }
    }
    // !SECTION disconnectDB
    /**
     * Undocumented function
     *
     * @param MvcEvent $e
     * @return void
     */
    // SECTION routeLogging
    public function routeLogging(MvcEvent $e)
    {
        // # get app
        $app = $e->getApplication();
        // # get ServiceManager
        $servicesManager = $app->getServiceManager();
        // # get authentication service
        $auth = $servicesManager->get(AuthenticationService::class);
        // # init user array
        $user = [
          'id' => null,
          'username' => null,
          'full_name' => null,
          'session_id' => null,
          'session_name' => null,
        ];
        if ($auth->hasIdentity()) { // ? has identity (has been login)
            // # get SessionManager
            $session = $servicesManager->get(\Laminas\Session\SessionManager::class);
            // # get identity
            $tmp = $auth->getIdentity();
            // # get session name
            $sess_name = $GLOBALS['SESS_OPT']['name'];
            if ($session->sessionExists()) { // ? session exist
                // # get session id
                $sess_id = $session->getId();
                // # set session name if exist
                $sess_name = $session->getName() ?? $GLOBALS['SESS_OPT']['name'];
            }
            // # set user array
            $user = [
              'id' => $tmp['id'] ?? null,
              'username' => $tmp['username'] ?? null,
              'full_name' => $tmp['full_name'] ?? null,
              'session_id' => $sess_id,
              'session_name' => $sess_name,
          ];
        }
        // # get matched route
        $routeMatch = $e->getRouteMatch();
        /** @var HttpRequest $request */
        $request = $e->getRequest();
        // # get request header
        $pHeaders = $request->getHeaders()->toArray();
        // # get request _SERVER variable
        // - disable
        // $pServer = $request->getServer()->toArray();
        // # get request environment variable
        // - disable
        // $pEnv = $request->getEnv()->toArray();
        // # get matched route
        $routeMatch = $e->getRouteMatch();
        // # init par array
        $par = [];
        // # get matched route name
        $par['route_name'] = $routeMatch->getMatchedRouteName();
        // # get route path
        $par['route'] = $request->getUri()->getPath();
        // # remove important data (param data)
        $route_params = $routeMatch->getParams();
        $out = rmImportantData($route_params);
        // # array to json
        $par['param'] = json_encode($out);
        // # remove important data (GET data)
        $qry = $request->getQuery()->toArray();
        $out = rmImportantData($qry);
        // # array to json
        $par['query'] = json_encode($out);
        // # get user id
        $par['user'] = $routeMatch->getParam('user_id', $user['id']);
        // # get user name
        $par['username'] = $routeMatch->getParam('user_name', $user['username']);
        // # get remote addr
        $par['ip_address'] = $request->getServer()->get('REMOTE_ADDR');
        // # get user agent
        $par['user_agent'] = $request->getServer()->get('HTTP_USER_AGENT');
        // # get method
        $par['method'] = $request->getMethod();
        // # remove important data (request content)
        $ctn = $request->getContent();
        $out = rmImportantData($ctn);
        // # array to json
        $par['content'] = json_encode($out);
        // # get target
        $target = $e->getTarget();
        /** @var HttpResponse $response */
        $response = $target->getResponse();
        // # get response content
        $par['response'] = $response->getContent();
        // # get session id
        $par['sess_id'] = $user['session_id'];
        // # get session name
        $par['sess_name'] = $user['session_name'];
        // # get controller
        $par['controller'] = $routeMatch->getParam('controller', null);
        // # get action
        $par['action'] = $routeMatch->getParam('action', null);
        // # get route id
        $par['route_id'] = $routeMatch->getParam('id', null);
        // # remove important data (POST data)
        $post = $request->getPost()->toArray();
        $out = rmImportantData($post);
        // # array to json
        $par['post'] = json_encode($out);
        // # array to json
        $par['header'] = json_encode($pHeaders);
        // $sql = "INSERT INTO `_route_log` (route_name, route, param, query, `user`,username, ip_address, user_agent, `method`, content, response, sess_id, sess_name, controller, `action`, route_id, post, header) VALUES(:route_name, :route, :param, :query, :user, :username, :ip_address, :user_agent, :method, :content, :response, :sess_id, :sess_name, :controller, :action, :route_id, :post, :header)";
        // !d($par, $sql);
        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $app_conf = $conf['app-config'];
        if (($app_conf['main_db']??null)==="postgres") {
            $postgres_conf = $conf['db-postgres'];
            $session_conf = $conf['session'];
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
                'port' => $mysql_conf['admin']['port'],
            ]);
        }
        $route_conf = $conf['route'];
        $medooDb->insert($route_conf['db_table_name'], $par);
    }
    // !SECTION routeLogging
    /**
     * setCacheView function
     *
     * @param MvcEvent $e
     * @return void
     */
    // SECTION setCacheView
    public function setCacheView(MvcEvent $e)
    {
        $me = $this;
        // # get target
        $app = $e->getTarget();
        // # get route param
        //   !d($e->getRouteMatch());
        $routeParam = $e->getRouteMatch()->getParams();
        //   !d($routeParam);die();
        // # get route name
        $route = $e->getRouteMatch()->getMatchedRouteName();
        // # create salt
        $salt = "cache-view-" . $route;
        /** @var HttpRequest $request */
        $request = $e->getRequest();
        // # get POST data
        $post = $request->getPost()->toArray();
        // # get GET data
        $query = $request->getQuery()->toArray();
        // # join param
        $join = ArrayUtils::merge($post, $query);
        // # init view cache
        $from_cache = ($join['viewcache'] ?? "1") === "1";
        // # remove viewcache param
        if (isset($join['viewcache'])) {
            unset($join['viewcache']);
        }
        if (isset($join['dbcache'])) {
            unset($join['dbcache']);
        }
        // # create hash
        $crypt = hash('sha256', json_encode($join) . $salt);
        // # create key
        $key = 'route-' . $route . '-' . $crypt;
        $key = str_replace('/', '-', $key);
        $key = str_replace('$', '+', $key);
        $key = str_replace('.', '_', $key);
        /** @var HttpResponse $response */
        $response = $app->getResponse();
        // # get content
        $html = $response->getContent();
        // # voku HtmlMin
        $minifier = new \voku\helper\HtmlMin();
        $minify_html = $minifier->minify($html);
        /** @var ServiceManager $sm */
        $sm = $app->getServiceManager();
        // # get view-file cache
        $vcache = $sm->get('view-file');
        //   zdebug($routeParam);die();
        if ($routeParam['is_caching'] ?? false) {
            if (!$from_cache || !$vcache->hasItem($key)) {
                $vcache->setItem($key, $minify_html);
                $response->setContent($minify_html);
            } elseif ($vcache->hasItem($key)) {
                $content = $vcache->getItem($key);
                $response->setContent($content);
            }
        }
    }
    // !SECTION setCacheView
}
