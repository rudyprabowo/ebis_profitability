<?php
namespace Core\Helper\Controller;

use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Mime;
use Laminas\Mime\Part as MimePart;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Debug\Debug;

/**
 * This view helper class displays a menu bar.
 */

class Email extends AbstractPlugin
{
    private $config;
    private $container;

    private $smtptransport = null;
    public $host = null;
    public $auth = null;
    public $username = null;
    public $password = null;
    public $ssl = "";
    public $port = 4004;
    public function __construct($container,$config)
    {
        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV') . ".conf");
        $smtp_conf = $conf['smtp'];
        $this->host = $smtp_conf['default']['host'];
        $this->auth = $smtp_conf['default']['auth'];
        $this->username = $smtp_conf['default']['username'];
        $this->password = $smtp_conf['default']['password'];
        $this->port = $smtp_conf['default']['port'];

        $me = $this;
        $me->container = $container;
        $me->config = $config;
        return $me->reconfigSMTP();
    }

    private function reconfigSMTP(){
        $me = $this;
        $ret = [
            'ret'=>false,
            'msg'=>'invalid request'
        ];
        try{
            $opt = [
                'name'              => $me->host,
                'host'              => $me->host,
                'port'              => $me->port,
                'connection_class'  => $me->auth,
                'connection_config' => [
                    'username' => $me->username,
                    'password' => $me->password,
                    //ssl: either the value ssl or tls
                    // 'ssl'=>?
                    //Port 25 is the default used for non-SSL connections, 465 for SSL, and 587 for TLS
                    // 'port'=>?
                ],
            ];
            if($me->ssl!==""){
                $opt['connection_config']['ssl'] = $me->ssl;
            }
            $options   = new SmtpOptions($opt);
            $me->smtptransport = new SmtpTransport();
            $me->smtptransport->setOptions($options);
            $ret = [
                'ret'=>true,
                'msg'=>'success'
            ];
        }catch(\Exception $e){
            $ret['msg'] = $e->getMessage();
        }
        return $ret;
    }

    public function setSMTP($host,$port,$auth,$username,$password,$ssl = ""){
        $me = $this;
        $me->host = $host;
        $me->port = $port;
        $me->auth = $auth;
        $me->ssl = $ssl;
        $me->username = $username;
        $me->password = $password;
        return $me->reconfigSMTP();
    }

    private function reconfigSMTPNoAuth(){
        $me = $this;
        $ret = [
            'ret'=>false,
            'msg'=>'invalid request'
        ];
        try{
            $opt = [
                'name'              => $me->host,
                'host'              => $me->host,
                'port'              => $me->port,
                // 'connection_class'  => $me->auth,
                // 'connection_config' => [
                //     'username' => $me->username,
                //     'password' => $me->password,
                    //ssl: either the value ssl or tls
                    // 'ssl'=>?
                    //Port 25 is the default used for non-SSL connections, 465 for SSL, and 587 for TLS
                    // 'port'=>?
                // ],
            ];
            if($me->ssl!==""){
                $opt['connection_config']['ssl'] = $me->ssl;
            }
            $options   = new SmtpOptions($opt);
            $me->smtptransport = new SmtpTransport();
            $me->smtptransport->setOptions($options);
            $ret = [
                'ret'=>true,
                'msg'=>'success'
            ];
        }catch(\Exception $e){
            $ret['msg'] = $e->getMessage();
        }
        return $ret;
    }

    public function setSMTPNoAuth($host,$port,$ssl = ""){
        $me = $this;
        $me->host = $host;
        $me->port = $port;
        $me->ssl = $ssl;
        return $me->reconfigSMTPNoAuth();
    }

