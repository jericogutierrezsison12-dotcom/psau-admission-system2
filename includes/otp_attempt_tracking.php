<?php
/**
 * OTP Attempt Tracking using Database Table
 * Tracks OTP verification attempts in the otp_requests table
 */

require_once 'db_connect.php';

/**
 * Check OTP verification attempts for an email and purpose
 * @param string $email Email address
 * @param string $purpose Purpose (registration, forgot_password, etc.)
 * @param string $otp_code OTP code to verify
 * @return array Result with success status and message
 */
function check_otp_attempts($email, $purpose, $otp_code) {
    global $conn;
    
    try {
        // Get the most recent OTP request for this email and purpose
        $stmt = $conn->prepare("SELECT * FROM otp_requests 
                               WHERE email = ? AND purpose = ? 
                               ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email, $purpose]);
        $otp_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$otp_request) {
            return ['success' => false, 'message' => 'No OTP found. Please request a new one.'];
        }
        
        // Check if OTP is still valid (within 10 minutes for registration, 5 minutes for forgot password)
        $expiry_minutes = ($purpose === 'forgot_password') ? 5 : 10;
        $expiry_time = strtotime($otp_request['created_at']) + ($expiry_minutes * 60);
        
        if (time() > $expiry_time) {
            return ['success' => false, 'message' => 'OTP has expired. Please request a new one.'];
        }
        
        // Count failed attempts for this OTP request (within the last 10 minutes)
        $check_time = date('Y-m-d H:i:s', strtotime($otp_request['created_at']));
        $stmt = $conn->prepare("SELECT COUNT(*) FROM otp_attempts 
                               WHERE email = ? AND purpose = ? 
                               AND otp_request_id = ? 
                               AND is_successful = 0 
                               AND attempted_at > ?");
        $stmt->execute([$email, $purpose, $otp_request['id'], $check_time]);
        $failed_attempts = $stmt->fetchColumn();
        
        // Check attempt limit (max 3 attempts per OTP)
        if ($failed_attempts >= 3) {
            return ['success' => false, 'message' => 'Too many failed attempts. Please request a new OTP.'];
        }
        
        // Verify OTP code (we need to get the actual OTP from session or generate it)
        // For now, we'll use a simple approach - check against a stored hash or use session
        $is_valid = verify_otp_code($email, $purpose, $otp_code);
        
        if (!$is_valid) {
            // Record failed attempt
            record_otp_attempt($email, $purpose, $otp_request['id'], $otp_code, false);
            
            $remaining = 3 - ($failed_attempts + 1);
            if ($remaining > 0) {
                return ['success' => false, 'message' => "Incorrect OTP. {$remaining} attempts remaining."];
            } else {
                return ['success' => false, 'message' => 'Too many failed attempts. Please request a new OTP.'];
            }
        }
        
        // Record successful attempt
        record_otp_attempt($email, $purpose, $otp_request['id'], $otp_code, true);
        
        return ['success' => true, 'message' => 'OTP verified successfully.'];
        
    } catch (PDOException $e) {
        error_log("OTP attempt check error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error verifying OTP. Please try again.'];
    }
}

/**
 * Verify OTP code against stored session data
 * @param string $email Email address
 * @param string $purpose Purpose
 * @param string $otp_code OTP code to verify
 * @return bool True if valid, false otherwise
 */
function verify_otp_code($email, $purpose, $otp_code) {
    // Check session-based OTP storage
    if ($purpose === 'registration' && isset($_SESSION['email_otp']['code'])) {
        return $otp_code === $_SESSION['email_otp']['code'];
    }
    
    if ($purpose === 'forgot_password' && isset($_SESSION['password_reset']['otp_code'])) {
        return $otp_code === $_SESSION['password_reset']['otp_code'];
    }
    
    if ($purpose === 'admin_register' && isset($_SESSION['admin_email_otp']['code'])) {
        return $otp_code === $_SESSION['admin_email_otp']['code'];
    }
    
    if ($purpose === 'admin_restricted_email' && isset($_SESSION['restricted_email_otp']['code'])) {
        return $otp_code === $_SESSION['restricted_email_otp']['code'];
    }
    
    return false;
}

/**
 * Record OTP attempt in database
 * @param string $email Email address
 * @param string $purpose Purpose
 * @param int $otp_request_id OTP request ID
 * @param string $otp_code OTP code attempted
 * @param bool $is_successful Whether the attempt was successful
 * @return bool Success status
 */
function record_otp_attempt($email, $purpose, $otp_request_id, $otp_code, $is_successful) {
    global $conn;
    
    try {
        // Create otp_attempts table if it doesn't exist
        $create_table_sql = "CREATE TABLE IF NOT EXISTS `otp_attempts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `email` varchar(255) NOT NULL,
            `purpose` varchar(50) NOT NULL,
            `otp_request_id` int(11) NOT NULL,
            `otp_code` varchar(6) NOT NULL,
            `is_successful` tinyint(1) NOT NULL DEFAULT 0,
            `ip_address` varchar(50) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_email_purpose` (`email`, `purpose`),
            KEY `idx_otp_request` (`otp_request_id`),
            KEY `idx_attempted_at` (`attempted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($create_table_sql);
        
        // Insert attempt record
        $stmt = $conn->prepare("INSERT INTO otp_attempts 
                               (email, purpose, otp_request_id, otp_code, is_successful, ip_address, user_agent) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $email,
            $purpose,
            $otp_request_id,
            $otp_code,
            $is_successful ? 1 : 0,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("OTP attempt recording error: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean up old OTP attempts (older than 24 hours)
 * @return bool Success status
 */
function cleanup_old_otp_attempts() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("DELETE FROM otp_attempts 
                               WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        error_log("OTP attempts cleanup error: " . $e->getMessage());
        return false;
    }
}
?>
