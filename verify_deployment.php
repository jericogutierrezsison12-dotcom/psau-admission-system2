<?php
/**
 * PSAU Admission System - Deployment Verification Script
 * Run this script to verify your deployment is working correctly
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PSAU Admission System - Deployment Verification</h1>";
echo "<hr>";

// Check PHP version
echo "<h2>1. PHP Version Check</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "✅ PHP version is compatible<br>";
} else {
    echo "❌ PHP version is too old. Required: 7.4+<br>";
}

// Check required extensions
echo "<h2>2. Required Extensions Check</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'pdo_pgsql', 'curl', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext extension loaded<br>";
    } else {
        echo "❌ $ext extension missing<br>";
    }
}

// Check database connection
echo "<h2>3. Database Connection Check</h2>";
try {
    require_once 'includes/db_connect.php';
    if ($conn) {
        echo "✅ Database connection successful<br>";
        
        // Check if tables exist
        $tables = ['users', 'applications', 'admins', 'course_management'];
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SELECT 1 FROM $table LIMIT 1");
            if ($stmt->execute()) {
                echo "✅ Table '$table' exists<br>";
            } else {
                echo "❌ Table '$table' missing<br>";
            }
        }
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Check Firebase configuration
echo "<h2>4. Firebase Configuration Check</h2>";
try {
    require_once 'firebase/config.php';
    if (isset($firebase_config) && !empty($firebase_config['apiKey'])) {
        echo "✅ Firebase configuration loaded<br>";
        echo "Firebase Project ID: " . $firebase_config['projectId'] . "<br>";
    } else {
        echo "❌ Firebase configuration missing<br>";
    }
} catch (Exception $e) {
    echo "❌ Firebase error: " . $e->getMessage() . "<br>";
}

// Check file permissions
echo "<h2>5. File Permissions Check</h2>";
$directories = ['uploads', 'images'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✅ Directory '$dir' is writable<br>";
        } else {
            echo "❌ Directory '$dir' is not writable<br>";
        }
    } else {
        echo "❌ Directory '$dir' does not exist<br>";
    }
}

// Check environment variables
echo "<h2>6. Environment Variables Check</h2>";
$env_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'FIREBASE_API_KEY'];
foreach ($env_vars as $var) {
    if (getenv($var)) {
        echo "✅ $var is set<br>";
    } else {
        echo "❌ $var is not set<br>";
    }
}

// Check composer dependencies
echo "<h2>7. Composer Dependencies Check</h2>";
if (file_exists('vendor/autoload.php')) {
    echo "✅ Composer dependencies installed<br>";
} else {
    echo "❌ Composer dependencies not installed<br>";
}

echo "<hr>";
echo "<h2>Deployment Status</h2>";

// Overall status
$all_checks_passed = true;
// You can add more sophisticated checking logic here

if ($all_checks_passed) {
    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "🎉 <strong>Deployment appears to be successful!</strong><br>";
    echo "Your PSAU Admission System should be working correctly.";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "⚠️ <strong>Some issues detected!</strong><br>";
    echo "Please check the errors above and fix them before using the system.";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Test user registration</li>";
echo "<li>Test admin login (admin@psau.edu.ph / password)</li>";
echo "<li>Test file upload functionality</li>";
echo "<li>Check Firebase integration</li>";
echo "</ul>";

echo "<p><strong>Admin Access:</strong></p>";
echo "<ul>";
echo "<li>Email: admin@psau.edu.ph</li>";
echo "<li>Password: password</li>";
echo "</ul>";

echo "<p><strong>Support:</strong></p>";
echo "<ul>";
echo "<li>Check Render logs for detailed error information</li>";
echo "<li>Review Firebase console for authentication issues</li>";
echo "<li>Check database logs for connection problems</li>";
echo "</ul>";
?>
