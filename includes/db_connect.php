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
    
    // Set connection to null instead of exiting to allow graceful error handling
    $conn = null;
    
    // Only exit on pages that absolutely require database (like dashboard, but not public index)
    // For public index page, we'll allow it to continue without database
    $script_path = $_SERVER['SCRIPT_NAME'] ?? '';
    $requires_db = (
        strpos($script_path, 'dashboard.php') !== false ||
        strpos($script_path, 'application_form.php') !== false ||
        strpos($script_path, 'profile.php') !== false ||
        strpos($script_path, '/admin/') !== false
    );
    
    if ($requires_db && !strpos($script_path, 'login.php')) {
        // For pages that require DB (but not login page), destroy session and redirect to login
        // Login page should handle DB errors gracefully without redirecting
        // Start session if not already started before destroying
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        // Clear all session data to prevent loops
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
        
        // Use absolute path from document root to prevent redirect loops
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        if (strpos($script_path, '/admin') !== false) {
            $redirect_url = $protocol . $host . '/public/login.php';
        } else {
            $redirect_url = $protocol . $host . '/public/login.php';
        }
        
        header('Location: ' . $redirect_url);
        exit;
    }
}