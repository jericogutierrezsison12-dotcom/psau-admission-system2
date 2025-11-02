<?php
/**
 * PSAU Admission System - Verify Applications
 * Page for administrators to review and verify submitted applications
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';
require_once '../includes/encryption.php';
require_once '../firebase/firebase_email.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    safe_redirect('login.php');
}

// Ensure only admin and registrar users can access verify applications
require_page_access('verify_applications');

// Get admin details
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all pending applications
$pending_applications = [];
try {
    $stmt = $conn->query("SELECT a.*, u.first_name, u.last_name, u.email, u.control_number 
                         FROM applications a 
                         JOIN users u ON a.user_id = u.id 
                         WHERE a.status = 'Submitted' 
                         ORDER BY a.created_at DESC");
    $pending_applications = $stmt->fetchAll();
    
    // Decrypt user data for display
    foreach ($pending_applications as &$app) {
        $app = decrypt_user_data($app);
    }
    unset($app);
} catch (PDOException $e) {
    error_log("Pending Applications Error: " . $e->getMessage());
}

// Get count for statistics
$total_pending = count($pending_applications);

// For pagination if needed later
$applications_per_page = 10;

// Check for notification messages
$success_message = null;
$error_message = null;

if (isset($_SESSION['message'])) {
    if ($_SESSION['message_type'] === 'success') {
        $success_message = $_SESSION['message'];
    } else {
        $error_message = $_SESSION['message'];
    }
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Application has been successfully verified. An email notification has been sent to the applicant.";
}

if (isset($_GET['rejected']) && $_GET['rejected'] == 1) {
    $success_message = "Application has been marked for resubmission. An email with instructions has been sent to the applicant.";
}

if (isset($_POST['action']) && $_POST['action'] === 'verify') {
    // Only admin and registrar can verify
    if (!in_array($role, ['admin','registrar'], true)) {
        $_SESSION['message'] = 'You do not have permission to verify applications.';
        $_SESSION['message_type'] = 'danger';
        safe_redirect('verify_applications.php');
        exit;
    }
    try {
        $conn->beginTransaction();
        
        $application_id = $_POST['application_id'];
        $verification_notes = $_POST['verification_notes'] ?? '';
        
        // Update application status
        $stmt = $conn->prepare("UPDATE applications SET status = 'Verified', verification_notes = ?, verified_at = NOW() WHERE id = ?");
        $stmt->execute([$verification_notes, $application_id]);
        
        // Record status change
        $stmt = $conn->prepare("
            INSERT INTO status_history 
            (application_id, status, description, performed_by) 
            VALUES (?, 'Verified', ?, ?)
        ");
        $stmt->execute([
            $application_id,
            "Application verified by admin. Notes: " . ($verification_notes ?: 'No notes provided'),
            "Admin: " . $_SESSION['admin_id']
        ]);
        
        // Get applicant details for email
        $stmt = $conn->prepare("
            SELECT u.* FROM users u
            JOIN applications a ON u.id = a.user_id
            WHERE a.id = ?
        ");
        $stmt->execute([$application_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Decrypt user data for email
            $user = decrypt_user_data($user);
            // Send verification email
            $verification_email = [
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email']
            ];
            send_verification_email($verification_email);
        }
        
        $conn->commit();
        
        // Trigger auto-scheduling for this specific application
        require_once 'auto_schedule_exam.php';
        $schedule_result = auto_schedule_verified_applicants($application_id);
        
        if ($schedule_result['success']) {
            $_SESSION['message'] = "Application verified successfully! " . $schedule_result['message'];
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Application verified but scheduling failed: " . $schedule_result['message'];
            $_SESSION['message_type'] = 'warning';
        }
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Verification error: " . $e->getMessage());
        $_SESSION['message'] = "Error during verification: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    safe_redirect('verify_applications.php');
    exit;
}

// Include the HTML template
include 'html/verify_applications.html';
