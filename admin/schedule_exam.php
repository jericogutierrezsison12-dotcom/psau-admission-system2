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

// Ensure only admin users can access schedule exam
require_page_access('schedule_exam');

$success_message = '';
$error_message = '';
if (isset($_SESSION['message'])) {
    if ($_SESSION['message_type'] === 'success') {
        $success_message = $_SESSION['message'];
    } else {
        $error_message = $_SESSION['message'];
    }
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
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
    
    if (empty($venues)) {
        error_log("No active venues found in the database");
    }
} catch (PDOException $e) {
    error_log("Error fetching venues: " . $e->getMessage());
}

// Get exam instructions from the database
$exam_instructions = [];
try {
    $stmt = $conn->query("
        SELECT instruction_text 
        FROM exam_instructions 
        ORDER BY id
    ");
    $exam_instructions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching exam instructions: " . $e->getMessage());
}

// Get exam required documents from the database
$exam_required_documents = [];
try {
    $stmt = $conn->query("
        SELECT document_name, description 
        FROM exam_required_documents 
        ORDER BY id
    ");
    $exam_required_documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching exam required documents: " . $e->getMessage());
}

// Format instructions and requirements for display
$formatted_instructions = '';
$formatted_requirements = '';

// Build formatted instructions string
foreach ($exam_instructions as $index => $instruction) {
    $formatted_instructions .= ($index + 1) . ". {$instruction['instruction_text']}\n";
}

// Build formatted requirements string
foreach ($exam_required_documents as $index => $document) {
    $formatted_requirements .= ($index + 1) . ". {$document['document_name']}\n";
    if (!empty($document['description'])) {
        $formatted_requirements .= "   - {$document['description']}\n";
    }
}

// Get all exam schedules
$stmt = $conn->prepare("
    SELECT es.*, a.username as admin_name,
           (SELECT COUNT(*) FROM exams WHERE exam_schedule_id = es.id) as current_count,
           v.name as venue_name
    FROM exam_schedules es
    JOIN admins a ON es.created_by = a.id
    LEFT JOIN venues v ON es.venue_id = v.id
    ORDER BY es.exam_date ASC, es.exam_time ASC
");
$stmt->execute();
$exam_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all verified applicants not yet scheduled for an exam
$stmt = $conn->prepare("
    SELECT a.id, a.user_id, a.status, a.created_at, a.verified_at,
           u.first_name, u.last_name, u.email, u.mobile_number, u.control_number
    FROM applications a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN exams e ON a.id = e.application_id
    WHERE a.status = 'Verified'
    GROUP BY a.id
    HAVING COUNT(e.id) = 0
    ORDER BY a.verified_at ASC, a.created_at ASC
");
$stmt->execute();
$verified_applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activeTab = isset($_GET['tab']) && in_array($_GET['tab'], ['schedules', 'create', 'assign']) ? $_GET['tab'] : 'schedules';
$selectedScheduleId = isset($_GET['schedule_id']) && is_numeric($_GET['schedule_id']) ? intval($_GET['schedule_id']) : '';

// Include the HTML template
include 'html/schedule_exam.html'; 