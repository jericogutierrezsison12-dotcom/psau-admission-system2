<?php
/**
 * Admin Registration Email OTP Endpoint
 * Handles sending email OTP for admin registration
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

    $email = trim($data['email'] ?? '');
    $recaptcha_token = $data['recaptcha_token'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Valid email is required');
    }
    
    // This endpoint is for sending OTP to user's own email during admin registration
    // The restricted email validation is handled in send_restricted_email_otp.php
    
    // reCAPTCHA validation (required for admin registration)
    if ($recaptcha_token === '') {
        throw new Exception('reCAPTCHA token is required');
    }
    
    require_once '../includes/api_calls.php';
    $recaptcha_valid = verify_recaptcha($recaptcha_token, 'admin_register');
    if (!$recaptcha_valid) {
        throw new Exception('reCAPTCHA verification failed');
    }

    // Basic gating: ensure registration session matches email
    if (!isset($_SESSION['admin_registration']['email']) || strcasecmp($_SESSION['admin_registration']['email'], $email) !== 0) {
        throw new Exception('Admin registration session not found for this email');
    }

    // Check OTP rate limiting
    $rate_limit = check_otp_rate_limit($email, 'admin_register');
    if (!$rate_limit['can_send']) {
        throw new Exception($rate_limit['message']);
    }

    // Generate 6-digit OTP and set 10-minute expiry
    $otp = random_int(100000, 999999);
    $_SESSION['admin_email_otp'] = [
        'code' => (string)$otp,
        'expires' => time() + (10 * 60),
    ];

    // Build email content
    require_once '../firebase/firebase_email.php';
    $subject = 'PSAU Admin Registration: Your Verification Code';
    $message = "<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>"
        ."<div style='background-color:#2E7D32;color:#fff;padding:16px;text-align:center;'>"
        ."<h2 style='margin:0'>Pampanga State Agricultural University</h2>"
        ."</div>"
        ."<div style='padding:20px;border:1px solid #ddd;border-top:none'>"
        ."<p>Dear Admin,</p>"
        ."<p>Your verification code for admin registration is:</p>"
        ."<p style='font-size:28px;letter-spacing:6px;font-weight:bold;text-align:center;margin:20px 0'>{$otp}</p>"
        ."<p>This code will expire in 10 minutes. If you did not request this code, you may ignore this email.</p>"
        ."<p>Best regards,<br>PSAU Admissions Team</p>"
        ."</div>"
        ."<div style='background:#f5f5f5;padding:10px;text-align:center;color:#666;font-size:12px'>&copy; "
        . date('Y') . " PSAU Admission System</div>"
        ."</div>";

    $result = firebase_send_email($email, $subject, $message);
    if (!$result || (is_array($result) && empty($result['success']))) {
        throw new Exception('Failed to send OTP email');
    }

    // Record OTP request for rate limiting
    record_otp_request($email, 'admin_register');

    // Clean any output buffer and send JSON response
    ob_end_clean();
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    // Clean any output buffer and send error response
    ob_end_clean();
    http_response_code(400);
    error_log("Admin OTP send error: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
