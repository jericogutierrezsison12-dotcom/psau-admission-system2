<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Ensure only admin users can access manage announcements
require_page_access('manage_announcements');

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

// Process announcement actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create':
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                
                if (empty($title) || empty($content)) {
                    throw new Exception('Title and content are required.');
                }
                
                $stmt = $conn->prepare("INSERT INTO announcements (title, content, created_by) VALUES (?, ?, ?)");
                $stmt->execute([$title, $content, $_SESSION['admin_id']]);
                
                // Log activity
                $stmt = $conn->prepare("INSERT INTO activity_logs (action, user_id, details) VALUES (?, ?, ?)");
                $stmt->execute(['add_announcement', $_SESSION['admin_id'], "Added new announcement: $title"]);
                
                $_SESSION['message'] = 'Announcement created successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: manage_announcement.php');
                exit;
                
            case 'update':
                $id = $_POST['id'] ?? 0;
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');
                
                if (empty($id) || empty($title) || empty($content)) {
                    throw new Exception('All fields are required.');
                }
                
                $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ? WHERE id = ?");
                $stmt->execute([$title, $content, $id]);
                
                // Log activity
                $stmt = $conn->prepare("INSERT INTO activity_logs (action, user_id, details) VALUES (?, ?, ?)");
                $stmt->execute(['edit_announcement', $_SESSION['admin_id'], "Updated announcement ID: $id"]);
                
                $_SESSION['message'] = 'Announcement updated successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: manage_announcement.php');
                exit;
                
            case 'delete':
                $id = $_POST['id'] ?? 0;
                
                if (empty($id)) {
                    throw new Exception('Invalid announcement ID.');
                }
                
                $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
                $stmt->execute([$id]);
                
                // Log activity
                $stmt = $conn->prepare("INSERT INTO activity_logs (action, user_id, details) VALUES (?, ?, ?)");
                $stmt->execute(['delete_announcement', $_SESSION['admin_id'], "Deleted announcement ID: $id"]);
                
                $_SESSION['message'] = 'Announcement deleted successfully';
                $_SESSION['message_type'] = 'success';
                header('Location: manage_announcement.php');
                exit;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get all announcements
try {
    $stmt = $conn->prepare("
        SELECT a.*, admin.username as admin_name 
        FROM announcements a
        JOIN admins admin ON a.created_by = admin.id
        ORDER BY a.created_at DESC
    ");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching announcements: " . $e->getMessage();
}

// Get admin details
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Include HTML template
include 'html/manage_announcement.html';
