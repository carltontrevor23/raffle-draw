<?php
/**
 * Database Configuration & PDO Connection
 * Customer Appreciation Month Raffle Draw System
 * 
 * Default credentials configured for standard local development (XAMPP).
 */

$host     = 'localhost';
$db_name  = 'raffle_db';
$username = 'root';
$password = ''; // Default password in XAMPP is empty
$charset  = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db_name};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on SQL errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
];

$pdo = null;
$db_connected = false;
$db_error = null;

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $db_connected = true;
} catch (PDOException $e) {
    $db_connected = false;
    $db_error = $e->getMessage();
}
