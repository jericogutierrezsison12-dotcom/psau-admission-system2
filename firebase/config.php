<?php
/**
 * Firebase Configuration
 * Centralized configuration for Firebase services
 */

// Detect environment
$is_production = !empty($_ENV['RENDER']) || !empty($_SERVER['RENDER']);

// Configure error reporting based on environment
if ($is_production) {
    // Production settings for Render
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    // Development settings
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Firebase project configuration - use environment variables if available
$firebase_config = [
    'apiKey' => getenv('FIREBASE_API_KEY') ?: 'AIzaSyB7HqxV971vmWiJiXnWdaFnMaFx1C1t6s8',
    'authDomain' => getenv('FIREBASE_AUTH_DOMAIN') ?: 'psau-admission-system.firebaseapp.com',
    'projectId' => getenv('FIREBASE_PROJECT_ID') ?: 'psau-admission-system',
    'storageBucket' => getenv('FIREBASE_STORAGE_BUCKET') ?: 'psau-admission-system.appspot.com',
    'messagingSenderId' => getenv('FIREBASE_MESSAGING_SENDER_ID') ?: '522448258958',
    'appId' => getenv('FIREBASE_APP_ID') ?: '1:522448258958:web:994b133a4f7b7f4c1b06df',
    'email_function_url' => getenv('FIREBASE_EMAIL_FUNCTION_URL') ?: 'https://sendemail-alsstt22ha-uc.a.run.app',
    'allowed_domains' => [
        'localhost',
        '127.0.0.1',
        'psau-admission-system.onrender.com'
    ]
];

// Validate required configuration
if (empty($firebase_config['apiKey']) || empty($firebase_config['email_function_url'])) {
    error_log("Firebase configuration error: Missing required fields");
    if ($is_production) {
        // In production, don't throw exceptions that might break JSON responses
        error_log("Firebase configuration incomplete in production");
    } else {
        throw new Exception("Firebase configuration error: Missing required fields");
    }
}

// Log configuration status (only in development)
if (!$is_production) {
    error_log("Firebase configuration loaded successfully");
    error_log("Email function URL: " . $firebase_config['email_function_url']);
}

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