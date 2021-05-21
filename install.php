<?php

// CLI only
if (php_sapi_name() !== 'cli') 
    die('Access Denied');

// Config file
$config = require __DIR__ . '/config.inc.php';
$dbConfig = & $config['database'];

try {

    // Database connection
    $conn = new PDO("mysql:host={$dbConfig['host']};", $dbConfig['username'], $dbConfig['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
    
    // Create database
    $result = $conn->exec("CREATE DATABASE {$dbConfig['database']} CHARACTER SET {$dbConfig['charset']} COLLATE {$dbConfig['collation']};");
    if ($result) {
        echo "Success: Create database '{$dbConfig['database']}' \n";
    }
    $conn->exec("USE {$dbConfig['database']};");

    // Create table with schema
    $sql = getTableSchema($dbConfig['table'], $dbConfig['charset'], $dbConfig['collation']);
    // echo $sql;exit;
    $result = $conn->exec($sql);
    if ($result === false) {
        die("Error!: " . $conn->errorInfo()[2] . "\n");
    }
    
    echo "Installation completed\n";
    
} catch (PDOException $e) {
    
    die("Error!: " . $e->getMessage() . "\n");
}

/**
 * Table schema
 *
 * @param string $table
 * @param string $charset
 * @return void
 */
function getTableSchema($table, $charset, $collation)
{
    return "CREATE TABLE `{$table}` (
        `sn` bigint(20) UNSIGNED NOT NULL,
        `start_datetime` datetime NOT NULL,
        `end_datetime` datetime NOT NULL,
        `period` smallint(5) UNSIGNED NOT NULL COMMENT 'Minute',
        `category` char(16) NOT NULL,
        `source` varchar(255) NOT NULL,
        `destination` varchar(255) NOT NULL,
        `host` varchar(255) NOT NULL,
        `mtr_loss` float UNSIGNED NOT NULL COMMENT '%',
        `mtr_sent` int(10) UNSIGNED NOT NULL,
        `mtr_avg` float UNSIGNED NOT NULL,
        `mtr_best` float UNSIGNED NOT NULL,
        `mtr_worst` float UNSIGNED NOT NULL,
        `mtr_raw` text NOT NULL,
        `command` varchar(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation};

        ALTER TABLE `{$table}`
            ADD PRIMARY KEY (`sn`),
            ADD KEY `start_datetime` (`start_datetime`);

        ALTER TABLE `{$table}`
            MODIFY `sn` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
        COMMIT;";
}