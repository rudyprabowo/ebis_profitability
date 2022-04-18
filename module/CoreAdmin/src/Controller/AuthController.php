<?php
declare (strict_types = 1);

namespace CoreAdmin\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Core\Form\CsrfForm;
use Laminas\Form\Element\Hidden as HiddenForm;
use Laminas\View\Model\ViewModel;
use Core\Adapter\Authentication\AuthenticationAdapter;
use Core\Helper\Controller\Logging;
use Laminas\Authentication\AuthenticationService;
use Laminas\Session\SessionManager;

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

    public function loginAction()
    {
        $me = $this;
        // $me->Logging()->dbLogging("login_success",$me);die();
        // !d($me->layout()->getVariables());die();
        $redirect_url = (string) $me->params()->fromQuery('redirect', '');
        if (strlen($redirect_url) > 2048) {
            $me->redirect()->toRoute("landing");
        }
        $authService = $me->container->get(AuthenticationService::class);
        if ($authService->hasIdentity()) {
            $redirect = $me->Routing()->defaultRedirect($authService->getIdentity(), 'coreadmin');
            if (!empty($redirect_url) && strlen($redirect_url) <= 2048) {
              $redirect = $redirect_url;
            }
            return $me->redirect()->toRoute($redirect);
        }

        $form = new CsrfForm();
        $redirectUrl = new HiddenForm('redirect_url');
        $redirectUrl->setValue($redirect_url);
        $redirectUrl->setAttribute("id", "redirect_url");
        $form->add($redirectUrl);
        // zdebug($me->url("coreadmin"));die();
        $viewModel = new ViewModel([
          'form' => $form,
          'redirect' => ($redirect_url!==null && $redirect_url!=="")?$redirect_url:""
      ]);
        // $viewModel->setTerminal(true);
        return $viewModel;
    }

    // private function login()
    // {
    //     $me = $this;
    //     //         !d($me->getRequest(),$me->layout()->getVariable('_vars_'));die();
    //     $redirectUrl = (string) $me->params()->fromQuery('redirectUrl', '');
    //     if (strlen($redirectUrl) > 2048) {
    //         $me->redirect()->toRoute("auth/login");
    //     }

    //     /** @var AuthenticationService $authService */
    //     $authService = $me->container->get(AuthenticationService::class);
    //     if ($authService->hasIdentity()) {
    //         if (empty($redirectUrl)) {
    //             return $me->Routing()->defaultRedirect($authService->getIdentity(), 'landing');
    //         } else {
    //             return $me->redirect()->toUrl($redirectUrl);
    //         }
    //     }

    //     $wait = 0;
    //     $isLoginError = false;
    //     $login_container = $me->container->get('container_login');
    //     if ($login_container->offsetExists("start_wait")) {
    //         $start_wait = $login_container->offsetGet("start_wait");
    //         $currentime = time();
    //         $wait = $currentime - $start_wait;
    //         // !d($start_wait,$wait);
    //         if ($wait >= _LOGIN_WAIT_) {
    //             $wait = 0;
    //             $login_container->offsetUnset("start_wait");
    //             $login_container->offsetUnset("try");
    //         } else {
    //             $wait = _LOGIN_WAIT_ - $wait;
    //         }
    //     }

    //     $form = new CsrfForm();
    //     $trylogin = 1;
    //     $msg = "";

    //     if ($me->getRequest()->isPost() && $wait === 0) {
    //         if ($login_container->offsetExists("try")) {
    //             $trylogin = $login_container->offsetGet("try") + 1;
    //         }

    //         $data = $me->params()->fromPost();
    //         $form->setData($data);
    //         // $isvalid = $form->isValid();
    //         try {
    //             $isvalid = $form->isValid();
    //         } catch (\Exception $e) {
    //             $isvalid = false;
    //             // die("exc");
    //             return $me->logoutAction();
    //         }
    //         if ($isvalid) {
    //             /** @var AuthenticationService $authService */
    //             $authService = $me->container->get(AuthenticationService::class);
    //             /** @var AuthenticationAdapter $authAdapter */
    //             $authAdapter = $authService->getAdapter();
    //             $authAdapter->setUsername($data['username']);
    //             $authAdapter->setPassword($data['password']);
    //             $authAdapter->setRememberMe($data['remember_me'] ?? "");

    //             /** @var Result $isLogin */
    //             $isLogin = $authService->authenticate();
    //             //                !d($isLogin);
    //             if ($isLogin->getCode() === Result::SUCCESS) {
    //                 $wait = 0;
    //                 $login_container->offsetUnset("start_wait");
    //                 $login_container->offsetUnset("try");
    //                 $init_container = $me->container->get("container_init");
    //                 $init_container->offsetSet("uid", $isLogin->getIdentity()['id']);
    //                 $isLoginError = false;

    //                 $vars = $me->layout()->getVariable('_vars_');
    //                 /** @var Logging $logHelper */
    //                 $logHelper = $me->Logging();
    //                 $logHelper->routeLogging($me->params(), $isLogin->getIdentity(), $vars);

    //                 //                    !d($isLogin->getIdentity());
    //                 if (empty($redirectUrl)) {
    //                     return $me->Routing()->defaultRedirect($isLogin->getIdentity(), 'landing');
    //                 } else {
    //                     return $me->redirect()->toUrl($redirectUrl);
    //                 }
    //             } else {
    //                 $isLoginError = true;
    //                 $msg = "Please input valid data";
    //                 $login_container->offsetSet("try", $trylogin);
    //             }
    //         } else {
    //             $isLoginError = true;
    //             $msg = "Please input valid data";
    //             $login_container->offsetSet("try", $trylogin);
    //         }

    //         if ($trylogin > _MAX_LOGIN_TRY_) {
    //             // if(env("LOGIN_BLOCK",false)===true)
    //             $isLoginError = false;
    //             $wait = _LOGIN_WAIT_;
    //             $currentime = time();
    //             $login_container->offsetUnset("try");
    //             $login_container->offsetSet("start_wait", $currentime);

    //             $request = $me->getRequest();
    //             $pPath = $request->getUri()->getPath();
    //             $pQuery = $request->getQuery()->toArray();
    //             $pPost = $request->getPost()->toArray();
    //             $pHeaders = $request->getHeaders()->toArray();
    //             $pServer = $request->getServer()->toArray();
    //             $pEnv = $request->getEnv()->toArray();
    //             $data = [
    //                 'path' => $pPath,
    //                 'query' => $pQuery,
    //                 'post' => $pPost,
    //                 'header' => $pHeaders,
    //                 'server' => $pServer,
    //                 'env' => $pEnv,
    //             ];
    //             $me->Logging()->alertLogging("login", "trylogin", $data);
    //         }
    //     }

    //     $redirect_url = new HiddenForm('redirect_url');
    //     $redirect_url->setValue($redirectUrl);
    //     $form->add($redirect_url);

    //     $view = new ViewModel([
    //         'form' => $form,
    //         'wait' => $wait,
    //         'try' => $trylogin,
    //         'msg' => $msg,
    //         'isLoginError' => $isLoginError,
    //         'redirectUrl' => $redirectUrl,
    //     ]);

    //     $view->setTemplate("login-1");
    //     $view->setTerminal(true);
    //     return $view;
    // }

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

        if ($module === "") {
            return $me->redirect()->toRoute('coreadmin/auth/login');
        } else {
            return $me->redirect()->toRoute('coreadmin/auth/login', [], ['query' => ['module' => $module]]);
        }
    }
}