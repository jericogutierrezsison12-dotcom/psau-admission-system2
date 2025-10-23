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

// Use the same base URL as chatbot
$base = 'https://flaskbot-4g2h.onrender.com';
$endpoints = [
    $base . '/api/predict',
    $base . '/predict',
    $base . '/get_course_recommendations',
    $base . '/course_recommendations'
];

function forward_json($url, $stanine, $gwa, $strand, $hobbies){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    // Use named parameters for /get_course_recommendations, array format for /predict
    if (strpos($url, '/get_course_recommendations') !== false || strpos($url, '/course_recommendations') !== false) {
        $payload = [
            'stanine' => strval($stanine),
            'gwa' => strval($gwa),
            'strand' => strval($strand),
            'hobbies' => strval($hobbies)
        ];
    } else {
        $payload = [
            'data' => [strval($stanine), strval($gwa), strval($strand), strval($hobbies)],
            'fn_index' => 0
        ];
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return [$code, $body, $err];
}

function forward_form($url, $stanine, $gwa, $strand, $hobbies){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'stanine' => strval($stanine),
        'gwa' => strval($gwa),
        'strand' => strval($strand),
        'hobbies' => strval($hobbies)
    ]));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return [$code, $body, $err];
}

function forward_get($url, $stanine, $gwa, $strand, $hobbies){
    $qs = http_build_query([
        'stanine' => strval($stanine),
        'gwa' => strval($gwa),
        'strand' => strval($strand),
        'hobbies' => strval($hobbies)
    ]);
    $full = strpos($url, '?') !== false ? $url . '&' . $qs : $url . '?' . $qs;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $full);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return [$code, $body, $err];
}

// Try each endpoint with JSON POST; on 405, try form POST, then GET
$status = null; $response = null; $err = null;
foreach ($endpoints as $url) {
    list($status, $response, $err) = forward_json($url, $stanine, $gwa, $strand, $hobbies);
    if ($status === 405) {
        list($status, $response, $err) = forward_form($url, $stanine, $gwa, $strand, $hobbies);
        if ($status === 405) {
            list($status, $response, $err) = forward_get($url, $stanine, $gwa, $strand, $hobbies);
        }
    }
    if ($response !== false && $status && $status !== 404) {
        break; // success or non-404 result
    }
}

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Upstream error', 'detail' => $err ?: 'unknown']);
    exit;
}

http_response_code($status ?: 200);

// Normalize response to JSON with a common shape
$decoded = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
    // Gradio returns data in 'data' field
    $recommendations = $decoded['data'] ?? $decoded['recommendations'] ?? $decoded['message'] ?? $decoded['response'] ?? null;
    if ($recommendations !== null) {
        echo json_encode(['recommendations' => $recommendations, 'raw' => $decoded]);
    } else {
        echo json_encode(['raw' => $decoded]);
    }
} else {
    echo json_encode(['recommendations' => trim(strip_tags($response)), 'raw' => $response]);
}
?>



