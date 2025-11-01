<?php
/**
 * Database Connection File
 * Establishes connection to MySQL database for PSAU Admission System
 */

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database credentials - use environment variables if available, otherwise Railway defaults
$host = $_ENV['DB_HOST'] ?? 'trolley.proxy.rlwy.net';
$dbname = $_ENV['DB_NAME'] ?? 'railway';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? 'maFGvjqYlZuUdOmjvzArclEoYpUejThA';
$port = $_ENV['DB_PORT'] ?? 48642;

// Create connection
$conn = null;
try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Set charset to utf8
    $conn->exec("SET NAMES utf8");
} catch(PDOException $e) {
    // Log error instead of displaying it directly
    error_log("Connection failed: " . $e->getMessage());
    error_log("Connection details - Host: $host, Port: $port, Database: $dbname, Username: $username");
    
    // If in development mode, you can display the error
    if(defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        error_log("Connection failed: " . $e->getMessage());
        error_log("Host: $host, Port: $port, Database: $dbname, Username: $username");
    } else {
        error_log("Database connection error. Please try again later.");
    }
    exit;
}