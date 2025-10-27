<?php
/**
 * PSAU Admission System - Course Selection
 * Allows applicants to choose their 1st, 2nd, and 3rd preferred courses
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/aes_encryption.php';

// Check if user is logged in
is_user_logged_in();

// Get user details
$user = get_current_user_data($conn);
$user_id = $user['id'];
$message = '';
$messageType = '';

// Check if the user has an application that has passed the exam
$stmt = $conn->prepare("
    SELECT a.id, a.status 
    FROM applications a
    WHERE a.user_id = ? AND a.status IN ('Score Posted', 'Course Assigned', 'Enrollment Scheduled', 'Enrolled')
    ORDER BY a.created_at DESC LIMIT 1
");
$stmt->execute([$user_id]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

$canSelectCourses = false;
$hasSelectedCourses = false;

// First check if user has already selected courses
$stmt = $conn->prepare("SELECT COUNT(*) FROM course_selections WHERE user_id = ?");
$stmt->execute([$user_id]);
$courseCount = $stmt->fetchColumn();

if ($courseCount > 0) {
    $hasSelectedCourses = true;
}

// Then check if they're eligible to select courses (if they haven't already)
if (!$hasSelectedCourses && $application && in_array($application['status'], ['Score Posted', 'Course Assigned'])) {
    $canSelectCourses = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canSelectCourses && !$hasSelectedCourses) {
    // Get selected courses
    $firstChoice = isset($_POST['first_choice']) ? intval($_POST['first_choice']) : 0;
    $secondChoice = isset($_POST['second_choice']) ? intval($_POST['second_choice']) : 0;
    $thirdChoice = isset($_POST['third_choice']) ? intval($_POST['third_choice']) : 0;
    
    if ($firstChoice <= 0) {
        $message = 'First choice course is required.';
        $messageType = 'danger';
    } elseif ($secondChoice > 0 && $secondChoice === $firstChoice) {
        $message = 'Second choice must be different from first choice.';
        $messageType = 'danger';
    } elseif ($thirdChoice > 0 && ($thirdChoice === $firstChoice || $thirdChoice === $secondChoice)) {
        $message = 'Third choice must be different from first and second choices.';
        $messageType = 'danger';
    } else {
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // First, delete any existing course selections for this user
            $deleteStmt = $conn->prepare("DELETE FROM course_selections WHERE user_id = ?");
            $deleteStmt->execute([$user_id]);
            
            // Insert new selections
            $insertStmt = $conn->prepare("INSERT INTO course_selections (user_id, course_id, preference_order) VALUES (?, ?, ?)");
            
            // Insert first choice (required)
            $insertStmt->execute([$user_id, $firstChoice, 1]);
            
            // Insert second choice if selected
            if ($secondChoice > 0) {
                $insertStmt->execute([$user_id, $secondChoice, 2]);
            }
            
            // Insert third choice if selected
            if ($thirdChoice > 0) {
                $insertStmt->execute([$user_id, $thirdChoice, 3]);
            }
            
            // Log activity
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (action, user_id, details)
                VALUES (?, ?, ?)
            ");
            $stmt->execute(['course_selection', $user_id, "User selected course preferences"]);
            
            // Commit transaction
            $conn->commit();
            
            $message = 'Course preferences saved successfully. You cannot change your selection now.';
            $messageType = 'success';
            $hasSelectedCourses = true; // Update status after successful submission
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get all available courses
$courses = [];
try {
    $stmt = $conn->prepare("SELECT * FROM courses ORDER BY course_name ASC");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'Error fetching courses: ' . $e->getMessage();
    $messageType = 'danger';
}

// Get user's selected courses
$selectedCourses = [
    1 => 0, // First choice
    2 => 0, // Second choice
    3 => 0  // Third choice
];
try {
    $stmt = $conn->prepare("SELECT course_id, preference_order FROM course_selections WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $userSelections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($userSelections as $selection) {
        $selectedCourses[$selection['preference_order']] = $selection['course_id'];
    }
} catch (PDOException $e) {
    $message = 'Error fetching selected courses: ' . $e->getMessage();
    $messageType = 'danger';
}

// Get course names for display
$selectedCourseDetails = [];
if ($hasSelectedCourses) {
    foreach ($selectedCourses as $preference => $courseId) {
        if ($courseId > 0) {
            $stmt = $conn->prepare("SELECT course_code, course_name FROM courses WHERE id = ?");
            $stmt->execute([$courseId]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($course) {
                $selectedCourseDetails[$preference] = $course;
            }
        }
    }
}

// Include the HTML template
include 'html/course_selection.html';
?> 