    public function sendMessage($subject = "",$from = [],$to = [], $msg = "",$htmlMarkup = "", $attach = [],$cc = [], $bcc = [], $replyto = [],
    $encode = "UTF-8", $headers = []){
        $me = $this;
        $ret = [
            'ret'=>false,
            'msg'=>'invalid request',
            'data'=>null
        ];

        $_from = [];
        foreach($from as $k=>$v){
            if(filter_var($v, FILTER_VALIDATE_EMAIL)){
                $_from[$k] = $v;
            }
        }
        if(count($_from)<=0){
            $_from[_APP_NAME_] = "no_reply@telkom.co.id";
        }

        $_to = [];
        foreach($to as $k=>$v){
            if(filter_var($v, FILTER_VALIDATE_EMAIL)){
                $_to[$k] = $v;
            }
        }

        $_cc = [];
        foreach($cc as $k=>$v){
            if(filter_var($v, FILTER_VALIDATE_EMAIL)){
                $_cc[$k] = $v;
            }
        }

        $_bcc = [];
        foreach($bcc as $k=>$v){
            if(filter_var($v, FILTER_VALIDATE_EMAIL)){
                $_bcc[$k] = $v;
            }
        }

        $_replyto = [];
        foreach($replyto as $k=>$v){
            if(filter_var($v, FILTER_VALIDATE_EMAIL)){
                $_replyto[$k] = $v;
            }
        }

        $_attach = [];
        foreach($attach as $v){
            // Debug::dump($v);die();
            if(isset($v['filepath']) && isset($v['filename']) && isset($v['filetype'])){
                if(file_exists($v['filepath'])){
                    $_attach[] = $v;
                }
            }
        }
        // Debug::dump($_attach);die();

        if($subject==="" || $subject===null || count($_to)<=0 ||
        (($msg === "" || $msg === null) && ($htmlMarkup === "" || $htmlMarkup === null))){
            $ret['msg'] = "invalid param";
        }else{
            try{
                $message = new Message();
                $message->setSubject($subject);
                $message->setEncoding($encode);
                foreach($_from as $k=>$v){
                    if(!(is_numeric($k))){
                        $message->addFrom($v,$k);
                    }else{
                        $message->addFrom($v);
                    }
                }
                foreach($_to as $k=>$v){
                    if(!(is_numeric($k))){
                        $message->addTo($v,$k);
                    }else{
                        $message->addTo($v);
                    }
                }
                foreach($_cc as $k=>$v){
                    if(!(is_numeric($k))){
                        $message->addCc($v,$k);
                    }else{
                        $message->addCc($v);
                    }
                }
                foreach($_bcc as $k=>$v){
                    if(!(is_numeric($k))){
                        $message->addBcc($v,$k);
                    }else{
                        $message->addBcc($v);
                    }
                }
                foreach($_replyto as $k=>$v){
                    if(!(is_numeric($k))){
                        $message->addReplyTo($v,$k);
                    }else{
                        $message->addReplyTo($v);
                    }
                }
                foreach($headers as $k=>$v){
                    $message->getHeaders()->addHeaderLine($k,$v);
                }
                // $message->setBody($msg);
                $text           = new MimePart($msg);
                $text->type     = Mime::TYPE_TEXT;
                $text->charset  = $encode;
                $text->encoding = Mime::ENCODING_QUOTEDPRINTABLE;

                $html           = new MimePart($htmlMarkup."<br>");
                $html->type     = Mime::TYPE_HTML;
                $html->charset  = $encode;
                $html->encoding = Mime::ENCODING_QUOTEDPRINTABLE;

                $body = new MimeMessage();
                $body->setParts([$text, $html]);

                // $contentPart[] = new MimePart($content->generateMessage());

                foreach($_attach as $v){
                    $tmpattach             = new MimePart(fopen($v['filepath'], 'r'));
                    $tmpattach->type        = $v['filetype'];
                    $tmpattach->filename    = $v['filename'];
                    $tmpattach->disposition = Mime::DISPOSITION_ATTACHMENT;
                    $tmpattach->encoding    = Mime::ENCODING_BASE64;
                    // $contentPart[] = $tmpattach;
                    $body->addPart($tmpattach);
                }
                // Debug::dump($body);die();
                // $body = new MimeMessage();
                // $body->setParts($contentPart);
                $message->setBody($body);

                // $contentTypeHeader = $message->getHeaders()->get('Content-Type');
                // if(count($_attach)>0){
                //     $contentTypeHeader->setType('multipart/related');
                // }else{
                //     $contentTypeHeader->setType('multipart/alternative');
                // }

                $ret['msg'] = "failed send message";
                $ret['data'] = $message->toString();
                $me->smtptransport->send($message);
                $ret['ret'] = true;
                $ret['msg'] = "success send message";
            }catch(\Exception $e){
                $ret['msg'] = $e->getMessage();
            }
        }

        return $ret;
    }

