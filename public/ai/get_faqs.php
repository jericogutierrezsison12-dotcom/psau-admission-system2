<?php
// FAQ service to fetch questions from database-dhe2.onrender.com
// Endpoint: GET ai/get_faqs.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Database connection to your Render service
$base_url = 'https://database-dhe2.onrender.com';

function fetch_faqs_from_database() {
    global $base_url;
    
    // Try to get all FAQs - we need a list endpoint
    // Your current /faqs endpoint requires a question parameter
    // We need to either:
    // 1. Add a new endpoint like /faqs/list in your Flask app, OR
    // 2. Use a generic search to get all FAQs
    
    // Use the new /faqs/list endpoint to get all FAQs
    $url = $base_url . '/faqs/list';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false || $error) {
        return [
            'success' => false,
            'error' => 'Database connection failed: ' . ($error ?: 'Unknown error'),
            'faqs' => []
        ];
    }
    
    if ($http_code !== 200) {
        return [
            'success' => false,
            'error' => 'Database returned HTTP ' . $http_code . '. Response: ' . $response,
            'faqs' => []
        ];
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => 'Invalid JSON response from database: ' . $response,
            'faqs' => []
        ];
    }
    
    return [
        'success' => true,
        'faqs' => $data['faqs'] ?? [],
        'raw' => $data
    ];
}

// Fetch FAQs from your database
$result = fetch_faqs_from_database();

if ($result['success']) {
    // Return the FAQs from your database
    echo json_encode([
        'success' => true,
        'faqs' => $result['faqs'],
        'source' => 'database-dhe2.onrender.com'
    ]);
} else {
    // Fallback to hardcoded FAQs if database fails
    $fallback_faqs = [
        [
            'id' => 1,
            'question' => 'What are the admission requirements?',
            'answer' => 'Basic requirements include...'
        ],
        [
            'id' => 2, 
            'question' => 'How do I apply for admission?',
            'answer' => 'To apply for admission...'
        ],
        [
            'id' => 3,
            'question' => 'When is the entrance examination?',
            'answer' => 'Entrance examinations are scheduled...'
        ],
        [
            'id' => 4,
            'question' => 'What courses are available?',
            'answer' => 'PSAU offers various undergraduate programs...'
        ]
    ];
    
    echo json_encode([
        'success' => false,
        'error' => $result['error'],
        'faqs' => $fallback_faqs,
        'source' => 'fallback'
    ]);
}
?>
