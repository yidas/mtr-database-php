<?php

// CLI only
if (php_sapi_name() !== 'cli') 
    die('Access Denied');

define('DEFAULT_DAYS', 90);

// Class load
require __DIR__. '/src/GetOpt.php';

// Config file
$config = require __DIR__ . '/config.inc.php';
$dbConfig = & $config['database'];
$mtrConfig = & $config['mtr'];
$config = $config['general'];

// Timezone setting
date_default_timezone_set($config['timezone']);

/**
 * Options definition
 */
$shortopts  = "";
$shortopts .= "d::";
// Long options
$longopts  = array(
    "days::",
);

// GetOpt
$getOpt = new GetOpt($shortopts, $longopts);
// var_dump($getOpt->getOptions());exit;
// Days
$optTmp = $getOpt->get(['days', 'd']);
$days = ($optTmp) ? (integer) $optTmp : DEFAULT_DAYS;

try {

    // Database connection
    $conn = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['database']}", $dbConfig['username'], $dbConfig['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
    
    // Purge before-date calculation
    $beforeDatetime = date("Y-m-d H:i:s", time() - ($days * 24 * 3600));
    echo $beforeDatetime;exit;

    // Delete records
    $result = $conn->exec("DELETE FROM {$dbConfig['table']} WHERE `start_datetime` < \"{$beforeDatetime}\";");
    // var_dump($result);exit;
    if ($result === false) {
        die("Error!: " . $conn->errorInfo()[2] . "\n");
    }
    
    echo "Purge completed - Deleted rows: {$result}\n";
    
} catch (PDOException $e) {
    
    die("Error!: " . $e->getMessage() . "\n");
}
