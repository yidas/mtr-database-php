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
    'api' => [
        'key' => '',            // API key must be the same bewteen agent and collector
        'agent' => [
            'enabled' => false, // To send MTR data to collector via API Agent (deafult is database)
            'reportUrl' => '',  // Collector's API URL
        ],
        'collector' => [
            'enabled' => false, // To receive MTR data from agent via API
        ],
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
        'enabled' => false,
        'username' => '',
        'password' => '',
        'categories' => [''],   // Category list for selection
    ],
];
