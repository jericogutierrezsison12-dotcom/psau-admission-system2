<?php
/**
 * PSAU Admission System - Login Page
 * Authenticates users and redirects to dashboard
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/api_calls.php';
require_once '../includes/security_functions.php';
require_once '../includes/functions.php'; // Added for remember me functions
require_once '../includes/simple_email.php'; // Added for email fallback
require_once '../includes/encryption.php';

// Redirect if already logged in
redirect_if_logged_in('dashboard.php');

// Initialize variables
$login_identifier = '';
$errors = [];
$block_info = null;

// Check if device is blocked
$device_id = get_device_identifier();
// Run cleanup to remove expired blocks
cleanup_expired_blocks();

// Initial check if device is blocked - only check if blocked, don't track attempt
$stmt = $conn->prepare("SELECT * FROM login_attempts 
                       WHERE device_id = ? 
                       AND is_blocked = 1 
                       AND block_expires > ?");
$stmt->execute([$device_id, date('Y-m-d H:i:s', time())]);

if ($stmt->rowCount() > 0) {
    $block_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $time_left = strtotime($block_data['block_expires']) - time();
    $block_info = [
        'blocked' => true, 
        'expires' => $block_data['block_expires'],
        'minutes_left' => ceil($time_left / 60)
    ];
}

// Check for remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $cookie_parts = explode(':', $_COOKIE['remember_me']);
    
    if (count($cookie_parts) === 2) {
        $selector = $cookie_parts[0];
        $token = $cookie_parts[1];
        
        $user_id = verify_remember_token($conn, $selector, $token);
        
        if ($user_id) {
            // Valid remember me token, set session
            $_SESSION['user_id'] = $user_id;
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            // Invalid remember me token, clear the cookie
            clear_remember_cookie();
        }
    }
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $login_identifier = trim($_POST['login_identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    // Check if device is blocked before processing
    if ($block_info && $block_info['blocked']) {
        $errors['blocked'] = 'Your device has been temporarily blocked due to multiple failed login attempts.';
    } else {
        // Validate form data
        if (empty($login_identifier)) {
            $errors['login_identifier'] = 'Email or mobile number is required';
        }
        
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        }
        
        // If no validation errors, attempt to login
        if (empty($errors)) {
            // Verify reCAPTCHA token only if secret is configured
            $recaptcha_secret = getenv('RECAPTCHA_SECRET') ?: ($_ENV['RECAPTCHA_SECRET'] ?? '');
            $recaptcha_token = $_POST['recaptcha_token'] ?? '';
            if (!empty($recaptcha_secret)) {
                if (!verify_recaptcha($recaptcha_token, 'login')) {
                    $errors['recaptcha'] = 'reCAPTCHA verification failed. Please try again.';
                }
            }
        }
        
        // If no validation errors after reCAPTCHA check, attempt to login
        if (empty($errors)) {
            try {
                // Try fast encrypted equality (may not match due to randomized IVs)
                $stmt = $conn->prepare("SELECT * FROM users WHERE (email = ? OR mobile_number = ?) AND is_verified = 1");
                $encId = enc_contact($login_identifier);
                $stmt->execute([$encId, $encId]);
                $user = $stmt->fetch();
                // Fallback: scan verified users and compare decrypted email/mobile
                if (!$user) {
                    $stmt = $conn->prepare("SELECT * FROM users WHERE is_verified = 1");
                    $stmt->execute();
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $needle = strtolower(trim($login_identifier));
                    foreach ($rows as $row) {
                        $decEmail = '';
                        $decMobile = '';
                        try { $decEmail = dec_contact($row['email'] ?? ''); } catch (Exception $e) { $decEmail = ''; }
                        try { $decMobile = dec_contact($row['mobile_number'] ?? ''); } catch (Exception $e) { $decMobile = ''; }
                        if (strtolower(trim($decEmail)) === $needle || strtolower(trim($decMobile)) === $needle) {
                            $user = $row;
                            break;
                        }
                    }
                }
                
                if ($user && !empty($user['is_blocked']) && (int)$user['is_blocked'] === 1) {
                    $reason = $user['block_reason'] ?? 'Your account has been blocked by the administrator.';
                    $errors['blocked'] = $reason;
                } elseif ($user && password_verify($password, $user['password'])) {
                    // Login successful, set session
                    $_SESSION['user_id'] = $user['id'];
                    
                    // Record successful login and reset failed attempts
                    track_login_attempt($device_id, true);
                    
                    // Handle remember me
                    if ($remember_me) {
                        // Create a new remember token
                        $token_data = create_remember_token($conn, $user['id']);
                        // Set the cookie
                        set_remember_cookie($token_data);
                    } else {
                        // Clear any existing remember me cookie
                        clear_remember_cookie();
                        // Clear any existing remember tokens for this user
                        clear_remember_token($conn, $user['id']);
                    }
                    
                    // Redirect to dashboard
                    header('Location: dashboard.php');
                    exit;
                } else {
                    // Invalid credentials
                    $errors['login'] = 'Invalid credentials. Please check your email/mobile and password.';
                    
                    // Record failed login attempt
                    $block_check = track_login_attempt($device_id, false);
                    if ($block_check['blocked']) {
                        if (isset($block_check['just_blocked']) && $block_check['just_blocked']) {
                            $errors['blocked'] = 'Your device has been blocked for 3 hours due to too many failed login attempts.';
                        } else {
                            $errors['blocked'] = 'Your device is currently blocked. Please try again later.';
                        }
                        $block_info = $block_check;
                    } else {
                        // Show remaining attempts (guard missing key on error paths)
                        $attempts = (is_array($block_check) && isset($block_check['attempts'])) ? (int)$block_check['attempts'] : null;
                        $remaining = (is_array($block_check) && array_key_exists('remaining', $block_check))
                            ? (int)$block_check['remaining']
                            : (isset($attempts) ? max(0, 5 - $attempts) : null);
                        if ($remaining === null) {
                            $errors['attempts'] = 'Failed login attempt.';
                        } else {
                            $errors['attempts'] = "Failed login attempt. You have {$remaining} attempts remaining before your device is blocked.";
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log("Login Error: " . $e->getMessage());
                $errors['login'] = 'An error occurred during login. Please try again.';
            }
        }
    }
}

// Include HTML header and start main content
include_once 'html/login.html';
