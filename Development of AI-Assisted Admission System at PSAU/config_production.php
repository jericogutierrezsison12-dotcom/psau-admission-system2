<?php
/**
 * Production Configuration for PSAU Admission System
 * InfinityFree Hosting Configuration
 */

// Production environment settings
define('ENVIRONMENT', 'production');
define('DEBUG_MODE', false);
define('BASE_URL', 'https://yourdomain.infinityfreeapp.com'); // Update with your actual domain

// Disable error display in production
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('Asia/Manila');

// Production database configuration
// Update these with your InfinityFree database details
define('DB_HOST', 'sqlXXX.infinityfree.com'); // Replace XXX with your server number
define('DB_NAME', 'if0_XXXXXXXX'); // Replace with your database name
define('DB_USER', 'if0_XXXXXXXX'); // Replace with your database username
define('DB_PASS', 'your_password_here'); // Replace with your database password

// Firebase configuration for production
define('FIREBASE_API_KEY', 'AIzaSyB7HqxV971vmWiJiXnWdaFnMaFx1C1t6s8');
define('FIREBASE_AUTH_DOMAIN', 'psau-admission-system.firebaseapp.com');
define('FIREBASE_PROJECT_ID', 'psau-admission-system');
define('FIREBASE_STORAGE_BUCKET', 'psau-admission-system.appspot.com');
define('FIREBASE_MESSAGING_SENDER_ID', '522448258958');
define('FIREBASE_APP_ID', '1:522448258958:web:994b133a4f7b7f4c1b06df');
define('FIREBASE_EMAIL_FUNCTION_URL', 'https://sendemail-alsstt22ha-uc.a.run.app');

// File upload settings
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['pdf', 'jpg', 'jpeg', 'png']);
define('UPLOAD_PATH', 'uploads/');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Email settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com'); // Update with your email
define('SMTP_PASSWORD', 'your-app-password'); // Update with your app password

// Application settings
define('APP_NAME', 'PSAU Admission System');
define('APP_VERSION', '1.0.0');
define('ADMIN_EMAIL', 'admin@psau.edu.ph'); // Update with admin email

// Logging
define('LOG_FILE', 'logs/app.log');
define('ERROR_LOG_FILE', 'logs/error.log');

// Create logs directory if it doesn't exist
if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}

// Set error log file
ini_set('error_log', ERROR_LOG_FILE);

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CORS settings (if needed)
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>
