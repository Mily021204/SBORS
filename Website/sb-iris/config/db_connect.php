<?php
// config/db_connect.php

$host = '127.0.0.1'; // Changed from localhost to force TCP/IP
$port = '3306';      // Added custom port from your phpMyAdmin
$db   = 'sb_iris_db';
$user = 'root'; 
$pass = '';     
$charset = 'utf8mb4';

// Appended port=$port to the DSN string
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // In a production environment, log the error rather than echoing it directly
    error_log($e->getMessage());
    exit('Database connection failed. Please contact the administrator.');
}
?>