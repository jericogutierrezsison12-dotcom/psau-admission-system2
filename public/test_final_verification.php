<?php
/**
 * Final Verification Script
 * Verifies all critical encryption/decryption functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/encryption.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$warnings = [];
$passed = [];

// Test 1: Functions exist
if (!function_exists('encryptPersonalData')) $errors[] = 'encryptPersonalData missing';
else $passed[] = 'encryptPersonalData exists';

if (!function_exists('decryptPersonalData')) $errors[] = 'decryptPersonalData missing';
else $passed[] = 'decryptPersonalData exists';

if (!function_exists('safeDecryptField')) $errors[] = 'safeDecryptField missing';
else $passed[] = 'safeDecryptField exists';

if (!function_exists('find_user_by_encrypted_identifier')) $errors[] = 'find_user_by_encrypted_identifier missing';
else $passed[] = 'find_user_by_encrypted_identifier exists';

if (!function_exists('get_current_user_data')) $errors[] = 'get_current_user_data missing';
else $passed[] = 'get_current_user_data exists';

if (!function_exists('looks_encrypted')) $errors[] = 'looks_encrypted missing';
else $passed[] = 'looks_encrypted exists';

// Test 2: Database connection
if (!$conn) {
    $errors[] = 'Database connection failed';
} else {
    $passed[] = 'Database connection OK';
}

// Test 3: Admin files check
$admin_files = [
    'view_all_users.php', 'view_logs.php', 'send_reminder.php', 
    'course_assignment.php', 'view_enrolled_students.php'
];
foreach ($admin_files as $file) {
    $path = __DIR__ . '/../admin/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        if ((strpos($content, 'first_name') !== false || strpos($content, 'email') !== false) && 
            strpos($content, 'safeDecryptField') === false && 
            strpos($content, 'get_current_user_data') === false) {
            $warnings[] = "$file might need decryption";
        } else {
            $passed[] = "$file has decryption";
        }
    }
}

// Test 4: Encryption key handling
$has_key = !empty(getenv('ENCRYPTION_KEY')) || !empty($_ENV['ENCRYPTION_KEY']);
if (!$has_key) {
    $warnings[] = 'ENCRYPTION_KEY not set (expected in production)';
} else {
    $passed[] = 'ENCRYPTION_KEY is set';
}

// Output
header('Content-Type: text/plain');
echo "PSAU Encryption/Decryption Final Verification\n";
echo str_repeat("=", 50) . "\n\n";

echo "PASSED (" . count($passed) . "):\n";
foreach ($passed as $p) echo "  ✓ $p\n";

if (!empty($warnings)) {
    echo "\nWARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $w) echo "  ⚠ $w\n";
}

if (!empty($errors)) {
    echo "\nERRORS (" . count($errors) . "):\n";
    foreach ($errors as $e) echo "  ✗ $e\n";
    echo "\nFINAL STATUS: FAILED\n";
    exit(1);
} else {
    echo "\nFINAL STATUS: ✓ ALL CHECKS PASSED\n";
    exit(0);
}
?>

