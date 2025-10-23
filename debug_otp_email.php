<?php
/**
 * Debug OTP Email Sending
 * This script helps debug OTP email sending issues
 */

// Start session
session_start();

// Include required files
require_once 'includes/db_connect.php';
require_once 'firebase/firebase_email.php';

// Test email sending
$test_email = 'jericogutierrezsison12@gmail.com';
$subject = 'Test OTP Email';
$message = '<h1>Test OTP: 123456</h1><p>This is a test email to debug OTP sending.</p>';

echo "<h2>Testing OTP Email Sending</h2>";
echo "<p><strong>Test Email:</strong> $test_email</p>";
echo "<p><strong>Subject:</strong> $subject</p>";

try {
    echo "<h3>Step 1: Testing Firebase Email Function</h3>";
    $result = firebase_send_email($test_email, $subject, $message);
    
    echo "<p><strong>Result:</strong> ";
    if (is_array($result)) {
        echo "Array: " . json_encode($result);
    } else {
        echo "Boolean: " . ($result ? 'true' : 'false');
    }
    echo "</p>";
    
    if ($result && (is_array($result) && !empty($result['success']))) {
        echo "<p style='color: green;'><strong>✅ Email sent successfully!</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>❌ Email sending failed!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Exception:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>Step 2: Testing Database Connection</h3>";
try {
    $stmt = $conn->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "<p style='color: green;'><strong>✅ Database connection successful!</strong></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Database error:</strong> " . $e->getMessage() . "</p>";
}

echo "<h3>Step 3: Testing Session</h3>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color: green;'><strong>✅ Session is active!</strong></p>";
} else {
    echo "<p style='color: red;'><strong>❌ Session is not active!</strong></p>";
}

echo "<h3>Step 4: Environment Check</h3>";
echo "<p><strong>Server Name:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Not set') . "</p>";
echo "<p><strong>HTTP Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "</p>";
echo "<p><strong>Request URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</p>";

echo "<h3>Step 5: Firebase Config Check</h3>";
global $firebase_config;
echo "<p><strong>Email Function URL:</strong> " . ($firebase_config['email_function_url'] ?? 'Not set') . "</p>";
echo "<p><strong>Project ID:</strong> " . ($firebase_config['projectId'] ?? 'Not set') . "</p>";
?>
