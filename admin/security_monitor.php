<?php
/**
 * PSAU Admission System - Security Monitoring Dashboard
 * Monitor system security, detect threats, and manage incidents
 */

require_once '../includes/check_auth.php';
require_once '../includes/backup_system.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Handle security actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'block_ip':
                $ip = $_POST['ip_address'] ?? '';
                if (empty($ip)) {
                    throw new Exception("Please provide an IP address to block");
                }
                blockIPAddress($ip);
                $message = "IP address $ip has been blocked";
                break;
                
            case 'unblock_ip':
                $ip = $_POST['ip_address'] ?? '';
                if (empty($ip)) {
                    throw new Exception("Please provide an IP address to unblock");
                }
                unblockIPAddress($ip);
                $message = "IP address $ip has been unblocked";
                break;
                
            case 'reset_failed_attempts':
                $user_id = $_POST['user_id'] ?? '';
                if (empty($user_id)) {
                    throw new Exception("Please provide a user ID");
                }
                resetFailedAttempts($user_id);
                $message = "Failed attempts reset for user $user_id";
                break;
                
            case 'force_logout':
                $user_id = $_POST['user_id'] ?? '';
                if (empty($user_id)) {
                    throw new Exception("Please provide a user ID");
                }
                forceUserLogout($user_id);
                $message = "User $user_id has been logged out";
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get security data
$security_stats = getSecurityStats();
$threats = getRecentThreats();
$blocked_ips = getBlockedIPs();
$failed_logins = getFailedLogins();
$suspicious_activity = getSuspiciousActivity();

function getSecurityStats() {
    global $conn;
    
    $stats = [
        'total_logins' => 0,
        'failed_logins' => 0,
        'blocked_ips' => 0,
        'suspicious_activities' => 0,
        'last_24h_attacks' => 0,
        'encryption_status' => false
    ];
    
    try {
        // Total logins (last 24 hours)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM activity_logs WHERE action = 'login' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['total_logins'] = $result['count'];
        
        // Failed logins (last 24 hours)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM activity_logs WHERE action = 'login_failed' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['failed_logins'] = $result['count'];
        
        // Blocked IPs
        $stmt = $conn->query("SELECT COUNT(*) as count FROM blocked_ips WHERE is_active = 1");
        $result = $stmt->fetch();
        $stats['blocked_ips'] = $result['count'];
        
        // Suspicious activities (last 24 hours)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM activity_logs WHERE action IN ('suspicious_activity', 'multiple_failed_logins', 'unusual_access_pattern') AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['suspicious_activities'] = $result['count'];
        
        // Check encryption status
        try {
            require_once '../includes/encryption.php';
            $test_data = PSAUEncryption::encrypt('test', 'security_check');
            $decrypted = PSAUEncryption::decrypt($test_data, 'security_check');
            $stats['encryption_status'] = ($decrypted === 'test');
        } catch (Exception $e) {
            $stats['encryption_status'] = false;
        }
        
    } catch (Exception $e) {
        // Handle database errors gracefully
    }
    
    return $stats;
}

