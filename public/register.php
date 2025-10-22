<?php
/**
 * PSAU Admission System - Registration Page
 * Allows new applicants to register with OTP verification
 */

// Include the database connection and other required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/simple_email.php'; // Added for email fallback
require_once '../includes/email_otp.php'; // Added for email OTP

// Redirect if already logged in
redirect_if_logged_in('dashboard.php');

// Initialize variables
$first_name = '';
$last_name = '';
$email = '';
$mobile_number = '';
$errors = [];
$step = 1; // Step 1: Form, Step 2: OTP Verification

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] == 1) {
        // Get form data
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile_number = trim($_POST['mobile_number'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate form data
        if (empty($first_name)) {
            $errors['first_name'] = 'First name is required';
        }
        
        if (empty($last_name)) {
            $errors['last_name'] = 'Last name is required';
        }
        
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $errors['email'] = 'Email is already registered';
            }
        }
        
        if (empty($mobile_number)) {
            $errors['mobile_number'] = 'Mobile number is required';
        } elseif (!preg_match('/^\+?[0-9]{10,15}$/', $mobile_number)) {
            $errors['mobile_number'] = 'Please enter a valid mobile number';
        } else {
            // Check if mobile number already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE mobile_number = ?");
            $stmt->execute([$mobile_number]);
            if ($stmt->fetchColumn() > 0) {
                $errors['mobile_number'] = 'Mobile number is already registered';
            }
        }
        
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

        // If no errors, proceed to OTP verification
        if (empty($errors)) {
            // Store form data in session for later use
            $_SESSION['registration'] = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'mobile_number' => $mobile_number,
                'password' => $password
            ];
            
            // Generate and send email OTP
            $otp_code = generate_otp_code();
            $email_sent = send_otp_email($email, $otp_code, 'registration');
            
            if ($email_sent) {
                // Store OTP in session
                store_otp_session($email, $otp_code, 'registration');
                $step = 2;
            } else {
                $errors['email'] = 'Failed to send verification email. Please try again.';
            }
        }
    } elseif (isset($_POST['step']) && $_POST['step'] == 2) {
        // Process OTP verification
        $otp_code = trim($_POST['otp_code'] ?? '');
        
        if (empty($otp_code)) {
            $errors['otp'] = 'OTP code is required';
        } else {
            // Verify email OTP
            $registration = $_SESSION['registration'];
            $verification_result = verify_otp_session($otp_code, $registration['email'], 'registration');
            
            if (!$verification_result['success']) {
                $errors['otp'] = $verification_result['message'];
            } else {
                // OTP verified, create user account
            try {
                $conn->beginTransaction();
                
                // Get registration data from session
                $registration = $_SESSION['registration'];
                
                // Include control number generator
                require_once '../includes/generate_control_number.php';
                
                // Generate control number
                $control_number = generate_control_number($conn);
                
                // Hash password
                $hashed_password = password_hash($registration['password'], PASSWORD_DEFAULT);
                
                // Insert user into database
                $stmt = $conn->prepare("INSERT INTO users (control_number, first_name, last_name, email, mobile_number, password, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $control_number,
                    $registration['first_name'],
                    $registration['last_name'],
                    $registration['email'],
                    $registration['mobile_number'],
                    $hashed_password,
                    1 // Verified through OTP
                ]);
                
                $user_id = $conn->lastInsertId();
                
                $conn->commit();
                
                // Set session for the new user
                $_SESSION['user_id'] = $user_id;
                
                // Unset temporary registration data
                unset($_SESSION['registration']);
                
                // Redirect to success page
                header('Location: registration_success.php?control_number=' . $control_number);
                exit;
            } catch (PDOException $e) {
                $conn->rollBack();
                error_log("Registration Error: " . $e->getMessage());
                $errors['registration'] = 'An error occurred during registration. Please try again.';
                $step = 1; // Go back to form
            }
        }
            }
        }
    }

// Include the HTML template
include('html/register.html');
?> 