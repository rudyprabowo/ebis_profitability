<?php

/**
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */

declare(strict_types = 1);

namespace App\Controller;

use Core\Adapter\Authentication\AuthenticationAdapter;
use Core\Form\CsrfForm;
use Core\Helper\Controller\Logging;
use Core\Model\UserModel;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Result;
use Laminas\Form\Element\Hidden as HiddenForm;
use Laminas\I18n\Validator\Alnum;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\SessionManager;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Validator\StringLength;
use Laminas\Validator\ValidatorChain;
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
        /** @var Response $response */
        $response = $me->getResponse();
        switch ((int) $routeParam['code']) {
            case HTTP_FORBIDDEN:{
                    $view->setTemplate("403");
                    $response->setStatusCode(HTTP_FORBIDDEN);
                    break;
                }
            case HTTP_UNAUTHORIZED:{
                    $view->setTemplate("401");
                    $response->setStatusCode(HTTP_UNAUTHORIZED);
                    break;
                }
            case HTTP_NOTFOUND:{
                    $view->setTemplate("404-A");
                    $response->setStatusCode(HTTP_NOTFOUND);
                    break;
                }
            case HTTP_BADREQUEST:{
                    $response->setStatusCode(HTTP_BADREQUEST);
                    $view->setTemplate("error-A");
                    break;
                }
            default:{
                    $response->setStatusCode((int) $routeParam['code']);
                    $view->setTemplate("error-A");
                    break;
                }
        }
        return $view;
    }

    public function loginAction()
    {
        $me = $this;
        // !d($me->getRequest(),$me->layout()->getVariable('_vars_'));die();
        $redirect_url = (string) $me->params()->fromQuery('redirect  ', '');
        if (strlen($redirect_url) > 2048) {
            $me->redirect()->toRoute("app/auth/login");
        }

        /** @var AuthenticationService $authService */
        $authService = $me->container->get(AuthenticationService::class);
        if ($authService->hasIdentity()) {
            if (empty($redirect_url)) {
                $redirect = $me->url()->fromRoute('app');
                return $me->redirect()->toUrl($redirect);
            // return $me->Routing()->defaultRedirect($authService->getIdentity(), 'app');
            } else {
                return $me->redirect()->toUrl($redirect_url);
            }
        }

        $wait = 0;
        $wait_login = 0;
        $is_login_error = false;

        $try_login = 0;
        $msg = "";
        $form = new CsrfForm();

        $captcha = new \Laminas\Captcha\Image([
            'name'    => 'captcha',
            'imgDir' => APP_PATH.'public/img/captcha',
            'imgUrl' => '/img/captcha',
            'imgAlt' => 'captcha',
            'width' => 300,
            'height' => 100,
            'fsize' => 50,
            'font' => APP_PATH.'data/app/Vera.ttf',
            // 'suffix' => 'captcha',
            'session' => $me->container->get('container_login'),
            'expiration' => 600,
            'dotNoiseLevel' => 450,
            'lineNoiseLevel' => 30,
            'useNumbers' => true,
            'wordLen' => 6,
            'timeout' => 300,
        ]);

        // zdebug($id);
        // zdebug(get_class_methods($captcha));
        // die();

        /** @var Request $request */
        $request = $me->getRequest();
        if ($request->isPost()) {
            $check_account = true;
            $pPost = $me->params()->fromPost();
            // zdebug($pPost);
            // die();
            $sessionManager = $me->container->get(\Laminas\Session\SessionManager::class);
            if (array_keys_exist($pPost, ["username","password","redirect_url","csrf","meta_csrf_name","meta_csrf_value"])) {
                $validAccountChain = new ValidatorChain();
                //validate input data
                $validAccountChain->attach(new Alnum());
                $validAccountChain->attach(
                    new StringLength(['min' => 5, 'max' => 15]),
                    true, // break chain on failure
                    1
                );
                if ($validAccountChain->isValid($pPost['username'])) {
                    $validPassChain = new StringLength(['min' => 5]);
                    if ($validPassChain->isValid($pPost['password'])) {
                        //validate csrf
                        $form->setData($pPost);
                        $csrfValid = false;
                        try {
                            $csrfValid = $form->isValid();
                        } catch (\Exception $e) {
                            $csrfValid = false;
                        }

                        // zdebug($csrfValid);die();
                        // zdebug(get_class($captcha->getSession()));
                        // zdebug($captcha->getWord());
                        // zdebug($captcha->isValid($pPost['captcha']??[], $pPost));
                        // die();
                        if (!$csrfValid) {
                            $is_login_error = true;
                            $msg = "Invalid submitted data";
                        } elseif (!$captcha->isValid($pPost['captcha']??[], $pPost)) {
                            $is_login_error = true;
                            $msg = "Invalid captcha";
                        } else {
                            unlink($captcha->getImgDir().$captcha->getId().'.png');
                        }
                    } else {
                        $is_login_error = true;
                        $msg = "Please input valid password";
                    }
                } else {
                    $is_login_error = true;
                    $msg = "Please input valid username";
                }

                //authenticate

                //auth valid = redirect
                //else login error
            } else {
                $is_login_error = true;
                $msg = "Invalid submitted data";
            }

            //get try login from session
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

            if ($is_login_error) {
                $try_login++;
                if ($sessionManager->sessionExists() && $sessionManager->isValid()) {
                    $login_container->offsetSet("try_login", $try_login);
                } else {
                    $_SESSION["try_login"] = $try_login;
                }
            }

            $check_account = !$is_login_error;
            // zdebug($try_login);

            $ini_reader = new \Laminas\Config\Reader\Ini();
            $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
            $filelog_conf = $conf['file_log'];
            $dblog_conf = $conf['db_log'];
            $login_conf = $conf['login'];
            //try login > treshold = block user + return message
            //else return message
            if ((int)$login_conf['try']>0 && $try_login>(int)$login_conf['try']) {
                $currentime = time();
                $wait_time = $currentime;
                $wait_login = (int)$login_conf['wait'];
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

                if ($wait>$wait_login) {
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
                    $try_login = 0;
                    $wait = 0;
                    $check_account = true;
                } elseif ($wait===0) {
                    if (($filelog_conf["login"]["failed"]??"0")==="1") {
                        $me->Logging()->alertLogging("login", "login_failed", $me);
                    }
                    if (($dblog_conf["login"]["failed"]??"0")==="1") {
                        $me->Logging()->dbLogging("login_failed", $me);
                    }

                    if ($login_conf['block']??false) {
                        $user_mdl = $me->container->get(UserModel::class);
                        $user_mdl->blockUserByUsername($pPost['username']);
                        $tmp = secondsToTime($wait_login - $wait);
                        //   $msg = "Your account (".$pPost['username'].") has been blocked";
                        $msg = "Please try again after ".$tmp['minute']." min ".$tmp['second']." sec";
                    }
                    $check_account = false;
                } else {
                    $tmp = secondsToTime($wait_login - $wait);
                    $msg = "Please try again after ".$tmp['minute']." min ".$tmp['second']." sec";
                    $check_account = false;
                }
            }

            // Check Account
            if ($check_account) {
                $validaccount = false;
                /** @var AuthenticationAdapter $authAdapter */
                $authAdapter = $authService->getAdapter();
                // zdebug($authAdapter::class);die();
                $authAdapter->setUsername($pPost['username']);
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
                    $try_login = 0;
                    $wait = 0;
                    $init_container = $me->container->get("container_init");
                    $init_container->offsetSet("uid", $isLogin->getIdentity()['id']);

                    $vars = $me->layout()->getVariable('_vars_');
                    /** @var Logging $logHelper */
                    $logHelper = $me->Logging();
                    $logHelper->routeLogging($me->params(), $isLogin->getIdentity(), $vars);

                    // !d($isLogin->getIdentity());
                    $redirect_url = (string) $me->params()->fromQuery('redirect', '');
                    // $redirect = $me->Routing()->defaultRedirectUrl($isLogin->getIdentity(), 'app');
                    $redirect = $me->url()->fromRoute('home');
                    // zdebug($redirect);die();
                    if (!empty($redirect_url) && strlen($redirect_url) <= 2048) {
                        $redirect = $redirect_url;
                    }
                    // !d($filelog_conf,$dblog_conf);die();
                    if (($filelog_conf["login"]["success"]??"0")==="1") {
                        $me->Logging()->infoLogging("login", "login_success", $me);
                    }
                    if (($dblog_conf["login"]["success"]??"0")==="1") {
                        $me->Logging()->dbLogging("login_success", $me);
                    }
                    $validaccount = true;
                    return $me->redirect()->toUrl($redirect);
                } elseif ($isLogin->getCode() === Result::FAILURE_UNCATEGORIZED) {
                    $msg = "Your account (".$pPost['username'].") has been blocked, please contact administrator";
                } else {
                    $msg = "Please input valid user and password";
                }

                if (!$validaccount) {
                    $try_login++;
                    if ($sessionManager->sessionExists() && $sessionManager->isValid()) {
                        $login_container->offsetSet("try_login", $try_login);
                    } else {
                        $_SESSION["try_login"] = $try_login;
                    }
                }
            }
        }

        $input_redirect_url = new HiddenForm('redirect_url');
        $input_redirect_url->setValue($redirect_url);
        $form->add($input_redirect_url);

        // !d(get_class($me->getEvent()->getViewModel()));die();
        // $vModel = $me->getEvent()->getViewModel();
        // !d(get_class_methods($vModel));die();
        // !d($vModel->getVariables());die();
        // !d(get_class_methods($vModel));die();
        /** @var \Laminas\View\Variables $vVars */
        // $vVars = $vModel->getVariables();
        // !d(get_class_methods($vVars));//die();
        // !d($vVars);//die();

        $captcha_id = $captcha->generate();
        $nVars = [
            'form' => $form,
            'wait_time' => $wait,
            'wait' => secondsToTime($wait_login - $wait),
            'try' => $try_login,
            'msg' => $msg,
            'is_login_error' => $is_login_error,
            'redirect_url' => $redirect_url,
            'captcha_id' =>$captcha_id,
            'captcha' => $captcha
        ];
        // zdebug($try_login);
        // zdebug($wait);
        // zdebug(secondsToTime($wait_login - $wait));
        // $vModel->setVariables($nVars);
        // $vVars = $vModel->getVariables();
        // !d($vVars);die();

        // $vModel->setTemplate("login/tailwind-1");
        // $vModel->setTerminal(true);
        return $nVars;
    }

    public function logoutAction()
    {
        $me = $this;
        // !d(__METHOD__);die();
        $module = (string) $me->params()->fromQuery('module', '');
        /** @var AuthenticationService $authService */
        $authService = $me->container->get(AuthenticationService::class);
        /** @var AuthenticationAdapter $authAdapter */
        // $authAdapter = $authService->getAdapter();
        if ($authService->hasIdentity()) {
            /** @var SessionManager $sessionManager */
            $sessionManager = $me->container->get(SessionManager::class);
            $sessionManager->forgetMe();
            $authService->clearIdentity();
            $sessionManager->destroy();
            // $authAdapter->deleteSessionRow();
        }
        // die('qqq');
        if ($module === "") {
            return $me->redirect()->toRoute('app/auth/login');
        } else {
            return $me->redirect()->toRoute('app/auth/login', [], ['query' => ['module' => $module]]);
        }
    }
}
