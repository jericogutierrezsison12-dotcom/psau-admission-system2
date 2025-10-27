<?php
/**
 * PSAU Admission System - Venue Management
 * Handles managing examination venues
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/aes_encryption.php';
require_once '../includes/functions.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

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

// Process venue actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Add new venue
    if ($action === 'add_venue') {
        $venue_name = trim($_POST['venue_name'] ?? '');
        $capacity = intval($_POST['capacity'] ?? 30);
        $description = trim($_POST['description'] ?? '');
        
        if (empty($venue_name)) {
            $error_message = "Venue name cannot be empty";
        } else {
            try {
                // Check if venue already exists
                $stmt = $conn->prepare("SELECT COUNT(*) FROM venues WHERE name = ?");
                $stmt->execute([$venue_name]);
                if ($stmt->fetchColumn() > 0) {
                    $error_message = "A venue with this name already exists";
                } else {
                    // Add venue to the venues table
                    $stmt = $conn->prepare("INSERT INTO venues (name, capacity, description, created_by) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $venue_name,
                        $capacity,
                        $description,
                        $_SESSION['admin_id']
                    ]);
                    
                    $_SESSION['message'] = "Venue '$venue_name' added successfully";
                    $_SESSION['message_type'] = "success";
                    header('Location: manage_venues.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
    
    // Edit venue
    else if ($action === 'edit_venue') {
        $venue_id = intval($_POST['venue_id'] ?? 0);
        $venue_name = trim($_POST['venue_name'] ?? '');
        $capacity = intval($_POST['capacity'] ?? 30);
        $description = trim($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($venue_name) || $venue_id <= 0) {
            $error_message = "Venue name cannot be empty and a valid venue must be selected";
        } else {
            try {
                // Check if venue name exists for a different venue id
                $stmt = $conn->prepare("SELECT COUNT(*) FROM venues WHERE name = ? AND id != ?");
                $stmt->execute([$venue_name, $venue_id]);
                if ($stmt->fetchColumn() > 0) {
                    $error_message = "Another venue with this name already exists";
                } else {
                    // Update venue in venues table
                    $stmt = $conn->prepare("UPDATE venues SET name = ?, capacity = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([
                        $venue_name,
                        $capacity,
                        $description,
                        $is_active,
                        $venue_id
                    ]);
                    
                    $_SESSION['message'] = "Venue updated successfully";
                    $_SESSION['message_type'] = "success";
                    header('Location: manage_venues.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
    
    // Delete venue
    else if ($action === 'delete_venue') {
        $venue_id = intval($_POST['venue_id'] ?? 0);
        
        if ($venue_id <= 0) {
            $error_message = "Invalid venue selected for deletion";
        } else {
            try {
                // Check if venue is in use in exams or exam_schedules
                $stmt = $conn->prepare("SELECT COUNT(*) FROM exam_schedules WHERE venue_id = ?");
                $stmt->execute([$venue_id]);
                $exam_schedule_count = $stmt->fetchColumn();
                
                $stmt = $conn->prepare("SELECT COUNT(*) FROM exams WHERE venue_id = ?");
                $stmt->execute([$venue_id]);
                $exams_count = $stmt->fetchColumn();
                
                if ($exam_schedule_count > 0 || $exams_count > 0) {
                    $error_message = "Cannot delete venue because it is in use by one or more exam schedules or exams";
                } else {
                    // Delete from venues table
                    $stmt = $conn->prepare("DELETE FROM venues WHERE id = ?");
                    $stmt->execute([$venue_id]);
                    
                    $_SESSION['message'] = "Venue deleted successfully";
                    $_SESSION['message_type'] = "success";
                    header('Location: manage_venues.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Get all venues
$venues = [];
try {
    $stmt = $conn->query("SELECT * FROM venues ORDER BY name");
    $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching venues: " . $e->getMessage();
}

// Get admin details
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Include the HTML template
include 'html/manage_venues.html'; 