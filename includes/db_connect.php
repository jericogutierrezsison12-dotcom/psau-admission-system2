<?php
/**
 * Database Connection File
 * Establishes connection to MySQL/PostgreSQL database for PSAU Admission System
 */

// Database credentials - use environment variables for production
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'psau_admission';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$port = $_ENV['DB_PORT'] ?? '3306';

// Determine database type based on environment
$db_type = $_ENV['DB_TYPE'] ?? 'mysql';

// Create connection string based on database type
if ($db_type === 'postgresql') {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
} else {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname";
}

// Create connection
$conn = null;
try {
    $conn = new PDO($dsn, $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set charset for MySQL
    if ($db_type === 'mysql') {
        $conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
    }
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