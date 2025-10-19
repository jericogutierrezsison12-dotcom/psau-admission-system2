<?php
/**
 * Recommendation Handler
 * Handles course recommendation requests and communicates with Python service
 */

require_once '../../includes/python_api.php';
require_once '../../includes/db_connect.php';

// Set JSON header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

try {
    // Check if this is a rating submission
    if (isset($input['ratings'])) {
        // Handle rating submission
        $rating_data = [
            'stanine' => intval($input['stanine'] ?? 0),
            'gwa' => floatval($input['gwa'] ?? 0),
            'strand' => $input['strand'] ?? '',
            'hobbies' => $input['hobbies'] ?? '',
            'ratings' => $input['ratings'] ?? []
        ];
        
        $response = call_python_recommendation($rating_data);
        echo json_encode($response);
        
    } else {
        // Handle recommendation request
        $recommendation_data = [
            'stanine' => intval($input['stanine'] ?? 0),
            'gwa' => floatval($input['gwa'] ?? 0),
            'strand' => $input['strand'] ?? '',
            'hobbies' => $input['hobbies'] ?? ''
        ];
        
        // Validate input
        if ($recommendation_data['stanine'] < 1 || $recommendation_data['stanine'] > 9) {
            echo json_encode(['error' => 'Stanine score must be between 1 and 9']);
            exit;
        }
        
        if ($recommendation_data['gwa'] < 75 || $recommendation_data['gwa'] > 100) {
            echo json_encode(['error' => 'GWA must be between 75 and 100']);
            exit;
        }
        
        if (empty($recommendation_data['strand'])) {
            echo json_encode(['error' => 'Strand is required']);
            exit;
        }
        
        if (empty($recommendation_data['hobbies'])) {
            echo json_encode(['error' => 'Hobbies are required']);
            exit;
        }
        
        $response = call_python_recommendation($recommendation_data);
        echo json_encode($response);
    }
    
} catch (Exception $e) {
    error_log('Recommendation Handler Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while processing your request']);
}
?>
