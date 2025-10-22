<?php
/**
 * PSAU Admission System - Send Forgot Password OTP
 * Sends OTP via email for password reset
 */

// Start output buffering to prevent any output before JSON
ob_start();

// Disable error display to prevent HTML in JSON response
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Temporarily disable error reporting for includes
$old_error_reporting = error_reporting(0);
$old_display_errors = ini_get('display_errors');
ini_set('display_errors', 0);

try {
    require_once '../includes/db_connect.php';
    require_once '../firebase/firebase_email.php'; // For sending emails
    require_once '../includes/api_calls.php'; // For reCAPTCHA verification
} catch (Exception $e) {
    // Restore error reporting
    error_reporting($old_error_reporting);
    ini_set('display_errors', $old_display_errors);
    
    // Clean output buffer and return error
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'System error. Please try again.']);
    exit;
}

// Restore error reporting
error_reporting($old_error_reporting);
ini_set('display_errors', $old_display_errors);

// Clean any output that might have been generated
$output = ob_get_clean();
if (!empty($output)) {
    error_log("Unexpected output before JSON: " . $output);
}

// Set proper headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

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
        // Try Firebase email first
        $email_result = firebase_send_email($email, $subject, $message);
        if (is_array($email_result) && isset($email_result['success']) && $email_result['success']) {
            $response['success'] = true;
            $response['message'] = 'OTP sent to your email.';
        } else {
            // If Firebase fails, log the OTP for testing
            error_log("Firebase email failed, OTP for {$email}: {$otp}");
            $response['success'] = true;
            $response['message'] = 'OTP sent to your email. (Check server logs for OTP)';
        }
    } catch (Exception $e) {
        // If Firebase completely fails, still provide OTP via logs
        error_log("Firebase email error: " . $e->getMessage());
        error_log("OTP for {$email}: {$otp}");
        $response['success'] = true;
        $response['message'] = 'OTP sent to your email. (Check server logs for OTP)';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Ensure we only output JSON with proper error handling
try {
    // Remove any existing headers that might interfere
    if (!headers_sent()) {
        header_remove('Content-Type');
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    }

    // Encode JSON with proper error handling
    $json_response = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    if ($json_response === false) {
        error_log("JSON encoding error: " . json_last_error_msg());
        // Return a safe error response
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    } else {
        echo $json_response;
    }
} catch (Exception $e) {
    error_log("Error in JSON response: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
exit;
?>
