<?php
/**
 * PSAU Admission System - Secure Encrypted File Download
 * Handles secure download of encrypted files
 */

require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/encrypted_file_storage.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized access');
}

// Get download token
$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    die('Invalid download token');
}

// Validate token
$file_info = $encrypted_storage->validateDownloadToken($token);

if (!$file_info) {
    http_response_code(403);
    die('Invalid or expired download token');
}

try {
    // Retrieve and decrypt file
    $file_content = $encrypted_storage->retrieveFile(
        $file_info['filename'], 
        $file_info['original_name']
    );
    
    // Get file info
    $file_stats = $encrypted_storage->getFileInfo($file_info['filename']);
    
    // Set headers for file download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file_info['original_name'] . '"');
    header('Content-Length: ' . strlen($file_content));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Output file content
    echo $file_content;
    
    // Clean up token
    unset($_SESSION['download_tokens'][$token]);
    
    // Log download activity
    require_once '../includes/encrypted_data_access.php';
    $encrypted_data->logActivity(
        'file_download',
        $_SESSION['user_id'],
        'Downloaded encrypted file: ' . $file_info['original_name'],
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    );
    
} catch (Exception $e) {
    error_log("File download error: " . $e->getMessage());
    http_response_code(500);
    die('Error downloading file');
}
?>
