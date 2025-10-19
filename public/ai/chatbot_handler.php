<?php
/**
 * Chatbot Handler
 * Handles chatbot requests and communicates with Python service
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

if (!$input || !isset($input['question'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Question is required']);
    exit;
}

$question = trim($input['question']);

if (empty($question)) {
    http_response_code(400);
    echo json_encode(['error' => 'Question cannot be empty']);
    exit;
}

try {
    // Call Python chatbot service
    $response = call_python_chatbot($question);
    
    // Return the response from Python service
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Chatbot Handler Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'answer' => 'Sorry, there was an error processing your question. Please try again later.',
        'confidence' => 0.0,
        'suggested_questions' => []
    ]);
}
?>
