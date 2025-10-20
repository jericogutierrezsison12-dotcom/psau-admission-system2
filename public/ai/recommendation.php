<?php
// Course Recommendation page loader

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Require login for recommendations
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
	header('Location: ../login.php');
	exit;
}

include __DIR__ . '/html/recommendation.html';
?>


