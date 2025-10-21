<?php
/**
 * PSAU Admission System - Admin Login Page
 * Authenticates admin users and redirects to admin dashboard
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/admin_auth.php';
require_once '../includes/security_functions.php';
require_once '../includes/api_calls.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in as admin
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Initialize variables
$username = '';
$errors = [];
$block_info = null;

// Debug: Log that the page is loaded
error_log('Admin login page loaded');

// Check if device is blocked
$device_id = get_device_identifier();
// Run cleanup to remove expired blocks
cleanup_expired_blocks();

// Initial check if device is blocked - only check if blocked, don't track attempt
$stmt = $conn->prepare("SELECT * FROM admin_login_attempts 
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

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('Admin login form submitted');
    error_log('POST data: ' . json_encode($_POST));
    
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    error_log('Form data - Username: ' . $username . ', Password length: ' . strlen($password));
    
    // Check if device is blocked before processing
    if ($block_info && $block_info['blocked']) {
        $errors['blocked'] = 'Your device has been temporarily blocked due to multiple failed login attempts.';
        error_log('Device blocked, cannot process login');
    } else {
        // Validate form data
        if (empty($username)) {
            $errors['username'] = 'Username is required';
            error_log('Username validation failed: empty');
        }
        
        if (empty($password)) {
            $errors['password'] = 'Password is required';
            error_log('Password validation failed: empty');
        }
        
        // If no validation errors, attempt to login
        if (empty($errors)) {
            error_log('No validation errors, attempting login...');
            try {
                // Check if admin exists with the provided username
                $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();
                
                if ($admin && password_verify($password, $admin['password'])) {
                    error_log('Admin login successful for username: ' . $username);
                    
                    // Login successful, set session
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
                    
                    // Record successful login and reset failed attempts
                    track_admin_login_attempt($device_id, true);
                    
                    // Log admin login activity
                    $stmt = $conn->prepare("INSERT INTO activity_logs (action, user_id, details, ip_address) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        'admin_login',
                        $admin['id'],
                        'Admin logged in successfully',
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                    
                    // Redirect to admin dashboard
                    header('Location: dashboard.php');
                    exit;
                } else {
                    error_log('Admin login failed - Invalid credentials for username: ' . $username);
                    
                    // Invalid credentials
                    $errors['login'] = 'Invalid username or password.';
                    
                    // Record failed login attempt
                    $block_check = track_admin_login_attempt($device_id, false);
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
                }
            } catch (PDOException $e) {
                error_log("Admin Login Error: " . $e->getMessage());
                $errors['login'] = 'An error occurred during login. Please try again.';
            }
        } else {
            error_log('Validation errors found: ' . json_encode($errors));
        }
    }
} else {
    error_log('Admin login page accessed via GET method');
}

// Include HTML header and start main content
include_once 'html/login.html';
?>
