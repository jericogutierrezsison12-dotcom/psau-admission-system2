<?php
/**
 * Email OTP System
 * Handles sending and verifying OTP codes via email
 */

require_once __DIR__ . '/simple_email.php';

/**
 * Generate a 6-digit OTP code
 * @return string 6-digit OTP code
 */
function generate_otp_code() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Send OTP code via email
 * @param string $email Recipient email address
 * @param string $otp_code 6-digit OTP code
 * @param string $purpose Purpose of OTP (registration, password_reset, etc.)
 * @return bool True if email was sent successfully, false otherwise
 */
function send_otp_email($email, $otp_code, $purpose = 'verification') {
    // Determine subject and message based on purpose
    switch ($purpose) {
        case 'registration':
            $subject = 'PSAU Admission System - Email Verification Code';
            $message = create_registration_otp_email($otp_code);
            break;
        case 'password_reset':
            $subject = 'PSAU Admission System - Password Reset Code';
            $message = create_password_reset_otp_email($otp_code);
            break;
        default:
            $subject = 'PSAU Admission System - Verification Code';
            $message = create_generic_otp_email($otp_code);
    }
    
    // Send email using the existing email system
    return send_email_with_fallback($email, $subject, $message);
}

/**
 * Create HTML email template for registration OTP
 * @param string $otp_code 6-digit OTP code
 * @return string HTML email content
 */
function create_registration_otp_email($otp_code) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Email Verification - PSAU Admission System</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2c5aa0; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; }
            .otp-code { background: #2c5aa0; color: white; font-size: 32px; font-weight: bold; text-align: center; padding: 20px; margin: 20px 0; border-radius: 8px; letter-spacing: 5px; }
            .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 12px; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>PSAU Admission System</h1>
                <h2>Email Verification</h2>
            </div>
            
            <div class='content'>
                <h3>Welcome to PSAU Admission System!</h3>
                <p>Thank you for registering with the PSAU Admission System. To complete your registration, please use the verification code below:</p>
                
                <div class='otp-code'>$otp_code</div>
                
                <p><strong>Instructions:</strong></p>
                <ol>
                    <li>Enter this 6-digit code in the verification form</li>
                    <li>Complete your registration process</li>
                    <li>Start your admission application</li>
                </ol>
                
                <div class='warning'>
                    <strong>Important:</strong> This code will expire in 10 minutes. Do not share this code with anyone.
                </div>
                
                <p>If you did not request this verification code, please ignore this email.</p>
                
                <p>Best regards,<br>
                <strong>PSAU Admission System Team</strong></p>
            </div>
            
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Create HTML email template for password reset OTP
 * @param string $otp_code 6-digit OTP code
 * @return string HTML email content
 */
function create_password_reset_otp_email($otp_code) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Password Reset - PSAU Admission System</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; }
            .otp-code { background: #dc3545; color: white; font-size: 32px; font-weight: bold; text-align: center; padding: 20px; margin: 20px 0; border-radius: 8px; letter-spacing: 5px; }
            .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 12px; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>PSAU Admission System</h1>
                <h2>Password Reset Verification</h2>
            </div>
            
            <div class='content'>
                <h3>Password Reset Request</h3>
                <p>We received a request to reset your password for your PSAU Admission System account. To proceed with the password reset, please use the verification code below:</p>
                
                <div class='otp-code'>$otp_code</div>
                
                <p><strong>Instructions:</strong></p>
                <ol>
                    <li>Enter this 6-digit code in the password reset form</li>
                    <li>Create your new password</li>
                    <li>Log in with your new credentials</li>
                </ol>
                
                <div class='warning'>
                    <strong>Important:</strong> This code will expire in 10 minutes. If you did not request a password reset, please ignore this email and your password will remain unchanged.
                </div>
                
                <p>Best regards,<br>
                <strong>PSAU Admission System Team</strong></p>
            </div>
            
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Create HTML email template for generic OTP
 * @param string $otp_code 6-digit OTP code
 * @return string HTML email content
 */
function create_generic_otp_email($otp_code) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Verification Code - PSAU Admission System</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #28a745; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; }
            .otp-code { background: #28a745; color: white; font-size: 32px; font-weight: bold; text-align: center; padding: 20px; margin: 20px 0; border-radius: 8px; letter-spacing: 5px; }
            .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 12px; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>PSAU Admission System</h1>
                <h2>Verification Code</h2>
            </div>
            
            <div class='content'>
                <h3>Your Verification Code</h3>
                <p>Please use the following verification code to complete your request:</p>
                
                <div class='otp-code'>$otp_code</div>
                
                <div class='warning'>
                    <strong>Important:</strong> This code will expire in 10 minutes. Do not share this code with anyone.
                </div>
                
                <p>If you did not request this verification code, please ignore this email.</p>
                
                <p>Best regards,<br>
                <strong>PSAU Admission System Team</strong></p>
            </div>
            
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Store OTP in session with expiration
 * @param string $email Email address
 * @param string $otp_code 6-digit OTP code
 * @param string $purpose Purpose of OTP
 * @param int $expiry_minutes Expiry time in minutes (default: 10)
 */
function store_otp_session($email, $otp_code, $purpose, $expiry_minutes = 10) {
    $_SESSION['otp_verification'] = [
        'email' => $email,
        'otp_code' => $otp_code,
        'purpose' => $purpose,
        'expires_at' => time() + ($expiry_minutes * 60),
        'attempts' => 0
    ];
}

/**
 * Verify OTP code from session
 * @param string $input_otp User input OTP code
 * @param string $email Email address to verify
 * @param string $purpose Purpose of OTP verification
 * @return array Result with success status and message
 */
function verify_otp_session($input_otp, $email, $purpose) {
    // Check if OTP session exists
    if (!isset($_SESSION['otp_verification'])) {
        return ['success' => false, 'message' => 'No verification code found. Please request a new one.'];
    }
    
    $otp_data = $_SESSION['otp_verification'];
    
    // Check if OTP has expired
    if (time() > $otp_data['expires_at']) {
        unset($_SESSION['otp_verification']);
        return ['success' => false, 'message' => 'Verification code has expired. Please request a new one.'];
    }
    
    // Check email match
    if ($otp_data['email'] !== $email) {
        return ['success' => false, 'message' => 'Email address does not match.'];
    }
    
    // Check purpose match
    if ($otp_data['purpose'] !== $purpose) {
        return ['success' => false, 'message' => 'Invalid verification purpose.'];
    }
    
    // Check attempt limit (max 3 attempts)
    if ($otp_data['attempts'] >= 3) {
        unset($_SESSION['otp_verification']);
        return ['success' => false, 'message' => 'Too many failed attempts. Please request a new verification code.'];
    }
    
    // Verify OTP code
    if ($otp_data['otp_code'] !== $input_otp) {
        // Increment attempts
        $_SESSION['otp_verification']['attempts']++;
        $remaining_attempts = 3 - $_SESSION['otp_verification']['attempts'];
        
        if ($remaining_attempts > 0) {
            return ['success' => false, 'message' => "Invalid verification code. {$remaining_attempts} attempts remaining."];
        } else {
            unset($_SESSION['otp_verification']);
            return ['success' => false, 'message' => 'Too many failed attempts. Please request a new verification code.'];
        }
    }
    
    // OTP verified successfully
    unset($_SESSION['otp_verification']);
    return ['success' => true, 'message' => 'Verification successful.'];
}
