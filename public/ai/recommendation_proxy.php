<?php
// Simple proxy to call the external Flask recommendation API, avoiding CORS issues from the browser
// Endpoint: POST ai/recommendation_proxy.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Read JSON body
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid JSON payload']);
    http_response_code(400);
    exit;
}

$stanine = $payload['stanine'] ?? '';
$gwa = $payload['gwa'] ?? '';
$strand = $payload['strand'] ?? '';
$hobbies = $payload['hobbies'] ?? '';

// Basic validation
if (trim($stanine) === '' || trim($gwa) === '' || trim($strand) === '') {
    echo json_encode(['error' => 'stanine, gwa, and strand are required']);
    http_response_code(400);
    exit;
}

// Use the new recommendation API URL
$base = 'https://recommender-np4e.onrender.com';
$endpoints = [
    $base . '/api/get_recommendations'
];

function forward_json($url, $stanine, $gwa, $strand, $hobbies){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    // Use named parameters for the new API
    $payload = [
        'stanine' => strval($stanine),
        'gwa' => strval($gwa),
        'strand' => strval($strand),
        'hobbies' => strval($hobbies)
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return [$code, $body, $err];
}


// Call the new recommendation API
$url = $endpoints[0];
list($status, $response, $err) = forward_json($url, $stanine, $gwa, $strand, $hobbies);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Upstream error', 'detail' => $err ?: 'unknown']);
    exit;
}

http_response_code($status ?: 200);

// Handle response from the new API
$decoded = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
    // Check if the API returned success and recommendations
    if (isset($decoded['success']) && $decoded['success'] === true && isset($decoded['recommendations'])) {
        $apiRecommendations = $decoded['recommendations'];
        
        // Convert the object format to an array format that the frontend expects
        $recommendationsArray = [];
        if (isset($apiRecommendations['course1'])) {
            $recommendationsArray[] = $apiRecommendations['course1'];
        }
        if (isset($apiRecommendations['course2'])) {
            $recommendationsArray[] = $apiRecommendations['course2'];
        }
        if (isset($apiRecommendations['course3'])) {
            $recommendationsArray[] = $apiRecommendations['course3'];
        }
        
        // Return in the format the frontend expects
        echo json_encode([
            'success' => true,
            'recommendations' => $recommendationsArray,
            'raw' => $decoded
        ]);
    } else {
        // Fallback to original logic
        $recommendations = $decoded['recommendations'] ?? $decoded['data'] ?? $decoded['message'] ?? $decoded['response'] ?? $decoded;
        echo json_encode(['recommendations' => $recommendations, 'raw' => $decoded]);
    }
} else {
    // If response is not JSON, return as plain text
    echo json_encode(['recommendations' => trim(strip_tags($response)), 'raw' => $response]);
}
?>



