<?php

return [
    'front' => array(
        'throwExceptions' => true,
    ),

    // Backend-Cache
    'cache' => array(
        'backend' => 'Black-Hole',
        'backendOptions' => array(),
        'frontendOptions' => array(
            'write_control' => false
        ),
    ),

    // Model-Cache
    'model' => array(
        'cacheProvider' => 'Array' // supports Apc, Array, Wincache and Xcache
    ),

    // Http-Cache
    'httpCache' => array(
        'enabled' => true, // true or false
        'debug' => true,
    ),

    'phpsettings' => [
        'display_errors' => 1,
        'display_startup_errors' => 1
    ],

    'csrfProtection' => [
        'frontend' => false,
        'backend' => false
    ],
    'db' => array(
        'username' => 'root',
        'password' => 'root',
        'dbname' => 'importexport_test',
        'host' => 'localhost',
        'port' => '3306'
    )
];
