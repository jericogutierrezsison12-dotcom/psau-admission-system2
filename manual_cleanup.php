<?php
/**
 * Manual Database Cleanup Script
 * Run this script manually to clean up encryption from database
 * Usage: php manual_cleanup.php
 */

require_once 'includes/db_connect.php';

echo "=== Manual Database Cleanup ===\n";
echo "This will remove all encryption-related tables and columns.\n";
echo "Are you sure you want to continue? (y/N): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'y') {
    echo "Cleanup cancelled.\n";
    exit(0);
}

echo "\nStarting cleanup...\n\n";

try {
    global $conn;
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Drop tables
    $tables = ['system_health', 'user_sessions', 'security_incidents', 'backup_history', 'blocked_ips', 'otp_codes'];
    foreach ($tables as $table) {
        $conn->exec("DROP TABLE IF EXISTS `$table`");
        echo "✓ Dropped table: $table\n";
    }
    
    // Drop encrypted columns from users
    $columns = ['first_name_encrypted', 'last_name_encrypted', 'email_encrypted', 'mobile_number_encrypted', 'gender_encrypted', 'birth_date_encrypted', 'address_encrypted'];
    foreach ($columns as $column) {
        $conn->exec("ALTER TABLE users DROP COLUMN IF EXISTS `$column`");
        echo "✓ Removed column: $column\n";
    }
    
    // Drop encrypted columns from applications
    $app_columns = ['notes_encrypted', 'previous_school_encrypted', 'school_year_encrypted', 'strand_encrypted', 'gpa_encrypted', 'address_encrypted'];
    foreach ($app_columns as $column) {
        $conn->exec("ALTER TABLE applications DROP COLUMN IF EXISTS `$column`");
        echo "✓ Removed column from applications: $column\n";
    }
    
    echo "\n=== Cleanup Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
