<?php
/**
 * PSAU Admission System - Database Migration Script
 * Automatically creates security tables on deployment
 */

require_once 'db_connect.php';

class DatabaseMigration {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Run all migrations
     */
    public function migrate() {
        try {
            $this->log("Starting database migration...");
            
            // Create security tables
            $this->createBlockedIPsTable();
            $this->createUserSessionsTable();
            $this->createActivityLogsTable();
            $this->createOTPAttemptsTable();
            $this->createSystemHealthTable();
            $this->createBackupHistoryTable();
            $this->createSecurityIncidentsTable();
            
            // Add indexes to existing tables if needed
            $this->addSecurityIndexes();
            
            $this->log("Database migration completed successfully!");
            return true;
            
        } catch (Exception $e) {
            $this->log("Migration failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create blocked_ips table
     */
    private function createBlockedIPsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `blocked_ips` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ip_address` varchar(45) NOT NULL,
            `reason` text,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ip_address` (`ip_address`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->exec($sql);
        $this->log("Created blocked_ips table");
    }
    
    /**
     * Create user_sessions table
     */
    private function createUserSessionsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `user_sessions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `session_id` varchar(128) NOT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `expires_at` timestamp NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_session_id` (`session_id`),
            KEY `idx_expires_at` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->exec($sql);
        $this->log("Created user_sessions table");
    }
    
    /**
     * Create activity_logs table
     */
    private function createActivityLogsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `activity_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `action` varchar(100) NOT NULL,
            `user_id` int(11) DEFAULT NULL,
            `details` text,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_action` (`action`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_ip_address` (`ip_address`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->exec($sql);
        $this->log("Created activity_logs table");
    }
    
    /**
     * Create otp_attempts table
     */
    private function createOTPAttemptsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `otp_attempts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `attempts` int(11) DEFAULT 1,
            `last_attempt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `is_blocked` tinyint(1) DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_ip_address` (`ip_address`),
            KEY `idx_last_attempt` (`last_attempt`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->exec($sql);
        $this->log("Created otp_attempts table");
    }
    
    /**
     * Create system_health table
     */
    private function createSystemHealthTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `system_health` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `check_type` varchar(50) NOT NULL,
            `status` enum('healthy','warning','critical') DEFAULT 'healthy',
            `message` text,
            `details` json DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_check_type` (`check_type`),
            KEY `idx_status` (`status`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->exec($sql);
        $this->log("Created system_health table");
    }
    
    /**
     * Create backup_history table
     */
    private function createBackupHistoryTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `backup_history` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `backup_id` varchar(100) NOT NULL,
            `backup_type` enum('full','incremental','emergency') NOT NULL,
            `status` enum('in_progress','completed','failed') DEFAULT 'in_progress',
            `file_size` bigint(20) DEFAULT NULL,
            `file_path` varchar(500) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `completed_at` timestamp NULL DEFAULT NULL,
            `error_message` text,
            PRIMARY KEY (`id`),
            UNIQUE KEY `backup_id` (`backup_id`),
            KEY `idx_backup_type` (`backup_type`),
            KEY `idx_status` (`status`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->exec($sql);
        $this->log("Created backup_history table");
    }
    
    /**
     * Create security_incidents table
     */
    private function createSecurityIncidentsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `security_incidents` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `incident_type` varchar(50) NOT NULL,
            `severity` enum('low','medium','high','critical') DEFAULT 'medium',
            `ip_address` varchar(45) DEFAULT NULL,
            `user_id` int(11) DEFAULT NULL,
            `description` text,
            `status` enum('open','investigating','resolved','closed') DEFAULT 'open',
            `resolution` text,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `resolved_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_incident_type` (`incident_type`),
            KEY `idx_severity` (`severity`),
            KEY `idx_status` (`status`),
            KEY `idx_ip_address` (`ip_address`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->exec($sql);
        $this->log("Created security_incidents table");
    }
    
    /**
     * Add security indexes to existing tables
     */
    private function addSecurityIndexes() {
        try {
            // Add indexes to users table for security
            $indexes = [
                "ALTER TABLE `users` ADD INDEX `idx_is_verified` (`is_verified`)",
                "ALTER TABLE `users` ADD INDEX `idx_created_at` (`created_at`)",
                "ALTER TABLE `users` ADD INDEX `idx_updated_at` (`updated_at`)"
            ];
            
            foreach ($indexes as $index) {
                try {
                    $this->conn->exec($index);
                } catch (Exception $e) {
                    // Index might already exist, continue
                }
            }
            
            // Add indexes to applications table
            $app_indexes = [
                "ALTER TABLE `applications` ADD INDEX `idx_status` (`status`)",
                "ALTER TABLE `applications` ADD INDEX `idx_user_id` (`user_id`)",
                "ALTER TABLE `applications` ADD INDEX `idx_created_at` (`created_at`)"
            ];
            
            foreach ($app_indexes as $index) {
                try {
                    $this->conn->exec($index);
                } catch (Exception $e) {
                    // Index might already exist, continue
                }
            }
            
            $this->log("Added security indexes to existing tables");
            
        } catch (Exception $e) {
            $this->log("Warning: Could not add some indexes: " . $e->getMessage());
        }
    }
    
    /**
     * Check if migration is needed
     */
    public function needsMigration() {
        try {
            // Check if blocked_ips table exists
            $stmt = $this->conn->query("SHOW TABLES LIKE 'blocked_ips'");
            return $stmt->rowCount() === 0;
        } catch (Exception $e) {
            return true; // Assume migration needed if we can't check
        }
    }
    
    /**
     * Log migration activity
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] MIGRATION: $message" . PHP_EOL;
        
        // Log to file
        $log_file = '../logs/migration.log';
        if (!is_dir('../logs/')) {
            mkdir('../logs/', 0755, true);
        }
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Also log to database if activity_logs table exists
        try {
            $stmt = $this->conn->prepare("INSERT INTO activity_logs (action, user_id, details, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute(['database_migration', 0, $message, $_SERVER['REMOTE_ADDR'] ?? 'system']);
        } catch (Exception $e) {
            // Ignore if activity_logs doesn't exist yet
        }
    }
}

// Auto-run migration if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) === 'database_migration.php') {
    try {
        $migration = new DatabaseMigration($conn);
        
        if ($migration->needsMigration()) {
            $migration->migrate();
            echo "Database migration completed successfully!";
        } else {
            echo "Database is already up to date.";
        }
    } catch (Exception $e) {
        echo "Migration failed: " . $e->getMessage();
        exit(1);
    }
}
?>
