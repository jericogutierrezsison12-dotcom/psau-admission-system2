<?php
/**
 * PSAU Admission System - Get Recent Uploads API
 * Endpoint to fetch recent bulk score uploads
 */

// Start session and include required files
session_start();
require_once '../includes/db_connect.php';

// Simple error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Simple query to get recent uploads with user names
    $query = "SELECT 
        ees.control_number,
        ees.stanine_score,
        ees.upload_date,
        ees.upload_method,
        u.first_name,
        u.last_name
    FROM entrance_exam_scores ees
    JOIN users u ON ees.control_number = u.control_number
    ORDER BY ees.upload_date DESC 
    LIMIT 10";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decrypt user data
    require_once '../includes/encryption.php';
    foreach ($results as &$result) {
        $result['first_name'] = safeDecryptField($result['first_name'] ?? '', 'users', 'first_name');
        $result['last_name'] = safeDecryptField($result['last_name'] ?? '', 'users', 'last_name');
    }
    unset($result);
    
    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($results);
    
} catch (Exception $e) {
    // Log error and send error response
    error_log("Error in get_recent_uploads.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 