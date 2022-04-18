<?php

/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

Kint::$depth_limit = 10;
Kint::$enabled_mode = env("DEBUG");

return [
    // 'laminas-cli' => [
    //     'commands' => [
    //         'scheduler-1min' => CoreAdmin\Command\Scheduler1Min::class,
    //     ],
    // ],
];
