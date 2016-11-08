<?php

return [
    'front' => [
        'throwExceptions' => true,
    ],

    // Backend-Cache
    'cache' => [
        'backend' => 'Black-Hole',
        'backendOptions' => [],
        'frontendOptions' => [
            'write_control' => false
        ],
    ],

    // Model-Cache
    'model' => [
        'cacheProvider' => 'Array' // supports Apc, Array, Wincache and Xcache
    ],

    // Http-Cache
    'httpCache' => [
        'enabled' => true, // true or false
        'debug' => true,
    ],

    'phpsettings' => [
        'display_errors' => 1,
        'display_startup_errors' => 1
    ],

    'csrfProtection' => [
        'frontend' => false,
        'backend' => false
    ],
    
    'db' => [
        'username' => 'root',
        'password' => 'root',
        'dbname' => 'importexport_test',
        'host' => 'localhost',
        'port' => '3306'
    ]
];
