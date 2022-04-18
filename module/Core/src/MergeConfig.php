<?php
declare(strict_types = 1);
namespace Core;

use Laminas\Stdlib\ArrayUtils;

class MergeConfig
{
    /**
     * appendRoutes function
     *
     * @param array $routes
     * @return array
     */
    // SECTION appendRoutes
    public function appendRoutes(&$routes)
    {
        // !d($routes);die();
        $me = $this;
        /**
         * @var bool $fromcache
         * # set routecache GET param to 0 to reload cache route
         */
        $from_cache = ($_GET['routecache'] ?? "1") === "1";
        // !d($from_cache);die('aa');
        if ($from_cache) {
            $from_cache = ($_GET['dbcache'] ?? "1") === "1";
        }
        // !d($from_cache);die();
        /**
         * @var array $tmp_routes
         * # routes data
         */
        $tmp_routes = [];
        $routes_loaded = false;
        $routecache_file = "all_active_route";
        if ($from_cache) { // ? load route from cache
            if (check_json_cache($routecache_file)) { // ? check route cache exist
                $tmp_routes = load_json_cache($routecache_file);
                $routes_loaded = true;
            }
        }
        if (!$routes_loaded) { // ? load route from db
            $ini_reader = new \Laminas\Config\Reader\Ini();
            $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
            $app_conf = $conf['app-config'];
            $session_conf = $conf['session'];
            try {
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
                    /**
                     * @var array $route
                     * # select routes data from db
                     */
                    $route = $medooDb->query("select * from get_" . $routecache_file . "()", [])->fetchAll();
                } else {
                    $mysql_conf = $conf['db-mysql'];
                    // zdebug($mysql_conf);
                    // die();
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
                        'command' => [
                            // 'SET search_path TO '.$session_conf['db_schema_name']
                        ]
                    ]);
                    // zdebug("call get_" . $routecache_file . "()");
                    // die();
                    /**
                     * @var array $route
                     * # select routes data from db
                     */
                    $route = $medooDb->query("call get_" . $routecache_file . "()", [])->fetchAll();
                }
            } catch (\Exception $e) {
                zdebug($e->getMessage());
                die();
            }
            // !d($route);die();
            $tmp_routes = $me->parseRouteData($route);
            save_json_cache($tmp_routes, $routecache_file);
        }
        // d($routes, $tmp_routes);
        // zdebug(ArrayUtils::merge($routes, $tmp_routes));
        // die();
        return ArrayUtils::merge($routes, $tmp_routes);
    }
    // !SECTION appendRoutes
    /**
     * parseRouteData function
     *
     * @param array $data
     * @return array $tmp_routes
     */
    // SECTION parseRouteData
    public function parseRouteData(&$data)
    {
        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $app_conf = $conf['app-config'];
        $layout_conf = $conf['layout'];

        $tmp_route = $data;
        $tmp_route2 = [];
        $tmp_routes2 = [];
        $tmp_routes = [];
        // zdebug($tmp_route);
        // die();
        if (count($tmp_route) > 0) { // ? route data from db more than 0 row
            foreach ($tmp_route as $k => $v) {
                $tmp_routes2[$v['id']] = $v;
                if (($v['parent_name'] === null || $v['parent_name'] === '') && !isset($tmp_routes[$v['name']])) {
                    $v['method'] = $v['method'] === null ? "[]" : $v['method'];
                    $tmpmethod = json_decode(strtoupper($v['method']), true);
                    $tmp_routes[$v['name']] = [
                        'type' => ($v['type'] === '' || $v['type'] === null) ? 'literal' : $v['type'],
                        'may_terminate' => $v['may_terminate'] === '0' ? false : true,
                        'options' => [
                            'route' => $v['route'] ?? '',
                            'defaults' => [
                                'id' => (int)$v['id'],
                                'title' => $v['title'] ?? '',
                                'session_name' => $v['session_name'] ?? '',
                                'layout' => $v['layout_name'] ?? $layout_conf['default'],
                                'method' => is_array($tmpmethod) ? $tmpmethod : [],
                                'show_title' => (int)$v['show_title'] === 1 ? true : false,
                                'is_logging' => (int)$v['is_logging'] === 1 ? true : false,
                                'is_caching' => (int)$v['is_caching'] === 1 ? true : false,
                                'is_public' => (int)$v['is_public'] === 1 ? true : false,
                                'is_guest' => (int)$v['is_public'] === 2 ? true : false,
                            ],
                        ],
                        'child_routes' => [],
                    ];
                    if ($v['action'] !== '' && $v['action'] !== null) {
                        $tmp = [
                            $v['module_name'],
                            'Controller',
                            $v['control_name'] . 'Controller',
                        ];
                        $tmp_routes[$v['name']]['options']['defaults']['controller'] = implode("\\", $tmp);
                        $tmp_routes[$v['name']]['options']['defaults']['action'] = $v['act_name'];
                    }
                } else {
                    $tmp_route2[$v['id']] = $v;
                }
            }
        }
        $tmp_route = $tmp_route2;
        foreach ($tmp_route as $k => $v) {
            if (isset($tmp_routes[$v['parent_name']])
                && !isset($tmp_routes[$v['parent_name']]['child_routes'][$v['name']])) {
                $v['method'] = $v['method'] === null ? "[]" : $v['method'];
                $tmpmethod = json_decode(strtoupper($v['method']), true);
                $tmp_routes[$v['parent_name']]['child_routes'][$v['name']] = [
                    'type' => ($v['type'] === '' || $v['type'] === null) ? 'literal' : $v['type'],
                    'may_terminate' => $v['may_terminate'] === '0' ? false : true,
                    'options' => [
                        'route' => $v['route'] ?? '',
                        'defaults' => [
                            'id' => (int)$v['id'],
                            'title' => $v['title'] ?? '',
                            'session_name' => $v['session_name'] ?? '',
                            'layout' => $v['layout_name'] ?? $layout_conf['default'],
                            'method' => is_array($tmpmethod) ? $tmpmethod : [],
                            'show_title' => (int)$v['show_title'] === 1 ? true : false,
                            'is_logging' => (int)$v['is_logging'] === 1 ? true : false,
                            'is_caching' => (int)$v['is_caching'] === 1 ? true : false,
                            'is_public' => (int)$v['is_public'] === 1 ? true : false,
                            'is_guest' => (int)$v['is_public'] === 2 ? true : false,
                        ],
                    ],
                    'child_routes' => [],
                ];
                if ($v['action'] !== '' && $v['action'] !== null) {
                    $tmp = [
                        $v['module_name'],
                        'Controller',
                        $v['control_name'] . 'Controller',
                    ];
                    $tmp_routes[$v['parent_name']]['child_routes'][$v['name']]['options']['defaults']['controller'] = implode("\\", $tmp);
                    $tmp_routes[$v['parent_name']]['child_routes'][$v['name']]['options']['defaults']['action'] = $v['act_name'];
                }
                unset($tmp_route2[$k]);
            }
        }
        $tmp_route = $tmp_route2;
        foreach ($tmp_route as $k => $v) {
            $tmp = [];
            if (isset($tmp_routes2[$v['parent']])) {
                $tmp = $tmp_routes2[$v['parent']];
            }
            if (count($tmp) > 0
                && isset($tmp_routes[$tmp['parent_name']])
                && isset($tmp_routes[$tmp['parent_name']]['child_routes'][$tmp['name']])
                && !isset($tmp_routes[$tmp['parent_name']]['child_routes'][$tmp['name']]['child_routes'][$v['name']])) {
                $v['method'] = $v['method'] === null ? "[]" : $v['method'];
                $tmpmethod = json_decode(strtoupper($v['method']), true);
                $tmp_routes[$tmp['parent_name']]['child_routes'][$tmp['name']]['child_routes'][$v['name']] = [
                    'type' => ($v['type'] === '' || $v['type'] === null) ? 'literal' : $v['type'],
                    'may_terminate' => $v['may_terminate'] === '0' ? false : true,
                    'options' => [
                        'route' => $v['route'] ?? '',
                        'defaults' => [
                            'id' => (int)$v['id'],
                            'title' => $v['title'] ?? '',
                            'session_name' => $v['session_name'] ?? '',
                            'layout' => $v['layout_name'] ?? _DEFAULT_LAYOUT_,
                            'method' => is_array($tmpmethod) ? $tmpmethod : [],
                            'show_title' => (int)$v['show_title'] === 1 ? true : false,
                            'is_logging' => (int)$v['is_logging'] === 1 ? true : false,
                            'is_caching' => (int)$v['is_caching'] === 1 ? true : false,
                            'is_public' => (int)$v['is_public'] === 1 ? true : false,
                            'is_guest' => (int)$v['is_public'] === 2 ? true : false,
                        ],
                    ],
                    'child_routes' => [],
                ];
                if ($v['action'] !== '' && $v['action'] !== null) {
                    $tmpx = [
                        $v['module_name'],
                        'Controller',
                        $v['control_name'] . 'Controller',
                    ];
                    $tmp_routes[$tmp['parent_name']]['child_routes'][$tmp['name']]['child_routes'][$v['name']]['options']['defaults']['controller'] = implode("\\", $tmpx);
                    $tmp_routes[$tmp['parent_name']]['child_routes'][$tmp['name']]['child_routes'][$v['name']]['options']['defaults']['action'] = $v['act_name'];
                }
                unset($tmp_route2[$k]);
            }
        }
        $tmp_route = $tmp_route2;
        foreach ($tmp_route as $k => $v) {
            $tmp = [];
            if (isset($tmp_routes2[$v['parent']])) {
                $tmp = $tmp_routes2[$v['parent']];
            }
            if (isset($tmp_routes2[$tmp['parent']])) {
                $tmp2 = $tmp_routes2[$tmp['parent']];
            }
            if (count($tmp) > 0 && count($tmp2) > 0
                && isset($tmp_routes[$tmp2['parent_name']])
                && isset($tmp_routes[$tmp2['parent_name']]['child_routes'][$tmp2['name']])
                && isset($tmp_routes[$tmp2['parent_name']]['child_routes'][$tmp2['name']])
                && isset($tmp_routes[$tmp2['parent_name']]['child_routes'][$tmp2['name']]['child_routes'][$tmp['name']])
                && !isset($tmp_routes[$tmp2['parent_name']]['child_routes'][$tmp2['name']]['child_routes'][$tmp['name']]['child_routes'][$v['name']])) {
                $v['method'] = $v['method'] === null ? "[]" : $v['method'];
                $tmpmethod = json_decode(strtoupper($v['method']), true);
                $tmp_routes[$tmp2['parent_name']]['child_routes'][$tmp2['name']]['child_routes'][$tmp['name']]['child_routes'][$v['name']] = [
                    'type' => ($v['type'] === '' || $v['type'] === null) ? 'literal' : $v['type'],
                    'may_terminate' => $v['may_terminate'] === '0' ? false : true,
                    'options' => [
                        'route' => $v['route'] ?? '',
                        'defaults' => [
                            'id' => (int)$v['id'],
                            'title' => $v['title'] ?? '',
                            'session_name' => $v['session_name'] ?? '',
                            'layout' => $v['layout_name'] ?? _DEFAULT_LAYOUT_,
                            'method' => is_array($tmpmethod) ? $tmpmethod : [],
                            'show_title' => (int)$v['show_title'] === 1 ? true : false,
                            'is_logging' => (int)$v['is_logging'] === 1 ? true : false,
                            'is_caching' => (int)$v['is_caching'] === 1 ? true : false,
                            'is_public' => (int)$v['is_public'] === 1 ? true : false,
                            'is_guest' => (int)$v['is_public'] === 2 ? true : false,
                        ],
                    ],
                    'child_routes' => [],
                ];
                if ($v['action'] !== '' && $v['action'] !== null) {
                    $tmpx = [
                        $v['module_name'],
                        'Controller',
                        $v['control_name'] . 'Controller',
                    ];
                    $tmp_routes[$tmp2['parent_name']]['child_routes'][$tmp2['name']]['child_routes'][$tmp['name']]['child_routes'][$v['name']]['options']['defaults']['controller'] = implode("\\", $tmpx);
                    $tmp_routes[$tmp2['parent_name']]['child_routes'][$tmp2['name']]['child_routes'][$tmp['name']]['child_routes'][$v['name']]['options']['defaults']['action'] = $v['act_name'];
                }
                unset($tmp_route2[$k]);
            }
        }
        // d($tmp_routes);die();
        return $tmp_routes;
    }
    // !SECTION parseRouteData
    /**
     * appendControllers function
     *
     * @param array $controllers
     * @return array
     */
    // SECTION appendControllers
    public function appendControllers(&$controllers)
    {
        /**
         * @var bool $fromcache
         * # set routecache GET param to 0 to reload cache route
         */
        $from_cache = ($_GET['controllercache'] ?? "1") === "1";
        $from_cache = ($_GET['dbcache'] ?? $from_cache) === "1";
        /**
         * @var array $tmp_controllers
         * # routes data
         */
        $tmp_controllers = [];
        $controllers_loaded = false;
        $controllercache_file = "all_active_script";
        if ($from_cache) { // ? load controller from cache
            if (check_json_cache($controllercache_file)) { // # check controller cache exist
                $tmp_controllers = load_json_cache($controllercache_file);
                $controllers_loaded = true;
            }
        }
        if (!$controllers_loaded) { // ? load controller from db
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
                /**
                 * @var array $controller
                 * # select controller data from db
                 */
                $controller = $medooDb->query("select * from get_" . $controllercache_file . "()", [])->fetchAll();
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
                    'command' => [
                        // 'SET search_path TO '.$session_conf['db_schema_name']
                    ]
                ]);
                /**
                 * @var array $controller
                 * # select controller data from db
                 */
                $controller = $medooDb->query("call get_" . $controllercache_file . "()", [])->fetchAll();
            }
            // !d($controller);die();
            foreach ($controller as $v) {
                $tmp = [
                    $v['module_name'],
                    'Controller',
                    $v['control_name'] . 'Controller',
                ];
                $tmp_controllers[implode("\\", $tmp)] = $v['control_factory'];
            }
            save_json_cache($tmp_controllers, $controllercache_file);
        }
        // !d($tmp_controllers);die();
        return ArrayUtils::merge($controllers, $tmp_controllers);
    }
    // !SECTION appendControllers
    /**
     * appendModules function
     *
     * @param array $modules
     * @return array
     */
    // SECTION appendModules
    public function appendModules(&$modules)
    {
        /**
         * @var bool $fromcache
         * # set routecache GET param to 0 to reload cache route
         */
        $from_cache = ($_GET['modulecache'] ?? "1") === "1";
        $from_cache = ($_GET['dbcache'] ?? $from_cache) === "1";
        /**
         * @var array $tmp_modules
         * # routes data
         */
        $tmp_modules = [];
        $modules_loaded = false;
        $modulecache_file = "all_active_module";
        if ($from_cache) { // ? load module from cache
            if (check_json_cache($modulecache_file)) { // # check module cache exist
                $tmp_modules = load_json_cache($modulecache_file);
                $modules_loaded = true;
            }
        }
        if (!$modules_loaded) { // ? load module from db
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
                /**
                 * @var array $module
                 * # select module data from db
                 */
                $module = $medooDb->query("select * from get_" . $modulecache_file . "()", [])->fetchAll();
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
                    'command' => [
                        // 'SET search_path TO '.$session_conf['db_schema_name']
                    ]
                ]);
                /**
                 * @var array $module
                 * # select module data from db
                 */
                $module = $medooDb->query("call get_" . $modulecache_file . "()", [])->fetchAll();
            }
            // !d($controller);die();
            foreach ($module as $v) {
                $tmp_modules[$v['name']]['session_name'] = $v['session_name'];
            }
            save_json_cache($tmp_modules, $modulecache_file);
        }
        // !d($tmp_controllers);die();
        return ArrayUtils::merge($modules, $tmp_modules);
    }
    // !SECTION appendModules
}
