<?php
/**
 * Firebase Configuration
 * Centralized configuration for Firebase services
 */

// Environment detection
$is_production = (getenv('RENDER') === 'true' || strpos($_SERVER['SERVER_NAME'] ?? '', 'onrender.com') !== false);

// Configure error reporting based on environment
if ($is_production) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Firebase project configuration
$firebase_config = [
    'apiKey' => 'AIzaSyBQ5jLQX2JggHQU0ikymEEjywxEos5Lr3c',
    'authDomain' => 'psau-admission-system-f55f8.firebaseapp.com',
    'projectId' => 'psau-admission-system-f55f8',
    'storageBucket' => 'psau-admission-system-f55f8.firebasestorage.app',
    'messagingSenderId' => '615441800587',
    'appId' => '1:615441800587:web:8b0df9b012e24c147da38e',
    'email_function_url' => 'https://us-central1-psau-admission-system-f55f8.cloudfunctions.net/sendEmail',
    'allowed_domains' => [
        'localhost',
        '127.0.0.1',
        'psau-admission-system2.onrender.com'
    ]
];

// Validate required configuration
if (empty($firebase_config['apiKey']) || empty($firebase_config['email_function_url'])) {
    error_log("Firebase configuration error: Missing required fields");
    if ($is_production) {
        // In production, log error but don't throw exception to prevent server errors
        error_log("Firebase configuration incomplete - email service may not work");
    } else {
        throw new Exception("Firebase configuration error: Missing required fields");
    }
}

// Log configuration status
error_log("Firebase configuration loaded successfully");
error_log("Email function URL: " . $firebase_config['email_function_url']);

// Firebase SDK version
define('FIREBASE_SDK_VERSION', '10.8.0'); // Update this when upgrading Firebase SDK

// Function to get Firebase config as JSON for JavaScript
function get_firebase_config_json() {
    global $firebase_config;
    
    // Remove email_function_url as it's not needed for client-side
    $client_config = $firebase_config;
    unset($client_config['email_function_url']);
    
    return json_encode($client_config);
}

// Function to get Firebase SDK URLs
function get_firebase_sdk_urls() {
    return [
        'app' => "https://www.gstatic.com/firebasejs/" . FIREBASE_SDK_VERSION . "/firebase-app.js",
        'analytics' => "https://www.gstatic.com/firebasejs/" . FIREBASE_SDK_VERSION . "/firebase-analytics.js",
        'auth' => "https://www.gstatic.com/firebasejs/" . FIREBASE_SDK_VERSION . "/firebase-auth.js"
    ];
} 