<?php
/**
 * PSAU Admission System - Course Assignment
 * This page allows admins to assign courses to applicants who have completed exams
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/aes_encryption.php';
require_once '../includes/admin_auth.php';
require_once '../firebase/firebase_email.php';

// Check if user is logged in as admin
is_admin_logged_in();

// Ensure only admin users can access course assignment
require_page_access('course_assignment');

// Initialize variables
$message = '';
$messageType = '';
$applicants = [];
$courses = [];

// Get admin details
$admin_id = $_SESSION['admin_id'];

// Get admin username
try {
    $stmt = $conn->prepare("SELECT username FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $admin_username = $admin_data['username'] ?? 'Admin';
} catch (PDOException $e) {
    $admin_username = 'Admin';
    error_log("Error fetching admin username: " . $e->getMessage());
}

// Handle course assignment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_course'])) {
    try {
        $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        
        // Validate inputs
        if ($application_id <= 0 || $user_id <= 0 || $course_id <= 0) {
            throw new Exception("Invalid selection. Please ensure all fields are selected.");
        }
        
        // Check if course has available slots
        $stmt = $conn->prepare("SELECT slots FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$course) {
            throw new Exception("Selected course not found.");
        }
        
        if ((int)$course['slots'] <= 0) {
            throw new Exception("No available slots for the selected course. Current available: " . (int)$course['slots']);
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Check if user has course preferences and if this course matches one of them
        $preference_matched = 0;
        $stmt = $conn->prepare("SELECT preference_order FROM course_selections WHERE user_id = ? AND course_id = ? LIMIT 1");
        $stmt->execute([$user_id, $course_id]);
        $preference = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($preference) {
            $preference_matched = 1;
        }
        
        // Insert into course_assignments
        $stmt = $conn->prepare("
            INSERT INTO course_assignments 
            (application_id, user_id, course_id, assigned_by, assignment_notes, preference_matched, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$application_id, $user_id, $course_id, $admin_id, $notes, $preference_matched]);
        
        // Update application status
        $stmt = $conn->prepare("UPDATE applications SET status = 'Course Assigned', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$application_id]);
        
        // Add entry to status_history
        $stmt = $conn->prepare("
            INSERT INTO status_history 
            (application_id, status, description, performed_by, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $application_id, 
            'Course Assigned', 
            'Course assigned by admin', 
            $admin_username
        ]);
        
        // NOTE: We do NOT decrease course slots here because:
        // 1. Course assignment is just preference matching
        // 2. Slots only decrease when enrollment schedules are created
        // 3. This prevents double-decreasing slots
        
        // Get user and course details for email
        $stmt = $conn->prepare("
            SELECT u.*, c.course_code, c.course_name, a.document_file_path, a.gpa, a.strand
            FROM users u 
            JOIN courses c ON c.id = ? 
            JOIN applications a ON a.user_id = u.id
            WHERE u.id = ?
        ");
        $stmt->execute([$course_id, $user_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Log activity
        $stmt = $conn->prepare("
            INSERT INTO activity_logs 
            (action, user_id, details, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $course_details = $data['course_code'] . ' - ' . $data['course_name'];
        $stmt->execute([
            'course_assigned', 
            $admin_id,
            "Course {$course_details} assigned to {$data['first_name']} {$data['last_name']} (ID: {$user_id})"
        ]);
        
        // Commit transaction
        $conn->commit();
        
        // Send email notification
        if (function_exists('send_course_assignment_email')) {
            try {
                $email_sent = send_course_assignment_email(
                    [
                        'email' => $data['email'],
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name']
                    ],
                    [
                        'course_code' => $data['course_code'],
                        'course_name' => $data['course_name']
                    ],
                    $notes,
                    [
                        'document_file_path' => $data['document_file_path'],
                        'gpa' => $data['gpa'],
                        'strand' => $data['strand']
                    ]
                );
                
                if (!$email_sent) {
                    error_log("Failed to send course assignment email to user: {$data['email']}");
                }
            } catch (Exception $email_error) {
                error_log("Course assignment email sending error: " . $email_error->getMessage());
            }
        }
        
        $message = "Course assigned successfully to {$data['first_name']} {$data['last_name']}";
        $messageType = 'success';
    } catch (PDOException $e) {
        // Rollback transaction on database error
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        error_log("Database Error: " . $e->getMessage());
        $message = "Database Error: " . $e->getMessage();
        $messageType = 'danger';
    } catch (Exception $e) {
        // Rollback transaction on other errors
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Get eligible applicants
try {
    $stmt = $conn->prepare("
        SELECT 
            a.id as application_id, 
            a.user_id, 
            u.first_name, 
            u.last_name, 
            u.email, 
            u.control_number,
            a.strand,
            a.gpa,
            a.document_file_path,
            ees.stanine_score,
            (SELECT COUNT(*) FROM course_selections WHERE user_id = a.user_id) as has_course_selections,
            (SELECT MIN(selection_date) FROM course_selections WHERE user_id = a.user_id) as selection_date
        FROM 
            applications a
        JOIN 
            users u ON a.user_id = u.id
        LEFT JOIN 
            entrance_exam_scores ees ON u.control_number = ees.control_number
        LEFT JOIN
            course_assignments ca ON a.id = ca.application_id
        WHERE 
            a.status = 'Score Posted'
            AND ca.id IS NULL
            AND (SELECT COUNT(*) FROM course_selections WHERE user_id = a.user_id) > 0
        ORDER BY 
            ees.stanine_score DESC, a.gpa DESC
    ");
    $stmt->execute();
    $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user course preferences for each applicant
    foreach ($applicants as $key => $applicant) {
        $stmt = $conn->prepare("
            SELECT 
                cs.preference_order,
                c.id as course_id,
                c.course_code,
                c.course_name,
                c.slots
            FROM 
                course_selections cs
            JOIN 
                courses c ON cs.course_id = c.id
            WHERE 
                cs.user_id = ?
            ORDER BY 
                cs.preference_order ASC
        ");
        $stmt->execute([$applicant['user_id']]);
        $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $applicants[$key]['preferences'] = $preferences;
    }
} catch (PDOException $e) {
    error_log("Error fetching applicants: " . $e->getMessage());
    $message = "Error fetching eligible applicants: " . $e->getMessage();
    $messageType = 'danger';
    $applicants = [];
}

// Get available courses
try {
    $stmt = $conn->prepare("
        SELECT 
            id, course_code, course_name, description, slots 
        FROM 
            courses 
        WHERE 
            slots > 0
        ORDER BY 
            course_name ASC
    ");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $message = "Error fetching eligible applicants: " . $e->getMessage();
    $messageType = 'danger';
    $courses = [];
}

// Get recently assigned courses
try {
    $stmt = $conn->prepare("
        SELECT 
            ca.id,
            ca.created_at,
            u.first_name,
            u.last_name,
            u.control_number,
            c.course_code,
            c.course_name,
            admin.username AS assigned_by,
            ca.preference_matched
        FROM 
            course_assignments ca
        JOIN 
            users u ON ca.user_id = u.id
        JOIN 
            courses c ON ca.course_id = c.id
        JOIN 
            admins admin ON ca.assigned_by = admin.id
        ORDER BY 
            ca.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching recent assignments: " . $e->getMessage());
    $recent_assignments = [];
}

// Include the HTML template
include 'html/course_assignment.html';