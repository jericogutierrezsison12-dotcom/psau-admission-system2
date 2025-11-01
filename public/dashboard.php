<?php
/**
 * PSAU Admission System - User Dashboard
 * Shows the applicant's admission progress and status
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/encryption.php';

// Check if user is logged in
is_user_logged_in();

// Get user details
$user = get_current_user_data($conn);

// Get application status
$application = null;
$hasApplication = false;
$status = 'Not Started';
$statusClass = 'danger';

if ($user) {
    // Check if user has an application
    $stmt = $conn->prepare("SELECT * FROM applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user['id']]);
    $application = $stmt->fetch();
    
    if ($application) {
        $hasApplication = true;
        $status = $application['status'];
        
        // Decrypt application data with safe fallbacks
        $application['previous_school'] = safeDecryptField($application['previous_school'] ?? '', 'applications', 'previous_school');
        $application['strand']          = safeDecryptField($application['strand']          ?? '', 'applications', 'strand');
        $application['gpa']             = safeDecryptField($application['gpa']             ?? '', 'applications', 'gpa');
        $application['address']         = safeDecryptField($application['address']         ?? '', 'applications', 'address');
        $application['school_year']     = safeDecryptField($application['school_year']     ?? '', 'applications', 'school_year');
        
        // Set status class for styling
        switch ($status) {
            case 'Submitted':
                $statusClass = 'info';
                break;
            case 'Verified':
                $statusClass = 'success';
                break;
            case 'Rejected':
                $statusClass = 'danger';
                break;
            case 'Exam Scheduled':
                $statusClass = 'primary';
                break;
            case 'Score Posted':
                $statusClass = 'secondary';
                break;
            case 'Course Assigned':
                $statusClass = 'warning';
                break;
            case 'Enrollment Scheduled':
                $statusClass = 'dark';
                break;
            case 'Enrolled':
                $statusClass = 'success';
                break;
            default:
                $statusClass = 'danger';
        }
    }
}

// Get exam schedule if available
$examSchedule = null;
if ($hasApplication && in_array($status, ['Exam Scheduled', 'Score Posted', 'Course Assigned', 'Enrollment Scheduled', 'Enrolled'])) {
    // Get exam information from the exams table, which is linked to exam_schedules
    $stmt = $conn->prepare("
        SELECT e.*, es.instructions, es.requirements, v.name as venue_name 
        FROM exams e
        LEFT JOIN exam_schedules es ON e.exam_schedule_id = es.id
        LEFT JOIN venues v ON e.venue_id = v.id
        WHERE e.application_id = ?
    ");
    $stmt->execute([$application['id']]);
    $examSchedule = $stmt->fetch();
}

// Get scores if available
$examScore = null;
if ($hasApplication && in_array($status, ['Score Posted', 'Course Assigned', 'Enrollment Scheduled', 'Enrolled'])) {
    $stmt = $conn->prepare("SELECT * FROM entrance_exam_scores WHERE control_number = ?");
    $stmt->execute([$user['control_number']]);
    $examScore = $stmt->fetch();
}

// Get course assignment if available
$courseAssignment = null;
if ($hasApplication && in_array($status, ['Course Assigned', 'Enrollment Scheduled', 'Enrolled'])) {
    $stmt = $conn->prepare("
        SELECT ca.*, c.course_code, c.course_name 
        FROM course_assignments ca
        JOIN courses c ON ca.course_id = c.id
        WHERE ca.application_id = ?
    ");
    $stmt->execute([$application['id']]);
    $courseAssignment = $stmt->fetch();
}

// Get enrollment schedule if available
$enrollmentSchedule = null;
if ($hasApplication && in_array($status, ['Enrollment Scheduled', 'Enrolled'])) {
    // Get the enrollment schedule
    $stmt = $conn->prepare("
        SELECT es.*, v.name as venue_name,
        (
            SELECT GROUP_CONCAT(instruction_text SEPARATOR '\n')
            FROM enrollment_instructions
            ORDER BY created_at ASC
        ) as instructions,
        (
            SELECT GROUP_CONCAT(document_name SEPARATOR '\n')
            FROM required_documents
            ORDER BY created_at ASC
        ) as requirements
        FROM enrollment_schedules es
        JOIN venues v ON es.venue_id = v.id
        WHERE es.course_id = ? AND es.is_active = 1
    ");
    $stmt->execute([$courseAssignment['course_id']]);
    $enrollmentSchedule = $stmt->fetch();
}

// Get status history
$statusHistory = [];
if ($hasApplication) {
    $stmt = $conn->prepare("
        SELECT * FROM status_history 
        WHERE application_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$application['id']]);
    $statusHistory = $stmt->fetchAll();
}

// Include the HTML template
include_once 'html/dashboard.html';