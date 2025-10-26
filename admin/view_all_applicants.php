<?php
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/admin_auth.php';
require_once '../includes/encryption.php';

// Check if user is logged in as admin
is_admin_logged_in();

// Ensure only admin users can access view all applicants
require_page_access('view_all_applicants');

try {
    // Get all applicants with their details and waiting time
    $query = "
        SELECT 
            u.id,
            u.control_number,
            u.first_name_encrypted,
            u.last_name_encrypted,
            u.email_encrypted,
            a.status,
            a.created_at as application_date,
            TIMESTAMPDIFF(DAY, a.created_at, NOW()) as waiting_days,
            ees.stanine_score,
            ea.status as enrollment_status,
            ea.created_at as enrollment_date,
            (SELECT created_at 
             FROM reminder_logs 
             WHERE user_id = u.id 
             ORDER BY created_at DESC 
             LIMIT 1) as last_reminder,
            (SELECT reminder_type 
             FROM reminder_logs 
             WHERE user_id = u.id 
             ORDER BY created_at DESC 
             LIMIT 1) as last_reminder_type
        FROM users u
        LEFT JOIN applications a ON u.id = a.user_id
        LEFT JOIN entrance_exam_scores ees ON u.control_number = ees.control_number
        LEFT JOIN enrollment_assignments ea ON u.id = ea.student_id
        ORDER BY a.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $raw_applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decrypt applicant data
    $applicants = [];
    foreach ($raw_applicants as $applicant) {
        $applicants[] = [
            'id' => $applicant['id'],
            'control_number' => $applicant['control_number'],
            'first_name' => decryptPersonalData($applicant['first_name_encrypted']),
            'last_name' => decryptPersonalData($applicant['last_name_encrypted']),
            'email' => decryptContactData($applicant['email_encrypted']),
            'status' => $applicant['status'],
            'application_date' => $applicant['application_date'],
            'waiting_days' => $applicant['waiting_days'],
            'stanine_score' => $applicant['stanine_score'],
            'enrollment_status' => $applicant['enrollment_status'],
            'enrollment_date' => $applicant['enrollment_date'],
            'last_reminder' => $applicant['last_reminder'],
            'last_reminder_type' => $applicant['last_reminder_type']
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching applicants: " . $e->getMessage());
    $error_message = "Error fetching applicants. Please try again later.";
}

// Include the HTML template
include 'html/view_all_applicants.html'; 