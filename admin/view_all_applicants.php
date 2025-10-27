<?php
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/admin_auth.php';

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
            u.first_name,
            u.last_name,
            u.email,
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
    $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Include AES encryption for decryption
    require_once '../includes/aes_encryption.php';
    
    // Decrypt sensitive data for each applicant
    foreach ($applicants as &$applicant) {
        $applicant['first_name'] = smartDecrypt($applicant['first_name'], 'personal_data');
        $applicant['last_name'] = smartDecrypt($applicant['last_name'], 'personal_data');
        $applicant['email'] = smartDecrypt($applicant['email'], 'contact_data');
        $applicant['mobile_number'] = smartDecrypt($applicant['mobile_number'], 'contact_data');
        $applicant['previous_school'] = smartDecrypt($applicant['previous_school'], 'academic_data');
        $applicant['school_year'] = smartDecrypt($applicant['school_year'], 'academic_data');
        $applicant['strand'] = smartDecrypt($applicant['strand'], 'academic_data');
    }
} catch (PDOException $e) {
    error_log("Error fetching applicants: " . $e->getMessage());
    $error_message = "Error fetching applicants. Please try again later.";
}

// Include the HTML template
include 'html/view_all_applicants.html'; 