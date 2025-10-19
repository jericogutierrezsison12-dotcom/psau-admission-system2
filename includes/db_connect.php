<?php
/**
 * Database Connection File
 * Establishes connection to MySQL database for PSAU Admission System
 */

// Database credentials - Environment-based configuration
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'psau_admission';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

// For InfinityFree hosting, use their database credentials
if (strpos($_SERVER['HTTP_HOST'] ?? '', 'infinityfree') !== false) {
    $host = 'sql201.infinityfree.com'; // Replace with your InfinityFree DB host
    $dbname = 'if0_12345678_psau_admission'; // Replace with your actual DB name
    $username = 'if0_12345678'; // Replace with your actual username
    $password = 'your_password_here'; // Replace with your actual password
}

// Create connection
$conn = null;
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Log error instead of displaying it directly
    error_log("Connection failed: " . $e->getMessage());
    
    // If in development mode, you can display the error
    if(defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        echo "Connection failed: " . $e->getMessage();
    } else {
        echo "Database connection error. Please try again later.";
    }
    exit;
} 