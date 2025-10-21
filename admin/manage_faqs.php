<?php
/**
 * PSAU Admission System - FAQ Management
 * Handles managing frequently asked questions
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
            case 'resolve_unanswered':
                $ua_id = (int)($_POST['ua_id'] ?? 0);
                $question = trim($_POST['question'] ?? '');
                $answer = trim($_POST['answer'] ?? '');
                if (empty($ua_id) || empty($question) || empty($answer)) {
                    throw new Exception('Question and answer are required.');
                }
                // Start transaction to ensure both operations are atomic
                $conn->beginTransaction();
                // Determine next sort order
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
                $ua_id = (int)($_POST['ua_id'] ?? 0);
                if (empty($ua_id)) {
                    throw new Exception('Invalid unanswered question ID.');
                }
                $stmt = $conn->prepare("DELETE FROM unanswered_questions WHERE id = ?");
                $stmt->execute([$ua_id]);
                $_SESSION['message'] = 'Unanswered question deleted';
                $_SESSION['message_type'] = 'success';
                header('Location: manage_faqs.php');
                exit;
            case 'add':
                $question = trim($_POST['question'] ?? '');
                $answer = trim($_POST['answer'] ?? '');
                
                if (empty($question) || empty($answer)) {
                    throw new Exception('Question and answer are required.');
                }
                
                // Get the maximum sort_order and add 1
                $stmt = $conn->query("SELECT MAX(sort_order) as max_order FROM faqs");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $sort_order = ($result['max_order'] ?? 0) + 1;
                
                $stmt = $conn->prepare("INSERT INTO faqs (question, answer, sort_order) VALUES (?, ?, ?)");
                $stmt->execute([$question, $answer, $sort_order]);
                
                $_SESSION['message'] = 'FAQ added successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: manage_faqs.php');
                exit;
                
            case 'edit':
                $id = $_POST['id'] ?? 0;
                $question = trim($_POST['question'] ?? '');
                $answer = trim($_POST['answer'] ?? '');
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($id) || empty($question) || empty($answer)) {
                    throw new Exception('All fields are required.');
                }
                
                $stmt = $conn->prepare("UPDATE faqs SET question = ?, answer = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$question, $answer, $is_active, $id]);
                
                $_SESSION['message'] = 'FAQ updated successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: manage_faqs.php');
                exit;
                
            case 'delete':
                $id = $_POST['id'] ?? 0;
                
                if (empty($id)) {
                    throw new Exception('Invalid FAQ ID.');
                }
                
                // Start transaction to handle sort_order updates
                $conn->beginTransaction();
                
                // Get the current sort_order of the FAQ to be deleted
                $stmt = $conn->prepare("SELECT sort_order FROM faqs WHERE id = ?");
                $stmt->execute([$id]);
                $current_order = $stmt->fetch(PDO::FETCH_ASSOC)['sort_order'];
                
                // Delete the FAQ
                $stmt = $conn->prepare("DELETE FROM faqs WHERE id = ?");
                $stmt->execute([$id]);
                
                // Update sort_order for remaining FAQs
                $stmt = $conn->prepare("UPDATE faqs SET sort_order = sort_order - 1 WHERE sort_order > ?");
                $stmt->execute([$current_order]);
                
                $conn->commit();
                
                $_SESSION['message'] = 'FAQ deleted successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: manage_faqs.php');
                exit;
                
            case 'toggle':
                $id = $_POST['id'] ?? 0;
                $is_active = $_POST['is_active'] ?? 0;
                
                if (empty($id)) {
                    throw new Exception('Invalid FAQ ID.');
                }
                
                $stmt = $conn->prepare("UPDATE faqs SET is_active = ? WHERE id = ?");
                $stmt->execute([$is_active, $id]);
                
                echo json_encode(['success' => true]);
                exit;
        }
    } catch (Exception $e) {
        if ($action === 'delete') {
            $conn->rollBack();
        }
        $error_message = $e->getMessage();
    }
}

// Get all FAQs
try {
    $stmt = $conn->query("SELECT * FROM faqs ORDER BY sort_order, id");
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching FAQs: " . $e->getMessage();
}

// Get unanswered questions
try {
    $stmt = $conn->query("SELECT id, question, created_at FROM unanswered_questions ORDER BY created_at DESC, id DESC");
    $unanswered = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If table missing, set empty and show warning later in UI
    $unanswered = [];
}

// Get admin details
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Include HTML template
include 'html/manage_faqs.html'; 