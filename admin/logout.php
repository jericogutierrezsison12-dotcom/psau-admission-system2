<?php
/**
 * PSAU Admission System - Admin Logout Page
 * Destroys the admin session and redirects to the admin login page
 */

// Required files
require_once '../includes/db_connect.php';

// Start the session
session_start();

// Get admin ID before clearing session
$admin_id = $_SESSION['admin_id'] ?? null;

// Log admin logout activity if admin was logged in
if ($admin_id) {
    try {
        $stmt = $conn->prepare("INSERT INTO activity_logs (action, user_id, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            'admin_logout',
            $admin_id,
            'Admin logged out successfully',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Error logging admin logout: " . $e->getMessage());
    }
}

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

// Redirect to admin login page
header("Location: login.php");
exit;
?>
