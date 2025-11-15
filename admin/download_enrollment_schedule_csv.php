<?php
/**
 * PSAU Admission System - Download Enrollment Schedule CSV
 * Downloads a CSV file with enrollment schedule details including control number, first name, last name, and status
 */

require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/admin_auth.php';

// Check if admin is logged in
is_admin_logged_in();
require_page_access('enrollment_schedule');

// Validate input
if (!isset($_GET['schedule_id']) || !is_numeric($_GET['schedule_id'])) {
    header('HTTP/1.1 400 Bad Request');
    die('Invalid schedule ID');
}

$schedule_id = intval($_GET['schedule_id']);

try {
    // Get enrollment schedule details
    $stmt = $conn->prepare("
        SELECT es.*, v.name as venue_name, c.course_code, c.course_name
        FROM enrollment_schedules es
        LEFT JOIN venues v ON es.venue_id = v.id
        LEFT JOIN courses c ON es.course_id = c.id
        WHERE es.id = ?
    ");
    $stmt->execute([$schedule_id]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$schedule) {
        header('HTTP/1.1 404 Not Found');
        die('Enrollment schedule not found');
    }
    
    // Get students assigned to this schedule with their enrollment status
    $stmt = $conn->prepare("
        SELECT 
            u.control_number,
            u.first_name,
            u.last_name,
            CASE 
                WHEN ea.status = 'completed' THEN 'Completed'
                WHEN ea.status = 'cancelled' THEN 'Cancelled'
                ELSE 'Pending'
            END as decision
        FROM enrollment_assignments ea
        JOIN users u ON ea.student_id = u.id
        WHERE ea.schedule_id = ?
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute([$schedule_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    $filename = 'enrollment_schedule_' . date('Y-m-d', strtotime($schedule['enrollment_date'])) . '_' . $schedule_id . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write header row to match enrollment completion CSV format
    fputcsv($output, ['Control Number', 'First Name', 'Last Name', 'Decision']);
    
    // Write data rows
    foreach ($students as $student) {
        fputcsv($output, [
            $student['control_number'],
            $student['first_name'],
            $student['last_name'],
            $student['decision']
        ]);
    }
    
    fclose($output);
    exit;
    
} catch (PDOException $e) {
    error_log("Error downloading enrollment schedule CSV: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    die('Error generating CSV file');
}
?>

