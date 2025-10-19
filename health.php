<?php
/**
 * Health Check Endpoint
 * Simple endpoint to verify PHP application is running
 */

header('Content-Type: application/json');

$health_status = [
    'status' => 'ok',
    'service' => 'psau-admission-php',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '1.0.0',
    'environment' => 'production'
];

// Check database connection
try {
    require_once 'includes/db_connect.php';
    if ($conn) {
        $health_status['database'] = 'connected';
    } else {
        $health_status['database'] = 'disconnected';
        $health_status['status'] = 'warning';
    }
} catch (Exception $e) {
    $health_status['database'] = 'error';
    $health_status['status'] = 'error';
    $health_status['error'] = $e->getMessage();
}

// Check Python service
require_once 'includes/python_api.php';
if (check_python_service_health()) {
    $health_status['python_service'] = 'available';
} else {
    $health_status['python_service'] = 'unavailable';
    $health_status['status'] = 'warning';
}

http_response_code($health_status['status'] === 'ok' ? 200 : 503);
echo json_encode($health_status);
?>
