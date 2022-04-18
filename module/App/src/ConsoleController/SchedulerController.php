<?php
/**
 * @copyright Copyright (c) 2021 Tech Mayantara Asia. (https://tma.web.id)
 */

namespace App\ConsoleController;

use Laminas\Console\Request as ConsoleRequest;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Mime;
use Laminas\Mime\Part as MimePart;
use Laminas\Mvc\Console\Controller\AbstractConsoleController;
use RuntimeException;

class SchedulerController extends AbstractConsoleController
{
    private $container;
    private $config;
    private $dataCache;
    private $accessLogin;
    public $restAPI;

    public function __construct($container, $config)
    {
        $this->container = $container;
        $this->config = $config;
        $this->dataCache = $container->get("data-file");
    }

    public function mungXML($xml)
    {
        $obj = SimpleXML_Load_String($xml);
        if ($obj === false) {
            return $xml;
        }

        // GET NAMESPACES, IF ANY
        $nss = $obj->getNamespaces(true);
        if (empty($nss)) {
            return $xml;
        }

        // CHANGE ns: INTO ns_
        $nsm = array_keys($nss);
        foreach ($nsm as $key) {
            // A REGULAR EXPRESSION TO MUNG THE XML
            $rgx
            = '#' // REGEX DELIMITER
             . '(' // GROUP PATTERN 1
             . '\<' // LOCATE A LEFT WICKET
             . '/?' // MAYBE FOLLOWED BY A SLASH
             . preg_quote($key) // THE NAMESPACE
             . ')' // END GROUP PATTERN
             . '(' // GROUP PATTERN 2
             . ':{1}' // A COLON (EXACTLY ONE)
             . ')' // END GROUP PATTERN
             . '#' // REGEX DELIMITER
            ;
            // INSERT THE UNDERSCORE INTO THE TAG NAME
            $rep
            = '$1' // BACKREFERENCE TO GROUP 1
             . '_' // LITERAL UNDERSCORE IN PLACE OF GROUP 2
            ;
            // PERFORM THE REPLACEMENT
            $xml = preg_replace($rgx, $rep, $xml);
        }
        return $xml;
    }
    
    //php ./public/index.php scheduler-app-5min
    public function scheduler5minAction()
    {
        error_reporting(E_ERROR | E_PARSE);
        $request = $this->getRequest();

        if (!$request instanceof ConsoleRequest) {
            throw new RuntimeException(
                'You can only use this action from a console!'
            );
        }

        $this->getConsole()->writeText(date("Y-m-d H:i:s") . PHP_EOL);
        $this->getConsole()->writeText("Start Job " . __METHOD__ . PHP_EOL);

        #INFO - Example Call API/URL via CURL
        $this->getConsole()->writeText("CAll API/URL via CURL" . PHP_EOL);
        \go(function () {
            $url = "https://jsonplaceholder.typicode.com/todos";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            // curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            // curl_setopt($ch, CURLOPT_USERPWD, implode(":", $this->accessLogin));
            // $headers = array();
            // $headers[] = 'User-Agent: AppTIOC';
            // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $output = curl_exec($ch);
            if ($output === false) {
                $this->getConsole()->writeText("CURL Error :" . curl_error($ch) . PHP_EOL);
            } else {
                $data = [];
                try {
                    $data = json_decode($output, true);
                    // !d("Call API/URL",count($data));
                } catch (\Exception $e) {
                    $this->getConsole()->writeText("JSON Decode Error :" . $e->getMessage() . PHP_EOL);
                }
            }
        });
    }
}
