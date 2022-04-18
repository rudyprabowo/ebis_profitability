<?php
declare(strict_types = 1);

namespace CoreAdmin\Controller;

use CoreAdmin\Model\UserModel;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Result;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\I18n\Validator\Alnum;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Validator\StringLength;
use Laminas\Validator\ValidatorChain;
use Laminas\View\Model\JsonModel;

class AjaxController extends AbstractActionController
{
    private $container;
    private $config;

    public function __construct($container, $config)
    {
        $me = $this;
        $me->container = $container;
        $me->config = $config;
    }

    public function loginAction()
    {
        // die('aaa');
        $me = $this;
        $ret = [
            "ret" => false,
            'msg' => 'INVALID REQUEST',
            'data' => [],
        ];
        // zdebug(get_class($me->getResponse()));die();

        /** @var Request $request */
        $request = $me->getRequest();
        if ($request->isPost()) {
            $pPost = $me->params()->fromPost();
            // zdebug($pPost);
            // die();
            if (array_keys_exist($pPost, ["account", "password", "remember"])) {
                $validAccountChain = new ValidatorChain();
                $validAccountChain->attach(new Alnum());
                $validAccountChain->attach(
                    new StringLength(['min' => 3, 'max' => 11]),
                    true, // break chain on failure
                    1
                );

                if ($validAccountChain->isValid($pPost['account'])) {
                    // email appears to be valid
                    $validPassChain = new StringLength(['min' => 3]);

                    if ($validPassChain->isValid($pPost['password'])) {
                        $authService = $me->container->get(AuthenticationService::class);
                        // zdebug($authService->hasIdentity());die();
                        if ($authService->hasIdentity()) {
                            $ret = [
                                "ret" => true,
                                'msg' => "HAS BEEN LOG IN",
                                'data' => [],
                            ];
                        } else {
                            $sessionManager = $me->container->get(\Laminas\Session\SessionManager::class);
                            $try_login = 0;
                            if ($sessionManager->sessionExists() && $sessionManager->isValid()) {
                                $login_container = $me->container->get('container_login');
                                if ($login_container->offsetExists("try_login")) {
                                    $try_login = $login_container->offsetGet("try_login");
                                } else {
                                    $login_container->offsetSet("try_login", 1);
                                }
                            } else {
                                if (isset($_SESSION["try_login"])) {
                                    $try_login = $_SESSION["try_login"];
                                } else {
                                    $_SESSION["try_login"] = 1;
                                }
                            }
                            // $tmp_try = $try_login;
                            // zdebug($try_login);
                            $try_login++;
                            if ($sessionManager->sessionExists() && $sessionManager->isValid()) {
                                $login_container->offsetSet("try_login", $try_login);
                            } else {
                                $_SESSION["try_login"] = $try_login;
                            }
                            $ini_reader = new \Laminas\Config\Reader\Ini();
                            $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
                            $filelog_conf = $conf['file_log'];
                            $dblog_conf = $conf['db_log'];
                            $login_conf = $conf['login'];
                            $check_account = true;
                            // zdebug($try_login);
                            // zdebug($login_conf['try']);
                            // zdebug($try_login>(int)$login_conf['try']);
                            if ((int) $login_conf['try'] > 0 && $try_login > (int) $login_conf['try']) {
                                $check_account = false;
                                $currentime = time();
                                $wait_time = $currentime;
                                $wait_login = (int) $login_conf['wait'];
                                $wait = 0;
                                if ($sessionManager->sessionExists() && $sessionManager->isValid()) {
                                    if ($login_container->offsetExists("wait_time")) {
                                        $wait_time = $login_container->offsetGet("wait_time");
                                        $wait = $currentime - $wait_time;
                                    } else {
                                        $login_container->offsetSet("wait_time", $wait_time);
                                    }
                                } else {
                                    if (isset($_SESSION["wait_time"])) {
                                        $wait_time = $_SESSION["wait_time"];
                                        $wait = $currentime - $wait_time;
                                    } else {
                                        $_SESSION["wait_time"] = $wait_time;
                                    }
                                }
                                // zdebug($wait);
                                // zdebug($check_account);
                                // zdebug($wait_time>$wait_login);
                                if ($wait > $wait_login) {
                                    if ($sessionManager->sessionExists() && $sessionManager->isValid()) {
                                        $login_container = $me->container->get('container_login');
                                        if ($login_container->offsetExists("try_login")) {
                                            $login_container->offsetSet("try_login", 0);
                                            $login_container->offsetUnset("try_login");
                                        }
                                        if ($login_container->offsetExists("wait_time")) {
                                            $login_container->offsetSet("wait_time", 0);
                                            $login_container->offsetUnset("wait_time");
                                        }
                                    } else {
                                        if (isset($_SESSION["try_login"])) {
                                            $_SESSION["try_login"] = 0;
                                            unset($_SESSION['try_login']);
                                        }
                                        if (isset($_SESSION["wait_time"])) {
                                            $_SESSION["wait_time"] = 0;
                                            unset($_SESSION['wait_time']);
                                        }
                                    }
                                    // Check Account
                                    $check_account = true;
                                } elseif ($wait === 0) {
                                    if (($filelog_conf["login"]["failed"] ?? "0") === "1") {
                                        $me->Logging()->alertLogging("login", "login_failed", $me);
                                    }
                                    if (($dblog_conf["login"]["failed"] ?? "0") === "1") {
                                        $me->Logging()->dbLogging("login_failed", $me);
                                    }

                                    if ($login_conf['block'] ?? false) {
                                        $user_mdl = $me->container->get(UserModel::class);
                                        $user_mdl->blockUserByUsername($pPost['account']);
                                    }

                                    $ret = [
                                        "ret" => false,
                                        'msg' => "MAXIMUM TRY",
                                        'data' => [
                                            'wait_time' => $wait,
                                            'wait' => secondsToTime($wait_login - $wait),
                                        ],
                                    ];
                                } else {
                                    $ret = [
                                        "ret" => false,
                                        'msg' => "MAXIMUM TRY",
                                        'data' => [
                                            'wait_time' => $wait,
                                            'wait' => secondsToTime($wait_login - $wait),
                                        ],
                                    ];
                                }
                            }

                            // zdebug($check_account);
                            // Check Account
                            if ($check_account) {
                                /** @var AuthenticationService $authService */
                                $authService = $me->container->get(AuthenticationService::class);
                                // zdebug($authService::class);die();
                                /** @var AuthenticationAdapter $authAdapter */
                                $authAdapter = $authService->getAdapter();
                                // zdebug($authAdapter::class);die();
                                $authAdapter->setUsername($pPost['account']);
                                $authAdapter->setPassword($pPost['password']);
                                $authAdapter->setRememberMe($pPost['remember'] ?? "");

                                /** @var Result $isLogin */
                                $isLogin = $authService->authenticate();
                                // !d($isLogin);die();
                                if ($isLogin->getCode() === Result::SUCCESS) {
                                    if ($sessionManager->sessionExists() && $sessionManager->isValid()) {
                                        $login_container = $me->container->get('container_login');
                                        if ($login_container->offsetExists("try_login")) {
                                            $login_container->offsetSet("try_login", 0);
                                            $login_container->offsetUnset("try_login");
                                        }
                                        if ($login_container->offsetExists("wait_time")) {
                                            $login_container->offsetSet("wait_time", 0);
                                            $login_container->offsetUnset("wait_time");
                                        }
                                    } else {
                                        if (isset($_SESSION["try_login"])) {
                                            $_SESSION["try_login"] = 0;
                                            unset($_SESSION['try_login']);
                                        }
                                        if (isset($_SESSION["wait_time"])) {
                                            $_SESSION["wait_time"] = 0;
                                            unset($_SESSION['wait_time']);
                                        }
                                    }
                                    $init_container = $me->container->get("container_init");
                                    $init_container->offsetSet("uid", $isLogin->getIdentity()['id']);

                                    $vars = $me->layout()->getVariable('_vars_');
                                    /** @var Logging $logHelper */
                                    $logHelper = $me->Logging();
                                    $logHelper->routeLogging($me->params(), $isLogin->getIdentity(), $vars);

                                    // !d($isLogin->getIdentity());
                                    $redirect_url = $pPost['redirect_url']??"";
                                    if ($redirect_url==="") {
                                        $redirect_url = (string) $me->params()->fromQuery('redirect', '');
                                    }
                                    // zdebug($redirect);die();
                                    $redirect = $redirect_url;
                                    if ($redirect_url==="" || empty($redirect_url) || strlen($redirect_url) > 2048) {
                                        $redirect = $me->Routing()->defaultRedirectUrl($isLogin->getIdentity(), 'coreadmin');
                                    }

                                    // zdebug(get_class_methods($redirect));
                                    // zdebug($redirect->toString());
                                    // zdebug($redirect->getHeaders());
                                    // zdebug(get_class_methods($redirect->getHeaders()));
                                    // zdebug($redirect->getHeaders()->toArray());
                                    // die();

                                    $ret = [
                                        "ret" => true,
                                        'msg' => "VALID",
                                        'data' => ['identity' => $isLogin->getIdentity(), 'redirect' => $redirect],
                                    ];
                                    // !d($filelog_conf,$dblog_conf);die();
                                    if (($filelog_conf["login"]["success"] ?? "0") === "1") {
                                        $me->Logging()->infoLogging("login", "login_success", $me);
                                    }
                                    if (($dblog_conf["login"]["success"] ?? "0") === "1") {
                                        $me->Logging()->dbLogging("login_success", $me);
                                    }
                                } elseif ($isLogin->getCode() === Result::FAILURE_UNCATEGORIZED) {
                                    $ret['msg'] = "USER BLOCKED";
                                    $ret['data']['try'] = $try_login;
                                // $ret['data']['tmp_try']=$tmp_try;
                                } else {
                                    $ret['msg'] = "User not found";
                                    $ret['data']['try'] = $try_login;
                                    // $ret['data']['tmp_try']=$tmp_try;
                                }
                            }
                        }
                    } else {
                        $ret['msg'] = $validPassChain->getMessages();
                    }
                } else {
                    // email is invalid; print the reasons
                    $ret['msg'] = $validAccountChain->getMessages();
                }
            }
        }

        // !d($ret);die();
        $viewModel = new JsonModel();
        $viewModel->setVariables($ret);
        return $viewModel;
    }

