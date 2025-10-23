<?php
/**
 * OTP Rate Limiting System
 * Prevents abuse of OTP sending functionality
 */

require_once 'db_connect.php';

/**
 * Check if user can request OTP based on rate limits
 * @param string $email Email address
 * @param string $purpose Purpose (registration, forgot_password, admin_register, etc.)
 * @return array Result with can_send, remaining, reset_time
 */
function check_otp_rate_limit($email, $purpose) {
    global $conn;
    
    try {
        // Rate limiting rules
        $limits = [
            'registration' => [
                'per_hour' => 3,      // 3 OTPs per hour
                'per_day' => 5,       // 5 OTPs per day
                'cooldown' => 60      // 60 seconds between requests
            ],
            'forgot_password' => [
                'per_hour' => 2,      // 2 OTPs per hour
                'per_day' => 3,       // 3 OTPs per day
                'cooldown' => 120     // 2 minutes between requests
            ],
            'admin_register' => [
                'per_hour' => 2,      // 2 OTPs per hour
                'per_day' => 3,       // 3 OTPs per day
                'cooldown' => 60      // 1 minute between requests
            ],
            'admin_restricted_email' => [
                'per_hour' => 1,      // 1 OTP per hour
                'per_day' => 2,       // 2 OTPs per day
                'cooldown' => 300     // 5 minutes between requests
            ]
        ];
        
        $current_time = time();
        $hour_ago = $current_time - 3600; // 1 hour ago
        $day_ago = $current_time - 86400; // 24 hours ago
        
        // Get rate limit rules for this purpose
        $rules = $limits[$purpose] ?? $limits['registration'];
        
        // Check cooldown period (time since last request)
        $stmt = $conn->prepare("SELECT created_at FROM otp_requests 
                               WHERE email = ? AND purpose = ? 
                               ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email, $purpose]);
        $last_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($last_request) {
            $last_request_time = strtotime($last_request['created_at']);
            $time_since_last = $current_time - $last_request_time;
            
            if ($time_since_last < $rules['cooldown']) {
                $wait_time = $rules['cooldown'] - $time_since_last;
                return [
                    'can_send' => false,
                    'reason' => 'cooldown',
                    'wait_seconds' => $wait_time,
                    'wait_minutes' => ceil($wait_time / 60),
                    'message' => "Please wait {$wait_minutes} minute(s) before requesting another OTP."
                ];
            }
        }
        
        // Check hourly limit
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM otp_requests 
                               WHERE email = ? AND purpose = ? 
                               AND created_at >= ?");
        $stmt->execute([$email, $purpose, date('Y-m-d H:i:s', $hour_ago)]);
        $hourly_count = $stmt->fetchColumn();
        
        if ($hourly_count >= $rules['per_hour']) {
            $next_reset = $hour_ago + 3600; // Next hour
            $wait_time = $next_reset - $current_time;
            return [
                'can_send' => false,
                'reason' => 'hourly_limit',
                'wait_seconds' => $wait_time,
                'wait_minutes' => ceil($wait_time / 60),
                'message' => "Hourly limit reached. Please wait {$wait_minutes} minute(s)."
            ];
        }
        
        // Check daily limit
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM otp_requests 
                               WHERE email = ? AND purpose = ? 
                               AND created_at >= ?");
        $stmt->execute([$email, $purpose, date('Y-m-d H:i:s', $day_ago)]);
        $daily_count = $stmt->fetchColumn();
        
        if ($daily_count >= $rules['per_day']) {
            $next_reset = $day_ago + 86400; // Next day
            $wait_time = $next_reset - $current_time;
            $wait_hours = ceil($wait_time / 3600);
            return [
                'can_send' => false,
                'reason' => 'daily_limit',
                'wait_seconds' => $wait_time,
                'wait_hours' => $wait_hours,
                'message' => "Daily limit reached. Please wait {$wait_hours} hour(s)."
            ];
        }
        
        // Calculate remaining requests
        $remaining_hourly = $rules['per_hour'] - $hourly_count;
        $remaining_daily = $rules['per_day'] - $daily_count;
        
        return [
            'can_send' => true,
            'remaining_hourly' => $remaining_hourly,
            'remaining_daily' => $remaining_daily,
            'rules' => $rules
        ];
        
    } catch (PDOException $e) {
        error_log("OTP rate limit check error: " . $e->getMessage());
        // If database error, allow request but log it
        return [
            'can_send' => true,
            'error' => 'Database error, allowing request'
        ];
    }
}

/**
 * Record OTP request for rate limiting
 * @param string $email Email address
 * @param string $purpose Purpose of OTP
 * @param string $ip_address IP address
 * @param string $user_agent User agent
 * @return bool Success status
 */
function record_otp_request($email, $purpose, $ip_address = null, $user_agent = null) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO otp_requests 
                               (email, purpose, ip_address, user_agent, created_at) 
                               VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
            $email,
            $purpose,
            $ip_address ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            $user_agent ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("OTP request recording error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get OTP request statistics for an email
 * @param string $email Email address
 * @param string $purpose Purpose (optional)
 * @return array Statistics
 */
function get_otp_stats($email, $purpose = null) {
    global $conn;
    
    try {
        $where_clause = "WHERE email = ?";
        $params = [$email];
        
        if ($purpose) {
            $where_clause .= " AND purpose = ?";
            $params[] = $purpose;
        }
        
        // Last 24 hours
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM otp_requests 
                               $where_clause AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute($params);
        $last_24h = $stmt->fetchColumn();
        
        // Last hour
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM otp_requests 
                               $where_clause AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute($params);
        $last_hour = $stmt->fetchColumn();
        
        // Last request
        $stmt = $conn->prepare("SELECT created_at FROM otp_requests 
                               $where_clause ORDER BY created_at DESC LIMIT 1");
        $stmt->execute($params);
        $last_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'last_24h' => $last_24h,
            'last_hour' => $last_hour,
            'last_request' => $last_request ? $last_request['created_at'] : null
        ];
    } catch (PDOException $e) {
        error_log("OTP stats error: " . $e->getMessage());
        return [
            'last_24h' => 0,
            'last_hour' => 0,
            'last_request' => null
        ];
    }
}

/**
 * Clean up old OTP request records (older than 7 days)
 * @return bool Success status
 */
function cleanup_old_otp_requests() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("DELETE FROM otp_requests 
                               WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        error_log("OTP cleanup error: " . $e->getMessage());
        return false;
    }
}
?>
