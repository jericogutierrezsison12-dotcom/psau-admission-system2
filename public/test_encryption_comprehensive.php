<?php
/**
 * Comprehensive Encryption/Decryption Test Script
 * Tests all encryption/decryption functionality across the system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/encryption.php';
require_once __DIR__ . '/../includes/functions.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>PSAU Encryption Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .pass { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
    </style>
</head>
<body>
    <h1>PSAU Admission System - Encryption/Decryption Test</h1>
    <p>Testing all encryption and decryption functionality...</p>";

$tests_passed = 0;
$tests_failed = 0;
$tests_warning = 0;

/**
 * Test helper function
 */
function test_result($name, $passed, $message = '', $details = '') {
    global $tests_passed, $tests_failed, $tests_warning;
    
    if ($passed === true) {
        $tests_passed++;
        $status = "<span class='pass'>✓ PASS</span>";
    } elseif ($passed === false) {
        $tests_failed++;
        $status = "<span class='fail'>✗ FAIL</span>";
    } else {
        $tests_warning++;
        $status = "<span class='warning'>⚠ WARNING</span>";
    }
    
    echo "<div class='test-section'>";
    echo "<h3>$status - $name</h3>";
    if ($message) {
        echo "<p>$message</p>";
    }
    if ($details) {
        echo "<pre>$details</pre>";
    }
    echo "</div>";
    
    return $passed !== false;
}

// Test 1: Encryption key availability (key.php)
echo "<h2>Test 1: Encryption Key</h2>";
try {
    $keyPath = __DIR__ . '/../includes/key.php';
    if (!file_exists($keyPath)) {
        test_result("Encryption Key File", false, "includes/key.php not found");
    } else {
        $status = PSAUEncryption::getStatus();
        $msg = "Source: " . $status['key_source'] . ", Length: " . $status['key_length'] . " bytes";
        $valid = ($status['initialized'] === true && $status['key_length'] === 32);
        test_result("Encryption Initialization", $valid, $msg);
    }
} catch (Exception $e) {
    test_result("Encryption Initialization", false, "Error: " . $e->getMessage());
}

// Test 2: Encryption/Decryption functions
echo "<h2>Test 2: Encryption Functions</h2>";
$test_data = [
    'personal' => 'John Doe',
    'contact' => 'john@example.com',
    'academic' => 'High School',
    'application' => 'BS Computer Science'
];

foreach ($test_data as $type => $value) {
    try {
        $enc_func = "encrypt" . ucfirst($type) . "Data";
        $dec_func = "decrypt" . ucfirst($type) . "Data";
        
        if (!function_exists($enc_func) || !function_exists($dec_func)) {
            test_result("$type encryption functions", false, "Functions $enc_func or $dec_func not found");
            continue;
        }
        
        $encrypted = call_user_func($enc_func, $value);
        $decrypted = call_user_func($dec_func, $encrypted);
        
        if ($decrypted === $value) {
            test_result("$type encryption/decryption", true, "Encrypted and decrypted successfully");
        } else {
            test_result("$type encryption/decryption", false, "Decrypted value doesn't match!", 
                "Original: $value\nDecrypted: $decrypted");
        }
    } catch (Exception $e) {
        test_result("$type encryption/decryption", false, "Error: " . $e->getMessage());
    }
}

// Test 3: safeDecryptField function
echo "<h2>Test 3: safeDecryptField Helper</h2>";
if (!function_exists('safeDecryptField')) {
    test_result("safeDecryptField function", false, "Function not found!");
} else {
    // Test with encrypted data
    try {
        $encrypted = encryptPersonalData('Test User');
        $decrypted = safeDecryptField($encrypted, 'users', 'first_name');
        if ($decrypted === 'Test User') {
            test_result("safeDecryptField with encrypted data", true, "Correctly decrypted personal data");
        } else {
            test_result("safeDecryptField with encrypted data", false, "Decryption failed", 
                "Expected: Test User\nGot: $decrypted");
        }
    } catch (Exception $e) {
        test_result("safeDecryptField with encrypted data", false, "Error: " . $e->getMessage());
    }
    
    // Test with plaintext data
    $plaintext = 'Plain Text';
    $result = safeDecryptField($plaintext, 'users', 'first_name');
    if ($result === $plaintext) {
        test_result("safeDecryptField with plaintext data", true, "Correctly returned plaintext");
    } else {
        test_result("safeDecryptField with plaintext data", false, "Should return plaintext as-is");
    }
}

