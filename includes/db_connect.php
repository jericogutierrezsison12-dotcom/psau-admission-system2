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

// Database credentials - use environment variables if available, otherwise Google Cloud SQL defaults
$host = $_ENV['DB_HOST'] ?? '34.170.34.174';
$dbname = $_ENV['DB_NAME'] ?? 'psau_admission';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? 'Psau_2025';
$port = $_ENV['DB_PORT'] ?? 3306;

// Create connection
$conn = null;
try {
    // Build DSN with SSL options for Google Cloud SQL (optional but recommended)
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    // Add SSL options if running on Render (production)
    $is_render = !empty($_ENV['RENDER']) || !empty($_SERVER['RENDER']);
    if ($is_render) {
        // Google Cloud SQL connection without SSL certificate verification for Render
        // Note: Make sure Render's IP ranges are authorized in Google Cloud SQL Console
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    
    $conn = new PDO($dsn, $username, $password, $options);
    
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