    public function sendMessageLoop($subject = "",$from = [],$to = [], $msg = "",$htmlMarkup = "", $attach = [],$cc = [], $bcc = [], $replyto = [],
    $encode = "UTF-8", $headers = []){
        $me = $this;
        $ret = [
            'ret'=>false,
            'msg'=>'invalid request',
            'data'=>null
        ];

        $_from = [];
        foreach($from as $k=>$v){
            if(filter_var($v, FILTER_VALIDATE_EMAIL)){
                $_from[$k] = $v;
            }
        }
        if(count($_from)<=0){
            $_from['Dashboard IPTV'] = "no_reply@telkom.co.id";
        }

        $_to = [];
        foreach($to as $k=>$v){
            if(filter_var($v, FILTER_VALIDATE_EMAIL)){
                $_to[$k] = $v;
            }
        }

        $_cc = [];
        foreach($cc as $k=>$v){
            if(filter_var($v, FILTER_VALIDATE_EMAIL)){
                $_cc[$k] = $v;
            }
        }

        $_bcc = [];
        foreach($bcc as $k=>$v){
            if(filter_var($v, FILTER_VALIDATE_EMAIL)){
                $_bcc[$k] = $v;
            }
        }

        $_replyto = [];
        foreach($replyto as $k=>$v){
            if(filter_var($v, FILTER_VALIDATE_EMAIL)){
                $_replyto[$k] = $v;
            }
        }

        $_attach = [];
        foreach($attach as $v){
            // Debug::dump($v);die();
            if(isset($v['filepath']) && isset($v['filename']) && isset($v['filetype'])){
                if(file_exists($v['filepath'])){
                    $_attach[] = $v;
                }
            }
        }
        // Debug::dump($_attach);die();

        if($subject==="" || $subject===null || count($_to)<=0 ||
        (($msg === "" || $msg === null) && ($htmlMarkup === "" || $htmlMarkup === null))){
            $ret['msg'] = "invalid param";
        }else{
            try{
                $message = new Message();
                $message->setSubject($subject);
                $message->setEncoding($encode);
                foreach($_from as $k=>$v){
                    if(!(is_numeric($k))){
                        $message->addFrom($v,$k);
                    }else{
                        $message->addFrom($v);
                    }
                }
                foreach($_cc as $k=>$v){
                    if(!(is_numeric($k))){
                        $message->addCc($v,$k);
                    }else{
                        $message->addCc($v);
                    }
                }
                foreach($_bcc as $k=>$v){
                    if(!(is_numeric($k))){
                        $message->addBcc($v,$k);
                    }else{
                        $message->addBcc($v);
                    }
                }
                foreach($_replyto as $k=>$v){
                    if(!(is_numeric($k))){
                        $message->addReplyTo($v,$k);
                    }else{
                        $message->addReplyTo($v);
                    }
                }
                foreach($headers as $k=>$v){
                    $message->getHeaders()->addHeaderLine($k,$v);
                }
                // $message->setBody($msg);
                $text           = new MimePart($msg);
                $text->type     = Mime::TYPE_TEXT;
                $text->charset  = $encode;
                $text->encoding = Mime::ENCODING_QUOTEDPRINTABLE;

                $html           = new MimePart($htmlMarkup."<br>");
                $html->type     = Mime::TYPE_HTML;
                $html->charset  = $encode;
                $html->encoding = Mime::ENCODING_QUOTEDPRINTABLE;

                $body = new MimeMessage();
                $body->setParts([$text, $html]);

                // $contentPart[] = new MimePart($content->generateMessage());

                foreach($_attach as $v){
                    $tmpattach             = new MimePart(fopen($v['filepath'], 'r'));
                    $tmpattach->type        = $v['filetype'];
                    $tmpattach->filename    = $v['filename'];
                    $tmpattach->disposition = Mime::DISPOSITION_ATTACHMENT;
                    $tmpattach->encoding    = Mime::ENCODING_BASE64;
                    // $contentPart[] = $tmpattach;
                    $body->addPart($tmpattach);
                }
                // Debug::dump($body);die();
                // $body = new MimeMessage();
                // $body->setParts($contentPart);
                $message->setBody($body);

                // $contentTypeHeader = $message->getHeaders()->get('Content-Type');
                // if(count($_attach)>0){
                //     $contentTypeHeader->setType('multipart/related');
                // }else{
                //     $contentTypeHeader->setType('multipart/alternative');
                // }

                $ret['msg'] = "failed send message";
                $ret['data'] = $message->toString();

                foreach($_to as $k=>$v){
                    if(!(is_numeric($k))){
                        $message->setTo($v,$k);
                    }else{
                        $message->setTo($v);
                    }
                    // !d($message->getTo());
                    try{
                        $me->smtptransport->send($message);
                        $ret[$v]['ret'] = true;
                        $ret[$v]['msg'] = "success send message";
                    }catch(\Exception $e){
                        $ret[$v]['ret'] = false;
                        $ret[$v]['msg'] = $e->getMessage();
                    }
                }
                $ret['ret'] = true;
                $ret['msg'] = "success send message";
            }catch(\Exception $e){
                $ret['msg'] = $e->getMessage();
            }
        }

        return $ret;
    }

}