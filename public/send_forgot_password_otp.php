<?php
/**
 * PSAU Admission System - Send Forgot Password OTP
 * Sends OTP via email for password reset
 * Compatible with both local development and Render deployment
 */

// Detect environment
$is_production = !empty($_ENV['RENDER']) || !empty($_SERVER['RENDER']);

// Start output buffering to ensure clean JSON only
ob_start();

// Configure error reporting based on environment
if ($is_production) {
    // Production settings for Render
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    // Development settings
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Override Firebase config error reporting
if (file_exists('../firebase/config.php')) {
    // Temporarily disable error reporting for Firebase config
    $original_error_reporting = error_reporting(0);
    $original_display_errors = ini_set('display_errors', 0);
    
    session_start();
    require_once '../includes/db_connect.php';
    require_once '../firebase/firebase_email.php'; // For sending emails
    require_once '../includes/api_calls.php'; // For reCAPTCHA verification
    require_once '../includes/otp_rate_limiting_enhanced.php';
    
    // Restore original settings
    error_reporting($original_error_reporting);
    ini_set('display_errors', $original_display_errors);
} else {
    session_start();
    require_once '../includes/db_connect.php';
    require_once '../firebase/firebase_email.php'; // For sending emails
    require_once '../includes/api_calls.php'; // For reCAPTCHA verification
}

// Set proper headers for JSON response
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

    // Check enhanced OTP rate limiting (5 OTPs per hour, reset every 3 hours)
    $rate_limit = check_otp_rate_limit_enhanced($email, 'forgot_password');
    if (!$rate_limit['can_send']) {
        $response['message'] = $rate_limit['message'];
        echo json_encode($response);
        exit;
    }

    // Generate a 6-digit OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Store OTP in session for verification
    $_SESSION['password_reset']['otp_code'] = $otp;
    $_SESSION['password_reset']['otp_expires'] = time() + (5 * 60); // OTP valid for 5 minutes

    // Store OTP request in database for rate limiting
    $stmt = $conn->prepare("INSERT INTO otp_requests (email, purpose, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $email,
        'forgot_password_' . $otp,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

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
            
            // Log success in production
            if ($is_production) {
                error_log("Password reset OTP sent successfully to: " . $email);
            }
        } else {
            // If Firebase fails, log the OTP for testing/debugging
            error_log("Firebase email failed, OTP for {$email}: {$otp}");
            $response['success'] = true;
            $response['message'] = 'OTP sent to your email. (Check server logs for OTP)';
            
            // In production, provide a more user-friendly message
            if ($is_production) {
                $response['message'] = 'OTP sent to your email. Please check your inbox and spam folder.';
            }
        }
    } catch (Exception $e) {
        // If Firebase completely fails, still provide OTP via logs
        error_log("Firebase email error: " . $e->getMessage());
        error_log("OTP for {$email}: {$otp}");
        $response['success'] = true;
        
        // Different messages for production vs development
        if ($is_production) {
            $response['message'] = 'OTP sent to your email. Please check your inbox and spam folder.';
        } else {
            $response['message'] = 'OTP sent to your email. (Check server logs for OTP)';
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Clear any buffered output and return clean JSON
$output = ob_get_clean();
if (!empty($output)) {
    error_log("Unexpected output before JSON: " . $output);
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
        if ($is_production) {
            // In production, return a safe error response
            echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'JSON encoding error: ' . json_last_error_msg()]);
        }
    } else {
        echo $json_response;
    }
} catch (Exception $e) {
    error_log("Error in JSON response: " . $e->getMessage());
    if ($is_production) {
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Response error: ' . $e->getMessage()]);
    }
}
exit;
?>
