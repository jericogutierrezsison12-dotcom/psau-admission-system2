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
            // Verify reCAPTCHA token
            $recaptcha_token = $_POST['recaptcha_token'] ?? '';
            if (!verify_recaptcha($recaptcha_token, 'login')) {
                $errors['recaptcha'] = 'reCAPTCHA verification failed. Please try again.';
            }
        }
        
        // If no validation errors after reCAPTCHA check, attempt to login
        if (empty($errors)) {
            try {
                // First, try direct database query (for backwards compatibility with unencrypted data)
                // This will work if data is not encrypted
                $stmt = $conn->prepare("SELECT * FROM users WHERE (email = ? OR mobile_number = ?) AND is_verified = 1 LIMIT 1");
                $stmt->execute([$login_identifier, $login_identifier]);
                $user = $stmt->fetch();
                
                // If not found with direct query, try encrypted lookup
                if (!$user) {
                    error_log("Login: Direct query found no user, trying encrypted lookup...");
                    $user = find_user_by_encrypted_identifier($conn, $login_identifier);
                } else {
                    error_log("Login: User found via direct query - ID: " . $user['id']);
                    // Check if functions.php is loaded (contains looks_encrypted function)
                    if (function_exists('looks_encrypted')) {
                        // Try to decrypt the data if it looks encrypted
                        if (!empty($user['email']) && looks_encrypted($user['email'])) {
                            try {
                                $user['email'] = decryptContactData($user['email']);
                                error_log("Login: Decrypted email successfully");
                            } catch (Exception $e) {
                                error_log("Login: Could not decrypt email: " . $e->getMessage());
                            }
                        }
                        if (!empty($user['mobile_number']) && looks_encrypted($user['mobile_number'])) {
                            try {
                                $user['mobile_number'] = decryptContactData($user['mobile_number']);
                                error_log("Login: Decrypted mobile successfully");
                            } catch (Exception $e) {
                                error_log("Login: Could not decrypt mobile: " . $e->getMessage());
                            }
                        }
                    } else {
                        error_log("Login: looks_encrypted function not available, skipping decryption check");
                    }
                }
                
                // Debug logging
                if (!$user) {
                    error_log("Login attempt: User not found for identifier: " . substr($login_identifier, 0, 5) . "...");
                    // Check if encryption key might be the issue
                    if (empty(getenv('ENCRYPTION_KEY')) && empty($_ENV['ENCRYPTION_KEY'])) {
                        error_log("CRITICAL: ENCRYPTION_KEY is not set! This will cause decryption to fail.");
                    }
                } else {
                    error_log("Login attempt: User found - ID: " . $user['id'] . ", Email: " . substr($user['email'] ?? 'N/A', 0, 5) . "...");
                    error_log("Login attempt: Password verification - User has password hash: " . (!empty($user['password']) ? 'Yes' : 'No'));
                }
                
                if ($user && !empty($user['is_blocked']) && (int)$user['is_blocked'] === 1) {
                    $reason = $user['block_reason'] ?? 'Your account has been blocked by the administrator.';
                    $errors['blocked'] = $reason;
                } elseif ($user && password_verify($password, $user['password'])) {
                    error_log("Login attempt: Password verified successfully for user ID: " . $user['id']);
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
                    if ($user) {
                        error_log("Login attempt: Password verification FAILED for user ID: " . $user['id']);
                        error_log("Login attempt: Provided password length: " . strlen($password));
                        error_log("Login attempt: Stored password hash starts with: " . substr($user['password'] ?? 'N/A', 0, 10));
                        
                        // Only track login attempts if user exists (valid email/mobile)
                        $block_check = track_login_attempt($device_id, false);
                        if ($block_check['blocked']) {
                            if (isset($block_check['just_blocked']) && $block_check['just_blocked']) {
                                $errors['blocked'] = 'Your device has been blocked for 3 hours due to too many failed login attempts.';
                            } else {
                                $errors['blocked'] = 'Your device is currently blocked. Please try again later.';
                            }
                            $block_info = $block_check;
                        } else {
                            // Show remaining attempts (check if 'remaining' key exists)
                            $remaining = $block_check['remaining'] ?? 0;
                            if ($remaining > 0) {
                                $errors['attempts'] = "Failed login attempt. You have {$remaining} attempts remaining before your device is blocked.";
                            } else {
                                $errors['login'] = 'Invalid credentials. Please check your email/mobile and password.';
                            }
                        }
                    } else {
                        error_log("Login attempt: No user found OR password verification failed");
                        // User doesn't exist - don't track login attempts to prevent enumeration
                        $errors['login'] = 'Invalid credentials. Please check your email/mobile and password.';
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
