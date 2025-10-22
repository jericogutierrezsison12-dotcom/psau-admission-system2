<?php
/**
 * Resend Admin Registration OTP
 * Handles resending OTP for admin registration
 */

// Start output buffering to prevent any output before JSON
ob_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../firebase/firebase_email.php';

// Set JSON header
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';

// Clean any previous output
ob_clean();

try {
    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email address'
        ]);
        exit;
    }
    
    // Check if email is allowed for admin registration
    if ($email !== 'jericogutierrezsison12@gmail.com') {
        echo json_encode([
            'success' => false,
            'message' => 'Only jericogutierrezsison12@gmail.com is allowed for admin registration'
        ]);
        exit;
    }
    
    // Generate new OTP
    $otp_code = sprintf('%06d', mt_rand(100000, 999999));
    $_SESSION['admin_email_otp'] = $otp_code;
    
    // Send email OTP
    $subject = 'Admin Registration OTP - PSAU Admission System';
    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
            <h2>Pampanga State Agricultural University</h2>
        </div>
        <div style='padding: 20px; border: 1px solid #ddd;'>
            <p>Dear Admin,</p>
            <p>Your new OTP code for admin registration is:</p>
            <div style='background-color: #f8f9fa; padding: 15px; text-align: center; border: 2px solid #2E7D32; margin: 20px 0;'>
                <h1 style='color: #2E7D32; margin: 0; font-size: 32px; letter-spacing: 5px;'>{$otp_code}</h1>
            </div>
            <p>This code will expire in 10 minutes. Please enter it to complete your admin registration.</p>
            <p>If you did not request this registration, please ignore this email.</p>
            <p>Best regards,<br>PSAU Admissions Team</p>
        </div>
        <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
            <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
        </div>
    </div>";
    
    $result = firebase_send_email($email, $subject, $message);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'OTP sent successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send OTP email'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Admin OTP resend error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while sending OTP'
    ]);
}

// End output buffering
ob_end_flush();
?>
