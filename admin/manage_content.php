<?php
/**
 * PSAU Admission System - Content Management
 * Handles managing various content types like instructions and required documents
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/aes_encryption.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Ensure only admin users can access manage content
require_page_access('manage_content');

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

// Get admin details
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Define content types and their configurations
$content_types = [
    'enrollment_instructions' => [
        'title' => 'Enrollment Instructions',
        'table' => 'enrollment_instructions',
        'fields' => [
            'instruction_text' => ['label' => 'Instruction Text', 'type' => 'textarea', 'required' => true]
        ],
        'list_fields' => ['id', 'instruction_text']
    ],
    'exam_instructions' => [
        'title' => 'Exam Instructions',
        'table' => 'exam_instructions',
        'fields' => [
            'instruction_text' => ['label' => 'Instruction Text', 'type' => 'textarea', 'required' => true]
        ],
        'list_fields' => ['id', 'instruction_text']
    ],
    'exam_required_documents' => [
        'title' => 'Exam Required Documents',
        'table' => 'exam_required_documents',
        'fields' => [
            'document_name' => ['label' => 'Document Name', 'type' => 'text', 'required' => true],
            'description' => ['label' => 'Description', 'type' => 'textarea', 'required' => true]
        ],
        'list_fields' => ['id', 'document_name', 'description']
    ],
    'required_documents' => [
        'title' => 'Required Documents for Enrollment',
        'table' => 'required_documents',
        'fields' => [
            'document_name' => ['label' => 'Document Name', 'type' => 'text', 'required' => true],
            'description' => ['label' => 'Description', 'type' => 'textarea', 'required' => true]
        ],
        'list_fields' => ['id', 'document_name', 'description']
    ]
];

// Get courses for dropdowns
$courses = [];
try {
    $stmt = $conn->query("SELECT id, course_code, course_name FROM courses ORDER BY course_name");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching courses: " . $e->getMessage();
}

// Get current content type
$content_type = isset($_GET['type']) && array_key_exists($_GET['type'], $content_types) ? $_GET['type'] : 'enrollment_instructions';
$current_content = $content_types[$content_type];

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        // Begin transaction
        $conn->beginTransaction();
    
        // Add new content
        if ($action === 'add') {
            $fields = [];
            $values = [];
            $placeholders = [];
            
            foreach ($current_content['fields'] as $field_name => $field_props) {
                if ($field_name === 'id') continue;
                
                if ($field_props['type'] === 'checkbox') {
                    $value = isset($_POST[$field_name]) ? 1 : ($field_props['default'] ?? 0);
                } else {
                    $value = $_POST[$field_name] ?? null;
                    
                    // All fields are now required
                    if (empty($value) && $value !== '0') {
                        throw new Exception("Field '{$field_props['label']}' is required");
                    }
                }
                
                $fields[] = $field_name;
                $values[] = $value;
                $placeholders[] = '?';
            }
            
            $sql = "INSERT INTO {$current_content['table']} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->execute($values);
            
            // Log activity
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (action, user_id, details)
                VALUES (?, ?, ?)
            ");
            $stmt->execute(['add_content', $admin['id'], "Added new {$content_type} content"]);
            
            $success_message = "Content added successfully.";
        }
        
        // Edit content
        else if ($action === 'edit') {
            $id = $_POST['id'] ?? 0;
            
            if (empty($id)) {
                throw new Exception("No record ID specified for editing.");
            }
            
            $sets = [];
            $values = [];
            
            foreach ($current_content['fields'] as $field_name => $field_props) {
                if ($field_name === 'id') continue;
                
                if ($field_props['type'] === 'checkbox') {
                    $value = isset($_POST[$field_name]) ? 1 : ($field_props['default'] ?? 0);
                } else {
                    $value = $_POST[$field_name] ?? null;
                    
                    // All fields are now required
                    if (empty($value) && $value !== '0') {
                        throw new Exception("Field '{$field_props['label']}' is required");
                    }
                }
                
                $sets[] = "{$field_name} = ?";
                $values[] = $value;
            }
            
            $sets[] = "updated_at = ?";
            $values[] = date('Y-m-d H:i:s');
            $values[] = $id;
            
            $sql = "UPDATE {$current_content['table']} SET " . implode(', ', $sets) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($values);
            
            $success_message = "{$current_content['title']} updated successfully.";
        }
        
        // Delete content
        else if ($action === 'delete') {
            $id = $_POST['id'] ?? 0;
            
            if (empty($id)) {
                throw new Exception("No record ID specified for deletion.");
            }
            
            // Delete the item
            $sql = "DELETE FROM {$current_content['table']} WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            
            $success_message = "{$current_content['title']} deleted successfully.";
        }
        
        // Commit transaction
        $conn->commit();
        
        // Redirect on success
        if ($success_message) {
            $_SESSION['message'] = $success_message;
            $_SESSION['message_type'] = 'success';
            header("Location: manage_content.php?type={$content_type}");
            exit;
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error_message = $e->getMessage();
    }
}

// Get content items for the current content type
$content_items = [];
try {
    $sql = "SELECT * FROM {$current_content['table']} ORDER BY id";
    $stmt = $conn->query($sql);
    $content_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching {$current_content['title']}: " . $e->getMessage();
}

// Get a single item for editing
$edit_item = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $sql = "SELECT * FROM {$current_content['table']} WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$_GET['edit']]);
        $edit_item = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error fetching item for editing: " . $e->getMessage();
    }
}

// Include HTML template
include 'html/manage_content.html'; 