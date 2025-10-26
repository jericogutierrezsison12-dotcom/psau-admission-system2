<?php
/**
 * Test script to verify AES-256-GCM encryption is working
 */

require_once 'includes/encryption.php';

echo "=== PSAU Admission System - Encryption Test ===\n\n";

try {
    // Test 1: Basic encryption/decryption
    echo "1. Testing basic encryption/decryption...\n";
    $test_data = "This is a test of the encryption system.";
    $encrypted = PSAUEncryption::encrypt($test_data, 'test');
    $decrypted = PSAUEncryption::decrypt($encrypted, 'test');
    
    if ($decrypted === $test_data) {
        echo "   ✓ Basic encryption/decryption working\n";
    } else {
        echo "   ✗ Basic encryption/decryption failed\n";
    }
    
    // Test 2: Database encryption
    echo "\n2. Testing database encryption...\n";
    $db_encrypted = PSAUEncryption::encryptForDatabase($test_data, 'users', 'first_name');
    $db_decrypted = PSAUEncryption::decryptFromDatabase($db_encrypted, 'users', 'first_name');
    
    if ($db_decrypted === $test_data) {
        echo "   ✓ Database encryption working\n";
    } else {
        echo "   ✗ Database encryption failed\n";
    }
    
    // Test 3: File encryption
    echo "\n3. Testing file encryption...\n";
    $file_encrypted = PSAUEncryption::encryptFile($test_data, 'test.txt');
    $file_decrypted = PSAUEncryption::decryptFile($file_encrypted, 'test.txt');
    
    if ($file_decrypted === $test_data) {
        echo "   ✓ File encryption working\n";
    } else {
        echo "   ✗ File encryption failed\n";
    }
    
    // Test 4: Helper functions
    echo "\n4. Testing helper functions...\n";
    $personal_data = "John Doe";
    $contact_data = "john@example.com";
    $academic_data = "95.5";
    $application_data = "I want to study computer science.";
    
    $enc_personal = encryptPersonalData($personal_data);
    $enc_contact = encryptContactData($contact_data);
    $enc_academic = encryptAcademicData($academic_data);
    $enc_application = encryptApplicationData($application_data);
    
    $dec_personal = decryptPersonalData($enc_personal);
    $dec_contact = decryptContactData($enc_contact);
    $dec_academic = decryptAcademicData($enc_academic);
    $dec_application = decryptApplicationData($enc_application);
    
    if ($dec_personal === $personal_data && 
        $dec_contact === $contact_data && 
        $dec_academic === $academic_data && 
        $dec_application === $application_data) {
        echo "   ✓ Helper functions working\n";
    } else {
        echo "   ✗ Helper functions failed\n";
    }
    
    // Test 5: Encryption status
    echo "\n5. Checking encryption status...\n";
    $status = PSAUEncryption::getStatus();
    echo "   Algorithm: " . $status['algorithm'] . "\n";
    echo "   Key Length: " . $status['key_length'] . " bytes\n";
    echo "   Initialized: " . ($status['initialized'] ? 'Yes' : 'No') . "\n";
    echo "   Key Source: " . $status['key_source'] . "\n";
    
    echo "\n=== All encryption tests completed! ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
