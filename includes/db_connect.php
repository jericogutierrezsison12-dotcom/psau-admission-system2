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
    // Google Cloud SQL requires SSL when "Allow only SSL connections" is enabled
    $is_render = !empty($_ENV['RENDER']) || !empty($_SERVER['RENDER']);
    if ($is_render) {
        // Enable SSL connection for Google Cloud SQL (required when "Allow only SSL connections" is enabled)
        // Google Cloud SQL uses server certificates that are auto-managed
        $options[PDO::MYSQL_ATTR_SSL_CA] = null; // Use system CA bundle or null for Google managed
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false; // Disable strict verification for Render compatibility
        // Note: Setting SSL_CA (even to null) enables SSL connection in MySQL PDO
    }
    
    $conn = new PDO($dsn, $username, $password, $options);
    
} catch(PDOException $e) {
    // Log error
    $error_msg = "Database Connection Error: " . $e->getMessage();
    error_log($error_msg);
    error_log("Connection details - Host: $host, Port: $port, Database: $dbname, Username: $username");
    
    // On Render, show a user-friendly error page instead of white screen
    if (!empty($_ENV['RENDER']) || !empty($_SERVER['RENDER'])) {
        // Render environment - show error page
        http_response_code(500);
        echo "<!DOCTYPE html><html><head><title>Database Connection Error</title>";
        echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;}";
        echo ".error-box{background:white;padding:30px;border-radius:8px;max-width:600px;margin:0 auto;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
        echo "h1{color:#d32f2f;margin-top:0;}code{background:#f5f5f5;padding:2px 6px;border-radius:3px;}</style></head><body>";
        echo "<div class='error-box'><h1>⚠️ Database Connection Error</h1>";
        echo "<p>The application cannot connect to the database. Please check:</p><ul>";
        echo "<li>Google Cloud SQL instance is running</li>";
        echo "<li>Render IP addresses are authorized in Google Cloud SQL Console</li>";
        echo "<li>Database credentials are correct</li>";
        echo "</ul><p><strong>Error:</strong> <code>" . htmlspecialchars($e->getMessage()) . "</code></p>";
        echo "<p><small>Check Render logs for more details.</small></p></div></body></html>";
        exit;
    }
    
    // Development mode
    if(defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        die("Database Connection Failed: " . htmlspecialchars($e->getMessage()) . 
            "<br>Host: $host, Port: $port, Database: $dbname");
    } else {
        error_log("Database connection error. Please try again later.");
        die("Database connection error. Please contact administrator.");
    }
}