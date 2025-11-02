<?php
/**
 * PSAU Admission System - Admin Dashboard
 * Central hub for administrators to manage the admission process
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/admin_auth.php';
require_once '../includes/encryption.php';

// Check if admin is logged in
is_admin_logged_in('login.php');

// Ensure user has access to dashboard
require_page_access('dashboard');

// Get admin details
$admin = get_current_admin($conn);

// Fetch course statistics
$course_stats = [];
try {
    // Get total number of courses
    $stmt = $conn->query("SELECT COUNT(*) FROM courses");
    $course_stats['total_courses'] = $stmt->fetchColumn();
    
    // Get total available slots across all courses
    $stmt = $conn->query("SELECT SUM(slots) FROM courses");
    $course_stats['total_slots'] = $stmt->fetchColumn() ?: 0;
    
    // Get recent courses added
    $stmt = $conn->query("SELECT * FROM courses ORDER BY created_at DESC LIMIT 5");
    $course_stats['recent_courses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Course Stats Error: " . $e->getMessage());
    $course_stats['total_courses'] = 0;
    $course_stats['total_slots'] = 0;
    $course_stats['recent_courses'] = [];
}

// Fetch statistics
$stats = [
    'total_applications' => 0,
    'pending_verifications' => 0,
    'scheduled_exams' => 0,
    'assigned_courses' => 0,
    'enrollment_scheduled' => 0,
];

try {
    // Total applications
    $stmt = $conn->query("SELECT COUNT(*) FROM applications");
    $stats['total_applications'] = $stmt->fetchColumn();

    // Pending verifications (applications that are submitted but not verified)
    $stmt = $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'Submitted'");
    $stats['pending_verifications'] = $stmt->fetchColumn();

    // Scheduled exams
    $stmt = $conn->query("SELECT COUNT(*) FROM exam_schedules");
    $stats['scheduled_exams'] = $stmt->fetchColumn();

    // Assigned courses
    $stmt = $conn->query("SELECT COUNT(*) FROM course_assignments");
    $stats['assigned_courses'] = $stmt->fetchColumn();

    // Enrollment scheduled
    $stmt = $conn->query("SELECT COUNT(*) FROM enrollment_schedules");
    $stats['enrollment_scheduled'] = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Dashboard Stats Error: " . $e->getMessage());
}

// Fetch recent activities
$recent_activities = [];
try {
    $stmt = $conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10");
    $recent_activities = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Recent Activities Error: " . $e->getMessage());
}

// Fetch recent applications that need verification
$pending_applications = [];
try {
    $stmt = $conn->query("SELECT a.*, u.first_name, u.last_name, u.control_number 
                         FROM applications a 
                         JOIN users u ON a.user_id = u.id 
                         WHERE a.status = 'Submitted' 
                         ORDER BY a.created_at DESC 
                         LIMIT 5");
    $pending_applications = $stmt->fetchAll();
    
    // Decrypt user data for display
    foreach ($pending_applications as &$app) {
        $app = decrypt_user_data($app);
    }
    unset($app);
} catch (PDOException $e) {
    error_log("Pending Applications Error: " . $e->getMessage());
}

// Get score upload statistics
$score_stats = [];
try {
    // Get total scores uploaded
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM entrance_exam_scores");
    $stmt->execute();
    $score_stats['total_scores'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get scores uploaded today
    $stmt = $conn->prepare("SELECT COUNT(*) as today FROM entrance_exam_scores WHERE DATE(upload_date) = CURDATE()");
    $stmt->execute();
    $score_stats['today_scores'] = $stmt->fetch(PDO::FETCH_ASSOC)['today'];

    // Get recent score uploads (need to decrypt user data after fetching)
    $stmt = $conn->prepare("
        SELECT ees.*, a.control_number, u.first_name, u.last_name, adm.username as admin_name
        FROM entrance_exam_scores ees
        JOIN applications a ON ees.application_id = a.id
        JOIN users u ON a.user_id = u.id
        JOIN admins adm ON ees.uploaded_by = adm.id
        ORDER BY ees.upload_date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decrypt user data in recent scores
    foreach ($recent_scores as &$score) {
        if (isset($score['first_name'])) $score['first_name'] = decrypt_data($score['first_name']);
        if (isset($score['last_name'])) $score['last_name'] = decrypt_data($score['last_name']);
    }
    unset($score);
} catch (PDOException $e) {
    $error_message = "Error fetching score statistics: " . $e->getMessage();
}

// Calculate pending assignments and preference match percentage
try {
                                                // Count students with scores posted but not assigned
                                                    $stmt = $conn->query("
                                                        SELECT COUNT(*) FROM applications 
                                                        WHERE status = 'Score Posted' 
                                                        AND id NOT IN (SELECT application_id FROM course_assignments)
                                                    ");
                                                    $pending_assignments = $stmt->fetchColumn();

                                                // Count preference matches
                                                    $stmt = $conn->query("
                                                        SELECT COUNT(*) FROM course_assignments 
                                                        WHERE preference_matched = 1
                                                    ");
                                                    $preference_matches = $stmt->fetchColumn();
                                                    
                                                    // Calculate percentage if there are assignments
                                                    $match_percentage = $stats['assigned_courses'] > 0 
                                                        ? round(($preference_matches / $stats['assigned_courses']) * 100) 
                                                        : 0;
                                                } catch (PDOException $e) {
    $pending_assignments = 0;
                                                    $match_percentage = 0;
                                                }

// Get recent course assignments
                                try {
                                    $stmt = $conn->query("
                                        SELECT 
                                            ca.created_at,
                                            u.first_name,
                                            u.last_name,
                                            c.course_code,
                                            c.course_name,
                                            ca.preference_matched
                                        FROM 
                                            course_assignments ca
                                        JOIN 
                                            users u ON ca.user_id = u.id
                                        JOIN 
                                            courses c ON ca.course_id = c.id
                                        ORDER BY 
                                            ca.created_at DESC
                                        LIMIT 5
                                    ");
                                    $recent_course_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch (PDOException $e) {
                                    $recent_course_assignments = [];
                                }

// Enrollment completion stats
$enrollment_stats = [
    'completed' => 0,
    'cancelled' => 0,
];
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM enrollment_assignments WHERE status = 'completed'");
    $enrollment_stats['completed'] = (int)$stmt->fetchColumn();
    $stmt = $conn->query("SELECT COUNT(*) FROM enrollment_assignments WHERE status = 'cancelled'");
    $enrollment_stats['cancelled'] = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Enrollment Stats Error: " . $e->getMessage());
}

// Include the HTML template
include 'html/dashboard.html';
