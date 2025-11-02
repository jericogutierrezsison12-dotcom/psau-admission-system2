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

// Check if device is blocked (only if database connection is available)
$device_id = get_device_identifier();
$block_info = null;

if (isset($conn) && $conn !== null) {
    try {
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
    } catch (Exception $e) {
        // Database error, continue without blocking check
        error_log("Error checking device block status: " . $e->getMessage());
    }
}

// Check for remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    // Check if database connection is available before using it
    if (isset($conn) && $conn !== null) {
        $cookie_parts = explode(':', $_COOKIE['remember_me']);
        
        if (count($cookie_parts) === 2) {
            $selector = $cookie_parts[0];
            $token = $cookie_parts[1];
            
            try {
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
            } catch (Exception $e) {
                // Database error, clear cookie to prevent loops
                error_log("Remember me check error in login: " . $e->getMessage());
                clear_remember_cookie();
            }
        }
    } else {
        // Database connection not available, clear cookie
        clear_remember_cookie();
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
            
            // Skip reCAPTCHA on localhost for development
            $is_localhost = (
                strpos($_SERVER['SERVER_NAME'] ?? '', 'localhost') !== false ||
                $_SERVER['SERVER_NAME'] === '127.0.0.1' ||
                strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
                $_SERVER['HTTP_HOST'] === '127.0.0.1'
            );
            
            if (!$is_localhost && !empty($recaptcha_token)) {
                try {
                    $recaptcha_result = verify_recaptcha($recaptcha_token, 'login');
                    if (!$recaptcha_result) {
                        $errors['recaptcha'] = 'reCAPTCHA verification failed. Please try again.';
                    }
                } catch (Exception $recaptcha_error) {
                    error_log("reCAPTCHA verification error: " . $recaptcha_error->getMessage());
                    // Don't block login on reCAPTCHA errors, but log them
                    // $errors['recaptcha'] = 'reCAPTCHA verification error. Please try again.';
                }
            } elseif (!$is_localhost && empty($recaptcha_token)) {
                $errors['recaptcha'] = 'reCAPTCHA token is missing. Please refresh the page and try again.';
            }
            // If localhost, skip reCAPTCHA check
        }
        
        // If no validation errors after reCAPTCHA check, attempt to login
        if (empty($errors)) {
            // Check if database connection is available
            if (!isset($conn) || $conn === null) {
                $errors['login'] = 'Database connection unavailable. Please try again later.';
            } else {
                try {
                    // Fetch all verified users to check encrypted email/mobile
                    // Limit to prevent performance issues
                    $stmt = $conn->prepare("SELECT id, email, mobile_number, password, is_blocked, block_reason, is_verified FROM users WHERE is_verified = 1 LIMIT 1000");
                    $stmt->execute();
                    $users = $stmt->fetchAll();
                    
                    $user = null;
                    // Decrypt and compare email/mobile_number with login_identifier
                    // Use a counter to prevent infinite loops
                    $max_iterations = min(count($users), 1000);
                    for ($i = 0; $i < $max_iterations; $i++) {
                        $u = $users[$i];
                        try {
                            $decrypted_email = !empty($u['email']) ? decrypt_data($u['email']) : '';
                            $decrypted_mobile = !empty($u['mobile_number']) ? decrypt_data($u['mobile_number']) : '';
                            
                            if (($decrypted_email === $login_identifier || $decrypted_mobile === $login_identifier)) {
                                $user = $u;
                                break;
                            }
                        } catch (Exception $decrypt_error) {
                            // Skip this user if decryption fails, continue to next
                            error_log("Decryption error for user {$u['id']}: " . $decrypt_error->getMessage());
                            continue;
                        }
                    }
                    
                    // If user not found in limited set, try a different approach - get full user data
                    if (!$user) {
                        // Fetch the full user record if we found a match
                        // We'll need to search again or use a different method
                        // For now, if not found, user remains null and will show invalid credentials
                    }
                    
                    if ($user && !empty($user['is_blocked']) && (int)$user['is_blocked'] === 1) {
                        $reason = $user['block_reason'] ?? 'Your account has been blocked by the administrator.';
                        $errors['blocked'] = $reason;
                    } elseif ($user && password_verify($password, $user['password'])) {
                        // Login successful - get full user data before decrypting
                        $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                        $user_stmt->execute([$user['id']]);
                        $user = $user_stmt->fetch();
                        
                        // Decrypt user data after successful login
                        $user = decrypt_user_data($user);
                        
                        // Login successful, set session
                        $_SESSION['user_id'] = $user['id'];
                        
                        // Record successful login and reset failed attempts
                        try {
                            track_login_attempt($device_id, true);
                        } catch (Exception $e) {
                            error_log("Error tracking login attempt: " . $e->getMessage());
                        }
                        
                        // Handle remember me
                        if ($remember_me) {
                            try {
                                // Create a new remember token
                                $token_data = create_remember_token($conn, $user['id']);
                                // Set the cookie
                                set_remember_cookie($token_data);
                            } catch (Exception $e) {
                                error_log("Error creating remember token: " . $e->getMessage());
                            }
                        } else {
                            try {
                                // Clear any existing remember me cookie
                                clear_remember_cookie();
                                // Clear any existing remember tokens for this user
                                clear_remember_token($conn, $user['id']);
                            } catch (Exception $e) {
                                error_log("Error clearing remember token: " . $e->getMessage());
                            }
                        }
                        
                        // Redirect to dashboard - use relative URL (works better)
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        // Invalid credentials
                        $errors['login'] = 'Invalid credentials. Please check your email/mobile and password.';
                        
                        // Record failed login attempt
                        try {
                            $block_check = track_login_attempt($device_id, false);
                            if ($block_check['blocked']) {
                                if (isset($block_check['just_blocked']) && $block_check['just_blocked']) {
                                    $errors['blocked'] = 'Your device has been blocked for 3 hours due to too many failed login attempts.';
                                } else {
                                    $errors['blocked'] = 'Your device is currently blocked. Please try again later.';
                                }
                                $block_info = $block_check;
                            } else {
                                // Show remaining attempts
                                $errors['attempts'] = "Failed login attempt. You have {$block_check['remaining']} attempts remaining before your device is blocked.";
                            }
                        } catch (Exception $track_error) {
                            error_log("Error tracking login attempt: " . $track_error->getMessage());
                            // Continue without blocking if tracking fails
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Login Error: " . $e->getMessage());
                    $errors['login'] = 'An error occurred during login. Please try again.';
                }
            }
        }
    }
}

// Include HTML header and start main content
include_once 'html/login.html';
