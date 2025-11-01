<?php
/**
 * Database Connection File
 * Establishes connection to MySQL database for PSAU Admission System
 */

// Load environment variables from .env file
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
            // Also set in environment for getenv() to work
            putenv("$key=$value");
        }
    }
}

// On Render, environment variables are set by the platform
// Make sure they're also in $_ENV if they exist in getenv()
if (empty($_ENV['ENCRYPTION_KEY']) && getenv('ENCRYPTION_KEY')) {
    $_ENV['ENCRYPTION_KEY'] = getenv('ENCRYPTION_KEY');
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
    
    // Don't call exit() here - let pages handle null $conn gracefully
    // This prevents redirect loops when connection fails
    $conn = null;
}