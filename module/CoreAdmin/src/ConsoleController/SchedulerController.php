<?php
/**
 * @copyright Copyright (c) 2021 Tech Mayantara Asia. (https://tma.web.id)
 */

namespace CoreAdmin\ConsoleController;

use Laminas\Console\Request as ConsoleRequest;
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

    //php ./public/index.php scheduler-1min
    public function scheduler1minAction()
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

        // \Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_CURL);
        $menuModel = $this->container->get(\CoreAdmin\Model\MenuModel::class);
        #INFO - Example Call Model
        $this->getConsole()->writeText("Get All Menu" . PHP_EOL);
        \go(function () use ($menuModel) {
            $allMenu = $menuModel->getAllMenu();
            !d("Get All Menu",count($allMenu));
        });
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
                $this->getConsole()->writeText("CURL Error :". curl_error($ch). PHP_EOL);
            } else {
                $data = [];
                try {
                    $data = json_decode($output, true);
                    !d("Call API/URL",count($data));
                } catch (\Exception $e) {
                    $this->getConsole()->writeText("JSON Decode Error :". $e->getMessage(). PHP_EOL);
                }
            }
        });
        #INFO - Example Web Crawling Using Panther
        $this->getConsole()->writeText("Web Crawling Using Panther" . PHP_EOL);
        $client = \Symfony\Component\Panther\Client::createChromeClient();
        // Or, if you care about the open web and prefer to use Firefox
        // $client = Symfony\Component\Panther\Client::createFirefoxClient();
        \go(function () use ($client) {
            $client->request('GET', 'https://api-platform.com'); // Yes, this website is 100% written in JavaScript
            $client->clickLink('Get started');

            // Wait for an element to be present in the DOM (even if hidden)
            $crawler = $client->waitFor('#installing-the-framework');
            // Alternatively, wait for an element to be visible
            $crawler = $client->waitForVisibility('#installing-the-framework');

            $this->getConsole()->writeText($crawler->filter('#installing-the-framework')->text() . PHP_EOL);
            $client->takeScreenshot(APP_PATH . 'data/upload/screen.png'); // Yeah, screenshot!
        });
    }


    //php ./public/index.php job-param [paramA] <paramB> <paramC>
    public function jobParamAction()
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

        $param_a = $request->getParam('paramA');
        $param_b = $request->getParam('paramB');
        $param_c = $request->getParam('paramC');

        !d($param_a,$param_b,$param_c);
    }
}