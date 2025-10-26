<?php
/**
 * PSAU Admission System - Comprehensive Backup System
 * Handles database backups, file backups, and disaster recovery
 */

require_once 'db_connect.php';
require_once 'encryption.php';

class BackupSystem {
    private $conn;
    private $backup_path;
    private $encryption_key;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->backup_path = '../backups/';
        $this->encryption_key = getenv('ENCRYPTION_KEY');
        
        // Create backup directory if it doesn't exist
        if (!is_dir($this->backup_path)) {
            mkdir($this->backup_path, 0755, true);
        }
    }
    
    /**
     * Create a complete system backup
     * @param string $backup_type Type of backup (full, incremental, emergency)
     * @return array Backup information
     */
    public function createBackup($backup_type = 'full') {
        $timestamp = date('Y-m-d_H-i-s');
        $backup_id = $backup_type . '_' . $timestamp;
        $backup_dir = $this->backup_path . $backup_id . '/';
        
        // Create backup directory
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $backup_info = [
            'backup_id' => $backup_id,
            'timestamp' => $timestamp,
            'type' => $backup_type,
            'status' => 'in_progress',
            'files' => [],
            'database' => null,
            'encryption' => null
        ];
        
        try {
            // 1. Backup database
            $backup_info['database'] = $this->backupDatabase($backup_dir);
            
            // 2. Backup uploaded files
            $backup_info['files'] = $this->backupFiles($backup_dir);
            
            // 3. Backup encryption keys and configuration
            $backup_info['encryption'] = $this->backupEncryptionConfig($backup_dir);
            
            // 4. Create backup manifest
            $this->createBackupManifest($backup_dir, $backup_info);
            
            // 5. Compress backup
            $this->compressBackup($backup_dir);
            
            $backup_info['status'] = 'completed';
            $this->logBackupActivity('backup_created', $backup_id, 'Backup created successfully');
            
            return $backup_info;
            
        } catch (Exception $e) {
            $backup_info['status'] = 'failed';
            $backup_info['error'] = $e->getMessage();
            $this->logBackupActivity('backup_failed', $backup_id, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Backup database with encryption
     */
    private function backupDatabase($backup_dir) {
        $db_backup_file = $backup_dir . 'database_backup.sql';
        
        // Get database credentials
        $host = $_ENV['DB_HOST'] ?? 'shuttle.proxy.rlwy.net';
        $dbname = $_ENV['DB_NAME'] ?? 'railway';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? 'JCfNOSYEIrgNDqxwzaHBEufEJDPLQkKU';
        $port = $_ENV['DB_PORT'] ?? 40148;
        
        // Create mysqldump command
        $command = "mysqldump -h $host -P $port -u $username -p$password $dbname > $db_backup_file 2>&1";
        
        // Execute backup
        $output = [];
        $return_code = 0;
        exec($command, $output, $return_code);
        
        if ($return_code !== 0) {
            throw new Exception("Database backup failed: " . implode("\n", $output));
        }
        
        // Encrypt the database backup
        $db_content = file_get_contents($db_backup_file);
        $encrypted_content = PSAUEncryption::encrypt($db_content, 'database_backup');
        file_put_contents($db_backup_file . '.encrypted', $encrypted_content);
        
        // Remove unencrypted file
        unlink($db_backup_file);
        
        return [
            'file' => 'database_backup.sql.encrypted',
            'size' => filesize($db_backup_file . '.encrypted'),
            'encrypted' => true
        ];
    }
    
    /**
     * Backup uploaded files
     */
    private function backupFiles($backup_dir) {
        $files_backup_dir = $backup_dir . 'files/';
        mkdir($files_backup_dir, 0755, true);
        
        $files_info = [];
        
        // Backup uploads directory
        if (is_dir('../uploads/')) {
            $this->copyDirectory('../uploads/', $files_backup_dir . 'uploads/');
            $files_info['uploads'] = $this->getDirectorySize('../uploads/');
        }
        
        // Backup images directory
        if (is_dir('../images/')) {
            $this->copyDirectory('../images/', $files_backup_dir . 'images/');
            $files_info['images'] = $this->getDirectorySize('../images/');
        }
        
        // Backup logs directory
        if (is_dir('../logs/')) {
            $this->copyDirectory('../logs/', $files_backup_dir . 'logs/');
            $files_info['logs'] = $this->getDirectorySize('../logs/');
        }
        
        return $files_info;
    }
    
    /**
     * Backup encryption configuration
     */
    private function backupEncryptionConfig($backup_dir) {
        $config_file = $backup_dir . 'encryption_config.json';
        
        $config = [
            'encryption_key_hash' => hash('sha256', $this->encryption_key),
            'algorithm' => 'AES-256-GCM',
            'backup_timestamp' => date('Y-m-d H:i:s'),
            'environment' => $_ENV['ENVIRONMENT'] ?? 'production',
            'database_info' => [
                'host' => $_ENV['DB_HOST'] ?? 'shuttle.proxy.rlwy.net',
                'dbname' => $_ENV['DB_NAME'] ?? 'railway',
                'port' => $_ENV['DB_PORT'] ?? 40148
            ]
        ];
        
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        
        return [
            'file' => 'encryption_config.json',
            'size' => filesize($config_file)
        ];
    }
    
    /**
     * Create backup manifest
     */
    private function createBackupManifest($backup_dir, $backup_info) {
        $manifest_file = $backup_dir . 'backup_manifest.json';
        
        $manifest = [
            'backup_id' => $backup_info['backup_id'],
            'created_at' => date('Y-m-d H:i:s'),
            'type' => $backup_info['type'],
            'status' => $backup_info['status'],
            'database' => $backup_info['database'],
            'files' => $backup_info['files'],
            'encryption' => $backup_info['encryption'],
            'total_size' => $this->getDirectorySize($backup_dir),
            'version' => '1.0',
            'system_info' => [
                'php_version' => PHP_VERSION,
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'os' => PHP_OS
            ]
        ];
        
        file_put_contents($manifest_file, json_encode($manifest, JSON_PRETTY_PRINT));
    }
    
    /**
     * Compress backup directory
     */
    private function compressBackup($backup_dir) {
        $parent_dir = dirname($backup_dir);
        $backup_name = basename($backup_dir);
        
        $command = "cd $parent_dir && tar -czf $backup_name.tar.gz $backup_name/ 2>&1";
        $output = [];
        $return_code = 0;
        exec($command, $output, $return_code);
        
        if ($return_code === 0) {
            // Remove uncompressed directory
            $this->removeDirectory($backup_dir);
        }
    }
    
    /**
     * Restore from backup
     */
    public function restoreFromBackup($backup_id) {
        $backup_file = $this->backup_path . $backup_id . '.tar.gz';
        
        if (!file_exists($backup_file)) {
            throw new Exception("Backup file not found: $backup_file");
        }
        
        // Extract backup
        $extract_dir = $this->backup_path . 'restore_' . date('Y-m-d_H-i-s') . '/';
        mkdir($extract_dir, 0755, true);
        
        $command = "cd $extract_dir && tar -xzf $backup_file 2>&1";
        $output = [];
        $return_code = 0;
        exec($command, $output, $return_code);
        
        if ($return_code !== 0) {
            throw new Exception("Failed to extract backup: " . implode("\n", $output));
        }
        
        // Read manifest
        $manifest_file = $extract_dir . $backup_id . '/backup_manifest.json';
        if (!file_exists($manifest_file)) {
            throw new Exception("Backup manifest not found");
        }
        
        $manifest = json_decode(file_get_contents($manifest_file), true);
        
        // Restore database
        $this->restoreDatabase($extract_dir . $backup_id . '/');
        
        // Restore files
        $this->restoreFiles($extract_dir . $backup_id . '/');
        
        // Clean up
        $this->removeDirectory($extract_dir);
        
        $this->logBackupActivity('backup_restored', $backup_id, 'Backup restored successfully');
        
        return $manifest;
    }
    
    /**
     * Restore database from backup
     */
    private function restoreDatabase($backup_dir) {
        $db_backup_file = $backup_dir . 'database_backup.sql.encrypted';
        
        if (!file_exists($db_backup_file)) {
            throw new Exception("Database backup file not found");
        }
        
        // Decrypt database backup
        $encrypted_content = file_get_contents($db_backup_file);
        $decrypted_content = PSAUEncryption::decrypt($encrypted_content, 'database_backup');
        
        // Get database credentials
        $host = $_ENV['DB_HOST'] ?? 'shuttle.proxy.rlwy.net';
        $dbname = $_ENV['DB_NAME'] ?? 'railway';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? 'JCfNOSYEIrgNDqxwzaHBEufEJDPLQkKU';
        $port = $_ENV['DB_PORT'] ?? 40148;
        
        // Restore database
        $temp_file = tempnam(sys_get_temp_dir(), 'restore_');
        file_put_contents($temp_file, $decrypted_content);
        
        $command = "mysql -h $host -P $port -u $username -p$password $dbname < $temp_file 2>&1";
        $output = [];
        $return_code = 0;
        exec($command, $output, $return_code);
        
        unlink($temp_file);
        
        if ($return_code !== 0) {
            throw new Exception("Database restore failed: " . implode("\n", $output));
        }
    }
    
    /**
     * Restore files from backup
     */
    private function restoreFiles($backup_dir) {
        $files_dir = $backup_dir . 'files/';
        
        if (is_dir($files_dir . 'uploads/')) {
            $this->copyDirectory($files_dir . 'uploads/', '../uploads/');
        }
        
        if (is_dir($files_dir . 'images/')) {
            $this->copyDirectory($files_dir . 'images/', '../images/');
        }
        
        if (is_dir($files_dir . 'logs/')) {
            $this->copyDirectory($files_dir . 'logs/', '../logs/');
        }
    }
    
    /**
     * List available backups
     */
    public function listBackups() {
        $backups = [];
        $files = glob($this->backup_path . '*.tar.gz');
        
        foreach ($files as $file) {
            $backup_name = basename($file, '.tar.gz');
            $backups[] = [
                'id' => $backup_name,
                'file' => $file,
                'size' => filesize($file),
                'created' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        // Sort by creation date (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['created']) - strtotime($a['created']);
        });
        
        return $backups;
    }
    
    /**
     * Clean up old backups
     */
    public function cleanupOldBackups($retention_days = 30) {
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        $backups = $this->listBackups();
        $deleted = 0;
        
        foreach ($backups as $backup) {
            if ($backup['created'] < $cutoff_date) {
                if (unlink($backup['file'])) {
                    $deleted++;
                    $this->logBackupActivity('backup_deleted', $backup['id'], 'Old backup cleaned up');
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Emergency backup (minimal, fast)
     */
    public function createEmergencyBackup() {
        $timestamp = date('Y-m-d_H-i-s');
        $backup_id = 'emergency_' . $timestamp;
        $backup_dir = $this->backup_path . $backup_id . '/';
        
        mkdir($backup_dir, 0755, true);
        
        try {
            // Only backup critical data
            $this->backupDatabase($backup_dir);
            $this->backupEncryptionConfig($backup_dir);
            
            // Create minimal manifest
            $manifest = [
                'backup_id' => $backup_id,
                'type' => 'emergency',
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'completed',
                'note' => 'Emergency backup - minimal data only'
            ];
            
            file_put_contents($backup_dir . 'backup_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
            
            // Compress
            $this->compressBackup($backup_dir);
            
            $this->logBackupActivity('emergency_backup_created', $backup_id, 'Emergency backup created');
            
            return $backup_id;
            
        } catch (Exception $e) {
            $this->logBackupActivity('emergency_backup_failed', $backup_id, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Helper methods
     */
    private function copyDirectory($src, $dst) {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $target = $dst . $iterator->getSubPathName();
            if ($item->isDir()) {
                mkdir($target, 0755, true);
            } else {
                copy($item, $target);
            }
        }
    }
    
    private function removeDirectory($dir) {
        if (!is_dir($dir)) return;
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item);
            } else {
                unlink($item);
            }
        }
        
        rmdir($dir);
    }
    
    private function getDirectorySize($dir) {
        $size = 0;
        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                $size += $file->getSize();
            }
        }
        return $size;
    }
    
    private function logBackupActivity($action, $backup_id, $details) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO activity_logs (action, user_id, details, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$action, 0, "Backup: $backup_id - $details", $_SERVER['REMOTE_ADDR'] ?? 'system']);
        } catch (Exception $e) {
            error_log("Failed to log backup activity: " . $e->getMessage());
        }
    }
}
?>
