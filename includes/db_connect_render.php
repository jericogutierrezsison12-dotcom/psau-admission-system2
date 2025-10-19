<?php
/**
 * Render Database Connection File
 * For Render deployment with external MySQL database
 */

// Get database credentials from environment variables
$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'psau_admission';
$username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root';
$password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';

// Create connection
$conn = null;
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Set charset to utf8
    $conn->exec("set names utf8mb4");
} catch(PDOException $e) {
    // Log error instead of displaying it directly
    error_log("Database connection failed: " . $e->getMessage());
    
    // If in development mode, you can display the error
    if(defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        echo "Database connection failed: " . $e->getMessage();
    } else {
        echo "Database connection error. Please try again later.";
    }
    exit;
}
?>