<?php
/**
 * PSAU Admission System - Encryption Setup Script
 * Sets up end-to-end encryption for the system
 */

require_once 'includes/db_connect.php';
require_once 'includes/encryption.php';
require_once 'includes/database_encryption_migration.php';

class EncryptionSetup {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Run complete encryption setup
     */
    public function setup() {
        echo "=== PSAU Admission System - Encryption Setup ===\n\n";
        
        try {
            // Step 1: Generate encryption key
            $this->generateEncryptionKey();
            
            // Step 2: Test encryption functionality
            $this->testEncryption();
            
            // Step 3: Run database migration
            $this->runDatabaseMigration();
            
            // Step 4: Create necessary directories
            $this->createDirectories();
            
            // Step 5: Set up file permissions
            $this->setFilePermissions();
            
            // Step 6: Generate configuration
            $this->generateConfiguration();
            
            echo "\n=== Encryption Setup Complete! ===\n";
            echo "Your system is now protected with end-to-end encryption.\n";
            echo "Please save the encryption key securely and update your .env file.\n";
            
        } catch (Exception $e) {
            echo "\n=== Setup Failed ===\n";
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Generate and display encryption key
     */
    private function generateEncryptionKey() {
        echo "1. Generating encryption key...\n";
        
        $key = base64_encode(random_bytes(32));
        
        echo "   Generated encryption key: " . $key . "\n";
        echo "   Please save this key securely!\n";
        echo "   Add this to your .env file: ENCRYPTION_KEY=" . $key . "\n\n";
        
        // Set environment variable for this session
        putenv("ENCRYPTION_KEY=" . $key);
    }
    
    /**
     * Test encryption functionality
     */
    private function testEncryption() {
        echo "2. Testing encryption functionality...\n";
        
        try {
            // Test basic encryption
            $test_data = "This is a test of the encryption system.";
            $encrypted = PSAUEncryption::encrypt($test_data, 'test');
            $decrypted = PSAUEncryption::decrypt($encrypted, 'test');
            
            if ($decrypted === $test_data) {
                echo "   ✓ Basic encryption/decryption working\n";
            } else {
                throw new Exception("Encryption test failed");
            }
            
            // Test database encryption
            $db_encrypted = PSAUEncryption::encryptForDatabase($test_data, 'users', 'first_name');
            $db_decrypted = PSAUEncryption::decryptFromDatabase($db_encrypted, 'users', 'first_name');
            
            if ($db_decrypted === $test_data) {
                echo "   ✓ Database encryption working\n";
            } else {
                throw new Exception("Database encryption test failed");
            }
            
            // Test file encryption
            $file_encrypted = PSAUEncryption::encryptFile($test_data, 'test.txt');
            $file_decrypted = PSAUEncryption::decryptFile($file_encrypted, 'test.txt');
            
            if ($file_decrypted === $test_data) {
                echo "   ✓ File encryption working\n";
            } else {
                throw new Exception("File encryption test failed");
            }
            
            echo "   ✓ All encryption tests passed\n\n";
            
        } catch (Exception $e) {
            throw new Exception("Encryption test failed: " . $e->getMessage());
        }
    }
    
    /**
     * Run database migration
     */
    private function runDatabaseMigration() {
        echo "3. Running database migration...\n";
        
        try {
            $migration = new DatabaseEncryptionMigration($this->conn);
            $migration->migrate();
            echo "   ✓ Database migration completed\n\n";
        } catch (Exception $e) {
            throw new Exception("Database migration failed: " . $e->getMessage());
        }
    }
    
    /**
     * Create necessary directories
     */
    private function createDirectories() {
        echo "4. Creating directories...\n";
        
        $directories = [
            '../uploads/encrypted/',
            '../uploads/encrypted/temp/',
            '../logs/',
            '../backups/encrypted/'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                if (mkdir($dir, 0755, true)) {
                    echo "   ✓ Created directory: $dir\n";
                } else {
                    echo "   ⚠ Failed to create directory: $dir\n";
                }
            } else {
                echo "   ✓ Directory exists: $dir\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Set file permissions
     */
    private function setFilePermissions() {
        echo "5. Setting file permissions...\n";
        
        $directories = [
            '../uploads/encrypted/',
            '../logs/'
        ];
        
        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                chmod($dir, 0755);
                echo "   ✓ Set permissions for: $dir\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Generate configuration files
     */
    private function generateConfiguration() {
        echo "6. Generating configuration...\n";
        
        // Generate .htaccess for encrypted uploads
        $htaccess_content = "Order Deny,Allow\nDeny from all";
        $htaccess_file = '../uploads/encrypted/.htaccess';
        
        if (file_put_contents($htaccess_file, $htaccess_content)) {
            echo "   ✓ Created .htaccess for encrypted uploads\n";
        }
        
        // Generate encryption status file
        $status = PSAUEncryption::getStatus();
        $status_file = '../logs/encryption_status.json';
        
        if (file_put_contents($status_file, json_encode($status, JSON_PRETTY_PRINT))) {
            echo "   ✓ Created encryption status file\n";
        }
        
        echo "\n";
    }
    
    /**
     * Display security recommendations
     */
    public function displaySecurityRecommendations() {
        echo "\n=== Security Recommendations ===\n";
        echo "1. Store your encryption key securely (not in version control)\n";
        echo "2. Regularly backup your encrypted data\n";
        echo "3. Monitor access logs for suspicious activity\n";
        echo "4. Keep your encryption key separate from your database\n";
        echo "5. Consider using a hardware security module (HSM) for production\n";
        echo "6. Regularly rotate encryption keys\n";
        echo "7. Test your backup and recovery procedures\n";
        echo "8. Implement proper access controls for encrypted data\n";
    }
}

// Run setup if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $setup = new EncryptionSetup($conn);
        $setup->setup();
        $setup->displaySecurityRecommendations();
    } catch (Exception $e) {
        echo "Setup failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>
