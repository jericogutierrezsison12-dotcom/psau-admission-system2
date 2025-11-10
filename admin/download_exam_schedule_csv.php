<?php
/**
 * PSAU Admission System - Download Exam Schedule CSV
 * Downloads a CSV file with exam schedule details including control number, first name, last name, and stanine score
 */

require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/admin_auth.php';

// Check if admin is logged in
is_admin_logged_in();
require_page_access('schedule_exam');

// Validate input
if (!isset($_GET['schedule_id']) || !is_numeric($_GET['schedule_id'])) {
    header('HTTP/1.1 400 Bad Request');
    die('Invalid schedule ID');
}

$schedule_id = intval($_GET['schedule_id']);

try {
    // Get exam schedule details
    $stmt = $conn->prepare("
        SELECT es.*, v.name as venue_name
        FROM exam_schedules es
        LEFT JOIN venues v ON es.venue_id = v.id
        WHERE es.id = ?
    ");
    $stmt->execute([$schedule_id]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        header('HTTP/1.1 404 Not Found');
        die('Exam schedule not found');
    }
    
    // Get applicants assigned to this schedule with their scores
    $stmt = $conn->prepare("
        SELECT 
            u.control_number,
            u.first_name,
            u.last_name,
            COALESCE(ees.stanine_score, '') as stanine_score
        FROM exams e
        JOIN applications a ON e.application_id = a.id
        JOIN users u ON a.user_id = u.id
        LEFT JOIN entrance_exam_scores ees ON u.control_number = ees.control_number
        WHERE e.exam_schedule_id = ?
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute([$schedule_id]);
    $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    $filename = 'exam_schedule_' . date('Y-m-d', strtotime($schedule['exam_date'])) . '_' . $schedule_id . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write header row
    fputcsv($output, ['Control Number', 'First Name', 'Last Name', 'Stanine Score']);
    
    // Write data rows
    foreach ($applicants as $applicant) {
        fputcsv($output, [
            $applicant['control_number'],
            $applicant['first_name'],
            $applicant['last_name'],
            $applicant['stanine_score']
        ]);
    }
    
    fclose($output);
    exit;
    
} catch (PDOException $e) {
    error_log("Error downloading exam schedule CSV: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    die('Error generating CSV file');
}
?>

