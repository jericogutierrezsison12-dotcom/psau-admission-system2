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

// Database credentials - prefer standardized DB_* envs, then fall back to common Railway/MySQL envs, then safe defaults
$host = $_ENV['DB_HOST']
    ?? $_ENV['MYSQLHOST']
    ?? $_ENV['RAILWAY_PRIVATE_DOMAIN']
    ?? $_ENV['RAILWAY_TCP_PROXY_DOMAIN']
    ?? '127.0.0.1';

$dbname = $_ENV['DB_NAME']
    ?? $_ENV['MYSQL_DATABASE']
    ?? 'railway';

$username = $_ENV['DB_USER']
    ?? $_ENV['MYSQLUSER']
    ?? 'root';

$password = $_ENV['DB_PASS']
    ?? $_ENV['MYSQLPASSWORD']
    ?? $_ENV['MYSQL_ROOT_PASSWORD']
    ?? 'zUILKKOYQTJykTmwhCYxFeaIWVHHjaKb';

$port = (int) ($_ENV['DB_PORT']
    ?? $_ENV['MYSQLPORT']
    ?? $_ENV['RAILWAY_TCP_PROXY_PORT']
    ?? 3306);

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
    // Auto-provision on first run if core tables are missing
    try {
        $chk = $conn->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'");
        $chk->execute();
        $hasUsers = (int)$chk->fetchColumn() > 0;
        if (!$hasUsers) {
            // Run provisioner to create all tables
            require_once __DIR__ . '/../scripts/provision_database.php';
        }
    } catch (Throwable $e) {
        // Ignore auto-provision errors here; normal pages can still handle gracefully
        error_log('Auto-provision check failed: ' . $e->getMessage());
    }
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