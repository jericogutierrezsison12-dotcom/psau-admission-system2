<?php
/**
 * PSAU Admission System - User Profile
 * Allows users to view and update their profile information
 */

// Include the database connection and session checker
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/encryption.php';

// Check if user is logged in
// No need to call session_start() as it's already called in session_checker.php
if (!isset($_SESSION['user_id'])) {
    // Debug: Session missing
    error_log('Session user_id missing in profile.php');
    header('Location: login.php');
    exit;
}

// Get current user data (includes educational background from applications table)
$user = get_current_user_data($conn);
if (!$user) {
    // Debug: User data missing
    error_log('User data missing in profile.php for user_id: ' . ($_SESSION['user_id'] ?? 'none'));
    header('Location: login.php');
    exit;
}

// Initialize variables
$message = '';
$messageType = 'success';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $birth_date = trim($_POST['birth_date'] ?? '');
        $mobile_number = trim($_POST['mobile_number'] ?? '');
        // Age is auto-calculated from birth_date, so we don't need to process it from POST
        $age = '';
        $current_password = trim($_POST['current_password'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        // Validate required fields
        if (empty($first_name) || empty($last_name)) {
            throw new Exception('First name and last name are required.');
        }

        // Initialize SQL parts
        $sql_parts = [];
        $params = [];
        
        // Encrypt personal info fields before saving
        $sql_parts[] = "first_name = ?";
        $params[] = encryptPersonalData($first_name);
        
        $sql_parts[] = "last_name = ?";
        $params[] = encryptPersonalData($last_name);
        
        $sql_parts[] = "gender = ?";
        $params[] = encryptPersonalData($gender);
        
        $sql_parts[] = "birth_date = ?";
        $params[] = !empty($birth_date) ? encryptPersonalData($birth_date) : null;
        
        $sql_parts[] = "mobile_number = ?";
        $params[] = encryptContactData($mobile_number);
        

        // Check if password change was requested
        if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
            // All password fields must be filled
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception('All password fields are required to change your password.');
            }

            // Verify current password
            $verify_sql = "SELECT password FROM users WHERE id = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->execute([$user['id']]);
            $stored_password = $verify_stmt->fetchColumn();

            if (!password_verify($current_password, $stored_password)) {
                throw new Exception('Current password is incorrect.');
            }

            // Validate new password
            if (strlen($new_password) < 8) {
                throw new Exception('Password must be at least 8 characters long');
            } elseif (!preg_match('/[A-Z]/', $new_password)) {
                throw new Exception('Password must contain at least one uppercase letter');
            } elseif (!preg_match('/[a-z]/', $new_password)) {
                throw new Exception('Password must contain at least one lowercase letter');
            } elseif (!preg_match('/[0-9]/', $new_password)) {
                throw new Exception('Password must contain at least one number');
            } elseif (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
                throw new Exception('Password must contain at least one special character');
            }

            // Confirm passwords match
            if ($new_password !== $confirm_password) {
                throw new Exception('New passwords do not match');
            }

            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_parts[] = "password = ?";
            $params[] = $hashed_password;
        }

        // Add user ID to parameters
        $params[] = $user['id'];

        // Build and execute the query for users table
        $sql = "UPDATE users SET " . implode(", ", $sql_parts) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt->execute($params)) {
            throw new Exception('Failed to update profile. Please try again.');
        }
        

        // Log the activity
        $activity_sql = "INSERT INTO activity_logs (action, user_id, details, ip_address) VALUES (?, ?, ?, ?)";
        $activity_stmt = $conn->prepare($activity_sql);
        $activity_stmt->execute([
            'profile_update',
            $user['id'],
            !empty($new_password) ? 'User updated profile information and password' : 'User updated profile information',
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        $message = !empty($new_password) ? 
            'Profile and password updated successfully. You will be logged out for security reasons.' : 
            'Profile updated successfully.';
        $messageType = 'success';

        // If password was changed, destroy session and redirect to login
        if (!empty($new_password)) {
            session_destroy();
            header('Location: login.php?message=' . urlencode('Password changed successfully. Please log in with your new password.'));
            exit;
        }

        // Refresh user data
        $user = get_current_user_data($conn);
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Include the HTML template
include 'html/profile.html';
?>