<?php
/**
 * Health Check Endpoint for Render
 * Simple endpoint to verify the application is running
 */

header('Content-Type: application/json');

try {
    // Basic health check
    $health = [
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0',
        'environment' => $_ENV['ENVIRONMENT'] ?? 'development'
    ];
    
    // Check database connection if available
    if (file_exists(__DIR__ . '/includes/db_connect.php')) {
        require_once __DIR__ . '/includes/db_connect.php';
        if (isset($conn) && $conn !== null) {
            $health['database'] = 'connected';
        } else {
            $health['database'] = 'disconnected';
            $health['status'] = 'degraded';
        }
    }
    
    http_response_code(200);
    echo json_encode($health, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'unhealthy',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}