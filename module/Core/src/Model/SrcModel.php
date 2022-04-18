<?php
namespace Core\Model;

use Laminas\Authentication\AuthenticationService;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Session\SessionManager;

class SrcModel
{
    private $config;
    private $container;
    // private $authService;
    private $dbSys;
    private $dataCache;

    public function __construct($container, $config)
    {
        $me = $this;
        $me->container = $container;
        $me->config = $config;
        // $me->authService = $container->get(AuthenticationService::class);
        $me->dbSys = $container->get("db-sys");

        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $app_conf = $conf['app-config'];
        if (($app_conf['main_db']??null)==="postgres") {
            $session_conf = $conf['session'];
            $me->dbSys->query(
                'SET search_path TO '.$session_conf['db_schema_name'],
                Adapter::QUERY_MODE_EXECUTE
            );
        }
        $me->dataCache = $container->get("data-file");
    }

    public function addModule(array $param)
    {
        $me = $this;
        $ret = [
            'ret' => false,
            'msg' => "Invalid Request"
        ];

        if (isset($param['name']) && isset($param['status']) && isset($param['session_name'])) {
            $field1 = [];
            $field2 = [];
            foreach ($param as $k => $v) {
                $field1[] = "`" . $k . '`';
                $field2[] = ":" . $k;
            }
            $sql = "INSERT INTO _module (" . implode(", ", $field1) . ") VALUES (" . implode(", ", $field2) . ")";
            $stmt = $me->dbSys->createStatement($sql, $param);
            $result = $stmt->execute();
            if ($result->valid()) {
                $mid = $result->getGeneratedValue();
                $ret = [
                    'ret' => true,
                    'msg' => "Success add module",
                    'mid' => $mid
                ];
            } else {
                $ret['msg']="Failed add module";
            }
        }

        return $ret;
    }

    public function addController(array $param)
    {
        $me = $this;
        $ret = [
            'ret' => false,
            'msg' => "Invalid Request"
        ];

        if (isset($param['module']) && isset($param['name']) && isset($param['status']) && isset($param['factory'])) {
            $field1 = [];
            $field2 = [];
            foreach ($param as $k => $v) {
                $field1[] = "`" . $k . '`';
                $field2[] = ":" . $k;
            }
            $sql = "INSERT INTO _controller (" . implode(", ", $field1) . ") VALUES (" . implode(", ", $field2) . ")";
            $stmt = $me->dbSys->createStatement($sql, $param);
            $result = $stmt->execute();
            if ($result->valid()) {
                $cid = $result->getGeneratedValue();
                $ret = [
                    'ret' => true,
                    'msg' => "Success add controller",
                    'cid' => $cid
                ];
            } else {
                $ret['msg']="Failed add controller";
            }
        }

        return $ret;
    }

    public function addAction(array $param)
    {
        $me = $this;
        $ret = [
            'ret' => false,
            'msg' => "Invalid Request"
        ];

        if (isset($param['controller']) && isset($param['name']) && isset($param['status'])) {
            $field1 = [];
            $field2 = [];
            foreach ($param as $k => $v) {
                if ($k==="name") {
                    $param[$k] = strtolower($v);
                }
                $field1[] = "`" . $k . '`';
                $field2[] = ":" . $k;
            }
            $sql = "INSERT INTO _action (" . implode(", ", $field1) . ") VALUES (" . implode(", ", $field2) . ")";
            $stmt = $me->dbSys->createStatement($sql, $param);
            $result = $stmt->execute();
            if ($result->valid()) {
                $aid = $result->getGeneratedValue();
                $ret = [
                    'ret' => true,
                    'msg' => "Success add action",
                    'aid' => $aid
                ];
            } else {
                $ret['msg']="Failed add action";
            }
        }

        return $ret;
    }
}
