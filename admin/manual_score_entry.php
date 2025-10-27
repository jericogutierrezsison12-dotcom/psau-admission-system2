<?php
/**
 * PSAU Admission System - Manual Score Entry
 * Page for admins to manually enter entrance exam scores
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/aes_encryption.php';
require_once '../includes/admin_auth.php';

// Check if user is logged in as admin
is_admin_logged_in();

// Ensure only admin users can access manual score entry
require_page_access('manual_score_entry');

// Get current admin
$admin_id = $_SESSION['admin_id'];

// Process form submission
$success_message = null;
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_score'])) {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Get form data
        $control_number = trim($_POST['control_number']);
        $stanine_score = intval($_POST['stanine_score']);
        $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : null;
        
        // Validate control number exists in users table and get user details
        $stmt = $conn->prepare("
            SELECT u.*, a.id as application_id 
            FROM users u 
            LEFT JOIN applications a ON u.id = a.user_id 
            WHERE u.control_number = ?
        ");
        $stmt->execute([$control_number]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("Invalid control number. No applicant found with this control number.");
        }
        
        // Check if score already exists
        $stmt = $conn->prepare("SELECT id FROM entrance_exam_scores WHERE control_number = ?");
        $stmt->execute([$control_number]);
        
        if ($stmt->rowCount() > 0) {
            // Update existing score
            $stmt = $conn->prepare("
                UPDATE entrance_exam_scores 
                SET stanine_score = ?, 
                    uploaded_by = ?, 
                    upload_date = NOW(), 
                    upload_method = 'manual',
                    remarks = ?
                WHERE control_number = ?
            ");
            
            $stmt->execute([
                $stanine_score,
                $admin_id,
                $remarks,
                $control_number
            ]);
            
            $message = "Score updated successfully for control number: $control_number";
        } else {
            // Insert new score
            $stmt = $conn->prepare("
                INSERT INTO entrance_exam_scores 
                (control_number, stanine_score, uploaded_by, upload_method, remarks)
                VALUES (?, ?, ?, 'manual', ?)
            ");
            
            $stmt->execute([
                $control_number,
                $stanine_score,
                $admin_id,
                $remarks
            ]);
            
            $message = "Score added successfully for control number: $control_number";
        }

        // Update application status to 'Score Posted'
        if ($user['application_id']) {
            $stmt = $conn->prepare("
                UPDATE applications 
                SET status = 'Score Posted' 
                WHERE id = ?
            ");
            $stmt->execute([$user['application_id']]);
            
            // Add to status history
            $stmt = $conn->prepare("
                INSERT INTO status_history 
                (application_id, status, description, performed_by) 
                VALUES (?, 'Score Posted', 'Entrance exam score has been posted', ?)
            ");
            
            // Get admin username from database or use admin ID if not available
            $admin_username = '';
            $admin_info_stmt = $conn->prepare("SELECT username FROM admins WHERE id = ?");
            $admin_info_stmt->execute([$admin_id]);
            $admin_info = $admin_info_stmt->fetch(PDO::FETCH_ASSOC);
            if ($admin_info && isset($admin_info['username'])) {
                $admin_username = $admin_info['username'];
            } else {
                $admin_username = "Admin ID: " . $admin_id;
            }
            
            $stmt->execute([$user['application_id'], $admin_username]);
        }
        
        // Send email notification
        require_once '../firebase/firebase_email.php';
        
        $email_sent = send_score_notification_email(
            [
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email']
            ],
            $control_number,
            $stanine_score
        );
        
        // Include email status in success message
        if ($email_sent) {
            $email_status = "Email notification sent to the applicant.";
        } else {
            $email_status = "However, email notification could not be sent.";
        }
        
        // Log the activity
        $stmt = $conn->prepare("INSERT INTO activity_logs (action, user_id, details) VALUES (?, ?, ?)");
        $stmt->execute(['score_upload', $admin_id, "Manual score entry for control number: $control_number"]);
        
        // Commit transaction
        $conn->commit();
        
        $success_message = $message . " " . $email_status;
    } catch (Exception $e) {
        // Roll back transaction
        $conn->rollBack();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get recent uploads
try {
    $query = "
        SELECT 
            ees.control_number,
            ees.stanine_score,
            ees.upload_date,
            u.first_name,
            u.last_name
        FROM entrance_exam_scores ees
        JOIN users u ON ees.control_number = u.control_number
        WHERE ees.upload_method = 'manual'
        ORDER BY ees.upload_date DESC
        LIMIT 10
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $recent_uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching recent uploads: " . $e->getMessage());
    $recent_uploads = [];
}

// Include the HTML template
include 'html/manual_score_entry.html'; 