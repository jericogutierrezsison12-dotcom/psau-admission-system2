<?php
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/admin_auth.php';

is_admin_logged_in('login.php');

// Allow all roles to view
$admin = get_current_admin($conn);
$role = $_SESSION['admin_role'] ?? 'admin';

// Fetch courses with basic stats
$courses = [];
try {
	$stmt = $conn->query("SELECT id, course_code, course_name, description, slots, created_at FROM courses ORDER BY course_name ASC");
	$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
	$courses = [];
}

include 'html/course_management.html';


