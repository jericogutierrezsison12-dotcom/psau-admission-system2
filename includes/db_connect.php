<?php
/**
 * Database Connection File
 * Establishes connection to MySQL database for PSAU Admission System
 * Compatible with both local development and Render deployment
 */

// Detect environment
$is_production = !empty($_ENV['RENDER']) || !empty($_SERVER['RENDER']);

// Database credentials - use environment variables if available
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'psau_admission';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

// Create connection
$conn = null;
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Set charset
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Log successful connection (only in development)
    if (!$is_production) {
        error_log("Database connected successfully to: $host/$dbname");
    }
    
} catch(PDOException $e) {
    // Log error instead of displaying it directly
    error_log("Database connection failed: " . $e->getMessage());
    error_log("Connection details - Host: $host, Database: $dbname, User: $username");
    
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