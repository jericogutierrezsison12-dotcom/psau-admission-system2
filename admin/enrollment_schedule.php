<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Ensure only admin users can access enrollment schedule
require_page_access('enrollment_schedule');

$success_message = '';
$error_message = '';
if (isset($_SESSION['admin_message'])) {
    if ($_SESSION['admin_message_type'] === 'success') {
        $success_message = $_SESSION['admin_message'];
    } else {
        $error_message = $_SESSION['admin_message'];
    }
    unset($_SESSION['admin_message']);
    unset($_SESSION['admin_message_type']);
}

// Get admin details
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Get venues from database
$venues = [];
try {
    $stmt = $conn->query("SELECT * FROM venues WHERE is_active = 1 ORDER BY name");
    $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching venues: " . $e->getMessage());
}

// Get all courses for dropdown
$courses = [];
try {
    $stmt = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
}

// Get enrollment schedules with instructions and requirements
$stmt = $conn->prepare("
    SELECT es.*, a.username as admin_name,
           v.name as venue_name, v.capacity as venue_capacity,
           c.course_code, c.course_name,
           (
               SELECT GROUP_CONCAT(instruction_text SEPARATOR '\n')
               FROM enrollment_instructions
               ORDER BY id
           ) AS instructions,
           (
               SELECT GROUP_CONCAT(CONCAT(document_name, '\n', description) SEPARATOR '\n\n')
               FROM required_documents
               ORDER BY id
           ) AS requirements
    FROM enrollment_schedules es
    JOIN admins a ON es.created_by = a.id
    LEFT JOIN venues v ON es.venue_id = v.id
    LEFT JOIN courses c ON es.course_id = c.id
    ORDER BY es.enrollment_date ASC, es.start_time ASC
");
$stmt->execute();
$enrollment_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare instructions and required documents per schedule
$schedule_instructions = [];
$schedule_documents = [];
foreach ($enrollment_schedules as $schedule) {
    // Instructions
    $query = "SELECT * FROM enrollment_instructions ORDER BY id";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $schedule_instructions[$schedule['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Required documents
    $query = "SELECT * FROM required_documents ORDER BY id";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $schedule_documents[$schedule['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all verified applicants not yet scheduled for enrollment
$stmt = $conn->prepare("
    SELECT a.id, a.user_id, a.status, a.created_at,
           u.first_name, u.last_name, u.email, u.mobile_number, u.control_number
    FROM applications a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN enrollment_assignments ea ON a.user_id = ea.student_id
    WHERE a.status = 'Course Assigned' AND ea.id IS NULL
    ORDER BY a.created_at ASC
");
$stmt->execute();
$verified_applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activeTab = isset($_GET['tab']) && in_array($_GET['tab'], ['schedules', 'create', 'assign']) ? $_GET['tab'] : 'schedules';
$selectedScheduleId = isset($_GET['schedule_id']) && is_numeric($_GET['schedule_id']) ? intval($_GET['schedule_id']) : '';

// Get default instructions and requirements for auto-fill
$default_instructions = '';
$default_requirements = '';
try {
    // Get instructions
    $stmt = $conn->query("SELECT instruction_text FROM enrollment_instructions ORDER BY id");
    $instructions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $formatted_instructions = '';
    foreach ($instructions as $index => $instruction) {
        $formatted_instructions .= ($index + 1) . ". {$instruction['instruction_text']}\n";
    }
    $default_instructions = $formatted_instructions;

    // Get requirements
    $stmt = $conn->query("SELECT document_name, description FROM required_documents ORDER BY id");
    $requirements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $formatted_requirements = '';
    foreach ($requirements as $index => $requirement) {
        $formatted_requirements .= ($index + 1) . ". {$requirement['document_name']}\n";
        if (!empty($requirement['description'])) {
            $formatted_requirements .= "   - {$requirement['description']}\n";
        }
    }
    $default_requirements = $formatted_requirements;
} catch (PDOException $e) {
    error_log('Error fetching default instructions/requirements: ' . $e->getMessage());
}

// Get eligible applicants
$eligible_applicants = [];
if ($activeTab === 'assign' && $selectedScheduleId) {
    // Get the course_id for the selected schedule
    $stmt = $conn->prepare('SELECT course_id FROM enrollment_schedules WHERE id = ?');
    $stmt->execute([$selectedScheduleId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $selected_course_id = $row ? $row['course_id'] : null;
    if ($selected_course_id) {
        // Get applicants with status 'Course Assigned', matching course, and not already assigned
        $stmt = $conn->prepare('
            SELECT DISTINCT
                a.id, 
                a.user_id, 
                a.status, 
                u.first_name, 
                u.last_name, 
                u.email, 
                u.mobile_number, 
                u.control_number,
                ca.created_at as course_assigned_at
            FROM applications a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN course_assignments ca ON ca.application_id = a.id
            LEFT JOIN enrollment_assignments ea ON a.user_id = ea.student_id
            WHERE a.status = "Course Assigned"
            AND ca.course_id = ?
            AND ea.id IS NULL
            ORDER BY ca.created_at DESC
        ');
        $stmt->execute([$selected_course_id]);
        $eligible_applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    // Get all verified applicants not yet scheduled for enrollment
    $stmt = $conn->prepare('
        SELECT DISTINCT
            a.id, 
            a.user_id, 
            a.status, 
            u.first_name, 
            u.last_name, 
            u.email, 
            u.mobile_number, 
            u.control_number,
            ca.created_at as course_assigned_at
        FROM applications a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN course_assignments ca ON ca.application_id = a.id
        LEFT JOIN enrollment_assignments ea ON a.user_id = ea.student_id
        WHERE a.status = "Course Assigned"
        AND ea.id IS NULL
        ORDER BY ca.created_at DESC
    ');
    $stmt->execute();
    $eligible_applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Include the HTML template
include 'html/enrollment_schedule.html';
