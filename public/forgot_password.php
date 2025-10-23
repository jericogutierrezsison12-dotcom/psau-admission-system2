<?php
/**
 * PSAU Admission System - Forgot Password Page
 * Allows users to reset their password using OTP sent via email
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/functions.php';
require_once '../includes/otp_attempt_tracking.php';

// Redirect if already logged in
redirect_if_logged_in('dashboard.php');

// Initialize variables
$email = '';
$errors = [];
$step = 1; // Step 1: Email, Step 2: OTP Verification, Step 3: New Password
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] == 1) {
        // Get email from form
        $email = trim($_POST['email'] ?? '');
        
        // Validate email
        if (empty($email)) {
            $errors['email'] = 'Email address is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } else {
            // Check if user exists with this email
            $stmt = $conn->prepare("SELECT id, first_name, last_name, email, mobile_number FROM users WHERE email = ? AND is_verified = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $errors['email'] = 'No verified account found with this email address';
            } else {
                // Store user data in session for later use
                $_SESSION['password_reset'] = [
                    'user_id' => $user['id'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'mobile_number' => $user['mobile_number'],
                    'timestamp' => time()
                ];
                
                // Move to OTP verification step
                $step = 2;
            }
        }
    } elseif (isset($_POST['step']) && $_POST['step'] == 2) {
        // Process OTP verification
        $otp_code = trim($_POST['otp_code'] ?? '');
        $recaptcha_verified = $_POST['recaptcha_verified'] ?? '';

        // Check reCAPTCHA verification
        if ($recaptcha_verified !== 'true') {
            $errors['recaptcha'] = 'reCAPTCHA verification is required';
        }

        // Validate OTP format
        if ($otp_code === '') {
            $errors['otp'] = 'OTP code is required';
        } elseif (!preg_match('/^\d{6}$/', $otp_code)) {
            $errors['otp'] = 'Invalid OTP format';
        }

        // Check OTP from session (same logic as admin registration)
        if (empty($errors['otp']) && empty($errors['recaptcha'])) {
            if (!isset($_SESSION['password_reset']['otp_code'], $_SESSION['password_reset']['otp_expires'])) {
                $errors['otp'] = 'No OTP found. Please resend the code.';
            } elseif (time() > (int)$_SESSION['password_reset']['otp_expires']) {
                $errors['otp'] = 'OTP has expired. Please resend the code.';
            } elseif ($otp_code !== (string)$_SESSION['password_reset']['otp_code']) {
                $errors['otp'] = 'Incorrect OTP. Please try again.';
            }
        }
        
        // Debug: Log session data to see what's happening
        error_log("OTP verification - Session data: " . json_encode($_SESSION));
        error_log("OTP verification - Step: " . $step . ", Errors: " . json_encode($errors));

        if (empty($errors['otp']) && empty($errors['recaptcha'])) {
            // OTP verified, move to password reset step
            $step = 3;
        } else {
            $step = 2; // Stay on OTP verification step if there are errors
            // Debug: Log the error to see what's happening
            error_log("OTP verification failed. Errors: " . json_encode($errors));
        }
    } elseif (isset($_POST['step']) && $_POST['step'] == 3) {
        // Process password reset
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate password
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = 'Password must contain at least one uppercase letter';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors['password'] = 'Password must contain at least one lowercase letter';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one number';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one special character';
        }
        
        if ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
        
        // Check if password reset session exists
        if (!isset($_SESSION['password_reset']) || !isset($_SESSION['password_reset']['user_id'])) {
            $errors['session'] = 'Password reset session expired. Please start again.';
            $step = 1;
        } else {
            // Check if session is expired (30 minutes)
            $session_age = time() - $_SESSION['password_reset']['timestamp'];
            if ($session_age > 1800) { // 30 minutes
                $errors['session'] = 'Password reset session expired. Please start again.';
                unset($_SESSION['password_reset']);
                $step = 1;
            }
        }
        
        // If no errors, update password
        if (empty($errors)) {
            try {
                $user_id = $_SESSION['password_reset']['user_id'];
                
                // Hash new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Update user password
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                // Clear any existing remember tokens for this user
                clear_remember_token($conn, $user_id);
                
                // Success message
                $success = true;
                $step = 4; // Move to success step
                
                // Unset password reset session
                unset($_SESSION['password_reset']);
            } catch (PDOException $e) {
                error_log("Password Reset Error: " . $e->getMessage());
                $errors['reset'] = 'An error occurred during password reset. Please try again.';
            }
        }
    }
}

// Include the HTML template
include_once 'html/forgot_password.html';