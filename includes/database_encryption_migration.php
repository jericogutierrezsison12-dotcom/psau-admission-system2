<?php
/**
 * PSAU Admission System - Database Encryption Migration
 * Adds encrypted columns to existing tables for sensitive data
 */

require_once 'db_connect.php';
require_once 'encryption.php';

class DatabaseEncryptionMigration {
    private $conn;
    private $encryption_key;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->encryption_key = getenv('ENCRYPTION_KEY');
        
        if (empty($this->encryption_key)) {
            throw new Exception("ENCRYPTION_KEY environment variable is required");
        }
    }
    
    /**
     * Run the complete migration
     */
    public function migrate() {
        echo "Starting database encryption migration...\n";
        
        try {
            $this->addEncryptedColumns();
            $this->migrateExistingData();
            $this->createEncryptionIndexes();
            
            echo "Database encryption migration completed successfully!\n";
        } catch (Exception $e) {
            echo "Migration failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    /**
     * Add encrypted columns to existing tables
     */
    private function addEncryptedColumns() {
        echo "Adding encrypted columns...\n";
        
        // Users table - encrypt personal information
        $this->addColumnIfNotExists('users', 'first_name_encrypted', 'TEXT');
        $this->addColumnIfNotExists('users', 'last_name_encrypted', 'TEXT');
        $this->addColumnIfNotExists('users', 'email_encrypted', 'TEXT');
        $this->addColumnIfNotExists('users', 'mobile_number_encrypted', 'TEXT');
        $this->addColumnIfNotExists('users', 'address_encrypted', 'TEXT');
        $this->addColumnIfNotExists('users', 'birth_date_encrypted', 'TEXT');
        $this->addColumnIfNotExists('users', 'gender_encrypted', 'TEXT');
        
        // Applications table - encrypt application data
        $this->addColumnIfNotExists('applications', 'gpa_encrypted', 'TEXT');
        $this->addColumnIfNotExists('applications', 'strand_encrypted', 'TEXT');
        $this->addColumnIfNotExists('applications', 'school_name_encrypted', 'TEXT');
        $this->addColumnIfNotExists('applications', 'school_address_encrypted', 'TEXT');
        $this->addColumnIfNotExists('applications', 'essay_response_encrypted', 'LONGTEXT');
        $this->addColumnIfNotExists('applications', 'personal_statement_encrypted', 'LONGTEXT');
        
        // Documents table - encrypt document metadata
        $this->addColumnIfNotExists('documents', 'file_name_encrypted', 'TEXT');
        $this->addColumnIfNotExists('documents', 'file_path_encrypted', 'TEXT');
        $this->addColumnIfNotExists('documents', 'file_content_encrypted', 'LONGBLOB');
        $this->addColumnIfNotExists('documents', 'ocr_text_encrypted', 'LONGTEXT');
        
        // Admins table - encrypt admin data
        $this->addColumnIfNotExists('admins', 'username_encrypted', 'TEXT');
        $this->addColumnIfNotExists('admins', 'email_encrypted', 'TEXT');
        $this->addColumnIfNotExists('admins', 'mobile_number_encrypted', 'TEXT');
        
        // Activity logs - encrypt sensitive log data
        $this->addColumnIfNotExists('activity_logs', 'details_encrypted', 'TEXT');
        $this->addColumnIfNotExists('activity_logs', 'ip_address_encrypted', 'TEXT');
        
        echo "Encrypted columns added successfully.\n";
    }
    
    /**
     * Add a column if it doesn't exist
     */
    private function addColumnIfNotExists($table, $column, $type) {
        $check_sql = "SELECT COUNT(*) FROM information_schema.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = ? 
                     AND COLUMN_NAME = ?";
        
        $stmt = $this->conn->prepare($check_sql);
        $stmt->execute([$table, $column]);
        
        if ($stmt->fetchColumn() == 0) {
            $alter_sql = "ALTER TABLE `$table` ADD COLUMN `$column` $type";
            $this->conn->exec($alter_sql);
            echo "Added column: $table.$column\n";
        }
    }
    
    /**
     * Migrate existing data to encrypted columns
     */
    private function migrateExistingData() {
        echo "Migrating existing data to encrypted columns...\n";
        
        // Migrate users table
        $this->migrateUsersTable();
        
        // Migrate applications table
        $this->migrateApplicationsTable();
        
        // Migrate documents table
        $this->migrateDocumentsTable();
        
        // Migrate admins table
        $this->migrateAdminsTable();
        
        // Migrate activity logs
        $this->migrateActivityLogsTable();
        
        echo "Data migration completed.\n";
    }
    
    /**
     * Migrate users table data
     */
    private function migrateUsersTable() {
        echo "Migrating users table...\n";
        
        $sql = "SELECT id, first_name, last_name, email, mobile_number, address, birth_date, gender 
                FROM users 
                WHERE first_name_encrypted IS NULL";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        foreach ($users as $user) {
            $update_sql = "UPDATE users SET 
                          first_name_encrypted = ?,
                          last_name_encrypted = ?,
                          email_encrypted = ?,
                          mobile_number_encrypted = ?,
                          address_encrypted = ?,
                          birth_date_encrypted = ?,
                          gender_encrypted = ?
                          WHERE id = ?";
            
            $stmt = $this->conn->prepare($update_sql);
            $stmt->execute([
                encryptPersonalData($user['first_name']),
                encryptPersonalData($user['last_name']),
                encryptContactData($user['email']),
                encryptContactData($user['mobile_number']),
                encryptPersonalData($user['address']),
                encryptPersonalData($user['birth_date']),
                encryptPersonalData($user['gender']),
                $user['id']
            ]);
        }
        
        echo "Migrated " . count($users) . " user records.\n";
    }
    
    /**
     * Migrate applications table data
     */
    private function migrateApplicationsTable() {
        echo "Migrating applications table...\n";
        
        $sql = "SELECT id, gpa, strand, school_name, school_address, essay_response, personal_statement 
                FROM applications 
                WHERE gpa_encrypted IS NULL";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $applications = $stmt->fetchAll();
        
        foreach ($applications as $app) {
            $update_sql = "UPDATE applications SET 
                          gpa_encrypted = ?,
                          strand_encrypted = ?,
                          school_name_encrypted = ?,
                          school_address_encrypted = ?,
                          essay_response_encrypted = ?,
                          personal_statement_encrypted = ?
                          WHERE id = ?";
            
            $stmt = $this->conn->prepare($update_sql);
            $stmt->execute([
                encryptAcademicData($app['gpa']),
                encryptAcademicData($app['strand']),
                encryptAcademicData($app['school_name']),
                encryptAcademicData($app['school_address']),
                encryptApplicationData($app['essay_response']),
                encryptApplicationData($app['personal_statement']),
                $app['id']
            ]);
        }
        
        echo "Migrated " . count($applications) . " application records.\n";
    }
    
    /**
     * Migrate documents table data
     */
    private function migrateDocumentsTable() {
        echo "Migrating documents table...\n";
        
        $sql = "SELECT id, file_name, file_path, file_content, ocr_text 
                FROM documents 
                WHERE file_name_encrypted IS NULL";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $documents = $stmt->fetchAll();
        
        foreach ($documents as $doc) {
            $update_sql = "UPDATE documents SET 
                          file_name_encrypted = ?,
                          file_path_encrypted = ?,
                          file_content_encrypted = ?,
                          ocr_text_encrypted = ?
                          WHERE id = ?";
            
            $stmt = $this->conn->prepare($update_sql);
            $stmt->execute([
                encryptPersonalData($doc['file_name']),
                encryptPersonalData($doc['file_path']),
                !empty($doc['file_content']) ? PSAUEncryption::encryptFile($doc['file_content'], $doc['file_name']) : null,
                encryptPersonalData($doc['ocr_text']),
                $doc['id']
            ]);
        }
        
        echo "Migrated " . count($documents) . " document records.\n";
    }
    
    /**
     * Migrate admins table data
     */
    private function migrateAdminsTable() {
        echo "Migrating admins table...\n";
        
        $sql = "SELECT id, username, email, mobile_number 
                FROM admins 
                WHERE username_encrypted IS NULL";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $admins = $stmt->fetchAll();
        
        foreach ($admins as $admin) {
            $update_sql = "UPDATE admins SET 
                          username_encrypted = ?,
                          email_encrypted = ?,
                          mobile_number_encrypted = ?
                          WHERE id = ?";
            
            $stmt = $this->conn->prepare($update_sql);
            $stmt->execute([
                encryptPersonalData($admin['username']),
                encryptContactData($admin['email']),
                encryptContactData($admin['mobile_number']),
                $admin['id']
            ]);
        }
        
        echo "Migrated " . count($admins) . " admin records.\n";
    }
    
    /**
     * Migrate activity logs table data
     */
    private function migrateActivityLogsTable() {
        echo "Migrating activity logs table...\n";
        
        $sql = "SELECT id, details, ip_address 
                FROM activity_logs 
                WHERE details_encrypted IS NULL";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $logs = $stmt->fetchAll();
        
        foreach ($logs as $log) {
            $update_sql = "UPDATE activity_logs SET 
                          details_encrypted = ?,
                          ip_address_encrypted = ?
                          WHERE id = ?";
            
            $stmt = $this->conn->prepare($update_sql);
            $stmt->execute([
                encryptPersonalData($log['details']),
                encryptPersonalData($log['ip_address']),
                $log['id']
            ]);
        }
        
        echo "Migrated " . count($logs) . " activity log records.\n";
    }
    
    /**
     * Create indexes for encrypted data searching
     */
    private function createEncryptionIndexes() {
        echo "Creating encryption indexes...\n";
        
        // Create indexes for encrypted email fields (for login)
        $this->createIndexIfNotExists('users', 'idx_email_encrypted', 'email_encrypted(255)');
        $this->createIndexIfNotExists('admins', 'idx_email_encrypted', 'email_encrypted(255)');
        
        // Create indexes for encrypted names (for searching)
        $this->createIndexIfNotExists('users', 'idx_name_encrypted', 'first_name_encrypted(255), last_name_encrypted(255)');
        
        echo "Encryption indexes created.\n";
    }
    
    /**
     * Create an index if it doesn't exist
     */
    private function createIndexIfNotExists($table, $index_name, $columns) {
        $check_sql = "SELECT COUNT(*) FROM information_schema.STATISTICS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = ? 
                     AND INDEX_NAME = ?";
        
        $stmt = $this->conn->prepare($check_sql);
        $stmt->execute([$table, $index_name]);
        
        if ($stmt->fetchColumn() == 0) {
            $create_sql = "CREATE INDEX `$index_name` ON `$table` ($columns)";
            $this->conn->exec($create_sql);
            echo "Created index: $table.$index_name\n";
        }
    }
}

// Run migration if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $migration = new DatabaseEncryptionMigration($conn);
        $migration->migrate();
    } catch (Exception $e) {
        echo "Migration failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>
