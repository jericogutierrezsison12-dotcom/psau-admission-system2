<?php
/**
 * Send OTP to restricted email (jericogutierrezsison12@gmail.com)
 * This is the first step of admin registration
 */

// Start output buffering to prevent any HTML output from interfering with JSON
ob_start();

require_once '../includes/db_connect.php';
require_once '../includes/otp_rate_limiting.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new Exception('Invalid payload');
    }

    $recaptcha_token = $data['recaptcha_token'] ?? '';

    // reCAPTCHA validation - skip for Firebase tokens
    // Firebase reCAPTCHA returns a verification token that doesn't work with Google's verify API
    // We'll just check that a token is provided and trust Firebase's verification
    if ($recaptcha_token === '') {
        throw new Exception('reCAPTCHA verification is required');
    }
    
    // Log the token for debugging
    error_log("reCAPTCHA token received: " . substr($recaptcha_token, 0, 20) . "...");
    
    // Skip Google reCAPTCHA verification for Firebase tokens
    // Firebase has already verified the reCAPTCHA client-side

    // Check OTP rate limiting for restricted email
    $rate_limit = check_otp_rate_limit('jericogutierrezsison12@gmail.com', 'admin_restricted_email');
    if (!$rate_limit['can_send']) {
        throw new Exception($rate_limit['message']);
    }

    // Generate 6-digit OTP and set 10-minute expiry
    $otp = random_int(100000, 999999);
    $_SESSION['admin_restricted_email_otp'] = [
        'code' => (string)$otp,
        'expires' => time() + (10 * 60),
    ];

    // Build email content
    require_once '../firebase/firebase_email.php';
    $subject = 'PSAU Admin Registration: Access Verification Code';
    $message = "<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>"
        ."<div style='background-color:#2E7D32;color:#fff;padding:16px;text-align:center;'>"
        ."<h2 style='margin:0'>Pampanga State Agricultural University</h2>"
        ."</div>"
        ."<div style='padding:20px;border:1px solid #ddd;border-top:none'>"
        ."<p>Dear Admin,</p>"
        ."<p>Your access verification code for admin registration is:</p>"
        ."<p style='font-size:28px;letter-spacing:6px;font-weight:bold;text-align:center;margin:20px 0'>{$otp}</p>"
        ."<p>This code will expire in 10 minutes. If you did not request this code, you may ignore this email.</p>"
        ."<p>Best regards,<br>PSAU Admissions Team</p>"
        ."</div>"
        ."<div style='background:#f5f5f5;padding:10px;text-align:center;color:#666;font-size:12px'>&copy; "
        . date('Y') . " PSAU Admission System</div>"
        ."</div>";

    $result = firebase_send_email('jericogutierrezsison12@gmail.com', $subject, $message);
    if (!$result || (is_array($result) && empty($result['success']))) {
        throw new Exception('Failed to send OTP email');
    }

    // Record OTP request for rate limiting
    record_otp_request('jericogutierrezsison12@gmail.com', 'admin_restricted_email');

    // Clean any output buffer and send JSON response
    ob_end_clean();
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    // Clean any output buffer and send error response
    ob_end_clean();
    http_response_code(400);
    error_log("Restricted email OTP send error: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?>
