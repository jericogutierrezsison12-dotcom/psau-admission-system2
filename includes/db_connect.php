<?php
/**
 * Database Connection File
 * Establishes connection to MySQL database for PSAU Admission System
 */

// Detect environment
$is_production = !empty($_ENV['RENDER']) || !empty($_SERVER['RENDER']);

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

// Database credentials - use environment variables if available
$host = getenv('DB_HOST') ?: 'shuttle.proxy.rlwy.net';
$dbname = getenv('DB_NAME') ?: 'railway';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: 'JCfNOSYEIrgNDqxwzaHBEufEJDPLQkKU';
$port = getenv('DB_PORT') ?: 40148;

// Create connection
$conn = null;
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Set charset (redundant, already in DSN, but harmless)
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Log successful connection (only in development)
    if (!$is_production) {
        error_log("Database connected successfully to: $host:$port/$dbname");
    }
    
} catch(PDOException $e) {
    // Log error instead of displaying it directly
    error_log("Database connection failed: " . $e->getMessage());
    error_log("Connection details - Host: $host, Port: $port, Database: $dbname, Username: $username");
    
    // Different error handling for production vs development
    if ($is_production) {
        // In production, show generic error message
        error_log("Database connection error in production environment");
        echo "Database connection error. Please try again later.";
    } else {
        // In development, show detailed error
        echo "Connection failed: " . $e->getMessage();
    }
    exit;
}