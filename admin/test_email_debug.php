<?php
/**
 * Debug script to test admin email functionality
 */

// Start output buffering
ob_start();

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/phpmailer_send.php';

// Set JSON header
header('Content-Type: application/json');

// Clean any previous output
ob_clean();

try {
    echo json_encode([
        'smtp_host' => getenv('SMTP_HOST') ?: 'NOT SET',
        'smtp_port' => getenv('SMTP_PORT') ?: 'NOT SET',
        'smtp_secure' => getenv('SMTP_SECURE') ?: 'NOT SET',
        'smtp_user' => getenv('SMTP_USER') ? 'SET' : (getenv('GMAIL_EMAIL') ? 'SET (via GMAIL_EMAIL)' : 'NOT SET'),
        'smtp_pass' => getenv('SMTP_PASS') || getenv('GMAIL_APP_PASSWORD') ? 'SET' : 'NOT SET',
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
