<?php
require_once __DIR__ . '/config.php';

$dbHost = 'localhost';
$dbName = 'nust_reconnect';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $ex) {
    http_response_code(500);
    exit('Database connection failed. Check includes/db.php and make sure the database was imported.');
}

require_once __DIR__ . '/functions.php';
