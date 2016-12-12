<?php

return [
    'front' => [
        'throwExceptions' => true,
    ],

    'cache' => [
        'backend' => 'Black-Hole',
        'backendOptions' => [],
        'frontendOptions' => [
            'write_control' => false
        ],
    ],

    'model' => [
        'cacheProvider' => 'Array'
    ],

    'httpCache' => [
        'enabled' => true,
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
        'username' => "__DB_USER__",
        'password' => "__DB_PASSWORD__",
        'dbname' => "__DB_DATABASE__",
        'host' => "__DB_HOST__",
        'port' => "__DB_PORT__"
    ]
];
