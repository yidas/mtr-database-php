<?php

// CLI only
if (php_sapi_name() !== 'cli') 
    die('Access Denied');

define('VERSION', "1.0.0");

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
$shortopts .= "h:";
$shortopts .= "m::";
$shortopts .= "p::";
$shortopts .= "c::";
$shortopts .= "T";
$shortopts .= "P::";
$shortopts .= "v";
// Long options
$longopts  = array(
    "category::",
    "host:",
    "mtr-arg::",
    "period::",
    "report-cycles::",
    "tcp",
    "port::",
    "debug",
    "help",
    "version",
);

// GetOpt
$getOpt = new GetOpt($shortopts, $longopts);
// var_dump($getOpt->getOptions());exit;
$mtr = [];
$mtr['mtrArgv'] = $getOpt->get(['mtr-arg', 'm']);
// Category
$optTmp = $getOpt->get(['category']);
$category = ($optTmp) ? $optTmp : $config['category'];
// Host
$optTmp = $getOpt->get(['host', 'h']);
$mtr['host'] = ($optTmp) ? $optTmp : $mtrConfig['host'];
// Period
$optTmp = $getOpt->get(['period', 'p']);
$mtr['period'] = ($optTmp) ? $optTmp : $mtrConfig['period'];
// Count
$optTmp = $getOpt->get(['report-cycles', 'c']);
$mtr['cycles'] = ($optTmp) ? $optTmp : $mtrConfig['count'];
// TCP
$optTmp = $getOpt->has(['tcp', 'T']);
$mtr['tcp-cmd'] = ($optTmp || $mtrConfig['tcp']) ? '--tcp' : '';
// TCP port
$optTmp = $getOpt->get(['port', 'P']);
$mtr['port'] = ($optTmp) ? $optTmp : $mtrConfig['port'];
$mtr['port-cmd'] = ($mtr['tcp-cmd']) ? "--port={$mtr['port']}" : '';
// Others
$debugMode = $getOpt->has(['debug']);
$showHelp = $getOpt->has(['help']);
$showVersion = $getOpt->has(['version']);

if ($showHelp) {
    
    exit;
}
elseif ($showVersion) {
    
    exit(VERSION . "\n");
}

// Database connection
try {

    // Database connection
    $conn = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['database']}", $dbConfig['username'], $dbConfig['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

} catch (PDOException $e) {
    
    die("Error!: " . $e->getMessage() . "\n");
}

// MTR process
$startDateTime = date("Y-m-d H:i:s");
$interval = floor(($mtr['period'] * 60) / $mtr['cycles']);
$cmd = "{$config['mtrCmd']} {$mtr['host']} -c {$mtr['cycles']} -i {$interval} {$mtr['tcp-cmd']} {$mtr['port-cmd']} -rb --json {$mtr['mtrArgv']}";
// $cmd = "{$config['mtrCmd']} -rb -c 3 -i 1 --json google.com";
if ($debugMode) {
    echo "{$cmd}\n";exit;
}
$output = shell_exec($cmd);
$endDateTime = date("Y-m-d H:i:s");
// echo $output;exit;
$data = json_decode($output, true);
// var_dump($data);exit;

// Check JSON
if (!isset($data['report'])) {
    die("Error!: MTR output result is wrong");
}

// Get end hub
$endHub = end($data['report']['hubs']);
// Save to database
$insertMap = [
    'start_datetime' => $startDateTime,
    'end_datetime' => $endDateTime,
    'category' => $category,
    'source' => $data['report']['mtr']['src'],
    'destination' => $data['report']['mtr']['dst'],
    'period' => $mtr['period'],
    'host' => $endHub['host'],
    'mtr_loss' => $endHub['Loss%'],
    'mtr_sent' => $endHub['Snt'],
    'mtr_avg' => $endHub['Avg'],
    'mtr_best' => $endHub['Best'],
    'mtr_worst' => $endHub['Wrst'],
    'mtr_raw' => $output,
    'command' => $cmd,
];
$sql = "INSERT INTO {$dbConfig['table']} (sn, start_datetime, end_datetime, period, category, source, destination, host, mtr_loss, mtr_sent, mtr_avg, mtr_best, mtr_worst, mtr_raw, command) 
    VALUES (NULL, :start_datetime, :end_datetime, :period, :category, :source, :destination, :host, :mtr_loss, :mtr_sent, :mtr_avg, :mtr_best, :mtr_worst, :mtr_raw, :command)";
$stmt = $conn->prepare($sql);
foreach ($insertMap as $key => $value) {
    $stmt->bindValue(":{$key}", $value);
}
$result = $stmt->execute();
if ($result === false) {
    // $stmt->debugDumpParams();
    die("Error!: " . $stmt->errorInfo()[2] . "\n");
}
    
exit("Process success\n");

