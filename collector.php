<?php

// Config file
$config = require __DIR__ . '/config.inc.php';
$dbConfig = & $config['database'];
$mtrConfig = & $config['mtr'];
$apiConfig = & $config['api'];
$config = $config['general'];

// Timezone setting
date_default_timezone_set($config['timezone']);

// Get request body
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Verify API key
if (!$apiConfig['collector']['enabled']) {
    http_response_code(403);
    exit;
}

// Request check
if (!$data || !isset($data['key']) || !isset($data['data'])) {
    http_response_code(400);
    exit;
}

// Verify API key
if ($data['key'] != $apiConfig['key']) {
    http_response_code(401);
    exit;
}

// Response body
$response = [];

// Database connection try
try {

    // Database connection
    $conn = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['database']}", $dbConfig['username'], $dbConfig['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

} catch (PDOException $e) {
    
    http_response_code(500);
    $response['message'] = $e->getMessage();
    // die("Error!: " . $e->getMessage() . "\n");
}

// Database writing
$sql = "INSERT INTO {$dbConfig['table']} (sn, start_datetime, end_datetime, period, category, source, destination, host, mtr_loss, mtr_sent, mtr_avg, mtr_best, mtr_worst, mtr_raw, command) 
VALUES (NULL, :start_datetime, :end_datetime, :period, :category, :source, :destination, :host, :mtr_loss, :mtr_sent, :mtr_avg, :mtr_best, :mtr_worst, :mtr_raw, :command)";
$stmt = $conn->prepare($sql);
foreach ($data['data'] as $key => $value) {
    $stmt->bindValue(":{$key}", $value);
}
$result = $stmt->execute();
if ($result === false) {
    // $stmt->debugDumpParams();
    http_response_code(500);
    $response['message'] = $stmt->errorInfo()[2];
    // die("Error!: " . $stmt->errorInfo()[2] . "\n");
}

header('Content-Type: application/json');
echo json_encode($response);