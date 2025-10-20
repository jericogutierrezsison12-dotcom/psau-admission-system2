<?php
/**
 * Database Connection File for InfinityFree Hosting
 * Establishes connection to MySQL database for PSAU Admission System
 * 
 * IMPORTANT: Update these credentials with your InfinityFree database details
 * You can find these in your InfinityFree control panel under "MySQL Databases"
 */

// InfinityFree Database credentials
// Replace these with your actual InfinityFree database details
$host = 'sqlXXX.infinityfree.com'; // Replace XXX with your server number
$dbname = 'if0_XXXXXXXX'; // Replace with your database name
$username = 'if0_XXXXXXXX'; // Replace with your database username
$password = 'your_password_here'; // Replace with your database password

// Create connection
$conn = null;
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set charset to UTF-8
    $conn->exec("set names utf8");
    
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

// Function to test database connection
function testDatabaseConnection() {
    global $conn;
    try {
        $stmt = $conn->query("SELECT 1");
        return true;
    } catch(PDOException $e) {
        error_log("Database test failed: " . $e->getMessage());
        return false;
    }
}
?>
