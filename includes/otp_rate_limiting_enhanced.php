<?php
require_once 'db_connect.php';

/**
 * Enhanced OTP rate limiting with 5 OTPs per hour, reset every 3 hours
 * @param string $email Email address
 * @param string $purpose Purpose (registration, forgot_password, etc.)
 * @return array Rate limit status
 */
function check_otp_rate_limit_enhanced($email, $purpose) {
    global $conn;
    
    try {
        // Check OTP requests in the last 3 hours
        $stmt = $conn->prepare("
            SELECT COUNT(*) as request_count,
                   MAX(created_at) as last_request
            FROM otp_requests 
            WHERE email = ? 
            AND purpose LIKE ? 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 3 HOUR)
        ");
        $stmt->execute([$email, $purpose . '_%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $request_count = (int)$result['request_count'];
        $last_request = $result['last_request'];
        
        // If 5 or more requests in last 3 hours, check if we can reset
        if ($request_count >= 5) {
            // Check if 3 hours have passed since first request in the window
            $stmt = $conn->prepare("
                SELECT MIN(created_at) as first_request
                FROM otp_requests 
                WHERE email = ? 
                AND purpose LIKE ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 3 HOUR)
            ");
            $stmt->execute([$email, $purpose . '_%']);
            $first_request = $stmt->fetchColumn();
            
            if ($first_request) {
                $first_request_time = strtotime($first_request);
                $three_hours_ago = time() - (3 * 60 * 60);
                
                // If first request was more than 3 hours ago, reset is allowed
                if ($first_request_time <= $three_hours_ago) {
                    return [
                        'can_send' => true,
                        'message' => 'Rate limit reset. You can now request a new OTP.',
                        'remaining_requests' => 5,
                        'reset_time' => date('Y-m-d H:i:s', $first_request_time + (3 * 60 * 60))
                    ];
                }
            }
            
            // Calculate when next request is allowed
            $next_allowed = strtotime($first_request) + (3 * 60 * 60);
            $time_remaining = $next_allowed - time();
            
            return [
                'can_send' => false,
                'message' => "Rate limit exceeded. You can request a new OTP in " . 
                           gmdate("H:i:s", $time_remaining) . " hours.",
                'remaining_requests' => 0,
                'reset_time' => date('Y-m-d H:i:s', $next_allowed)
            ];
        }
        
        return [
            'can_send' => true,
            'message' => 'OTP request allowed.',
            'remaining_requests' => 5 - $request_count,
            'reset_time' => null
        ];
        
    } catch (PDOException $e) {
        error_log("OTP rate limiting error: " . $e->getMessage());
        return [
            'can_send' => false,
            'message' => 'Server error. Please try again later.',
            'remaining_requests' => 0,
            'reset_time' => null
        ];
    }
}

/**
 * Store OTP code in database
 * @param string $email Email address
 * @param string $purpose Purpose
 * @param string $otp_code 6-digit OTP code
 * @param int $expiry_minutes Expiry time in minutes
 * @return bool Success status
 */
function store_otp_code($email, $purpose, $otp_code, $expiry_minutes = 10) {
    global $conn;
    
    try {
        $expires_at = date('Y-m-d H:i:s', time() + ($expiry_minutes * 60));
        
        $stmt = $conn->prepare("
            INSERT INTO otp_codes (email, purpose, otp_code, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $email,
            $purpose,
            $otp_code,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $expires_at
        ]);
        
        return $result;
        
    } catch (PDOException $e) {
        error_log("OTP code storage error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify OTP code with attempt tracking
 * @param string $email Email address
 * @param string $purpose Purpose
 * @param string $input_otp User input OTP
 * @return array Verification result
 */
function verify_otp_code_enhanced($email, $purpose, $input_otp) {
    global $conn;
    $max_attempts = 3;
    
    try {
        // Get the latest valid OTP for this email and purpose
        $stmt = $conn->prepare("
            SELECT id, otp_code, expires_at, attempts, is_used
            FROM otp_codes 
            WHERE email = ? 
            AND purpose = ? 
            AND expires_at > NOW() 
            AND is_used = 0
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$email, $purpose]);
        $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$otp_record) {
            return [
                'success' => false, 
                'message' => 'No valid OTP found. Please request a new one.',
                'remaining_attempts' => 0
            ];
        }
        
        $otp_id = $otp_record['id'];
        $correct_otp = $otp_record['otp_code'];
        $attempts = (int)$otp_record['attempts'];
        
        // Check if max attempts reached
        if ($attempts >= $max_attempts) {
            return [
                'success' => false,
                'message' => 'Maximum attempts reached. Please request a new OTP.',
                'remaining_attempts' => 0
            ];
        }
        
        // Increment attempt count
        $stmt = $conn->prepare("UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?");
        $stmt->execute([$otp_id]);
        
        // Check if OTP matches
        if ($input_otp === $correct_otp) {
            // Mark as used
            $stmt = $conn->prepare("UPDATE otp_codes SET is_used = 1 WHERE id = ?");
            $stmt->execute([$otp_id]);
            
            return [
                'success' => true,
                'message' => 'OTP verified successfully.',
                'remaining_attempts' => $max_attempts - $attempts - 1
            ];
        } else {
            $remaining = $max_attempts - $attempts - 1;
            return [
                'success' => false,
                'message' => $remaining > 0 ? 
                    "Incorrect OTP. {$remaining} attempts remaining." : 
                    "Incorrect OTP. Maximum attempts reached.",
                'remaining_attempts' => $remaining
            ];
        }
        
    } catch (PDOException $e) {
        error_log("OTP verification error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Server error during verification. Please try again.',
            'remaining_attempts' => 0
        ];
    }
}
?>
