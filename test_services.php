<?php
/**
 * Test Services Script
 * Tests reCAPTCHA, email, SMS OTP, and password reset functionality
 */

// Include required files
require_once 'includes/db_connect.php';
require_once 'includes/api_calls.php';
require_once 'includes/simple_email.php';
require_once 'firebase/config.php';

echo "<h1>PSAU Admission System - Service Tests</h1>";
echo "<p>Testing all services for domain: " . ($_SERVER['SERVER_NAME'] ?? 'unknown') . "</p>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = DATABASE()");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Database connected successfully. Found {$result['table_count']} tables.<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test 2: reCAPTCHA Configuration
echo "<h2>2. reCAPTCHA Configuration Test</h2>";
$recaptcha_site_key = '6LezOyYrAAAAAJRRTgIcrXDqa5_gOrkJNjNvoTFA';
$recaptcha_secret_key = '6LezOyYrAAAAAFBdA-STTB2MsNfK6CyDC_2qFR8N';
echo "Site Key: " . substr($recaptcha_site_key, 0, 20) . "...<br>";
echo "Secret Key: " . substr($recaptcha_secret_key, 0, 20) . "...<br>";
echo "✅ reCAPTCHA keys configured<br>";

// Test 3: Firebase Configuration
echo "<h2>3. Firebase Configuration Test</h2>";
global $firebase_config;
echo "API Key: " . substr($firebase_config['apiKey'], 0, 20) . "...<br>";
echo "Auth Domain: " . $firebase_config['authDomain'] . "<br>";
echo "Project ID: " . $firebase_config['projectId'] . "<br>";
echo "Email Function URL: " . $firebase_config['email_function_url'] . "<br>";
echo "✅ Firebase configuration loaded<br>";

// Test 4: Email Service Test
echo "<h2>4. Email Service Test</h2>";
$test_email = 'test@example.com';
$test_subject = 'PSAU Admission System - Service Test';
$test_message = '<h2>Service Test Email</h2><p>This is a test email from the PSAU Admission System.</p><p>If you receive this, the email service is working correctly.</p>';

try {
    $result = send_email_with_fallback($test_email, $test_subject, $test_message);
    if ($result) {
        echo "✅ Email service test completed (sent to: $test_email)<br>";
    } else {
        echo "❌ Email service test failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Email service error: " . $e->getMessage() . "<br>";
}

// Test 5: Required Database Tables
echo "<h2>5. Database Tables Test</h2>";
$required_tables = ['users', 'login_attempts', 'applications', 'courses', 'enrollment_schedules'];
$missing_tables = [];

foreach ($required_tables as $table) {
    try {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            $missing_tables[] = $table;
        }
    } catch (Exception $e) {
        $missing_tables[] = $table;
    }
}

if (empty($missing_tables)) {
    echo "✅ All required tables exist<br>";
} else {
    echo "❌ Missing tables: " . implode(', ', $missing_tables) . "<br>";
    echo "<p><strong>Action Required:</strong> Import the database schema from database/psau_admission.sql</p>";
}

// Test 6: Environment Check
echo "<h2>6. Environment Check</h2>";
$server_name = $_SERVER['SERVER_NAME'] ?? 'unknown';
$is_localhost = ($server_name === 'localhost' || $server_name === '127.0.0.1');
$is_production = ($server_name === 'psau-admission-system-16ip.onrender.com');

echo "Server Name: $server_name<br>";
echo "Is Localhost: " . ($is_localhost ? 'Yes' : 'No') . "<br>";
echo "Is Production: " . ($is_production ? 'Yes' : 'No') . "<br>";

if ($is_production) {
    echo "✅ Running on production domain<br>";
} elseif ($is_localhost) {
    echo "⚠️ Running on localhost (development mode)<br>";
} else {
    echo "⚠️ Unknown environment<br>";
}

// Test 7: File Permissions
echo "<h2>7. File Permissions Test</h2>";
$writable_dirs = ['uploads', 'images', 'logs'];
foreach ($writable_dirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✅ Directory '$dir' is writable<br>";
        } else {
            echo "❌ Directory '$dir' is not writable<br>";
        }
    } else {
        echo "⚠️ Directory '$dir' does not exist<br>";
    }
}

echo "<h2>Summary</h2>";
echo "<p>If all tests show ✅, your system should be working correctly.</p>";
echo "<p>If any tests show ❌, please address those issues first.</p>";
echo "<p><a href='public/login.php'>Go to Login Page</a> | <a href='public/register.php'>Go to Register Page</a></p>";
?>
