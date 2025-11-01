<?php
/**
 * Test Encryption Key from key.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Encryption Key Test</h1>";

require_once __DIR__ . '/../includes/encryption.php';

// Check if key was loaded
try {
    $test_data = "Test encryption with key.php";
    
    // Try to encrypt
    $encrypted = encryptPersonalData($test_data);
    echo "<p>✓ Encryption successful (length: " . strlen($encrypted) . ")</p>";
    
    // Try to decrypt
    $decrypted = decryptPersonalData($encrypted);
    echo "<p>✓ Decryption successful</p>";
    
    if ($decrypted === $test_data) {
        echo "<p style='color:green;'>✓✓✓ KEY.PHP IS WORKING! Encryption/Decryption roundtrip successful!</p>";
    } else {
        echo "<p style='color:red;'>✗ Decryption mismatch: Expected '$test_data', Got '$decrypted'</p>";
    }
    
    // Test with database data
    require_once __DIR__ . '/../includes/db_connect.php';
    if ($conn) {
        echo "<p>✓ Database connection successful</p>";
        
        $stmt = $conn->prepare("SELECT first_name, email FROM users LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user) {
            $dec_first = safeDecryptField($user['first_name'] ?? '', 'users', 'first_name');
            $dec_email = safeDecryptField($user['email'] ?? '', 'users', 'email');
            
            echo "<p>✓ Database decryption test:</p>";
            echo "<ul>";
            echo "<li>First Name (original length: " . strlen($user['first_name'] ?? '') . ", decrypted: " . substr($dec_first, 0, 20) . "...)</li>";
            echo "<li>Email (original length: " . strlen($user['email'] ?? '') . ", decrypted: " . substr($dec_email, 0, 20) . "...)</li>";
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check key source
$keyPath = __DIR__ . '/../includes/key.php';
if (file_exists($keyPath)) {
    echo "<p style='color:green;'>✓ key.php file exists at: $keyPath</p>";
} else {
    echo "<p style='color:red;'>✗ key.php file NOT found at: $keyPath</p>";
}

echo "<hr>";
echo "<p>Test completed. Check results above.</p>";
?>

