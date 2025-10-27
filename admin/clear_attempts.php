<?php
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/aes_encryption.php';
require_once '../includes/admin_auth.php';
require_once '../includes/functions.php';

// Restrict to admin role only
require_page_access('clear_attempts');

$message = '';
$success = false;
$attempts = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $control_number = trim($_POST['control_number'] ?? '');
    if ($control_number === '') {
        $message = 'Please enter a control number.';
    } else {
        try {
            // Find user by control number
            $stmt = $conn->prepare("SELECT id, control_number, first_name, last_name FROM users WHERE control_number = ?");
            $stmt->execute([$control_number]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $message = 'User not found for control number.';
            } else {
                // Clear attempts
                $stmt = $conn->prepare("DELETE FROM application_attempts WHERE user_id = ?");
                $stmt->execute([$user['id']]);

                // Log activity
                $admin_id = $_SESSION['admin_id'];
                log_admin_activity($conn, $admin_id, 'clear_attempts', 'Cleared application attempts for ' . $control_number);

                $message = 'Cleared application attempts for ' . htmlspecialchars($control_number) . '.';
                $success = true;
            }
        } catch (Exception $e) {
            error_log('Clear attempts error: ' . $e->getMessage());
            $message = 'Error clearing attempts.';
        }
    }
}

// If GET with control number, show current attempts
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['control_number'])) {
    $cn = trim($_GET['control_number']);
    if ($cn !== '') {
        $stmt = $conn->prepare("SELECT id FROM users WHERE control_number = ?");
        $stmt->execute([$cn]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $stmt = $conn->prepare("SELECT attempt_date, was_successful, pdf_message FROM application_attempts WHERE user_id = ? ORDER BY attempt_date DESC");
            $stmt->execute([$user['id']]);
            $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

include 'html/clear_attempts.html';
?>


