<?php
declare(strict_types = 1);
// die('qqq');
date_default_timezone_set("Asia/Jakarta");
ini_set('memory_limit', '128M');
// ini_set('memory_limit', '1024M');
// ini_set('memory_limit', '2048M');

// die('qqq');
// phpinfo();die();
define("DS", DIRECTORY_SEPARATOR);
define("APP_PATH", realpath(__DIR__). DS . '..' . DS);

ini_set('display_errors', "On");
ini_set('display_startup_errors', "On");
error_reporting(E_ALL);
#error_reporting(E_ALL ^ E_DEPRECATED);

// Composer autoloading
include __DIR__ . '/../vendor/autoload.php';
// include '/lamira/vendor/autoload.php';

use Dotenv\Dotenv;
use Laminas\Mvc\Application;
use Laminas\Stdlib\ArrayUtils;

$is_env = false;
try {
    if (method_exists('Dotenv\Dotenv', 'createUnsafeImmutable')) {
        Dotenv::createUnsafeImmutable(APP_PATH)->load();
    } else {
        Dotenv::createMutable(APP_PATH)->load();
    }
    $is_env = true;
} catch (Exception $e) {
    $msg = $e->getMessage();
    $msg = preg_replace("/( at.)\[(.*)\]/", "", $msg);
    echo $msg.PHP_EOL;
}

// var_dump($is_env);
// die();
if ($is_env) {
    error_reporting(0);
    ini_set('display_errors', "Off");
    ini_set('display_startup_errors', "Off");
    // var_dump(env('APPLICATION_ENV'));
    // var_dump((env['APPLICATION_ENV'] ?? "production"));
    // die();
    // die('qqq');
    
    if (env('DEBUG', "false") === 'true') {
        // ini_set("xdebug.var_display_max_children", -1);
        // ini_set("xdebug.var_display_max_data", -1);
        // ini_set("xdebug.var_display_max_depth", -1);
        ini_set('display_errors', "On");
        ini_set('display_startup_errors', "On");
        // error_reporting(E_ALL);
        // die('qqq');
        error_reporting(E_ALL ^ E_DEPRECATED);
    }

    /**
     * This makes our life easier when dealing with paths. Everything is relative
     * to the application root now.
     */
    chdir(dirname(__DIR__));

    // Decline static file requests back to the PHP built-in webserver
    if (php_sapi_name() === 'cli-server') {
        $path = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        if (is_string($path) && __FILE__ !== $path && is_file($path)) {
            return false;
        }
        unset($path);
    }

    if (!class_exists(Application::class)) {
        throw new RuntimeException(
            "Unable to load application.\n"
            . "- Type `composer install` if you are developing locally.\n"
            . "- Type `vagrant ssh -c 'composer install'` if you are using Vagrant.\n"
            . "- Type `docker-compose run laminas composer install` if you are using Docker.\n"
        );
    }

    // require __DIR__ . '/../_/function.php';
    // if (($_SERVER['APPLICATION_ENV']) ?? "production" === 'development') {
    //     $const = file_get_contents(__DIR__ . '/../_/constant');
    //     eval($const);
    //     // die("qqq");
    // }else{
    //     $const = decrypt_const();
    //     eval($const);
    // }
    // require __DIR__ . '/../_/constant.php';
    // require __DIR__ . '/../_/variable.php';

    // Retrieve configuration
    $appConfig = require __DIR__ . '/../config/application.config.php';
    if (file_exists(__DIR__ . '/../config/'.env('APPLICATION_ENV', "development").'.config.php')) {
        $appConfig = ArrayUtils::merge($appConfig, require __DIR__ . '/../config/'.env('APPLICATION_ENV', "development").'.config.php');
    }
    // !d($appConfig);die();

    // Run the application!
    Application::init($appConfig)->run();
}
