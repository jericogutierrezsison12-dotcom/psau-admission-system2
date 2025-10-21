<?php
/**
 * Health Check Endpoint for Railway
 * Returns application status
 */

header('Content-Type: application/json');

$status = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '1.0.0',
    'environment' => $_ENV['ENVIRONMENT'] ?? 'development'
];

// Check database connection
try {
    require_once 'includes/db_connect.php';
    $status['database'] = 'connected';
} catch (Exception $e) {
    $status['database'] = 'error';
    $status['status'] = 'unhealthy';
}

// Check Firebase configuration
try {
    require_once 'firebase/config.php';
    $status['firebase'] = 'configured';
} catch (Exception $e) {
    $status['firebase'] = 'error';
    $status['status'] = 'unhealthy';
}

http_response_code($status['status'] === 'healthy' ? 200 : 503);
echo json_encode($status, JSON_PRETTY_PRINT);
