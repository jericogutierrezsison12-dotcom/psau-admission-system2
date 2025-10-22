<?php
/**
 * PSAU Admission System - Send Forgot Password OTP
 * Sends OTP via email for password reset
 */

session_start();
require_once '../includes/db_connect.php';
// require_once '../firebase/firebase_email.php'; // For sending emails - disabled for now
require_once '../includes/security_functions.php'; // For reCAPTCHA verification

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $recaptchaResponse = $input['recaptchaResponse'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email address.';
        echo json_encode($response);
        exit;
    }

    // Verify reCAPTCHA
    if (!verify_recaptcha($recaptchaResponse)) {
        $response['message'] = 'reCAPTCHA verification failed. Please try again.';
        echo json_encode($response);
        exit;
    }

    // Check if password reset session exists and email matches
    if (!isset($_SESSION['password_reset']) || $_SESSION['password_reset']['email'] !== $email) {
        $response['message'] = 'Invalid password reset session. Please start the process again.';
        echo json_encode($response);
        exit;
    }

    // Generate a 6-digit OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Store OTP in session for verification
    $_SESSION['password_reset']['otp_code'] = $otp;
    $_SESSION['password_reset']['otp_expires'] = time() + (5 * 60); // OTP valid for 5 minutes

    // Send OTP via email
    $subject = "PSAU Admission System: Password Reset OTP";
    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
            <h2>Pampanga State Agricultural University</h2>
        </div>
        <div style='padding: 20px; border: 1px solid #ddd;'>
            <p>Dear {$_SESSION['password_reset']['first_name']} {$_SESSION['password_reset']['last_name']},</p>
            <p>Your One-Time Password (OTP) for password reset is: <strong>{$otp}</strong></p>
            <p>This code is valid for 5 minutes. Please do not share this code with anyone.</p>
            <p>If you did not request this password reset, please ignore this email and contact support.</p>
            <p>Best regards,<br>PSAU Admissions Team</p>
        </div>
        <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
            <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
        </div>
    </div>";

    try {
        // For now, just simulate successful email sending
        // In production, you would use a real email service
        $response['success'] = true;
        $response['message'] = 'OTP sent to your email.';
        
        // Log the OTP for testing (remove in production)
        error_log("Forgot Password OTP for {$email}: {$otp}");
        
        // TODO: Replace with actual email sending service
        // $email_result = firebase_send_email($email, $subject, $message);
        // if ($email_result['success']) {
        //     $response['success'] = true;
        //     $response['message'] = 'OTP sent to your email.';
        // } else {
        //     $response['message'] = 'Failed to send OTP email: ' . ($email_result['message'] ?? 'Unknown error');
        // }
    } catch (Exception $e) {
        $response['message'] = 'Error sending OTP email: ' . $e->getMessage();
        error_log("Error in send_forgot_password_otp.php: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
