<?php
// Simple proxy to call the external Flask chatbot API, avoiding CORS issues from the browser
// Endpoint: POST ai/chatbot_proxy.php

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

$message = $payload['message'] ?? '';
if (trim($message) === '') {
    echo json_encode(['error' => 'Message is required']);
    http_response_code(400);
    exit;
}

// Prefer /chat based on upstream HTML; fallback to /chatbot
$base = 'https://flaskbot2.onrender.com';
$endpoints = [$base . '/chat', $base . '/chatbot'];

function forward_json($url, $message){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $message]));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return [$code, $body, $err];
}

function forward_form($url, $message){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['message' => $message]));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return [$code, $body, $err];
}

function forward_get($url, $message){
    $qs = http_build_query(['message' => $message]);
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
$debug_mode = isset($_GET['debug']) && $_GET['debug'] === '1';

foreach ($endpoints as $url) {
    if ($debug_mode) {
        error_log("Trying endpoint: $url");
    }
    
    list($status, $response, $err) = forward_json($url, $message);
    if ($debug_mode) {
        error_log("JSON POST result - Status: $status, Response: " . substr($response, 0, 200));
    }
    
    if ($status === 405) {
        list($status, $response, $err) = forward_form($url, $message);
        if ($debug_mode) {
            error_log("Form POST result - Status: $status, Response: " . substr($response, 0, 200));
        }
        
        if ($status === 405) {
            list($status, $response, $err) = forward_get($url, $message);
            if ($debug_mode) {
                error_log("GET result - Status: $status, Response: " . substr($response, 0, 200));
            }
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
    // The new API returns 'response' key with the AI reply
    $reply = $decoded['response'] ?? $decoded['reply'] ?? $decoded['message'] ?? null;
    if ($reply !== null) {
        echo json_encode(['reply' => $reply, 'raw' => $decoded]);
    } else {
        echo json_encode(['raw' => $decoded]);
    }
} else {
    echo json_encode(['reply' => trim(strip_tags($response)), 'raw' => $response]);
}
?>