    public function callModelAction()
    {
        $me = $this;
        $ret = [
            "ret" => false,
            'msg' => 'INVALID REQUEST',
            'data' => [],
        ];
        // zdebug(env("BYPASS"));die();
        // zdebug(env("BYPASS","false")==="true");die();
        // zdebug($auth->hasIdentity());die();
        /** @var Request $request */
        $request = $me->getRequest();
        $pRoute = $me->params()->fromRoute();
        $authorized = false;
        // zdebug($pRoute);die();
        if (isset($pRoute['app']) && isset($pRoute['model']) && isset($pRoute['func'])) {
            $app = $pRoute['app'];
            $mdl = $pRoute['model'];
            $func = $pRoute['func'];
            $cls = $app . "\\Model\\" . $mdl;
            //   !d($app,$mdl,$func,$cls);die();
            $modelValid = $me->Secure()->checkCallModel($app, $mdl, $func);
            //   zdebug($modelValid);die();
            if ($modelValid) {
                $pHeader = $me->params()->fromHeader();
                $csrf = findCSRF($pHeader);
                $csrfValid = $me->Secure()->checkCSRF($csrf);
                if ($csrfValid || env("BYPASS", "false") === "true") {
                    $authorized = true;
                    $auth = $me->container->get(AuthenticationService::class);
                    $identity = [];
                    $isLogin = $auth->hasIdentity();
                    if ($isLogin) {
                        $identity = $auth->getIdentity();
                    }
                    session_write_close();

                    $pFiles = $me->params()->fromFiles();
                    $pQuery = $me->params()->fromQuery();
                    $pPost = $me->params()->fromPost();
                    // !d($request->getContent());die();
                    $pContent = [];
                    try {
                        $pContent = json_decode($request->getContent(), true);
                    } catch (\Exception $e) {
                    }

                    if (!is_array($pContent)) {
                        $pContent = [$pContent];
                    }
                    // !d($pFiles,$pHeader,$pRoute,$pQuery,$pPost,$pContent);die();
                    // !d($pRoute);
                    try {
                        $app = $pRoute['app'];
                        $mdl = $pRoute['model'];
                        $func = $pRoute['func'];
                        $cls = $app . "\\Model\\" . $mdl;
                        // !d($cls,$func);//die();
                        // $model = $me->container->get(\App\Model\MenuModel::class);
                        $model = $me->container->get($cls);
                        // !d(get_class($model));die();
                        $exist = method_exists($model, $func);
                        // !d($exist);die();
                        if ($exist) {
                            // !d($pQuery, $pPost, $pContent);die();
                            $par = ArrayUtils::merge($pContent, $pQuery);
                            $par = ArrayUtils::merge($par, $pPost);
                            // $par = ArrayUtils::merge($par, $pHeader);
                            // zdebug($par);
                            // zdebug($model->{$func}($par));die();
                            $ret = [
                                'ret' => true,
                                'msg' => 'Success Request',
                                'data' => $model->{$func}($par),
                            ];
                            // zdebug($ret);die();
                        }
                    } catch (\Exception $e) {
                        // if (($_SERVER['APPLICATION_ENV'] ?? "production") === 'development') {
                        zdebug($e->getMessage());
                        die();
                        // }
                    } catch (\ArgumentCountError $e) {
                    }
                }
            }
        }

        if (!$authorized) {
            $file = __METHOD__;
            $file = preg_replace("/[^a-zA-Z0-9_]/", "_", $file);
            $me->Logging()->alertLogging("ajax", $file, $me);

            /** @var Response $response */
            $response = $me->getResponse();
            $response->setStatusCode(HTTP_UNAUTHORIZED);
        }
        $viewModel = new JsonModel();
        // $viewModel->setVariable('items', $items);
        // !d($ret);die();
        // zdebug($ret);die();
        // unset($ret['data'][8]);
        $viewModel->setVariables($ret);
        return $viewModel;
    }
}
