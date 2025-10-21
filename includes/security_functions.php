<?php
/**
 * Security Functions for PSAU Admission System
 * Handles device blocking and login security
 */

require_once 'db_connect.php';

/**
 * Track login attempt and check if device is blocked
 * 
 * @param string $device_id Device identifier (fingerprint or IP)
 * @param bool $success Whether login was successful
 * @return array Status array with block information
 */
function track_login_attempt($device_id, $success = false) {
    global $conn;
    $current_time = time();
    $block_duration = 3 * 60 * 60; // 3 hours in seconds
    $monitoring_period = 60 * 60; // 1 hour in seconds
    $max_attempts = 5; // Maximum allowed attempts within monitoring period
    
    try {
        // Check if device is currently blocked
        $stmt = $conn->prepare("SELECT * FROM login_attempts 
                               WHERE device_id = ? 
                               AND is_blocked = 1 
                               AND block_expires > ?");
        $stmt->execute([$device_id, date('Y-m-d H:i:s', $current_time)]);
        
        if ($stmt->rowCount() > 0) {
            $block_info = $stmt->fetch(PDO::FETCH_ASSOC);
            $time_left = strtotime($block_info['block_expires']) - $current_time;
            
            return [
                'blocked' => true, 
                'expires' => $block_info['block_expires'],
                'minutes_left' => ceil($time_left / 60)
            ];
        }
        
        // Record this attempt
        $stmt = $conn->prepare("INSERT INTO login_attempts 
                               (device_id, attempt_time, is_success, ip_address, user_agent) 
                               VALUES (?, NOW(), ?, ?, ?)");
        $stmt->execute([
            $device_id, 
            $success ? 1 : 0, 
            $_SERVER['REMOTE_ADDR'] ?? 'unknown', 
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // If successful login, clear previous failed attempts for this device
        if ($success) {
            $stmt = $conn->prepare("UPDATE login_attempts 
                                   SET is_blocked = 0 
                                   WHERE device_id = ?");
            $stmt->execute([$device_id]);
            
            return ['blocked' => false];
        }
        
        // Count failed attempts within the monitoring period
        $check_time = date('Y-m-d H:i:s', $current_time - $monitoring_period);
        $stmt = $conn->prepare("SELECT COUNT(*) as attempt_count 
                               FROM login_attempts 
                               WHERE device_id = ? 
                               AND is_success = 0 
                               AND attempt_time > ?");
        $stmt->execute([$device_id, $check_time]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If exceeded max attempts, block the device
        if ($result['attempt_count'] >= $max_attempts) {
            $block_expires = date('Y-m-d H:i:s', $current_time + $block_duration);
            
            // Update all recent attempts to blocked status
            $stmt = $conn->prepare("UPDATE login_attempts 
                                   SET is_blocked = 1, block_expires = ? 
                                   WHERE device_id = ? 
                                   AND attempt_time > ?");
            $stmt->execute([$block_expires, $device_id, $check_time]);
            
            return [
                'blocked' => true, 
                'expires' => $block_expires,
                'minutes_left' => ceil($block_duration / 60),
                'just_blocked' => true
            ];
        }
        
        return [
            'blocked' => false, 
            'attempts' => $result['attempt_count'],
            'remaining' => $max_attempts - $result['attempt_count']
        ];
        
    } catch (PDOException $e) {
        error_log("Login Security Error: " . $e->getMessage());
        // On error, don't block legitimate users
        return ['blocked' => false, 'error' => true];
    }
}

/**
 * Generate a device identifier based on browser fingerprint or IP
 * 
 * @return string Device identifier
 */
function get_device_identifier() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // If fingerprint was provided (via JS), use that
    if (!empty($_POST['device_fingerprint'])) {
        return $_POST['device_fingerprint'];
    }
    
    // Create a basic device ID from IP and browser info
    return md5($ip . $agent);
}

/**
 * Run cleanup to automatically remove expired blocks
 * Call this periodically to keep the database clean
 */
function cleanup_expired_blocks() {
    global $conn;
    
    try {
        // Clean up regular user login attempts
        $stmt = $conn->prepare("UPDATE login_attempts 
                               SET is_blocked = 0 
                               WHERE is_blocked = 1 
                               AND block_expires < NOW()");
        $stmt->execute();
        
        // Clean up admin login attempts
        $stmt = $conn->prepare("UPDATE admin_login_attempts 
                               SET is_blocked = 0 
                               WHERE is_blocked = 1 
                               AND block_expires < NOW()");
        $stmt->execute();
        
        // Optional: Delete old records to keep table size manageable
        $oneMonthAgo = date('Y-m-d H:i:s', strtotime('-1 month'));
        $stmt = $conn->prepare("DELETE FROM login_attempts 
                               WHERE attempt_time < ?");
        $stmt->execute([$oneMonthAgo]);
        
        $stmt = $conn->prepare("DELETE FROM admin_login_attempts 
                               WHERE attempt_time < ?");
        $stmt->execute([$oneMonthAgo]);
        
    } catch (PDOException $e) {
        error_log("Cleanup Error: " . $e->getMessage());
    }
}

/**
 * Track admin login attempt and check if device is blocked
 * 
 * @param string $device_id Device identifier (fingerprint or IP)
 * @param bool $success Whether login was successful
 * @return array Status array with block information
 */
function track_admin_login_attempt($device_id, $success = false) {
    global $conn;
    $current_time = time();
    $block_duration = 3 * 60 * 60; // 3 hours in seconds
    $monitoring_period = 60 * 60; // 1 hour in seconds
    $max_attempts = 5; // Maximum allowed attempts within monitoring period
    
    try {
        // Check if device is currently blocked
        $stmt = $conn->prepare("SELECT * FROM admin_login_attempts 
                               WHERE device_id = ? 
                               AND is_blocked = 1 
                               AND block_expires > ?");
        $stmt->execute([$device_id, date('Y-m-d H:i:s', $current_time)]);
        
        if ($stmt->rowCount() > 0) {
            $block_info = $stmt->fetch(PDO::FETCH_ASSOC);
            $time_left = strtotime($block_info['block_expires']) - $current_time;
            
            return [
                'blocked' => true, 
                'expires' => $block_info['block_expires'],
                'minutes_left' => ceil($time_left / 60)
            ];
        }
        
        // Record this attempt
        $stmt = $conn->prepare("INSERT INTO admin_login_attempts 
                               (device_id, attempt_time, is_success, ip_address, user_agent) 
                               VALUES (?, NOW(), ?, ?, ?)");
        $stmt->execute([
            $device_id, 
            $success ? 1 : 0, 
            $_SERVER['REMOTE_ADDR'] ?? 'unknown', 
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // If successful login, clear previous failed attempts for this device
        if ($success) {
            $stmt = $conn->prepare("UPDATE admin_login_attempts 
                                   SET is_blocked = 0 
                                   WHERE device_id = ?");
            $stmt->execute([$device_id]);
            
            return ['blocked' => false];
        }
        
        // Count failed attempts within the monitoring period
        $check_time = date('Y-m-d H:i:s', $current_time - $monitoring_period);
        $stmt = $conn->prepare("SELECT COUNT(*) as attempt_count 
                               FROM admin_login_attempts 
                               WHERE device_id = ? 
                               AND is_success = 0 
                               AND attempt_time > ?");
        $stmt->execute([$device_id, $check_time]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If exceeded max attempts, block the device
        if ($result['attempt_count'] >= $max_attempts) {
            $block_expires = date('Y-m-d H:i:s', $current_time + $block_duration);
            
            // Update all recent attempts to blocked status
            $stmt = $conn->prepare("UPDATE admin_login_attempts 
                                   SET is_blocked = 1, block_expires = ? 
                                   WHERE device_id = ? 
                                   AND attempt_time > ?");
            $stmt->execute([$block_expires, $device_id, $check_time]);
            
            return [
                'blocked' => true, 
                'expires' => $block_expires,
                'minutes_left' => ceil($block_duration / 60),
                'just_blocked' => true
            ];
        }
        
        return [
            'blocked' => false, 
            'attempts' => $result['attempt_count'],
            'remaining' => $max_attempts - $result['attempt_count']
        ];
        
    } catch (PDOException $e) {
        error_log("Admin Login Security Error: " . $e->getMessage());
        // On error, don't block legitimate users
        return ['blocked' => false, 'error' => true];
    }
}