<?php
// Minimal endpoint to send 6-digit OTP to the registration email

require_once '../includes/db_connect.php';
require_once '../includes/otp_rate_limiting.php';
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

header('Content-Type: application/json');

try {
	$raw = file_get_contents('php://input');
	$data = json_decode($raw, true);
	if (!is_array($data)) {
		throw new Exception('Invalid payload');
	}

	$email = trim($data['email'] ?? '');
	$recaptcha_token = $data['recaptcha_token'] ?? '';

	if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		throw new Exception('Valid email is required');
	}
	if ($recaptcha_token === '') {
		throw new Exception('reCAPTCHA token is required');
	}

	// Basic gating: ensure registration session matches email
	if (!isset($_SESSION['registration']['email']) || strcasecmp($_SESSION['registration']['email'], $email) !== 0) {
		throw new Exception('Registration session not found for this email');
	}

	// Check OTP rate limiting
	$rate_limit = check_otp_rate_limit($email, 'registration');
	if (!$rate_limit['can_send']) {
		throw new Exception($rate_limit['message']);
	}

	// Generate 6-digit OTP and set 10-minute expiry
	$otp = random_int(100000, 999999);
	
	// Store OTP in database for attempt tracking
	$stmt = $conn->prepare("INSERT INTO otp_requests (email, purpose, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, NOW())");
	$stmt->execute([
		$email,
		'registration_' . $otp, // Store OTP code in purpose field
		$_SERVER['REMOTE_ADDR'] ?? 'unknown',
		$_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
	]);
	
	// Also store in session for backward compatibility
	$_SESSION['email_otp'] = [
		'code' => (string)$otp,
		'expires' => time() + (10 * 60),
	];

	// Build email content
	require_once '../firebase/firebase_email.php';
	$subject = 'PSAU Admission: Your Verification Code';
	$message = "<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>"
		."<div style='background-color:#2E7D32;color:#fff;padding:16px;text-align:center;'>"
		."<h2 style='margin:0'>Pampanga State Agricultural University</h2>"
		."</div>"
		."<div style='padding:20px;border:1px solid #ddd;border-top:none'>"
		."<p>Dear Applicant,</p>"
		."<p>Your verification code is:</p>"
		."<p style='font-size:28px;letter-spacing:6px;font-weight:bold;text-align:center;margin:20px 0'>{$otp}</p>"
		."<p>This code will expire in 10 minutes. If you did not request this code, you may ignore this email.</p>"
		."<p>Best regards,<br>PSAU Admissions Team</p>"
		."</div>"
		."<div style='background:#f5f5f5;padding:10px;text-align:center;color:#666;font-size:12px'>&copy; "
		. date('Y') . " PSAU Admission System</div>"
		."</div>";

    try {
        $result = firebase_send_email($email, $subject, $message);
        if (!$result || (is_array($result) && empty($result['success']))) {
            // Log failure but still allow flow; OTP stored in DB
            error_log("Registration OTP email send failed for {$email}");
        }
    } catch (Throwable $mailErr) {
        // Log and continue; client can still verify using code received later or after resend
        error_log('Registration OTP mail error: ' . $mailErr->getMessage());
    }

	// Record OTP request for rate limiting
	record_otp_request($email, 'registration');

	echo json_encode(['ok' => true]);
} catch (Throwable $e) {
	http_response_code(400);
	echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
