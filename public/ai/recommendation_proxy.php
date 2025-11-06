<?php
// Simple proxy to call the external Flask recommendation API, avoiding CORS issues from the browser
// Endpoint: POST ai/recommendation_proxy.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection for course name lookup
require_once __DIR__ . '/../../includes/db_connect.php';

// Function to get full course name from database or fallback mapping
function getFullCourseName($courseCode) {
    global $conn;
    
    // Try to get from database first
    try {
        $stmt = $conn->prepare("SELECT course_name FROM courses WHERE course_code = ?");
        $stmt->execute([$courseCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && !empty($result['course_name'])) {
            return $result['course_name'];
        }
    } catch (Exception $e) {
        // Database lookup failed, use fallback mapping
    }
    
    // Fallback comprehensive course mapping
    $courseMapping = [
        // CASTECH
        'BSA' => 'Bachelor of Science in Agriculture',
        'BSFish' => 'Bachelor of Science in Fisheries',
        'BSFoodTech' => 'Bachelor of Science in Food Technology',
        
        // CFA
        'BSFo' => 'Bachelor of Science in Forestry',
        'BSAgFo' => 'Bachelor of Science in AgroForestry',
        
        // CBEE
        'BSHM' => 'Bachelor of Science in Hospitality Management',
        'BSEntrep' => 'Bachelor of Science in Entrepreneurship',
        'BSAgribus' => 'Bachelor of Science in Agriculture Business',
        'BSAGRIBUS' => 'Bachelor of Science in Agriculture Business',
        'BSAgEcon' => 'Bachelor of Science in Agricultural Economics',
        
        // CAS
        'BSBio' => 'Bachelor of Science in Biology',
        'BSMath' => 'Bachelor of Science in Mathematics',
        'BAELS' => 'Bachelor of Arts in English Language Studies',
        'BSDevComm' => 'Bachelor of Science in Development Communication',
        
        // COECS
        'BSABE' => 'Bachelor of Science in Agricultural and Biosystems Engineering',
        'BSGE' => 'Bachelor of Science in Geodetic Engineering',
        'BSCE' => 'Bachelor of Science in Civil Engineering',
        'BSCpE' => 'Bachelor of Science in Computer Engineering',
        'BSIT' => 'Bachelor of Science in Information Technology',
        
        // COED
        'BTLEd' => 'Bachelor of Technology and Livelihood Education',
        'BSED' => 'Bachelor of Secondary Education',
        'BEED' => 'Bachelor of Elementary Education',
        'BPE' => 'Bachelor of Physical Education',
        
        // CVM
        'DVM' => 'Doctor of Veterinary Medicine',
        
        // Additional common courses
        'BSCS' => 'Bachelor of Science in Computer Science',
        'BSBA' => 'Bachelor of Science in Business Administration',
        'BSFOODTECH' => 'Bachelor of Science in Food Technology',
        'BSAGFO' => 'Bachelor of Science in AgroForestry',
        
        // Common variations
        'BSAGRIBUS' => 'Bachelor of Science in Agriculture Business',
        'BSAGRI' => 'Bachelor of Science in Agriculture',
        'BSFOOD' => 'Bachelor of Science in Food Technology',
        'BSFOREST' => 'Bachelor of Science in Forestry',
        'BSAGRO' => 'Bachelor of Science in AgroForestry'
    ];
    
    return $courseMapping[$courseCode] ?? $courseCode;
}

// Function to format course with full name
function formatCourseWithFullName($courseCode) {
    $fullName = getFullCourseName($courseCode);
    if ($fullName === $courseCode) {
        return $courseCode; // Return as-is if no mapping found
    }
    return $courseCode . ' (' . $fullName . ')';
}

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
$base = 'https://recommender2.onrender.com';
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
            $course1 = $apiRecommendations['course1'];
            // Remove confidence from course name
            $course1 = preg_replace('/\s*\(Confidence:\s*[0-9.]+%\)/', '', $course1);
            // Add full course name
            $course1 = formatCourseWithFullName($course1);
            $recommendationsArray[] = $course1;
        }
        if (isset($apiRecommendations['course2'])) {
            $course2 = $apiRecommendations['course2'];
            // Remove confidence from course name
            $course2 = preg_replace('/\s*\(Confidence:\s*[0-9.]+%\)/', '', $course2);
            // Add full course name
            $course2 = formatCourseWithFullName($course2);
            $recommendationsArray[] = $course2;
        }
        if (isset($apiRecommendations['course3'])) {
            $course3 = $apiRecommendations['course3'];
            // Remove confidence from course name
            $course3 = preg_replace('/\s*\(Confidence:\s*[0-9.]+%\)/', '', $course3);
            // Add full course name
            $course3 = formatCourseWithFullName($course3);
            $recommendationsArray[] = $course3;
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
        
        // Remove confidence from recommendations if it's an array and add full course names
        if (is_array($recommendations)) {
            $recommendations = array_map(function($item) {
                if (is_string($item)) {
                    // Remove confidence
                    $cleanItem = preg_replace('/\s*\(Confidence:\s*[0-9.]+%\)/', '', $item);
                    // Add full course name
                    return formatCourseWithFullName($cleanItem);
                }
                return $item;
            }, $recommendations);
        }
        
        echo json_encode(['recommendations' => $recommendations, 'raw' => $decoded]);
    }
} else {
    // If response is not JSON, return as plain text
    $cleanResponse = trim(strip_tags($response));
    // Remove confidence from plain text response
    $cleanResponse = preg_replace('/\s*\(Confidence:\s*[0-9.]+%\)/', '', $cleanResponse);
    // Add full course name for plain text response
    $cleanResponse = formatCourseWithFullName($cleanResponse);
    echo json_encode(['recommendations' => $cleanResponse, 'raw' => $response]);
}
?>