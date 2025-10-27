<?php
/**
 * Remove all encryption-related code and database structures
 * Run this script to remove encryption from the codebase
 */

require_once 'includes/db_connect.php';

echo "=== Removing Encryption System ===\n\n";

try {
    // Use the global $conn variable from db_connect.php
    global $conn;
    
    // Step 1: Drop security tables
    echo "1. Dropping security tables...\n";
    $tables_to_drop = [
        'system_health',
        'user_sessions',
        'security_incidents',
        'backup_history',
        'blocked_ips',
        'otp_codes'
    ];
    
    foreach ($tables_to_drop as $table) {
        try {
            $conn->exec("DROP TABLE IF EXISTS `$table`");
            echo "   ✓ Dropped table: $table\n";
        } catch (PDOException $e) {
            echo "   ✗ Error dropping $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Step 2: Remove encrypted columns from users table
    echo "\n2. Removing encrypted columns from users table...\n";
    $columns_to_drop = [
        'first_name_encrypted',
        'last_name_encrypted',
        'email_encrypted',
        'mobile_number_encrypted',
        'gender_encrypted',
        'birth_date_encrypted',
        'address_encrypted'
    ];
    
    foreach ($columns_to_drop as $column) {
        try {
            $conn->exec("ALTER TABLE users DROP COLUMN IF EXISTS `$column`");
            echo "   ✓ Removed column: $column\n";
        } catch (PDOException $e) {
            echo "   ✗ Error removing $column: " . $e->getMessage() . "\n";
        }
    }
    
    // Step 3: Check and remove encrypted columns from applications table
    echo "\n3. Checking applications table...\n";
    try {
        $stmt = $conn->query("SHOW COLUMNS FROM applications LIKE '%_encrypted'");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($columns)) {
            foreach ($columns as $column) {
                try {
                    $conn->exec("ALTER TABLE applications DROP COLUMN IF EXISTS `$column`");
                    echo "   ✓ Removed column from applications: $column\n";
                } catch (PDOException $e) {
                    echo "   ✗ Error removing $column: " . $e->getMessage() . "\n";
                }
            }
        } else {
            echo "   ✓ No encrypted columns found in applications table\n";
        }
    } catch (PDOException $e) {
        echo "   ✗ Error checking applications table: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== Encryption System Removed Successfully ===\n";
    echo "Next steps:\n";
    echo "1. Update all PHP files to remove encryption includes and calls\n";
    echo "2. Update registration and profile PHP files\n";
    echo "3. Remove encryption.php and related files\n";
    echo "4. Commit and push changes to Git\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