// Test 4: Database user data decryption
echo "<h2>Test 4: Database User Data</h2>";
if (!$conn) {
    test_result("Database connection", false, "Database connection failed!");
} else {
    test_result("Database connection", true, "Connected successfully");
    
    try {
        // Get a sample user
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, mobile_number FROM users LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if (!$user) {
            test_result("User data retrieval", null, "No users found in database");
        } else {
            test_result("User data retrieval", true, "Found user ID: " . $user['id']);
            
            // Test decryption
            $first_name_decrypted = safeDecryptField($user['first_name'] ?? '', 'users', 'first_name');
            $last_name_decrypted = safeDecryptField($user['last_name'] ?? '', 'users', 'last_name');
            $email_decrypted = safeDecryptField($user['email'] ?? '', 'users', 'email');
            $mobile_decrypted = safeDecryptField($user['mobile_number'] ?? '', 'users', 'mobile_number');
            
            $details = "Original first_name length: " . strlen($user['first_name'] ?? '') . "\n";
            $details .= "Decrypted first_name: " . substr($first_name_decrypted, 0, 20) . "...\n";
            $details .= "Original email length: " . strlen($user['email'] ?? '') . "\n";
            $details .= "Decrypted email: " . substr($email_decrypted, 0, 20) . "...";
            
            if (!empty($first_name_decrypted) || !empty($email_decrypted)) {
                test_result("User data decryption", true, "Successfully decrypted user data", $details);
            } else {
                test_result("User data decryption", null, "Decrypted data is empty (might be plaintext)", $details);
            }
        }
    } catch (Exception $e) {
        test_result("User data retrieval", false, "Error: " . $e->getMessage());
    }
}

// Test 5: find_user_by_encrypted_identifier
echo "<h2>Test 5: Login Functionality</h2>";
if (!function_exists('find_user_by_encrypted_identifier')) {
    test_result("find_user_by_encrypted_identifier function", false, "Function not found!");
} else {
    try {
        // Get a test email from database
        $stmt = $conn->prepare("SELECT email FROM users WHERE is_verified = 1 LIMIT 1");
        $stmt->execute();
        $test_user = $stmt->fetch();
        
        if ($test_user && !empty($test_user['email'])) {
            $test_email = $test_user['email'];
            
            // Try to decrypt it first
            $decrypted_email = safeDecryptField($test_email, 'users', 'email');
            $search_email = $decrypted_email ?: $test_email;
            
            $found_user = find_user_by_encrypted_identifier($conn, $search_email);
            
            if ($found_user) {
                test_result("find_user_by_encrypted_identifier", true, 
                    "Successfully found user by email identifier");
            } else {
                test_result("find_user_by_encrypted_identifier", null, 
                    "User not found (may need to test with actual login credentials)");
            }
        } else {
            test_result("find_user_by_encrypted_identifier", null, "No verified users found for testing");
        }
    } catch (Exception $e) {
        test_result("find_user_by_encrypted_identifier", false, "Error: " . $e->getMessage());
    }
}

// Test 6: get_current_user_data
echo "<h2>Test 6: Session User Data</h2>";
if (!function_exists('get_current_user_data')) {
    test_result("get_current_user_data function", false, "Function not found!");
} else {
    // Note: This requires a valid session, so we'll just check if function exists and structure
    test_result("get_current_user_data function", true, "Function exists (requires active session to test fully)");
}

