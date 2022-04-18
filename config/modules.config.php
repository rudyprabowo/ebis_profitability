<?php

/**
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */

/**
 * List of enabled modules for this application.
 *
 * This should be an array of module namespaces used in the application.
 */
return [
    'Laminas\Cache',
    'Laminas\Mvc\Plugin\FilePrg',
    'Laminas\Mvc\Plugin\Prg',
    'Laminas\Mvc\Middleware',
    'Laminas\Hydrator',
    'Laminas\Serializer',
    'Laminas\Mail',
    'Laminas\Di',
    'Laminas\Log',
    'Laminas\Db',
    'Laminas\Mvc\Plugin\FlashMessenger',
    'Laminas\Mvc\Plugin\Identity',
    'Laminas\Session',
    'Laminas\Mvc\I18n',
    // 'Laminas\Mvc\Console',
    'Laminas\Form',
    'Laminas\InputFilter',
    'Laminas\Filter',
    'Laminas\I18n',
    'Laminas\Cache\Module',
    'Laminas\Router',
    'Laminas\Validator',
    'Laminas\Diactoros',
    'Laminas\ZendFrameworkBridge',
    'Laminas\Cache\Storage\Adapter\Filesystem',
    'Core',
    'CoreAdmin',
    'App'
];
