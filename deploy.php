<?php
/**
 * PSAU Admission System - Deployment Script
 * Automatically runs on Render deployment
 */

// Set execution time limit for deployment
set_time_limit(300);

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Set environment variables from Render
foreach ($_ENV as $key => $value) {
    putenv("$key=$value");
}

try {
    echo "Starting PSAU Admission System deployment...\n";
    
    // 1. Create necessary directories
    $directories = [
        'logs',
        'backups',
        'uploads/encrypted',
        'uploads/encrypted/temp',
        'cache',
        'tmp'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "Created directory: $dir\n";
        }
    }
    
    // 2. Set proper permissions
    $writable_dirs = ['logs', 'backups', 'uploads', 'cache', 'tmp'];
    foreach ($writable_dirs as $dir) {
        if (is_dir($dir)) {
            chmod($dir, 0755);
            echo "Set permissions for: $dir\n";
        }
    }
    
    // 3. Run database migration
    require_once 'includes/db_connect.php';
    require_once 'includes/database_migration.php';
    
    $migration = new DatabaseMigration($conn);
    if ($migration->needsMigration()) {
        echo "Running database migration...\n";
        $migration->migrate();
        echo "Database migration completed!\n";
    } else {
        echo "Database is up to date.\n";
    }
    
    // 4. Test encryption system
    require_once 'includes/encryption.php';
    
    try {
        $test_data = PSAUEncryption::encrypt('deployment_test', 'system');
        $decrypted = PSAUEncryption::decrypt($test_data, 'system');
        
        if ($decrypted === 'deployment_test') {
            echo "Encryption system working correctly!\n";
        } else {
            throw new Exception("Encryption test failed");
        }
    } catch (Exception $e) {
        echo "WARNING: Encryption system test failed: " . $e->getMessage() . "\n";
        echo "Please check your ENCRYPTION_KEY environment variable.\n";
    }
    
    // 5. Create initial system health record
    try {
        $stmt = $conn->prepare("INSERT INTO system_health (check_type, status, message, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            'deployment',
            'healthy',
            'System deployed successfully',
            json_encode([
                'deployment_time' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
            ])
        ]);
        echo "Created initial system health record.\n";
    } catch (Exception $e) {
        echo "Could not create system health record: " . $e->getMessage() . "\n";
    }
    
    // 6. Set up automated backup if not exists
    if (!file_exists('admin/auto_backup.php')) {
        echo "Backup system not found. Please ensure all files are uploaded.\n";
    } else {
        echo "Backup system ready.\n";
    }
    
    echo "Deployment completed successfully!\n";
    echo "System is ready for use.\n";
    
} catch (Exception $e) {
    echo "Deployment failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
