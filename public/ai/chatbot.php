<?php
// Chatbot page loader with Python API integration
require_once '../../includes/python_api.php';

// Ensure sessions for navbar/mobile templates
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Determine sidebar visibility based on login state
$showSidebar = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Check if Python service is available
$python_service_available = check_python_service_health();

// Include HTML template
include __DIR__ . '/html/chatbot.html';
?>


