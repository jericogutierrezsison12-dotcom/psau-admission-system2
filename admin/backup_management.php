<?php
/**
 * PSAU Admission System - Backup Management Interface
 * Admin interface for managing backups and disaster recovery
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

// Handle backup actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create_backup':
                $backup_type = $_POST['backup_type'] ?? 'full';
                $backup_info = $backup_system->createBackup($backup_type);
                $message = "Backup created successfully: " . $backup_info['backup_id'];
                break;
                
            case 'create_emergency_backup':
                $backup_id = $backup_system->createEmergencyBackup();
                $message = "Emergency backup created: $backup_id";
                break;
                
            case 'restore_backup':
                $backup_id = $_POST['backup_id'] ?? '';
                if (empty($backup_id)) {
                    throw new Exception("Please select a backup to restore");
                }
                $backup_system->restoreFromBackup($backup_id);
                $message = "Backup restored successfully: $backup_id";
                break;
                
            case 'delete_backup':
                $backup_id = $_POST['backup_id'] ?? '';
                if (empty($backup_id)) {
                    throw new Exception("Please select a backup to delete");
                }
                $backup_file = '../backups/' . $backup_id . '.tar.gz';
                if (file_exists($backup_file)) {
                    unlink($backup_file);
                    $message = "Backup deleted successfully: $backup_id";
                } else {
                    throw new Exception("Backup file not found");
                }
                break;
                
            case 'cleanup_old':
                $retention_days = $_POST['retention_days'] ?? 30;
                $deleted = $backup_system->cleanupOldBackups($retention_days);
                $message = "Cleaned up $deleted old backup(s)";
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get list of backups
$backups = $backup_system->listBackups();

// Get system status
$system_status = [
    'total_backups' => count($backups),
    'total_size' => array_sum(array_column($backups, 'size')),
    'latest_backup' => !empty($backups) ? $backups[0]['created'] : 'Never',
    'backup_directory' => '../backups/',
    'disk_space' => disk_free_space('../backups/')
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Management - PSAU Admission System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .backup-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .backup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .backup-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        
        .backup-actions {
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
        }
        
        .action-card h3 {
            margin-top: 0;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-warning {
            background: #f39c12;
        }
        
        .btn-warning:hover {
            background: #e67e22;
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
        
        .backup-actions-cell {
            display: flex;
            gap: 10px;
        }
        
        .backup-actions-cell form {
            display: inline;
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
        
        .emergency-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .emergency-section h3 {
            color: #856404;
            margin-top: 0;
        }
        
        .file-size {
            color: #666;
            font-size: 0.9em;
        }
        
        .backup-type {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .backup-type.full {
            background: #d4edda;
            color: #155724;
        }
        
        .backup-type.incremental {
            background: #cce5ff;
            color: #004085;
        }
        
        .backup-type.emergency {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="backup-container">
        <div class="backup-header">
            <h1>🛡️ Backup Management System</h1>
            <p>Protect your data with automated backups and disaster recovery</p>
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
        
        <!-- System Status -->
        <div class="backup-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $system_status['total_backups']; ?></div>
                <div>Total Backups</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($system_status['total_size'] / 1024 / 1024, 2); ?> MB</div>
                <div>Total Size</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $system_status['latest_backup']; ?></div>
                <div>Latest Backup</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($system_status['disk_space'] / 1024 / 1024 / 1024, 2); ?> GB</div>
                <div>Free Space</div>
            </div>
        </div>
        
        <!-- Emergency Backup Section -->
        <div class="emergency-section">
            <h3>🚨 Emergency Backup</h3>
            <p>Create a quick backup in case of system issues or before major changes.</p>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="create_emergency_backup">
                <button type="submit" class="btn btn-warning" onclick="return confirm('Create emergency backup? This will backup critical data only.')">
                    Create Emergency Backup
                </button>
            </form>
        </div>
        
        <!-- Backup Actions -->
        <div class="backup-actions">
            <div class="action-card">
                <h3>📦 Create New Backup</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create_backup">
                    <div class="form-group">
                        <label for="backup_type">Backup Type:</label>
                        <select name="backup_type" id="backup_type" required>
                            <option value="full">Full Backup (Database + Files)</option>
                            <option value="incremental">Incremental (Changes Only)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn" onclick="return confirm('Create backup? This may take several minutes.')">
                        Create Backup
                    </button>
                </form>
            </div>
            
            <div class="action-card">
                <h3>🧹 Cleanup Old Backups</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="cleanup_old">
                    <div class="form-group">
                        <label for="retention_days">Keep Backups For (Days):</label>
                        <input type="number" name="retention_days" id="retention_days" value="30" min="1" max="365" required>
                    </div>
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Delete old backups? This action cannot be undone.')">
                        Cleanup Old Backups
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Backup List -->
        <div class="backup-list">
            <h3>📋 Available Backups</h3>
            <?php if (empty($backups)): ?>
                <div style="padding: 20px; text-align: center; color: #666;">
                    No backups found. Create your first backup to get started.
                </div>
            <?php else: ?>
                <table class="backup-table">
                    <thead>
                        <tr>
                            <th>Backup ID</th>
                            <th>Type</th>
                            <th>Created</th>
                            <th>Size</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($backup['id']); ?></strong>
                                </td>
                                <td>
                                    <span class="backup-type <?php echo explode('_', $backup['id'])[0]; ?>">
                                        <?php echo ucfirst(explode('_', $backup['id'])[0]); ?>
                                    </span>
                                </td>
                                <td><?php echo $backup['created']; ?></td>
                                <td class="file-size"><?php echo number_format($backup['size'] / 1024 / 1024, 2); ?> MB</td>
                                <td class="backup-actions-cell">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="restore_backup">
                                        <input type="hidden" name="backup_id" value="<?php echo htmlspecialchars($backup['id']); ?>">
                                        <button type="submit" class="btn" onclick="return confirm('Restore this backup? This will overwrite current data.')">
                                            Restore
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_backup">
                                        <input type="hidden" name="backup_id" value="<?php echo htmlspecialchars($backup['id']); ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this backup? This action cannot be undone.')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Security Information -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 30px;">
            <h3>🔒 Security Features</h3>
            <ul>
                <li><strong>Encrypted Backups:</strong> All backups are encrypted using AES-256-GCM</li>
                <li><strong>Secure Storage:</strong> Backup files are stored with restricted permissions</li>
                <li><strong>Access Control:</strong> Only administrators can manage backups</li>
                <li><strong>Audit Trail:</strong> All backup activities are logged</li>
                <li><strong>Data Integrity:</strong> Backup verification ensures data integrity</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Auto-refresh page every 5 minutes to show latest backups
        setTimeout(function() {
            location.reload();
        }, 300000);
        
        // Confirm critical actions
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const action = form.querySelector('input[name="action"]').value;
                    if (action === 'restore_backup' || action === 'delete_backup') {
                        if (!confirm('This action cannot be undone. Are you sure?')) {
                            e.preventDefault();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
