<?php
/**
 * Encryption Test with Temporary Key
 * Sets a test key and runs comprehensive tests
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set a temporary test key for testing
$test_key = base64_encode(random_bytes(32));
putenv("ENCRYPTION_KEY=$test_key");
$_ENV['ENCRYPTION_KEY'] = $test_key;

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/encryption.php';
require_once __DIR__ . '/../includes/functions.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>PSAU Encryption Test (with key)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .pass { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
    </style>
</head>
<body>
    <h1>PSAU Admission System - Encryption Test (with Key)</h1>
    <p>Testing with temporary encryption key...</p>";

$tests_passed = 0;
$tests_failed = 0;

function test_result($name, $passed, $message = '') {
    global $tests_passed, $tests_failed;
    
    if ($passed) {
        $tests_passed++;
        $status = "<span class='pass'>✓ PASS</span>";
    } else {
        $tests_failed++;
        $status = "<span class='fail'>✗ FAIL</span>";
    }
    
    echo "<div class='test-section'><h3>$status - $name</h3>";
    if ($message) echo "<p>$message</p>";
    echo "</div>";
}

// Test 1: Encryption/Decryption roundtrip
echo "<h2>Test 1: Encryption Roundtrip</h2>";
$test_data = [
    ['John Doe', 'Personal'],
    ['john@example.com', 'Contact'],
    ['09123456789', 'Contact'],
    ['High School', 'Academic']
];

foreach ($test_data as $item) {
    $data = $item[0];
    $type = $item[1];
    
    $func_map = [
        'Personal' => ['encryptPersonalData', 'decryptPersonalData'],
        'Contact' => ['encryptContactData', 'decryptContactData'],
        'Academic' => ['encryptAcademicData', 'decryptAcademicData']
    ];
    
    $enc_func = $func_map[$type][0];
    $dec_func = $func_map[$type][1];
    
    try {
        $encrypted = call_user_func($enc_func, $data);
        $decrypted = call_user_func($dec_func, $encrypted);
        $match = ($decrypted === $data);
        test_result("$type encryption roundtrip", $match, 
            $match ? "✓ Encrypted and decrypted successfully: " . substr($data, 0, 20) . "..." : 
                     "✗ Mismatch: Original='$data', Decrypted='$decrypted'");
    } catch (Exception $e) {
        test_result("$type encryption roundtrip", false, "Error: " . $e->getMessage());
    }
}

// Test 2: safeDecryptField
echo "<h2>Test 2: safeDecryptField</h2>";
try {
    $test_value = 'Test User';
    $encrypted_name = encryptPersonalData($test_value);
    
    // Verify it's actually encrypted (should be base64 and long)
    if (strlen($encrypted_name) < 60) {
        test_result("safeDecryptField encryption", false, "Encrypted value too short: " . strlen($encrypted_name));
    } else {
        test_result("safeDecryptField encryption", true, "Value encrypted (length: " . strlen($encrypted_name) . ")");
        
        $decrypted = safeDecryptField($encrypted_name, 'users', 'first_name');
        $match = ($decrypted === $test_value);
        test_result("safeDecryptField decryption", $match, 
            $match ? "✓ Correctly decrypted: $decrypted" : "✗ Mismatch: Expected='$test_value', Got='$decrypted'");
    }
} catch (Exception $e) {
    test_result("safeDecryptField", false, "Error: " . $e->getMessage());
}

$plain = safeDecryptField('Plain Text', 'users', 'first_name');
test_result("safeDecryptField plaintext", $plain === 'Plain Text', "Plaintext returned as-is");

// Test 3: Database queries
echo "<h2>Test 3: Database Functions</h2>";
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM users LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user) {
            $dec_first = safeDecryptField($user['first_name'] ?? '', 'users', 'first_name');
            $dec_last = safeDecryptField($user['last_name'] ?? '', 'users', 'last_name');
            $dec_email = safeDecryptField($user['email'] ?? '', 'users', 'email');
            
            test_result("Database decryption", 
                !empty($dec_first) || !empty($dec_last) || !empty($dec_email),
                "User ID {$user['id']}: Decrypted fields available");
        } else {
            test_result("Database decryption", false, "No users found");
        }
    } catch (Exception $e) {
        test_result("Database decryption", false, "Error: " . $e->getMessage());
    }
}

// Test 4: find_user_by_encrypted_identifier
echo "<h2>Test 4: Login Functions</h2>";
if (function_exists('find_user_by_encrypted_identifier') && $conn) {
    try {
        // Get a user's email
        $stmt = $conn->prepare("SELECT email FROM users WHERE is_verified = 1 LIMIT 1");
        $stmt->execute();
        $test_user = $stmt->fetch();
        
        if ($test_user) {
            $email = safeDecryptField($test_user['email'], 'users', 'email');
            $found = find_user_by_encrypted_identifier($conn, $email);
            test_result("find_user_by_encrypted_identifier", $found !== null, 
                $found ? "Found user ID: {$found['id']}" : "User not found");
        } else {
            test_result("find_user_by_encrypted_identifier", false, "No verified users");
        }
    } catch (Exception $e) {
        test_result("find_user_by_encrypted_identifier", false, "Error: " . $e->getMessage());
    }
}

// Test 5: Check admin files
echo "<h2>Test 5: File Checks</h2>";
$files = [
    'admin/view_all_users.php',
    'admin/view_logs.php',
    'admin/send_reminder.php',
    'admin/course_assignment.php'
];

$all_good = true;
foreach ($files as $file) {
    $path = __DIR__ . '/../' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        if (strpos($content, 'safeDecryptField') === false && 
            (strpos($content, 'first_name') !== false || strpos($content, 'email') !== false)) {
            $all_good = false;
            test_result("$file decryption", false, "Missing decryption");
            break;
        }
    }
}

if ($all_good) {
    test_result("Admin files decryption", true, "Key files have decryption");
}

// Summary
echo "<h2>Summary</h2>";
echo "<div class='test-section'>";
echo "<table><tr><th>Status</th><th>Count</th></tr>";
echo "<tr><td><span class='pass'>Passed</span></td><td>$tests_passed</td></tr>";
echo "<tr><td><span class='fail'>Failed</span></td><td>$tests_failed</td></tr>";
echo "</table>";

if ($tests_failed === 0) {
    echo "<p class='pass'><strong>✓ All tests passed! System is ready.</strong></p>";
} else {
    echo "<p class='fail'><strong>✗ $tests_failed test(s) failed.</strong></p>";
}
echo "</div></body></html>";
?>

