<?php
/**
 * PSAU Admission System - Forgot Password Page
 * Allows users to reset their password using OTP sent via SMS
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/functions.php';

// Redirect if already logged in
redirect_if_logged_in('dashboard.php');

// Initialize variables
$mobile_number = '';
$errors = [];
$step = 1; // Step 1: Mobile Number, Step 2: OTP Verification, Step 3: New Password
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] == 1) {
        // Get mobile number from form
        $mobile_number = trim($_POST['mobile_number'] ?? '');
        
        // Validate mobile number
        if (empty($mobile_number)) {
            $errors['mobile_number'] = 'Mobile number is required';
        } elseif (!preg_match('/^\d{9,15}$/', $mobile_number)) {
            $errors['mobile_number'] = 'Please enter a valid mobile number (9-15 digits)';
        } else {
            // Format mobile number for search - remove any potential formatting
            $search_mobile = preg_replace('/[^0-9]/', '', $mobile_number);
            
            // If the number starts with a '0', also try without it
            if (strlen($search_mobile) > 0 && $search_mobile[0] === '0') {
                $search_mobile_no_zero = substr($search_mobile, 1);
            } else {
                $search_mobile_no_zero = $search_mobile;
            }
            
            // Try different formats (with and without leading digits)
            $stmt = $conn->prepare("SELECT id, first_name, last_name, email, mobile_number FROM users 
                                   WHERE mobile_number = ? 
                                   OR mobile_number = ? 
                                   OR mobile_number = ? 
                                   OR mobile_number = ? 
                                   OR mobile_number = ? 
                                   OR mobile_number LIKE ? 
                                   OR mobile_number LIKE ?");
            
            $stmt->execute([
                $search_mobile,               // Exactly as entered
                '+63'.$search_mobile,         // With +63 prefix
                '+63'.$search_mobile_no_zero, // With +63 but no leading zero
                '0'.$search_mobile_no_zero,   // With leading zero
                $search_mobile_no_zero,       // Without leading zero
                '%'.$search_mobile_no_zero,   // Ends with number without zero
                '%'.$search_mobile           // Ends with number as entered
            ]);
            
            $user = $stmt->fetch();
            
            if (!$user) {
                $errors['mobile_number'] = 'No account found with this mobile number';
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
        
        if (empty($otp_code)) {
            $errors['otp'] = 'OTP code is required';
        } elseif (!isset($_POST['firebase_verified']) || $_POST['firebase_verified'] !== 'true') {
            $errors['otp'] = 'OTP verification failed. Please try again.';
        } else {
            // OTP verified, move to password reset step
            $step = 3;
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