<?php

/**
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace App;

use Laminas\ModuleManager\ModuleEvent;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\MvcEvent;

class Module
{
    /**
     * Load Every Request (1)
     */
    public function init(ModuleManager $moduleManager){
        // !d(__METHOD__);
    }

    /**
     * Load Every Request (3)
     */
    public function onBootstrap(MvcEvent $mvcEvent){
        // !d(__METHOD__);
    }

    /**
     * Load Every Request (2)
     */
    public function getConfig() : array
    {
        // !d(__METHOD__);
        return include __DIR__ . '/../config/module.config.php';
    }
}
