<?php
namespace Core\Helper\Controller;

use Laminas\Authentication\AuthenticationService;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Mvc\MvcEvent;
use Laminas\Session\SessionManager;
use Laminas\Stdlib\ArrayUtils;

/**
 * This view helper class displays a menu bar.
 */

class Logging extends AbstractPlugin
{
    private $config;
    private $container;
    private $authService;
    private $sessionManager;
    private $log_dir = "data/log/";

    public function __construct($container, $config)
    {
        $me = $this;
        $me->container = $container;
        $me->config = $config;
        try {
            $me->authService = $container->get(AuthenticationService::class);
        } catch (\Exception $e) {
            $me->authService = null;
        }
        try {
            $me->sessionManager = $container->get(SessionManager::class);
        } catch (\Exception $e) {
            $me->sessionManager = null;
        }

        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $me->log_dir = $conf['app-config']['log_dir'];
    }

    public function warningLogging($type, $filename, $controlAction, $extra = [], $separator = "|")
    {
        $me = $this;
        return $me->fileLogging("warning", $type, $filename, $controlAction, $extra, $separator);
    }

    public function infoLogging($type, $filename, $controlAction, $extra = [], $separator = "|")
    {
        $me = $this;
        return $me->fileLogging("info", $type, $filename, $controlAction, $extra, $separator);
    }

    public function alertLogging($type, $filename, $controlAction, $extra = [], $separator = "|")
    {
        $me = $this;
        return $me->fileLogging("alert", $type, $filename, $controlAction, $extra, $separator);
    }

    public function errorLogging($type, $filename, $controlAction, $extra = [], $separator = "|")
    {
        $me = $this;
        return $me->fileLogging("error", $type, $filename, $controlAction, $extra, $separator);
    }

    public function fileLogging($level, $type, $filename, $controlAction, $extra = [], $separator = "|")
    {
        $ret = [
            "msg"=>"FAILED",
            "ret"=>false
        ];

        /** @var MvcEvent $e  */
        $e = $controlAction->getEvent();
        if ($level!=="" && $type!=="" && $filename!=="") {
            $me = $this;

            $auth = $me->authService;
            $user = [
                'id' => null,
                'username' => null,
                'full_name' => null,
                'session_id' => null,
                'session_name' => null,
            ];
            if ($auth!==null && $auth->hasIdentity()) { // ? has identity (has been login)
                $session = $me->sessionManager;
                $tmp = $auth->getIdentity();
                $sess_name = $GLOBALS['SESS_OPT']['name'];
                if ($session!==null && $session->sessionExists()) { // ? session exist
                    $sess_id = $session->getId();
                    $sess_name = $session->getName() ?? $GLOBALS['SESS_OPT']['name'];
                }
                $user = [
                    'id' => $tmp['id'] ?? null,
                    'username' => $tmp['username'] ?? null,
                    'full_name' => $tmp['full_name'] ?? null,
                    'session_id' => $sess_id,
                    'session_name' => $sess_name,
                ];
            }
            /** @var Request $request */
            $request = $e->getRequest();
            $pHeaders = $request->getHeaders()->toArray();
            $routeMatch = $e->getRouteMatch();
            $par = [];
            $par['route_name'] = $routeMatch->getMatchedRouteName();
            $par['route'] = $request->getUri()->getPath();
            $route_params = $routeMatch->getParams();
            $out = rmImportantData($route_params);
            $par['param'] = json_encode($out);
            $qry = $request->getQuery()->toArray();
            $out = rmImportantData($qry);
            $par['query'] = json_encode($out);
            $par['user'] = $routeMatch->getParam('user_id', $user['id']);
            $par['username'] = $routeMatch->getParam('user_name', $user['username']);
            $par['ip_address'] = $request->getServer()->get('REMOTE_ADDR');
            $par['user_agent'] = $request->getServer()->get('HTTP_USER_AGENT');
            $par['method'] = $request->getMethod();
            $ctn = $request->getContent();
            $out = rmImportantData($ctn);
            $par['content'] = json_encode($out);
            $target = $e->getTarget();
            $par['sess_id'] = $user['session_id'];
            $par['sess_name'] = $user['session_name'];
            $par['controller'] = $routeMatch->getParam('controller', null);
            $par['action'] = $routeMatch->getParam('action', null);
            $par['route_id'] = $routeMatch->getParam('id', null);
            $post = $request->getPost()->toArray();
            $out = rmImportantData($post);
            $par['post'] = json_encode($out);
            $par['header'] = json_encode($pHeaders);
            $ext = "";
            if ($extra!=null && is_array($extra) && count($extra)>0) {
                $ext = json_encode($extra);
            }
            $par['extra'] = $ext;
            try {
                $format = '%timestamp%'.$separator.'%priorityName%'.$separator.'%message%';
                $formatter = new \Laminas\Log\Formatter\Simple($format);
                $writer = new \Laminas\Log\Writer\Stream($me->log_dir.$type.'/'.date('Ymd').'_'.$filename.'.log');
                $writer->setFormatter($formatter);
                $logger = new \Laminas\Log\Logger();
                $logger->addWriter($writer);

                switch ($level) {
                    case 'error':
                        $logger->err(json_encode($par));
                        break;
                    case 'alert':
                        $logger->alert(json_encode($par));
                        break;
                    case 'info':
                        $logger->info(json_encode($par));
                        break;
                    case 'warning':
                        $logger->warn(json_encode($par));
                        break;
                    case 'error':
                        $logger->err(json_encode($par));
                        break;
                    default:
                        break;
                }
                $logger = null;
                $ret = [
                    "msg"=>"SUCCESS",
                    "ret"=>true
                ];
            } catch (\Exception $e) {
                $ret['msg'] = $e->getMessage();
            }
        }

        return $ret;
    }

