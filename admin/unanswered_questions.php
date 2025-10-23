<?php
/**
 * PSAU Admission System - Unanswered Questions View
 * Displays all unanswered questions for admin review
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Ensure only admin users can access unanswered questions
require_page_access('manage_faqs');

// Initialize messages
$success_message = '';
$error_message = '';

// Handle session messages
if (isset($_SESSION['message'])) {
    if ($_SESSION['message_type'] === 'success') {
        $success_message = $_SESSION['message'];
    } else {
        $error_message = $_SESSION['message'];
    }
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Process actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'resolve_unanswered':
                $ua_id = (int)($_POST['ua_id'] ?? 0);
                $question = trim($_POST['question'] ?? '');
                $answer = trim($_POST['answer'] ?? '');
                
                if (empty($ua_id) || empty($question) || empty($answer)) {
                    throw new Exception('Question and answer are required.');
                }
                
                // Start transaction
                $conn->beginTransaction();
                
                // Get next sort order
                $stmt = $conn->query("SELECT MAX(sort_order) as max_order FROM faqs");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $sort_order = ($result['max_order'] ?? 0) + 1;
                
                // Insert into faqs
                $stmt = $conn->prepare("INSERT INTO faqs (question, answer, sort_order, is_active) VALUES (?, ?, ?, 1)");
                $stmt->execute([$question, $answer, $sort_order]);
                
                // Delete from unanswered_questions
                $stmt = $conn->prepare("DELETE FROM unanswered_questions WHERE id = ?");
                $stmt->execute([$ua_id]);
                
                $conn->commit();
                
                $_SESSION['message'] = 'Unanswered question added as FAQ successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: unanswered_questions.php');
                exit;
                
            case 'delete_unanswered':
                $id = (int)($_POST['id'] ?? 0);
                
                if (empty($id)) {
                    throw new Exception('Question ID is required.');
                }
                
                $stmt = $conn->prepare("DELETE FROM unanswered_questions WHERE id = ?");
                $stmt->execute([$id]);
                
                $_SESSION['message'] = 'Unanswered question deleted successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: unanswered_questions.php');
                exit;
                
            default:
                throw new Exception('Invalid action.');
        }
    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = 'error';
        header('Location: unanswered_questions.php');
        exit;
    }
}

// Get unanswered questions
try {
    $stmt = $conn->query("SELECT id, question, created_at FROM unanswered_questions ORDER BY created_at DESC, id DESC");
    $unanswered = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching unanswered questions: " . $e->getMessage();
    $unanswered = [];
}

// Get admin details
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Include HTML template
include 'html/unanswered_questions.html';
?>
