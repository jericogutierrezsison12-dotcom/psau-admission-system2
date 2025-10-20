<?php
/**
 * PSAU Admission System - Logout Page
 * Destroys the user session and redirects to the login page
 */

// Required files
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Start the session
session_start();

// Get user ID before clearing session
$user_id = $_SESSION['user_id'] ?? null;

// Clear remember me token from database if user is logged in
if ($user_id) {
    clear_remember_token($conn, $user_id);
}

// Clear remember me cookie
clear_remember_cookie();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit; 