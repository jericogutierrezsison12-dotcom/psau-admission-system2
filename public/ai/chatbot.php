<?php
// Chatbot page loader
// Guests can view, but we hide sidebar if not logged in

// Ensure sessions for navbar/mobile templates
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Determine sidebar visibility based on login state
$showSidebar = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Include HTML template
include __DIR__ . '/html/chatbot.html';
?>


