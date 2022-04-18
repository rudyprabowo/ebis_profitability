<?php
namespace Core\Helper\Controller;

use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\BlockCipher;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Session\SessionManager;
use Laminas\Stdlib\ArrayUtils;
use Zend\Debug\Debug;

/**
 * This view helper class displays a menu bar.
 */

class Secure extends AbstractPlugin
{
    private $config;
    private $container;
    private $authService;
    private $sessionManager;
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
    }

    public function checkCSRF(Array $csrf){
        $me = $this;
        $isValid = false;
        // zdebug($csrf);
        if(array_keys_exist($csrf,['key','lowkey','val'])) {
            $tmp_token = null;
            $md5 = str_replace("-csrf-token","",$csrf['lowkey']);
            // zdebug($md5);//die();
            /** @var \Laminas\Session\SessionManager $sessMgr */
            $sessMgr = $me->sessionManager;
            if ($sessMgr->sessionExists() && $sessMgr->isValid()) {
                // # get data container
                $container_data = $me->container->get('container_data');
                // zdebug($md5."-CSRF");
                // zdebug($container_data->getArrayCopy());
                // zdebug($container_data->offsetExists($md5."-CSRF"));
                if ($container_data->offsetExists($md5."-CSRF")) {
                    $tmp_token = $container_data->offsetGet($md5 . "-CSRF");
                }
            } else { // ? not login
                if (isset($_SESSION[$md5."-CSRF"])) {
                    $tmp_token = $_SESSION[$md5."-CSRF"];
                }
            }
            // !d($csrf['val'],$tmp_token);die();
            if ($csrf['val']!==null && $csrf['val']===$tmp_token) {
              $isValid = true;
            }
        }
        return $isValid;
    }

    public function checkCallModel(string $module,string $model,string $func){
        $me = $this;
        $isValid = false;
        $coreMdl = $me->container->get(\CoreAdmin\Model\CoreModel::class);
        // die('www');
        if (!isNullEmpty([$module,$model,$func])) {
            // zdebug($me->authService->getIdentity());die();
            $identity = $me->authService->getIdentity();
            $id = $identity['id']??-1;
            // !d($module,$model,$func);die();
            $mdl = $coreMdl->getModelAcl($module, $id);
            // zdebug($model);die();
            if(in_array($func,($mdl[implode("\\",[$module,"Model",$model])]??[]))){
                $isValid = true;
            }
        }
        // zdebug($isValid);die();
        return $isValid;
    }
}