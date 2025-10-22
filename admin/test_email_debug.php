<?php
/**
 * Debug script to test admin email functionality
 */

// Start output buffering
ob_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../firebase/firebase_email.php';

// Set JSON header
header('Content-Type: application/json');

// Clean any previous output
ob_clean();

try {
    // Test Firebase config
    global $firebase_config;
    echo json_encode([
        'firebase_config' => $firebase_config,
        'email_function_url' => $firebase_config['email_function_url'] ?? 'NOT SET',
        'api_key' => $firebase_config['apiKey'] ?? 'NOT SET'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    echo json_encode([
        'fatal_error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

ob_end_flush();
?>
