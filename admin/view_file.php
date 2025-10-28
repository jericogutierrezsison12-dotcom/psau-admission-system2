<?php
/**
 * PSAU Admission System - Admin File Viewer
 * Handles secure viewing of uploaded files for admin users
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/admin_auth.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    die('Unauthorized access');
}

// Get file path from query parameter
$file_path = $_GET['path'] ?? '';
$file_type = $_GET['type'] ?? '';

if (empty($file_path)) {
    http_response_code(400);
    die('No file path provided');
}

// Security: Only allow viewing files from uploads/ and images/ directories
$allowed_dirs = ['uploads/', 'images/'];
$is_allowed = false;

foreach ($allowed_dirs as $dir) {
    if (strpos($file_path, $dir) === 0) {
        $is_allowed = true;
        break;
    }
}

if (!$is_allowed) {
    http_response_code(403);
    die('Access denied: Invalid file path');
}

// Construct full file path
$full_path = '../' . $file_path;

// Check if file exists
if (!file_exists($full_path)) {
    http_response_code(404);
    die('File not found');
}

// Get file info
$file_info = pathinfo($full_path);
$file_extension = strtolower($file_info['extension'] ?? '');
$file_size = filesize($full_path);
$mime_type = mime_content_type($full_path);

// Determine if this is an image or PDF
$is_image = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
$is_pdf = $file_extension === 'pdf';

// Set appropriate headers
if ($is_image) {
    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . $file_size);
    header('Cache-Control: public, max-age=3600');
} elseif ($is_pdf) {
    header('Content-Type: application/pdf');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: public, max-age=3600');
    header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
} else {
    // For other file types, force download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Content-Length: ' . $file_size);
}

// Output file content
readfile($full_path);
exit;
?>