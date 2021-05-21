<?php

return [
    'general' => [
        'mtrCmd' => 'mtr',
        'timezone' => 'Asia/Taipei',
        'category' => '',   // Category mark for distinguishing
    ],
    'mtr' => [
        'host' => 'google.com',
        'period' => 10,     // Minute
        'count' => 60,      // Report-cycles
        'tcp' => false,     // TCP mode
        'port' => 443,      // Port number for TCP mode
    ],
    'database' => [
        'host' => 'localhost',
        'driver'    => 'mysql',
        'database'  => 'mtr_database',
        'username'  => '',
        'password'  => '',
        'table' => 'records',
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
    'dashboard' => [
        'enable' => true,
        'username' => '',
        'password' => '',
        'categories' => [''],   // Category list for selection
    ],
];
