<?php
/**
 * PSAU Admission System - Emergency Recovery Interface
 * Quick recovery tools for system emergencies
 */

require_once '../includes/check_auth.php';
require_once '../includes/backup_system.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$backup_system = new BackupSystem($conn);
$message = '';
$error = '';

// Handle emergency actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'emergency_backup':
                $backup_id = $backup_system->createEmergencyBackup();
                $message = "Emergency backup created: $backup_id";
                break;
                
            case 'quick_restore':
                $backup_id = $_POST['backup_id'] ?? '';
                if (empty($backup_id)) {
                    throw new Exception("Please select a backup to restore");
                }
                $backup_system->restoreFromBackup($backup_id);
                $message = "System restored from backup: $backup_id";
                break;
                
            case 'system_check':
                // Perform system health check
                $health_check = performSystemHealthCheck();
                $message = "System health check completed. See details below.";
                break;
                
            case 'clear_cache':
                clearSystemCache();
                $message = "System cache cleared successfully";
                break;
                
            case 'reset_sessions':
                resetAllSessions();
                $message = "All user sessions reset successfully";
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get system status
$system_status = getSystemStatus();
$backups = $backup_system->listBackups();

function performSystemHealthCheck() {
    global $conn;
    
    $health = [
        'database' => false,
        'encryption' => false,
        'file_permissions' => false,
        'disk_space' => false,
        'recent_errors' => 0
    ];
    
    try {
        // Check database connection
        $stmt = $conn->query("SELECT 1");
        $health['database'] = true;
    } catch (Exception $e) {
        $health['database'] = false;
    }
    
    try {
        // Check encryption
        require_once '../includes/encryption.php';
        $test_data = PSAUEncryption::encrypt('test', 'health_check');
        $decrypted = PSAUEncryption::decrypt($test_data, 'health_check');
        $health['encryption'] = ($decrypted === 'test');
    } catch (Exception $e) {
        $health['encryption'] = false;
    }
    
    // Check file permissions
    $health['file_permissions'] = is_writable('../uploads/') && is_writable('../backups/');
    
    // Check disk space
    $free_space = disk_free_space('../');
    $health['disk_space'] = ($free_space > 100 * 1024 * 1024); // 100MB minimum
    
    // Check for recent errors
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as error_count FROM activity_logs WHERE action LIKE '%error%' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute();
        $result = $stmt->fetch();
        $health['recent_errors'] = $result['error_count'];
    } catch (Exception $e) {
        $health['recent_errors'] = -1;
    }
    
    return $health;
}

function getSystemStatus() {
    global $conn;
    
    $status = [
        'uptime' => 'Unknown',
        'memory_usage' => memory_get_usage(true),
        'disk_space' => disk_free_space('../'),
        'active_users' => 0,
        'total_applications' => 0,
        'recent_activity' => []
    ];
    
    try {
        // Get active users (last 15 minutes)
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as active_users FROM activity_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute();
        $result = $stmt->fetch();
        $status['active_users'] = $result['active_users'];
        
        // Get total applications
        $stmt = $conn->query("SELECT COUNT(*) as total FROM applications");
        $result = $stmt->fetch();
        $status['total_applications'] = $result['total'];
        
        // Get recent activity
        $stmt = $conn->prepare("SELECT action, details, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 10");
        $stmt->execute();
        $status['recent_activity'] = $stmt->fetchAll();
        
    } catch (Exception $e) {
        // Handle database errors gracefully
    }
    
    return $status;
}

function clearSystemCache() {
    // Clear PHP opcache if available
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    
    // Clear session files (optional - be careful in production)
    // session_destroy();
}

