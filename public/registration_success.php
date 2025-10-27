<?php
/**
 * PSAU Admission System - Registration Success Page
 * Displayed after successful registration and OTP verification
 */

require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/aes_encryption.php';

// Check if user is logged in, otherwise redirect to login
is_user_logged_in();

// Get control number from URL parameter
$control_number = isset($_GET['control_number']) ? $_GET['control_number'] : '';

// Get user details
$user = get_current_user_data($conn);

// If no control number in URL, use the one from user record
if (empty($control_number) && $user) {
    $control_number = $user['control_number'];
}

// Include the HTML template
include('html/registration_success.html');
?> 