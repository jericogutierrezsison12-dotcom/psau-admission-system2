<?php
/**
 * PSAU Admission System - Registration Page
 * Email-based 6-digit OTP after reCAPTCHA
 */

// Dependencies
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/api_calls.php'; // verify_recaptcha, sendEmailViaFirebase

// Redirect if already logged in
redirect_if_logged_in('dashboard.php');

// State
$first_name = '';
$last_name = '';
$email = '';
$errors = [];
$step = 1; // 1: form, 2: OTP

// Helpers
function generate_email_otp_code() {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function send_registration_otp_email($to_email, $otp_code) {
    $subject = 'Your PSAU verification code';
    $html = '<p>Use this 6-digit code to verify your registration:</p>' .
            '<h2 style="letter-spacing:4px;">' . htmlspecialchars($otp_code) . '</h2>' .
            '<p>This code expires in 10 minutes. Do not share it.</p>';
    if (!function_exists('sendEmailViaFirebase')) {
        return ['success' => false, 'message' => 'Email service unavailable'];
    }
    return sendEmailViaFirebase([
        'to' => $to_email,
        'subject' => $subject,
        'message' => $html
    ]);
}

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] == '1') {
        // Gather
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $confirm_password = (string)($_POST['confirm_password'] ?? '');
        $recaptcha_token = trim($_POST['recaptcha_token'] ?? '');

        // Validate
        if ($first_name === '') { $errors['first_name'] = 'First name is required'; }
        if ($last_name === '') { $errors['last_name'] = 'Last name is required'; }
        if ($email === '') { $errors['email'] = 'Email is required'; }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Please enter a valid email address'; }
        else {
            $stmt = $conn->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) { $errors['email'] = 'Email is already registered'; }
        }
        if ($password === '') { $errors['password'] = 'Password is required'; }
        elseif (strlen($password) < 8) { $errors['password'] = 'Password must be at least 8 characters long'; }
        elseif (!preg_match('/[A-Z]/', $password)) { $errors['password'] = 'Password must contain at least one uppercase letter'; }
        elseif (!preg_match('/[a-z]/', $password)) { $errors['password'] = 'Password must contain at least one lowercase letter'; }
        elseif (!preg_match('/[0-9]/', $password)) { $errors['password'] = 'Password must contain at least one number'; }
        elseif (!preg_match('/[^A-Za-z0-9]/', $password)) { $errors['password'] = 'Password must contain at least one special character'; }
        if ($password !== $confirm_password) { $errors['confirm_password'] = 'Passwords do not match'; }

        // reCAPTCHA
        if (empty($errors)) {
            if ($recaptcha_token === '') {
                $errors['recaptcha'] = 'Please complete the reCAPTCHA verification.';
            } elseif (!verify_recaptcha($recaptcha_token)) {
                $errors['recaptcha'] = 'reCAPTCHA verification failed. Please try again.';
            }
        }

        if (empty($errors)) {
            // Store registration
            $_SESSION['registration'] = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'password' => $password
            ];

            // Create and send OTP
            $otp_code = generate_email_otp_code();
            $_SESSION['email_otp'] = [
                'email' => $email,
                'code' => $otp_code,
                'expires_at' => time() + (10 * 60),
                'attempts' => 0
            ];

            $send = send_registration_otp_email($email, $otp_code);
            if (is_array($send) && !empty($send['success'])) {
                $step = 2;
            } else {
                $errors['email'] = 'Failed to send verification email. Please try again.';
            }
        }
    } elseif (isset($_POST['step']) && $_POST['step'] == '2') {
        // Verify OTP
        $otp_code = trim($_POST['otp_code'] ?? '');
        if ($otp_code === '' || strlen($otp_code) !== 6 || !ctype_digit($otp_code)) {
            $errors['otp'] = 'Enter a valid 6-digit code';
        } else {
            $otp = $_SESSION['email_otp'] ?? null;
            $registration = $_SESSION['registration'] ?? null;
            if (!$otp || !$registration) {
                $errors['registration'] = 'Session expired. Please start again.';
                $step = 1;
            } elseif (time() > ($otp['expires_at'] ?? 0)) {
                unset($_SESSION['email_otp']);
                $errors['otp'] = 'Code expired. Please restart registration.';
            } elseif (($otp['email'] ?? '') !== $registration['email']) {
                $errors['otp'] = 'Email mismatch. Please restart registration.';
            } elseif (($otp['attempts'] ?? 0) >= 3) {
                unset($_SESSION['email_otp']);
                $errors['otp'] = 'Too many attempts. Please restart registration.';
            } elseif ($otp['code'] !== $otp_code) {
                $_SESSION['email_otp']['attempts'] = ($otp['attempts'] ?? 0) + 1;
                $remaining = 3 - $_SESSION['email_otp']['attempts'];
                $errors['otp'] = $remaining > 0 ? 'Invalid code. ' . $remaining . ' attempts remaining.' : 'Too many attempts. Please restart registration.';
                if ($remaining <= 0) { unset($_SESSION['email_otp']); }
            } else {
                // Success: create user
                try {
                    $conn->beginTransaction();
                    require_once '../includes/generate_control_number.php';
                    $control_number = generate_control_number($conn);
                    $hashed_password = password_hash($registration['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('INSERT INTO users (control_number, first_name, last_name, email, password, is_verified) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([
                        $control_number,
                        $registration['first_name'],
                        $registration['last_name'],
                        $registration['email'],
                        $hashed_password,
                        1
                    ]);
                    $user_id = $conn->lastInsertId();
                    $conn->commit();
                    $_SESSION['user_id'] = $user_id;
                    unset($_SESSION['registration'], $_SESSION['email_otp']);
                    header('Location: registration_success.php?control_number=' . $control_number);
                    exit;
                } catch (PDOException $e) {
                    $conn->rollBack();
                    error_log('Registration Error: ' . $e->getMessage());
                    $errors['registration'] = 'An error occurred during registration. Please try again.';
                    $step = 1;
                }
            }
        }
    }
}

// Render view
include('html/register.html');
?>