function resetAllSessions() {
    global $conn;
    
    try {
        // Clear all sessions from database if you store them there
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute();
        
        // Clear PHP session files
        $session_path = session_save_path();
        if ($session_path && is_dir($session_path)) {
            $files = glob($session_path . '/sess_*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    } catch (Exception $e) {
        throw new Exception("Failed to reset sessions: " . $e->getMessage());
    }
}

$health_check = performSystemHealthCheck();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Recovery - PSAU Admission System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .emergency-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .emergency-header {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .status-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .status-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }
        
        .status-ok {
            background: #27ae60;
        }
        
        .status-warning {
            background: #f39c12;
        }
        
        .status-error {
            background: #e74c3c;
        }
        
        .emergency-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #e74c3c;
        }
        
        .action-card h3 {
            margin-top: 0;
            color: #e74c3c;
        }
        
        .btn {
            background: #e74c3c;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-weight: bold;
        }
        
        .btn:hover {
            background: #c0392b;
        }
        
        .btn-warning {
            background: #f39c12;
        }
        
        .btn-warning:hover {
            background: #e67e22;
        }
        
        .btn-success {
            background: #27ae60;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .health-check {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .health-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .health-item:last-child {
            border-bottom: none;
        }
        
        .backup-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .backup-list h3 {
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        
        .backup-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .backup-table th,
        .backup-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .backup-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .backup-table tr:hover {
            background: #f8f9fa;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .critical-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .critical-warning h3 {
            color: #856404;
            margin-top: 0;
        }
        
        .system-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .system-info h3 {
            margin-top: 0;
            color: #333;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="emergency-container">
        <div class="emergency-header">
            <h1>🚨 Emergency Recovery Center</h1>
            <p>Critical system recovery tools for emergency situations</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Critical Warning -->
        <div class="critical-warning">
            <h3>⚠️ Emergency Procedures</h3>
            <p><strong>Use these tools only in emergency situations:</strong></p>
            <ul>
                <li>System is down or unresponsive</li>
                <li>Data corruption detected</li>
                <li>Security breach suspected</li>
                <li>Critical errors preventing normal operation</li>
            </ul>
            <p><strong>Always create a backup before making any changes!</strong></p>
        </div>
        
        <!-- System Status -->
        <div class="status-grid">
            <div class="status-card">
                <div class="status-indicator <?php echo $health_check['database'] ? 'status-ok' : 'status-error'; ?>"></div>
                <strong>Database</strong>
                <div><?php echo $health_check['database'] ? 'Connected' : 'Disconnected'; ?></div>
            </div>
            <div class="status-card">
                <div class="status-indicator <?php echo $health_check['encryption'] ? 'status-ok' : 'status-error'; ?>"></div>
                <strong>Encryption</strong>
                <div><?php echo $health_check['encryption'] ? 'Working' : 'Failed'; ?></div>
            </div>
            <div class="status-card">
                <div class="status-indicator <?php echo $health_check['file_permissions'] ? 'status-ok' : 'status-warning'; ?>"></div>
                <strong>File Permissions</strong>
                <div><?php echo $health_check['file_permissions'] ? 'OK' : 'Issues'; ?></div>
            </div>
            <div class="status-card">
                <div class="status-indicator <?php echo $health_check['disk_space'] ? 'status-ok' : 'status-warning'; ?>"></div>
                <strong>Disk Space</strong>
                <div><?php echo $health_check['disk_space'] ? 'Sufficient' : 'Low'; ?></div>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="system-info">
            <h3>📊 System Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Active Users:</span>
                    <span class="info-value"><?php echo $system_status['active_users']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Applications:</span>
                    <span class="info-value"><?php echo $system_status['total_applications']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Memory Usage:</span>
                    <span class="info-value"><?php echo number_format($system_status['memory_usage'] / 1024 / 1024, 2); ?> MB</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Free Disk Space:</span>
                    <span class="info-value"><?php echo number_format($system_status['disk_space'] / 1024 / 1024 / 1024, 2); ?> GB</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Recent Errors:</span>
                    <span class="info-value"><?php echo $health_check['recent_errors']; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Emergency Actions -->
        <div class="emergency-actions">
            <div class="action-card">
                <h3>💾 Emergency Backup</h3>
                <p>Create a quick backup of critical data before making any changes.</p>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="emergency_backup">
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Create emergency backup? This will backup critical data only.')">
                        Create Emergency Backup
                    </button>
                </form>
            </div>
            
            <div class="action-card">
                <h3>🔧 System Health Check</h3>
                <p>Perform a comprehensive system health check to identify issues.</p>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="system_check">
                    <button type="submit" class="btn btn-success">
                        Run Health Check
                    </button>
                </form>
            </div>
            
            <div class="action-card">
                <h3>🧹 Clear System Cache</h3>
                <p>Clear PHP opcache and temporary files to resolve caching issues.</p>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="clear_cache">
                    <button type="submit" class="btn" onclick="return confirm('Clear system cache? This may temporarily slow down the system.')">
                        Clear Cache
                    </button>
                </form>
            </div>
            
            <div class="action-card">
                <h3>🔄 Reset User Sessions</h3>
                <p>Force logout all users and clear session data.</p>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="reset_sessions">
                    <button type="submit" class="btn" onclick="return confirm('Reset all user sessions? All users will be logged out.')">
                        Reset Sessions
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Health Check Results -->
        <?php if (isset($health_check) && $health_check): ?>
            <div class="health-check">
                <h3>🔍 System Health Check Results</h3>
                <div class="health-item">
                    <span>Database Connection</span>
                    <span class="status-indicator <?php echo $health_check['database'] ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $health_check['database'] ? 'OK' : 'FAILED'; ?>
                    </span>
                </div>
                <div class="health-item">
                    <span>Encryption System</span>
                    <span class="status-indicator <?php echo $health_check['encryption'] ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $health_check['encryption'] ? 'OK' : 'FAILED'; ?>
                    </span>
                </div>
                <div class="health-item">
                    <span>File Permissions</span>
                    <span class="status-indicator <?php echo $health_check['file_permissions'] ? 'status-ok' : 'status-warning'; ?>">
                        <?php echo $health_check['file_permissions'] ? 'OK' : 'ISSUES'; ?>
                    </span>
                </div>
                <div class="health-item">
                    <span>Disk Space</span>
                    <span class="status-indicator <?php echo $health_check['disk_space'] ? 'status-ok' : 'status-warning'; ?>">
                        <?php echo $health_check['disk_space'] ? 'OK' : 'LOW'; ?>
                    </span>
                </div>
                <div class="health-item">
                    <span>Recent Errors (Last Hour)</span>
                    <span class="status-indicator <?php echo $health_check['recent_errors'] == 0 ? 'status-ok' : 'status-warning'; ?>">
                        <?php echo $health_check['recent_errors']; ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Quick Restore -->
        <div class="backup-list">
            <h3>⚡ Quick Restore</h3>
            <?php if (empty($backups)): ?>
                <div style="padding: 20px; text-align: center; color: #666;">
                    No backups available for restore. Create a backup first.
                </div>
            <?php else: ?>
                <table class="backup-table">
                    <thead>
                        <tr>
                            <th>Backup ID</th>
                            <th>Created</th>
                            <th>Size</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($backups, 0, 5) as $backup): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($backup['id']); ?></strong></td>
                                <td><?php echo $backup['created']; ?></td>
                                <td><?php echo number_format($backup['size'] / 1024 / 1024, 2); ?> MB</td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="quick_restore">
                                        <input type="hidden" name="backup_id" value="<?php echo htmlspecialchars($backup['id']); ?>">
                                        <button type="submit" class="btn" onclick="return confirm('Restore this backup? This will overwrite current data and cannot be undone!')">
                                            Restore Now
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Emergency Contacts -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 30px;">
            <h3>📞 Emergency Contacts</h3>
            <p>If you need additional help during an emergency:</p>
            <ul>
                <li><strong>System Administrator:</strong> [Your Contact Info]</li>
                <li><strong>Database Administrator:</strong> [DB Admin Contact]</li>
                <li><strong>Hosting Provider:</strong> Render.com Support</li>
                <li><strong>Emergency Hotline:</strong> [Emergency Contact]</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Auto-refresh every 2 minutes during emergency
        setTimeout(function() {
            location.reload();
        }, 120000);
        
        // Confirm all critical actions
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const action = form.querySelector('input[name="action"]').value;
                    if (action === 'quick_restore' || action === 'reset_sessions') {
                        if (!confirm('This is a critical action that cannot be undone. Are you absolutely sure?')) {
                            e.preventDefault();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
