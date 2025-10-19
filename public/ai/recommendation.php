<?php
// Course Recommendation page loader with Python API integration
require_once '../../includes/python_api.php';

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Require login for recommendations
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
	header('Location: ../login.php');
	exit;
}

// Check if Python service is available
$python_service_available = check_python_service_health();

include __DIR__ . '/html/recommendation.html';
?>