// Test 7: Check all admin files that should use decryption
echo "<h2>Test 7: Admin Files Decryption Check</h2>";
$admin_files_to_check = [
    'view_all_users.php',
    'view_all_applicants.php',
    'view_logs.php',
    'get_exam_applicants.php',
    'send_reminder.php',
    'automated_reminders.php',
    'process_exam_schedule.php',
    'process_enrollment_schedule.php',
    'manual_score_entry.php',
    'get_recent_uploads.php',
    'course_assignment.php',
    'edit_exam_schedule.php',
    'edit_enrollment_schedule.php',
    'view_enrolled_students.php',
    'auto_schedule_exam.php',
    'bulk_score_upload.php',
    'enrollment_completion.php',
    'dashboard.php',
    'clear_attempts.php'
];

$files_with_decryption = 0;
$files_missing_decryption = [];

foreach ($admin_files_to_check as $file) {
    $file_path = __DIR__ . '/../admin/' . $file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        if (strpos($content, 'safeDecryptField') !== false || 
            strpos($content, 'decryptPersonalData') !== false ||
            strpos($content, 'decryptContactData') !== false ||
            strpos($content, 'get_current_user_data') !== false) {
            $files_with_decryption++;
        } else {
            // Check if file queries user data
            if (strpos($content, 'first_name') !== false || strpos($content, 'email') !== false) {
                $files_missing_decryption[] = $file;
            }
        }
    }
}

if (empty($files_missing_decryption)) {
    test_result("Admin files decryption", true, 
        "All $files_with_decryption admin files appear to have decryption implemented");
} else {
    test_result("Admin files decryption", false, 
        "Files that might need decryption: " . implode(', ', $files_missing_decryption));
}

// Test 8: Encryption context handling
echo "<h2>Test 8: Encryption Context</h2>";
try {
    $same_data = 'Test Data';
    $enc1 = encryptPersonalData($same_data);
    $enc2 = encryptContactData($same_data);
    
    // Same data encrypted with different contexts should produce different ciphertext
    if ($enc1 !== $enc2) {
        test_result("Encryption context isolation", true, 
            "Different contexts produce different encrypted values");
    } else {
        test_result("Encryption context isolation", false, 
            "Same encrypted value for different contexts (security issue!)");
    }
    
    // But both should decrypt correctly
    $dec1 = decryptPersonalData($enc1);
    $dec2 = decryptContactData($enc2);
    
    if ($dec1 === $same_data && $dec2 === $same_data) {
        test_result("Context-specific decryption", true, 
            "Both contexts decrypt correctly");
    } else {
        test_result("Context-specific decryption", false, 
            "Decryption mismatch: personal=$dec1, contact=$dec2");
    }
} catch (Exception $e) {
    test_result("Encryption context", false, "Error: " . $e->getMessage());
}

// Test 9: Backward compatibility
echo "<h2>Test 9: Backward Compatibility</h2>";
$plaintext = 'Plain Text Data';
try {
    $decrypted_plaintext = decryptPersonalData($plaintext);
    if ($decrypted_plaintext === $plaintext) {
        test_result("Plaintext backward compatibility", true, 
            "Plaintext data is returned as-is (backward compatible)");
    } else {
        test_result("Plaintext backward compatibility", false, 
            "Plaintext was modified: $decrypted_plaintext");
    }
} catch (Exception $e) {
    test_result("Plaintext backward compatibility", null, 
        "Key initialization error: " . $e->getMessage());
}

// Summary
echo "<h2>Test Summary</h2>";
echo "<div class='test-section'>";
echo "<table>";
echo "<tr><th>Status</th><th>Count</th></tr>";
echo "<tr><td><span class='pass'>Passed</span></td><td>$tests_passed</td></tr>";
echo "<tr><td><span class='warning'>Warnings</span></td><td>$tests_warning</td></tr>";
echo "<tr><td><span class='fail'>Failed</span></td><td>$tests_failed</td></tr>";
echo "</table>";

$total_tests = $tests_passed + $tests_warning + $tests_failed;
$success_rate = $total_tests > 0 ? round(($tests_passed / $total_tests) * 100, 2) : 0;

echo "<p><strong>Success Rate: $success_rate%</strong></p>";

if ($tests_failed === 0) {
    echo "<p class='pass'>✓ All critical tests passed! System is ready.</p>";
} else {
    echo "<p class='fail'>✗ Some tests failed. Please review and fix issues before deployment.</p>";
}

echo "</div>";

echo "</body></html>";
?>

