<?php
/**
 * Auto Database Cleanup on Deploy
 * This script automatically removes encryption-related tables and columns
 * when deployed on Render/Railway
 */

// Only run this script in production environment
if (!isset($_ENV['RENDER']) && !isset($_ENV['RAILWAY_ENVIRONMENT'])) {
    echo "This script only runs in production environment (Render/Railway)\n";
    exit(0);
}

require_once 'includes/db_connect.php';

echo "=== Auto Database Cleanup on Deploy ===\n";
echo "Environment: " . ($_ENV['RENDER'] ? 'Render' : 'Railway') . "\n\n";

try {
    // Use the global $conn variable from db_connect.php
    global $conn;
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    echo "Database connected successfully\n\n";
    
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
    
    // Step 3: Remove encrypted columns from applications table
    echo "\n3. Removing encrypted columns from applications table...\n";
    $app_columns_to_drop = [
        'notes_encrypted',
        'previous_school_encrypted',
        'school_year_encrypted',
        'strand_encrypted',
        'gpa_encrypted',
        'address_encrypted'
    ];
    
    foreach ($app_columns_to_drop as $column) {
        try {
            $conn->exec("ALTER TABLE applications DROP COLUMN IF EXISTS `$column`");
            echo "   ✓ Removed column from applications: $column\n";
        } catch (PDOException $e) {
            echo "   ✗ Error removing $column: " . $e->getMessage() . "\n";
        }
    }
    
    // Step 4: Clean up any remaining encryption files
    echo "\n4. Cleaning up encryption files...\n";
    $files_to_remove = [
        'includes/encryption.php',
        'includes/encrypted_file_storage.php',
        'includes/encrypted_data_access.php',
        'includes/database_encryption_migration.php',
        'public/download_encrypted_file.php',
        'setup_encryption.php',
        'ENCRYPTION_GUIDE.md',
        'remove_encryption.php',
        'cleanup_encryption.sql'
    ];
    
    foreach ($files_to_remove as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                echo "   ✓ Removed file: $file\n";
            } else {
                echo "   ✗ Failed to remove file: $file\n";
            }
        } else {
            echo "   - File not found: $file\n";
        }
    }
    
    echo "\n=== Database Cleanup Completed Successfully ===\n";
    echo "All encryption-related tables, columns, and files have been removed.\n";
    echo "The application is now ready to run without encryption.\n";
    
    // Log the cleanup completion
    error_log("Auto database cleanup completed on " . date('Y-m-d H:i:s'));
    
} catch (Exception $e) {
    echo "Error during cleanup: " . $e->getMessage() . "\n";
    error_log("Auto cleanup error: " . $e->getMessage());
    exit(1);
}
