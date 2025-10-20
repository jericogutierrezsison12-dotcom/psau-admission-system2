<?php
/**
 * Firebase Configuration
 * Centralized configuration for Firebase services
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Firebase project configuration
$firebase_config = [
    'apiKey' => 'AIzaSyB7HqxV971vmWiJiXnWdaFnMaFx1C1t6s8',
    'authDomain' => 'psau-admission-system.firebaseapp.com',
    'projectId' => 'psau-admission-system',
    'storageBucket' => 'psau-admission-system.appspot.com',
    'messagingSenderId' => '522448258958',
    'appId' => '1:522448258958:web:994b133a4f7b7f4c1b06df',
    'email_function_url' => 'https://sendemail-alsstt22ha-uc.a.run.app'
];

// Validate required configuration
if (empty($firebase_config['apiKey']) || empty($firebase_config['email_function_url'])) {
    error_log("Firebase configuration error: Missing required fields");
    throw new Exception("Firebase configuration error: Missing required fields");
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