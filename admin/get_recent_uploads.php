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
    // Simple query to get recent uploads
    $query = "SELECT 
        control_number,
        stanine_score,
        upload_date,
        upload_method
    FROM entrance_exam_scores 
    ORDER BY upload_date DESC 
    LIMIT 10";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($results);
    
} catch (Exception $e) {
    // Log error and send error response
    error_log("Error in get_recent_uploads.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 