<?php
/**
 * @copyright Copyright (c) 2021 Tech Mayantara Asia. (https://tma.web.id)
 */

namespace CoreAdmin\ConsoleController;

use Laminas\Console\Request as ConsoleRequest;
use Laminas\Mvc\Console\Controller\AbstractConsoleController;
use RuntimeException;

class ScriptController extends AbstractConsoleController
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
        $this->_data_cache = $container->get("data-file");
    }

    static function gearmanUploadModule($job) {
      echo "Execute gearmanUploadModule" . PHP_EOL;
      zdebug($job);
      // $me->_data_cache->setItem('gearmanUploadModule', json_encode($job->workload()));
    }

    static function gearmanUploadModuleStatus($task) {
      echo "STATUS: " . $task->unique() . ", " . $task->jobHandle() . " - " . $task->taskNumerator() .
      "/" . $task->taskDenominator() . PHP_EOL;
    }

    static function gearmanUploadModuleComplete($task) {
      echo "COMPLETE: " . $task->unique() . ", " . $task->data() . PHP_EOL;
    }

    //php ./public/index.php gearman-upload-module
    public function gearmanUploadModuleAction()
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
        $me = $this;

        $worker= new \GearmanWorker();
        $worker->addServer();
        $worker->addFunction("gearman_upload_module", array($me, 'gearmanUploadModule'));
        while ($worker->work());
    }

    //php ./public/index.php gearman-upload-module-client
    public function gearmanUploadModuleClientAction()
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
        $me = $this;

        $gmc= new \GearmanClient();
        $gmc->addServer();
        $gmc->setCompleteCallback(array($me, 'gearmanUploadModuleComplete'));
        $gmc->setStatusCallback(array($me, 'gearmanUploadModuleStatus'));
        $task = $gmc->addTaskBackground("gearman_upload_module", 'xxx', null, "1");
        if (! $gmc->runTasks())
        {
          $this->getConsole()->writeText("ERROR " . $gmc->error() . PHP_EOL);
        }else{
          $this->getConsole()->writeText("END" . PHP_EOL);
        }
    }
}