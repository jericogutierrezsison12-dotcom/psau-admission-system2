<?php
/**
 * Test AES Encryption System
 * Verifies that encryption/decryption is working properly
 */

require_once 'includes/aes_encryption.php';

echo "=== AES Encryption Test ===\n\n";

// Test data
$test_data = [
    'personal' => 'John Doe',
    'contact' => 'john.doe@example.com',
    'academic' => 'Bachelor of Science in Computer Science',
    'application' => 'Previous School: ABC High School'
];

echo "Testing encryption/decryption...\n\n";

foreach ($test_data as $type => $data) {
    echo "Testing $type data: '$data'\n";
    
    try {
        // Encrypt
        $encrypted = '';
        switch ($type) {
            case 'personal':
                $encrypted = encryptPersonalData($data);
                break;
            case 'contact':
                $encrypted = encryptContactData($data);
                break;
            case 'academic':
                $encrypted = encryptAcademicData($data);
                break;
            case 'application':
                $encrypted = encryptApplicationData($data);
                break;
        }
        
        echo "  Encrypted: " . substr($encrypted, 0, 50) . "...\n";
        
        // Decrypt
        $decrypted = '';
        switch ($type) {
            case 'personal':
                $decrypted = decryptPersonalData($encrypted);
                break;
            case 'contact':
                $decrypted = decryptContactData($encrypted);
                break;
            case 'academic':
                $decrypted = decryptAcademicData($encrypted);
                break;
            case 'application':
                $decrypted = decryptApplicationData($encrypted);
                break;
        }
        
        echo "  Decrypted: '$decrypted'\n";
        
        // Verify
        if ($data === $decrypted) {
            echo "  ✓ SUCCESS: Data matches\n";
        } else {
            echo "  ✗ FAILED: Data mismatch\n";
        }
        
    } catch (Exception $e) {
        echo "  ✗ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Test smart decrypt with unencrypted data
echo "Testing smart decrypt with unencrypted data...\n";
$unencrypted = 'This is not encrypted';
$result = smartDecrypt($unencrypted, 'personal_data');
echo "Original: '$unencrypted'\n";
echo "Result: '$result'\n";
if ($unencrypted === $result) {
    echo "✓ SUCCESS: Smart decrypt handles unencrypted data\n";
} else {
    echo "✗ FAILED: Smart decrypt failed\n";
}

echo "\n=== Test Complete ===\n";

// Show encryption status
$status = PSAUAESEncryption::getStatus();
echo "\nEncryption Status:\n";
echo "- Initialized: " . ($status['initialized'] ? 'Yes' : 'No') . "\n";
echo "- Key Length: " . $status['key_length'] . " bytes\n";
echo "- Key Source: " . $status['key_source'] . "\n";