    public function routeLogging($params, $user = [], $vars = [])
    {
        $me = $this;
        // Debug::dump($params);
        // $pHeader = $params->fromHeader();
        $pRoute = $params->fromRoute();
        $pQuery = $params->fromQuery();
        // !d($_SERVER);
        // d($pHeader);
        // d($pRoute);
        // d($pQuery);
        // d($user);
        // die('sss');

        $db = $me->container->get('db-sys');
        $ret = [
            "msg"=>"FAILED",
            "ret"=>false
        ];
        $data = [
            'username' => $user['username'] ?? "",
            'useragent' => $_SERVER['HTTP_USER_AGENT'] ?? "",
            // 'uid' => $user['id'] ?? "",
            'route_name' => $vars['route_name'] ?? "",
            'route' => json_encode($pRoute),
            'query' => json_encode($pQuery),
            'ipaddress' => $_SERVER['REMOTE_ADDR'] ?? "",
            // 'fullname' => $user['full_name'] ?? ""
        ];
        $sql = "INSERT INTO _sys._route_log
        (username, ipaddress, created_date, user_agent, route_name, route, query)
        VALUES(:username, :ipaddress, CURRENT_TIMESTAMP, :useragent, :route_name, :route, :query)";
        // die($sql);
        try {
            $stmt = $db->createStatement($sql, $data);
            // !d($stmt);die();
            $result = $stmt->execute(); // die("ok");
            // !d($result);die();
            $result->getResource()->closeCursor();
            if (!$result->valid()) {
                return [
                    "ret" => false,
                    "affected_row" => 0,
                    "generated_value" => 0,
                    "data" => $data,
                    "msg" => "FAILED INSERT"
                ];
            } else {
                return [
                    "ret" => true,
                    "affected_row" => $result->getAffectedRows(),
                    "generated_value" => $result->getGeneratedValue(),
                    "data" => $data,
                    "msg" => "SUCCESS INSERT"
                ];
            }
        } catch (\Exception $e) {
            return [
                "ret" => false,
                "affected_row" => 0,
                "generated_value" => 0,
                "data" => $data,
                "msg" => $e->getMessage()
            ];
        }

        return $ret;
    }

    public function dbLogging($table = "", $controlAction, $extra = [])
    {
        $ret = [
            "msg"=>"FAILED",
            "ret"=>false
        ];

        // !d($table,get_class($controlAction),get_class_methods($controlAction));
        // !d(get_class_methods($controlAction->params()),$controlAction->params()->fromRoute());
        // !d(get_class($controlAction->getEvent()));
        /** @var MvcEvent $e  */
        $e = $controlAction->getEvent();
        /** @var Request $request  */
        $request = $controlAction->getRequest();
        // !d(get_class_methods($request));
        /** @var Response $response  */
        $response = $controlAction->getResponse();
        // !d(get_class_methods($response));
        // die();
        if ($table!=="") {
            $me = $this;

            // # get authentication service
            $auth = $me->authService;
            // # init user array
            $user = [
                'id' => null,
                'username' => null,
                'full_name' => null,
                'session_id' => null,
                'session_name' => null,
            ];
            if ($auth!==null && $auth->hasIdentity()) { // ? has identity (has been login)
                // # get SessionManager
                $session = $me->sessionManager;
                // # get identity
                $tmp = $auth->getIdentity();
                // # get session name
                $sess_name = $GLOBALS['SESS_OPT']['name'];
                if ($session!==null && $session->sessionExists()) { // ? session exist
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
            // !d($user);//die();
            /** @var HttpRequest $request */
            $request = $e->getRequest();
            // # get request header
            $pHeaders = $request->getHeaders()->toArray();
            // $pHeaders = $controlAction->params()->fromHeader();
            // !d($pHeaders);
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
            // $route_params = $controlAction->params()->fromRoute();
            $out = rmImportantData($route_params);
            // # array to json
            $par['param'] = json_encode($out);
            // # remove important data (GET data)
            $qry = $request->getQuery()->toArray();
            // $qry = $controlAction->params()->fromQuery();
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
            // // # get response content
            // $par['response'] = $response->getContent();
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
            $ext = "";
            if ($extra!=null && is_array($extra) && count($extra)>0) {
                $ext = json_encode($extra);
            }
            $par['extra'] = $ext;
            // zdebug($par);die();
            $ini_reader = new \Laminas\Config\Reader\Ini();
            $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
            $app_conf = $conf['app-config'];
            try {
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
                // INFO insert to route log table
                $medooDb->insert("_".$table."_log", $par);
                $ret = [
                    "msg"=>"SUCCESS",
                    "ret"=>true
                ];
            } catch (\Exception $e) {
                $ret['msg'] = $e->getMessage();
            }
        }
        // zdebug($ret);die();
        return $ret;
    }
}
