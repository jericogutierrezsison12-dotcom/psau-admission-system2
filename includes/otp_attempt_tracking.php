<?php
require_once 'db_connect.php';

function check_otp_attempts($email, $purpose, $input_otp) {
    global $conn;
    $max_attempts = 3; // Max attempts per OTP code
    $otp_expiry_minutes = ($purpose === 'forgot_password') ? 5 : 10; // OTP valid for 5 or 10 minutes

    try {
        // Fetch the latest OTP request for the given email and purpose
        $stmt = $conn->prepare("SELECT id, purpose, created_at FROM otp_requests 
                                WHERE email = ? AND purpose LIKE ? 
                                ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email, $purpose . '_%']);
        $otp_request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otp_request) {
            return ['success' => false, 'message' => 'No verification code found. Please request a new one.'];
        }

        $otp_id = $otp_request['id'];
        // Extract OTP code from purpose field (format: "forgot_password_123456" or "registration_123456")
        $correct_otp = substr($otp_request['purpose'], strlen($purpose) + 1);
        
        // Check if OTP has expired using database time comparison
        $stmt = $conn->prepare("SELECT TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_ago FROM otp_requests WHERE id = ?");
        $stmt->execute([$otp_id]);
        $minutes_ago = $stmt->fetchColumn();
        
        if ($minutes_ago > $otp_expiry_minutes) {
            return ['success' => false, 'message' => 'Verification code has expired. Please request a new one.'];
        }

        // Record the current attempt
        $stmt = $conn->prepare("INSERT INTO otp_attempts (email, purpose, otp_request_id, otp_code, is_successful, ip_address, user_agent, attempted_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $email,
            $purpose,
            $otp_id,
            $correct_otp,
            ($input_otp === $correct_otp) ? 1 : 0,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        // Count failed attempts for this specific OTP request
        $stmt = $conn->prepare("SELECT COUNT(*) FROM otp_attempts 
                                WHERE otp_request_id = ? AND is_successful = 0");
        $stmt->execute([$otp_id]);
        $failed_attempts = $stmt->fetchColumn();

        if ($input_otp !== $correct_otp) {
            $remaining_attempts = $max_attempts - $failed_attempts;
            if ($remaining_attempts <= 0) {
                return ['success' => false, 'message' => 'Too many failed attempts. Please request a new verification code.'];
            } else {
                return ['success' => false, 'message' => "Incorrect OTP. {$remaining_attempts} attempts remaining."];
            }
        }

        // If OTP is correct, mark all attempts for this OTP as successful
        $stmt = $conn->prepare("UPDATE otp_attempts SET is_successful = 1 WHERE otp_request_id = ?");
        $stmt->execute([$otp_id]);
        return ['success' => true, 'message' => 'Verification successful.'];

    } catch (PDOException $e) {
        error_log("OTP attempt tracking error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Server error during OTP verification. Please try again later.'];
    }
}

// Function to clean up old OTP attempts (e.g., run daily via cron job)
function cleanup_old_otp_attempts() {
    global $conn;
    try {
        $stmt = $conn->prepare("DELETE FROM otp_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute();
        error_log("Cleaned up old OTP attempts.");
    } catch (PDOException $e) {
        error_log("Error cleaning up old OTP attempts: " . $e->getMessage());
    }
}
?>
