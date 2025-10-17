<?php
/**
 * PSAU Admission System - Course Management
 * Page for administrators to manage courses
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/admin_auth.php';

// Check if user is logged in as admin
is_admin_logged_in();

// Ensure only admin users can access course management
require_page_access('course_management');

// Get current admin
$admin_id = $_SESSION['admin_id'];

// Initialize messages
$success_message = null;
$error_message = null;

// Handle Add/Edit Course
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Add New Course
        if (isset($_POST['add_course'])) {
            $course_code = trim($_POST['course_code']);
            $course_name = trim($_POST['course_name']);
            $description = trim($_POST['description']);
            $total_capacity = intval($_POST['total_capacity']);
            
            // Validate input
            if (empty($course_code) || empty($course_name)) {
                throw new Exception("Course code and name are required fields.");
            }
            
            if ($total_capacity <= 0) {
                throw new Exception("Total capacity must be greater than 0.");
            }
            
            // Check if course code already exists
            $stmt = $conn->prepare("SELECT id FROM courses WHERE course_code = ?");
            $stmt->execute([$course_code]);
            
            if ($stmt->rowCount() > 0) {
                throw new Exception("Course code already exists. Please use a different code.");
            }
            
            // Insert new course with initial values
            $stmt = $conn->prepare("
                INSERT INTO courses (course_code, course_name, description, total_capacity, enrolled_students, scheduled_slots, slots)
                VALUES (?, ?, ?, ?, 0, 0, ?)
            ");
            
            $stmt->execute([$course_code, $course_name, $description, $total_capacity, $total_capacity]);
            
            // Log activity
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (action, user_id, details)
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute(['add_course', $admin_id, "Added new course: $course_code - $course_name with capacity: $total_capacity"]);
            
            $success_message = "Course added successfully with capacity: $total_capacity";
        }
        
        // Edit Course
        else if (isset($_POST['edit_course'])) {
            $course_id = intval($_POST['course_id']);
            $course_code = trim($_POST['course_code']);
            $course_name = trim($_POST['course_name']);
            $description = trim($_POST['description']);
            $total_capacity = intval($_POST['total_capacity']);
            
            // Validate input
            if (empty($course_code) || empty($course_name)) {
                throw new Exception("Course code and name are required fields.");
            }
            
            if ($total_capacity <= 0) {
                throw new Exception("Total capacity must be greater than 0.");
            }
            
            // Check if course code already exists (excluding the current course)
            $stmt = $conn->prepare("SELECT id FROM courses WHERE course_code = ? AND id != ?");
            $stmt->execute([$course_code, $course_id]);
            
            if ($stmt->rowCount() > 0) {
                throw new Exception("Course code already exists. Please use a different code.");
            }
            
            // Get current course data
            $stmt = $conn->prepare("SELECT scheduled_slots FROM courses WHERE id = ?");
            $stmt->execute([$course_id]);
            $current_course = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current_course) {
                throw new Exception("Course not found.");
            }
            
            // Check if new capacity is less than already scheduled slots
            if ($total_capacity < $current_course['scheduled_slots']) {
                throw new Exception("Cannot reduce capacity below already scheduled slots ({$current_course['scheduled_slots']}).");
            }
            
            // Update course
            $stmt = $conn->prepare("
                UPDATE courses 
                SET course_code = ?, course_name = ?, description = ?, total_capacity = ?, slots = ?
                WHERE id = ?
            ");
            
            $new_available_slots = $total_capacity - $current_course['scheduled_slots'];
            $stmt->execute([$course_code, $course_name, $description, $total_capacity, $new_available_slots, $course_id]);
            
            // Log activity
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (action, user_id, details)
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute(['edit_course', $admin_id, "Updated course: $course_code - $course_name, new capacity: $total_capacity"]);
            
            $success_message = "Course updated successfully. New capacity: $total_capacity, Available slots: $new_available_slots";
        }
        
        // Delete Course
        else if (isset($_POST['delete_course'])) {
            $course_id = intval($_POST['course_id']);
            
            // Get course details for logging
            $stmt = $conn->prepare("SELECT course_code, course_name FROM courses WHERE id = ?");
            $stmt->execute([$course_id]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$course) {
                throw new Exception("Course not found.");
            }
            
            // Check if course is being used in any course assignments
            $stmt = $conn->prepare("SELECT COUNT(*) FROM course_assignments WHERE course_id = ?");
            $stmt->execute([$course_id]);
            $usage_count = $stmt->fetchColumn();
            
            if ($usage_count > 0) {
                throw new Exception("Cannot delete course. It is currently assigned to $usage_count student(s).");
            }
            
            // Delete course
            $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$course_id]);
            
            // Log activity
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (action, user_id, details)
                VALUES (?, ?, ?)
            ");
            
            $stmt->execute(['delete_course', $admin_id, "Deleted course: {$course['course_code']} - {$course['course_name']}"]);
            
            $success_message = "Course deleted successfully.";
        }
        
        // Commit transaction
        $conn->commit();
    } catch (Exception $e) {
        // Roll back transaction
        $conn->rollBack();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle edit request (GET method)
$edit_course = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([intval($_GET['edit'])]);
        $edit_course = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$edit_course) {
            $error_message = "Course not found.";
        }
    } catch (PDOException $e) {
        $error_message = "Error fetching course details: " . $e->getMessage();
    }
}

// Get all courses with computed counts (pending, enrolled, cancelled)
$courses = [];
try {
    $stmt = $conn->prepare(" 
        SELECT 
            c.*, 
            COALESCE(p.pending_count, 0) AS pending_count,
            COALESCE(e.enrolled_count, 0) AS enrolled_count,
            COALESCE(x.cancelled_count, 0) AS cancelled_count
        FROM courses c
        LEFT JOIN (
            SELECT es.course_id, COUNT(*) AS pending_count
            FROM enrollment_assignments ea
            JOIN enrollment_schedules es ON ea.schedule_id = es.id
            WHERE ea.status = 'pending'
            GROUP BY es.course_id
        ) p ON p.course_id = c.id
        LEFT JOIN (
            SELECT es.course_id, COUNT(*) AS enrolled_count
            FROM enrollment_assignments ea
            JOIN enrollment_schedules es ON ea.schedule_id = es.id
            WHERE ea.status = 'completed'
            GROUP BY es.course_id
        ) e ON e.course_id = c.id
        LEFT JOIN (
            SELECT es.course_id, COUNT(*) AS cancelled_count
            FROM enrollment_assignments ea
            JOIN enrollment_schedules es ON ea.schedule_id = es.id
            WHERE ea.status = 'cancelled'
            GROUP BY es.course_id
        ) x ON x.course_id = c.id
        ORDER BY c.course_code ASC
    ");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching courses: " . $e->getMessage();
}

// Include the HTML template
include 'html/course_management.html'; 