<?php
/**
 * PSAU Admission System - Comprehensive FAQ Management
 * Handles managing FAQs and unanswered questions in one interface
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

// Ensure only admin users can access manage FAQs
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

// Process FAQ actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_faq':
                $question = trim($_POST['question'] ?? '');
                $answer = trim($_POST['answer'] ?? '');
                $sort_order = (int)($_POST['sort_order'] ?? 0);
                
                if (empty($question) || empty($answer)) {
                    throw new Exception('Question and answer are required.');
                }
                
                // Get next sort order if not provided
                if ($sort_order == 0) {
                    $stmt = $conn->query("SELECT MAX(sort_order) as max_order FROM faqs");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $sort_order = ($result['max_order'] ?? 0) + 1;
                }
                
                $stmt = $conn->prepare("INSERT INTO faqs (question, answer, sort_order, is_active) VALUES (?, ?, ?, 1)");
                $stmt->execute([$question, $answer, $sort_order]);
                
                $_SESSION['message'] = 'FAQ added successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: manage_faqs.php');
                exit;
                
            case 'edit_faq':
                $id = (int)($_POST['id'] ?? 0);
                $question = trim($_POST['question'] ?? '');
                $answer = trim($_POST['answer'] ?? '');
                $sort_order = (int)($_POST['sort_order'] ?? 0);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($id) || empty($question) || empty($answer)) {
                    throw new Exception('ID, question and answer are required.');
                }
                
                $stmt = $conn->prepare("UPDATE faqs SET question = ?, answer = ?, sort_order = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$question, $answer, $sort_order, $is_active, $id]);
                
                $_SESSION['message'] = 'FAQ updated successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: manage_faqs.php');
                exit;
                
            case 'delete_faq':
                $id = (int)($_POST['id'] ?? 0);
                
                if (empty($id)) {
                    throw new Exception('FAQ ID is required.');
                }
                
                $stmt = $conn->prepare("DELETE FROM faqs WHERE id = ?");
                $stmt->execute([$id]);
                
                $_SESSION['message'] = 'FAQ deleted successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: manage_faqs.php');
                exit;
                
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
                header('Location: manage_faqs.php');
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
                header('Location: manage_faqs.php');
                exit;
                
            default:
                throw new Exception('Invalid action.');
        }
    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = 'error';
        header('Location: manage_faqs.php');
        exit;
    }
}

// Handle GET actions (edit and resolve)
$edit_faq = null;
$resolve_question = null;

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'edit':
            $id = (int)($_GET['id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("SELECT * FROM faqs WHERE id = ?");
                $stmt->execute([$id]);
                $edit_faq = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            break;
            
        case 'resolve':
            $id = (int)($_GET['id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("SELECT * FROM unanswered_questions WHERE id = ?");
                $stmt->execute([$id]);
                $resolve_question = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            break;
    }
}

// Get all FAQs
try {
    $stmt = $conn->query("SELECT * FROM faqs ORDER BY sort_order, id");
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching FAQs: " . $e->getMessage();
    $faqs = [];
}

// Get unanswered questions
try {
    $stmt = $conn->query("SELECT id, question, created_at FROM unanswered_questions ORDER BY created_at DESC, id DESC");
    $unanswered = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $unanswered = [];
}

// Get admin details
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Include HTML template
include 'html/manage_faqs.html';
?>
