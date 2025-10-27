<?php
require_once '../includes/db_connect.php';
require_once '../includes/aes_encryption.php';
require_once '../includes/admin_auth.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Ensure only admin and registrar users can access this page
require_page_access('view_enrolled_students');

// Handle search functionality
$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search_condition = '';
$status_condition = '';
$date_condition = '';

if (!empty($search_term)) {
    $search_condition = " AND (
        u.control_number LIKE :search OR 
        u.first_name LIKE :search OR 
        u.last_name LIKE :search OR 
        u.email LIKE :search OR 
        u.mobile_number LIKE :search OR
        CONCAT(u.first_name, ' ', u.last_name) LIKE :search OR
        EXISTS (
            SELECT 1 FROM course_assignments ca2 
            JOIN courses c2 ON ca2.course_id = c2.id 
            WHERE ca2.user_id = u.id 
            AND (c2.course_code LIKE :search OR c2.course_name LIKE :search)
        )
    )";
}

if (!empty($status_filter)) {
    $status_condition = " AND ea.status = :status_filter";
}

if (!empty($date_from) && !empty($date_to)) {
    $date_condition = " AND DATE(ea.created_at) BETWEEN :date_from AND :date_to";
} elseif (!empty($date_from)) {
    $date_condition = " AND DATE(ea.created_at) >= :date_from";
} elseif (!empty($date_to)) {
    $date_condition = " AND DATE(ea.created_at) <= :date_to";
}

// Initialize variables
$students = [];
$counts = [
    'completed' => 0,
    'cancelled' => 0,
    'pending' => 0
];
$error_message = '';

try {
    // Query to get enrolled students with course information
    $query = "
        SELECT 
            u.id,
            u.control_number,
            u.first_name,
            u.last_name,
            u.email,
            u.mobile_number,
            a.image_2x2_path as profile_picture,
            a.pdf_file as pdf_path,
            ees.stanine_score,
            c.course_code,
            c.course_name,
            ea.status as enrollment_status,
            ea.created_at as enrollment_date
        FROM enrollment_assignments ea
        JOIN users u ON ea.student_id = u.id
        LEFT JOIN applications a ON u.id = a.user_id
        LEFT JOIN entrance_exam_scores ees ON u.control_number = ees.control_number
        LEFT JOIN course_assignments ca ON u.id = ca.user_id
        LEFT JOIN courses c ON ca.course_id = c.id
        WHERE ea.status IN ('completed', 'cancelled', 'pending')
        $search_condition
        $status_condition
        $date_condition
        ORDER BY ea.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($search_term)) {
        $search_param = "%$search_term%";
        $stmt->bindParam(':search', $search_param);
    }
    
    if (!empty($status_filter)) {
        $stmt->bindParam(':status_filter', $status_filter);
    }
    
    if (!empty($date_from)) {
        $stmt->bindParam(':date_from', $date_from);
    }
    
    if (!empty($date_to)) {
        $stmt->bindParam(':date_to', $date_to);
    }
    
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get status counts
    $count_query = "
        SELECT 
            ea.status,
            COUNT(*) as count
        FROM enrollment_assignments ea
        JOIN users u ON ea.student_id = u.id
        WHERE ea.status IN ('completed', 'cancelled', 'pending')
        $search_condition
        $status_condition
        $date_condition
        GROUP BY ea.status
    ";
    
    $count_stmt = $conn->prepare($count_query);
    if (!empty($search_term)) {
        $count_stmt->bindParam(':search', $search_param);
    }
    if (!empty($status_filter)) {
        $count_stmt->bindParam(':status_filter', $status_filter);
    }
    if (!empty($date_from)) {
        $count_stmt->bindParam(':date_from', $date_from);
    }
    if (!empty($date_to)) {
        $count_stmt->bindParam(':date_to', $date_to);
    }
    $count_stmt->execute();
    $status_counts = $count_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize counts
    foreach ($status_counts as $count) {
        $counts[$count['status']] = $count['count'];
    }
    
} catch (PDOException $e) {
    error_log("Error fetching enrolled students: " . $e->getMessage());
    $error_message = "Error fetching enrolled students: " . $e->getMessage();
    $students = [];
    $counts = [
        'completed' => 0,
        'cancelled' => 0,
        'pending' => 0
    ];
}

// Include the HTML template
include 'html/view_enrolled_students.html';
?>
