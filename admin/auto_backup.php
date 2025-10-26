<?php
/**
 * PSAU Admission System - Automated Backup Script
 * Runs automated backups on schedule
 */

require_once '../includes/db_connect.php';
require_once '../includes/backup_system.php';

// Set execution time limit for long-running backup operations
set_time_limit(300); // 5 minutes

// Log backup execution
function logBackupExecution($message) {
    $log_file = '../logs/auto_backup.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

try {
    logBackupExecution("Starting automated backup process");
    
    $backup_system = new BackupSystem($conn);
    
    // Determine backup type based on time
    $hour = (int)date('H');
    $day = (int)date('j');
    
    // Full backup at 2 AM daily
    if ($hour === 2) {
        $backup_type = 'full';
        logBackupExecution("Scheduled full backup at 2 AM");
    }
    // Incremental backup every 6 hours
    elseif ($hour % 6 === 0) {
        $backup_type = 'incremental';
        logBackupExecution("Scheduled incremental backup at hour $hour");
    }
    // Emergency backup if system load is high
    else {
        $load = sys_getloadavg();
        if ($load[0] > 2.0) { // High system load
            $backup_type = 'emergency';
            logBackupExecution("High system load detected ($load[0]), creating emergency backup");
        } else {
            logBackupExecution("No backup needed at this time");
            exit(0);
        }
    }
    
    // Create backup
    $backup_info = $backup_system->createBackup($backup_type);
    
    logBackupExecution("Backup completed successfully: " . $backup_info['backup_id']);
    
    // Clean up old backups (keep last 30 days)
    $deleted_count = $backup_system->cleanupOldBackups(30);
    if ($deleted_count > 0) {
        logBackupExecution("Cleaned up $deleted_count old backup(s)");
    }
    
    // Check disk space
    $free_space = disk_free_space('../backups/');
    $free_space_gb = $free_space / (1024 * 1024 * 1024);
    
    if ($free_space_gb < 1) { // Less than 1GB free
        logBackupExecution("WARNING: Low disk space ($free_space_gb GB remaining)");
        
        // Send alert email if configured
        if (function_exists('mail') && !empty($_ENV['ADMIN_EMAIL'])) {
            $subject = "PSAU Admission System - Low Disk Space Alert";
            $message = "Backup system is running low on disk space. Only $free_space_gb GB remaining.";
            mail($_ENV['ADMIN_EMAIL'], $subject, $message);
        }
    }
    
    logBackupExecution("Automated backup process completed successfully");
    
} catch (Exception $e) {
    logBackupExecution("ERROR: " . $e->getMessage());
    
    // Send error alert if configured
    if (function_exists('mail') && !empty($_ENV['ADMIN_EMAIL'])) {
        $subject = "PSAU Admission System - Backup Error";
        $message = "Automated backup failed: " . $e->getMessage();
        mail($_ENV['ADMIN_EMAIL'], $subject, $message);
    }
    
    exit(1);
}
?>
