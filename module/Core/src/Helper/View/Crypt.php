<?php
namespace Core\Helper\View;

use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Authentication\AuthenticationService;
use Laminas\Session\SessionManager;
use Laminas\Crypt\Password\Bcrypt;
use Zend\Debug\Debug;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Crypt\BlockCipher;

/**
 * This view helper class displays a menu bar.
 */

class Crypt extends AbstractHelper
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

    public function encryptData(Array $data,$algo = "aes",$mode = "gcm",$key = "",$bypass = false){
        $me = $this;
        //algo = aes | blowfish
        //mode = gcm | ccm | cfb
        $blockCipher = BlockCipher::factory(
            'openssl',
            [
                'algo' => $algo,
                'mode' => $mode
            ]
        );
        $blockCipher->setKey($key);
        // if(!is_array($data))$data = [$data];
        $res1 = $blockCipher->encrypt(json_encode($data));
        $encrypt = $res1;

        $auth = $me->authService;
        $session = $me->sessionManager;
        if(!$bypass && $auth!==null && $auth->hasIdentity() && $session->sessionExists()){
            $session = $me->sessionManager;
            // $tmp = $auth->getIdentity();
            $sess_id = $session->getId();

            $blockCipher->setKey($sess_id);
            $res2 = $blockCipher->encrypt($encrypt);
            $encrypt = $res2;
        }
        return $encrypt;
    }

    public function decryptData(String $txt,$algo = "aes",$mode = "gcm",$key = "",$bypass = false){
        $me = $this;
        //algo = aes | blowfish
        //mode = gcm | ccm | cfb
        $blockCipher = BlockCipher::factory(
            'openssl',
            [
                'algo' => $algo,
                'mode' => $mode
            ]
        );
        $auth = $me->authService;
        $session = $me->sessionManager;

        $decrypt = strval($txt);
        if(!$bypass && $auth!==null && $auth->hasIdentity() && $session->sessionExists()){
            // $tmp = $auth->getIdentity();
            $sess_id = $session->getId();

            $blockCipher->setKey($sess_id);
            $res1 = $blockCipher->decrypt($decrypt);
            $decrypt = $res1;
        }

        $blockCipher->setKey($key);
        $ret = $blockCipher->decrypt($decrypt);
        return json_decode($ret,true);
    }

    public function encryptString($data,$algo = "aes",$mode = "gcm",$key = "",$bypass = false){
        $me = $this;
        //algo = aes | blowfish
        //mode = gcm | ccm | cfb
        $blockCipher = BlockCipher::factory(
            'openssl',
            [
                'algo' => $algo,
                'mode' => $mode
            ]
        );
        $blockCipher->setKey($key);
        // if(!is_array($data))$data = [$data];
        $res1 = $blockCipher->encrypt(strval($data));
        $encrypt = $res1;

        $auth = $me->authService;
        $session = $me->sessionManager;
        if(!$bypass && $auth!==null && $auth->hasIdentity() && $session->sessionExists()){
            $session = $me->sessionManager;
            // $tmp = $auth->getIdentity();
            $sess_id = $session->getId();

            $blockCipher->setKey($sess_id);
            $res2 = $blockCipher->encrypt($encrypt);
            $encrypt = $res2;
        }
        return $encrypt;
    }

    public function decryptString(String $txt,$algo = "aes",$mode = "gcm",$key = "",$bypass = false){
        $me = $this;
        //algo = aes | blowfish
        //mode = gcm | ccm | cfb
        $blockCipher = BlockCipher::factory(
            'openssl',
            [
                'algo' => $algo,
                'mode' => $mode
            ]
        );
        $auth = $me->authService;
        $session = $me->sessionManager;

        $decrypt = strval($txt);
        if(!$bypass && $auth!==null && $auth->hasIdentity() && $session->sessionExists()){
            // $tmp = $auth->getIdentity();
            $sess_id = $session->getId();

            $blockCipher->setKey($sess_id);
            $res1 = $blockCipher->decrypt($decrypt);
            $decrypt = $res1;
        }

        $blockCipher->setKey($key);
        $ret = $blockCipher->decrypt($decrypt);
        return $ret;
    }
}