function getRecentThreats() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT action, details, ip_address, created_at, user_id 
            FROM activity_logs 
            WHERE action IN ('suspicious_activity', 'multiple_failed_logins', 'unusual_access_pattern', 'blocked_ip_attempt')
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function getBlockedIPs() {
    global $conn;
    
    try {
        $stmt = $conn->query("SELECT * FROM blocked_ips WHERE is_active = 1 ORDER BY created_at DESC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function getFailedLogins() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT ip_address, COUNT(*) as attempt_count, MAX(created_at) as last_attempt
            FROM activity_logs 
            WHERE action = 'login_failed' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY ip_address 
            HAVING attempt_count >= 3
            ORDER BY attempt_count DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function getSuspiciousActivity() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT action, details, ip_address, created_at, user_id
            FROM activity_logs 
            WHERE action IN ('unusual_access_pattern', 'suspicious_activity')
            AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY created_at DESC
            LIMIT 15
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function blockIPAddress($ip) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO blocked_ips (ip_address, reason, is_active, created_at) VALUES (?, 'Manual block by admin', 1, NOW()) ON DUPLICATE KEY UPDATE is_active = 1, created_at = NOW()");
        $stmt->execute([$ip]);
        
        // Log the action
        $stmt = $conn->prepare("INSERT INTO activity_logs (action, user_id, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute(['ip_blocked', $_SESSION['admin_id'], "IP $ip blocked by admin", $_SERVER['REMOTE_ADDR'] ?? 'system']);
        
    } catch (Exception $e) {
        throw new Exception("Failed to block IP: " . $e->getMessage());
    }
}

function unblockIPAddress($ip) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("UPDATE blocked_ips SET is_active = 0 WHERE ip_address = ?");
        $stmt->execute([$ip]);
        
        // Log the action
        $stmt = $conn->prepare("INSERT INTO activity_logs (action, user_id, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute(['ip_unblocked', $_SESSION['admin_id'], "IP $ip unblocked by admin", $_SERVER['REMOTE_ADDR'] ?? 'system']);
        
    } catch (Exception $e) {
        throw new Exception("Failed to unblock IP: " . $e->getMessage());
    }
}

function resetFailedAttempts($user_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("DELETE FROM otp_attempts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Log the action
        $stmt = $conn->prepare("INSERT INTO activity_logs (action, user_id, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute(['failed_attempts_reset', $_SESSION['admin_id'], "Failed attempts reset for user $user_id", $_SERVER['REMOTE_ADDR'] ?? 'system']);
        
    } catch (Exception $e) {
        throw new Exception("Failed to reset attempts: " . $e->getMessage());
    }
}

function forceUserLogout($user_id) {
    global $conn;
    
    try {
        // Clear user sessions
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Log the action
        $stmt = $conn->prepare("INSERT INTO activity_logs (action, user_id, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute(['force_logout', $_SESSION['admin_id'], "User $user_id forced logout by admin", $_SERVER['REMOTE_ADDR'] ?? 'system']);
        
    } catch (Exception $e) {
        throw new Exception("Failed to force logout: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Monitor - PSAU Admission System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .security-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .security-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .security-stats {
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
            border-left: 4px solid #3498db;
        }
        
        .stat-card.danger {
            border-left-color: #e74c3c;
        }
        
        .stat-card.warning {
            border-left-color: #f39c12;
        }
        
        .stat-card.success {
            border-left-color: #27ae60;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-number.danger {
            color: #e74c3c;
        }
        
        .stat-number.warning {
            color: #f39c12;
        }
        
        .stat-number.success {
            color: #27ae60;
        }
        
        .security-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .security-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .section-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .section-header h3 {
            margin: 0;
            color: #333;
        }
        
        .section-content {
            padding: 20px;
        }
        
        .threat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .threat-item:last-child {
            border-bottom: none;
        }
        
        .threat-info {
            flex: 1;
        }
        
        .threat-type {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .threat-type.suspicious {
            background: #f8d7da;
            color: #721c24;
        }
        
        .threat-type.failed-login {
            background: #fff3cd;
            color: #856404;
        }
        
        .threat-type.unusual {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .threat-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #e67e22;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .btn-info {
            background: #3498db;
            color: white;
        }
        
        .btn-info:hover {
            background: #2980b9;
        }
        
        .ip-address {
            font-family: monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .timestamp {
            color: #666;
            font-size: 0.9em;
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
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .status-active {
            background: #27ae60;
        }
        
        .status-inactive {
            background: #95a5a6;
        }
        
        .no-data {
            text-align: center;
            color: #666;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="security-container">
        <div class="security-header">
            <h1>🛡️ Security Monitoring Dashboard</h1>
            <p>Monitor threats, manage security incidents, and protect the system</p>
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
        
        <!-- Security Statistics -->
        <div class="security-stats">
            <div class="stat-card <?php echo $security_stats['failed_logins'] > 10 ? 'danger' : ($security_stats['failed_logins'] > 5 ? 'warning' : 'success'); ?>">
                <div class="stat-number <?php echo $security_stats['failed_logins'] > 10 ? 'danger' : ($security_stats['failed_logins'] > 5 ? 'warning' : 'success'); ?>">
                    <?php echo $security_stats['failed_logins']; ?>
                </div>
                <div>Failed Logins (24h)</div>
            </div>
            
            <div class="stat-card <?php echo $security_stats['suspicious_activities'] > 5 ? 'danger' : ($security_stats['suspicious_activities'] > 2 ? 'warning' : 'success'); ?>">
                <div class="stat-number <?php echo $security_stats['suspicious_activities'] > 5 ? 'danger' : ($security_stats['suspicious_activities'] > 2 ? 'warning' : 'success'); ?>">
                    <?php echo $security_stats['suspicious_activities']; ?>
                </div>
                <div>Suspicious Activities (24h)</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $security_stats['blocked_ips']; ?></div>
                <div>Blocked IPs</div>
            </div>
            
            <div class="stat-card <?php echo $security_stats['encryption_status'] ? 'success' : 'danger'; ?>">
                <div class="stat-number <?php echo $security_stats['encryption_status'] ? 'success' : 'danger'; ?>">
                    <?php echo $security_stats['encryption_status'] ? '✓' : '✗'; ?>
                </div>
                <div>Encryption Status</div>
            </div>
        </div>
        
        <!-- Security Sections -->
        <div class="security-sections">
            <!-- Recent Threats -->
            <div class="security-section">
                <div class="section-header">
                    <h3>🚨 Recent Threats</h3>
                </div>
                <div class="section-content">
                    <?php if (empty($threats)): ?>
                        <div class="no-data">No recent threats detected</div>
                    <?php else: ?>
                        <?php foreach ($threats as $threat): ?>
                            <div class="threat-item">
                                <div class="threat-info">
                                    <span class="threat-type <?php echo str_replace('_', '-', $threat['action']); ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $threat['action'])); ?>
                                    </span>
                                    <span class="ip-address"><?php echo htmlspecialchars($threat['ip_address']); ?></span>
                                    <div class="timestamp"><?php echo $threat['created_at']; ?></div>
                                    <div style="font-size: 0.9em; color: #666; margin-top: 5px;">
                                        <?php echo htmlspecialchars($threat['details']); ?>
                                    </div>
                                </div>
                                <div class="threat-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="block_ip">
                                        <input type="hidden" name="ip_address" value="<?php echo htmlspecialchars($threat['ip_address']); ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Block this IP address?')">
                                            Block IP
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Failed Logins -->
            <div class="security-section">
                <div class="section-header">
                    <h3>🔒 Failed Login Attempts</h3>
                </div>
                <div class="section-content">
                    <?php if (empty($failed_logins)): ?>
                        <div class="no-data">No suspicious login attempts</div>
                    <?php else: ?>
                        <?php foreach ($failed_logins as $attempt): ?>
                            <div class="threat-item">
                                <div class="threat-info">
                                    <span class="ip-address"><?php echo htmlspecialchars($attempt['ip_address']); ?></span>
                                    <div style="color: #e74c3c; font-weight: bold;">
                                        <?php echo $attempt['attempt_count']; ?> attempts
                                    </div>
                                    <div class="timestamp">Last: <?php echo $attempt['last_attempt']; ?></div>
                                </div>
                                <div class="threat-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="block_ip">
                                        <input type="hidden" name="ip_address" value="<?php echo htmlspecialchars($attempt['ip_address']); ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Block this IP address?')">
                                            Block IP
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Blocked IPs Management -->
        <div class="security-section">
            <div class="section-header">
                <h3>🚫 Blocked IP Addresses</h3>
            </div>
            <div class="section-content">
                <?php if (empty($blocked_ips)): ?>
                    <div class="no-data">No IPs currently blocked</div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Reason</th>
                                <th>Blocked Since</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blocked_ips as $blocked): ?>
                                <tr>
                                    <td><span class="ip-address"><?php echo htmlspecialchars($blocked['ip_address']); ?></span></td>
                                    <td><?php echo htmlspecialchars($blocked['reason']); ?></td>
                                    <td><?php echo $blocked['created_at']; ?></td>
                                    <td>
                                        <span class="status-indicator <?php echo $blocked['is_active'] ? 'status-active' : 'status-inactive'; ?>"></span>
                                        <?php echo $blocked['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </td>
                                    <td>
                                        <?php if ($blocked['is_active']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="unblock_ip">
                                                <input type="hidden" name="ip_address" value="<?php echo htmlspecialchars($blocked['ip_address']); ?>">
                                                <button type="submit" class="btn btn-success" onclick="return confirm('Unblock this IP address?')">
                                                    Unblock
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Manual IP Blocking -->
        <div class="security-section">
            <div class="section-header">
                <h3>🔧 Manual IP Management</h3>
            </div>
            <div class="section-content">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h4>Block IP Address</h4>
                        <form method="POST">
                            <input type="hidden" name="action" value="block_ip">
                            <div class="form-group">
                                <label for="block_ip">IP Address:</label>
                                <input type="text" name="ip_address" id="block_ip" placeholder="192.168.1.100" required>
                            </div>
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Block this IP address?')">
                                Block IP
                            </button>
                        </form>
                    </div>
                    
                    <div>
                        <h4>Unblock IP Address</h4>
                        <form method="POST">
                            <input type="hidden" name="action" value="unblock_ip">
                            <div class="form-group">
                                <label for="unblock_ip">IP Address:</label>
                                <input type="text" name="ip_address" id="unblock_ip" placeholder="192.168.1.100" required>
                            </div>
                            <button type="submit" class="btn btn-success" onclick="return confirm('Unblock this IP address?')">
                                Unblock IP
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security Recommendations -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 30px;">
            <h3>💡 Security Recommendations</h3>
            <ul>
                <li><strong>Regular Backups:</strong> Ensure automated backups are running daily</li>
                <li><strong>Monitor Failed Logins:</strong> Block IPs with excessive failed attempts</li>
                <li><strong>Update Encryption Keys:</strong> Rotate encryption keys regularly</li>
                <li><strong>Review Activity Logs:</strong> Check for suspicious patterns regularly</li>
                <li><strong>Strong Passwords:</strong> Enforce strong password policies</li>
                <li><strong>Two-Factor Authentication:</strong> Consider implementing 2FA for admins</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Auto-refresh every 30 seconds for real-time monitoring
        setTimeout(function() {
            location.reload();
        }, 30000);
        
        // Confirm all critical actions
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const action = form.querySelector('input[name="action"]').value;
                    if (action === 'block_ip' || action === 'unblock_ip') {
                        if (!confirm('This action will affect system security. Are you sure?')) {
                            e.preventDefault();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
