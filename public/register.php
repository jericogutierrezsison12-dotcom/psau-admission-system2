<?php
/**
 * PSAU Admission System - Registration Page
 * Allows new applicants to register with OTP verification
 */

// Include the database connection and other required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/otp_attempt_tracking.php';

// Redirect if already logged in
redirect_if_logged_in('dashboard.php');

// Initialize variables
$first_name = '';
$last_name = '';
$email = '';
$mobile_number = '';
$errors = [];

// Determine current step based on session data
if (isset($_SESSION['registration']) && isset($_SESSION['email_otp'])) {
    $step = 2; // OTP verification step
    // Debug: Log session detection
    error_log("Registration - Detected step 2 from session data");
} else {
    $step = 1; // Form step
    // Debug: Log session detection
    error_log("Registration - Detected step 1, session data: " . json_encode([
        'has_registration' => isset($_SESSION['registration']),
        'has_email_otp' => isset($_SESSION['email_otp'])
    ]));
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] == 1) {
        // Get form data
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
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
        
        // Mobile number is no longer required; we'll assign a system-generated placeholder later
        
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
                'password' => $password
            ];
            
            // Move to OTP verification step
            $step = 2;
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
            if (!isset($_SESSION['email_otp']['code'], $_SESSION['email_otp']['expires'])) {
                $errors['otp'] = 'No OTP found. Please resend the code.';
            } elseif (time() > (int)$_SESSION['email_otp']['expires']) {
                $errors['otp'] = 'OTP has expired. Please resend the code.';
            } elseif ($otp_code !== (string)$_SESSION['email_otp']['code']) {
                $errors['otp'] = 'Incorrect OTP. Please try again.';
            }
        }
        
        // Debug: Log session data to see what's happening
        error_log("OTP verification - Session data: " . json_encode($_SESSION));
        error_log("OTP verification - Step: " . $step . ", Errors: " . json_encode($errors));

        if (empty($errors['otp']) && empty($errors['recaptcha'])) {
            // OTP verified, create user account
            try {
                $conn->beginTransaction();
                
                // Get registration data from session
                $registration = $_SESSION['registration'];
                
                // Include control number generator
                require_once '../includes/generate_control_number.php';
                
                // Generate control number
                $control_number = generate_control_number($conn);

                // Generate a unique placeholder mobile number to satisfy NOT NULL + UNIQUE constraint
                $generated_mobile = null;
                for ($i = 0; $i < 5; $i++) {
                    $candidate = '999' . str_pad((string)random_int(0, 9999999), 7, '0', STR_PAD_LEFT); // 10 digits starting with 999
                    $check = $conn->prepare("SELECT COUNT(*) FROM users WHERE mobile_number = ?");
                    $check->execute([$candidate]);
                    if ($check->fetchColumn() == 0) {
                        $generated_mobile = $candidate;
                        break;
                    }
                }
                if ($generated_mobile === null) {
                    throw new Exception('Failed to generate unique placeholder mobile number');
                }
                
                // Hash password
                $hashed_password = password_hash($registration['password'], PASSWORD_DEFAULT);
                
                // Insert user into database
                $stmt = $conn->prepare("INSERT INTO users (control_number, first_name, last_name, email, mobile_number, password, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $control_number,
                    $registration['first_name'],
                    $registration['last_name'],
                    $registration['email'],
                    $generated_mobile,
                    $hashed_password,
                    1 // Verified through OTP
                ]);
                
                $user_id = $conn->lastInsertId();
                
                $conn->commit();
                
                // Set session for the new user
                $_SESSION['user_id'] = $user_id;
                
                // Unset temporary registration data
                unset($_SESSION['registration']);
                unset($_SESSION['email_otp']);
                
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
        } else {
            $step = 2; // Stay on OTP verification step if there are errors
            // Debug: Log the error to see what's happening
            error_log("OTP verification failed. Errors: " . json_encode($errors));
            error_log("OTP verification failed. Step set to: " . $step);
            error_log("OTP verification failed. Session data after error: " . json_encode([
                'has_registration' => isset($_SESSION['registration']),
                'has_email_otp' => isset($_SESSION['email_otp']),
                'registration_data' => $_SESSION['registration'] ?? null,
                'email_otp_data' => $_SESSION['email_otp'] ?? null
            ]));
        }
    }

// Include the HTML template
include('html/register.html');
?> 