<?php
include __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

define("BASE_PATH",__DIR__ . "/../");
define("SQL_PATH",BASE_PATH . "get_started/data/");
define("CONF_PATH",BASE_PATH . "conf/");

$is_env = false;
try{
    if (method_exists('Dotenv\Dotenv', 'createUnsafeImmutable')) {
        Dotenv::createUnsafeImmutable(BASE_PATH)->load();
    } else {
        Dotenv::createMutable(BASE_PATH)->load();
    }
    $is_env = true;
}catch (Exception $e){
    $msg = $e->getMessage();
    $msg = preg_replace("/( at.)\[(.*)\]/","",$msg);
    echo $msg.PHP_EOL;
}

if($is_env){

    $ini_reader = new \Laminas\Config\Reader\Ini();
    $conf = $ini_reader->fromFile(CONF_PATH . env('APPLICATION_ENV') . ".conf");
    $app_conf = $conf['app-config'];
    $mysql_conf = $conf['db-mysql'];
    /**
     * @var \Medoo\Medoo $medooDb
     */
    $medooDb = new \Medoo\Medoo([
        'database_type' => 'mysql',
        'database_name' => $mysql_conf['admin']['database'],
        'server' => $mysql_conf['admin']['hostname'],
        'username' => $mysql_conf['admin']['username'],
        'password' => $mysql_conf['admin']['password'],
        'port' => $mysql_conf['admin']['port'],
    ]);
    /**
     * @var array $route
     * # select routes data from db
     */
    // !d(get_class_methods($medooDb));die();
    $route = $medooDb->query("select * from _user", [])->fetchAll();
}