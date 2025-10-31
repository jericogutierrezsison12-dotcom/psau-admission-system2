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

// Load PHP-provided encryption key if present (works on Render without shell env)
if (file_exists(__DIR__ . '/secret_key.php')) {
    // secret_key.php should define $ENCRYPTION_KEY_B64
    include_once __DIR__ . '/secret_key.php';
    if (!empty($ENCRYPTION_KEY_B64)) {
        putenv('ENCRYPTION_KEY=' . $ENCRYPTION_KEY_B64);
        $_ENV['ENCRYPTION_KEY'] = $ENCRYPTION_KEY_B64;
    }
}

// Database credentials - use environment variables if available, otherwise Railway defaults
$host = $_ENV['DB_HOST'] ?? 'yamanote.proxy.rlwy.net';
$dbname = $_ENV['DB_NAME'] ?? 'railway';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? 'IaUWDcKClkXCxBMsWoPDmVwgLjtICxyg';
$port = $_ENV['DB_PORT'] ?? 47606;

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