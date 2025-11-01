<?php
/**
 * Encryption Debug Page
 * Use this to test encryption/decryption and diagnose issues
 * WARNING: Remove this file after debugging for security!
 */

require_once '../includes/db_connect.php';
require_once '../includes/encryption.php';
require_once '../includes/functions.php';

// Simple authentication check - only allow in development or with admin access
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Access denied. Please login first.");
}

// Check if user is admin (you can modify this check)
// For now, allow any logged-in user for debugging
$is_admin = true; // Change this to check actual admin status

?>
<!DOCTYPE html>
<html>
<head>
    <title>Encryption Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        .warning { background: #fff3cd; border-color: #ffeaa7; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 3px; overflow-x: auto; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
        .test-result { margin: 10px 0; padding: 10px; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Encryption Debug Tool</h1>
        <p><strong>WARNING:</strong> Remove this file after debugging!</p>

        <?php
        // Test 1: Check for includes/key.php
        echo '<div class="section">';
        echo '<h2>1. Key File Check</h2>';
        $keyPath = __DIR__ . '/../includes/key.php';
        if (file_exists($keyPath)) {
            echo '<div class="test-result success">✓ Encryption key file found at <strong>includes/key.php</strong>.</div>';
            require $keyPath;
            if (isset($ENCRYPTION_KEY) && !empty($ENCRYPTION_KEY)) {
                echo '<div class="test-result success">✓ <strong>$ENCRYPTION_KEY</strong> is defined and not empty.</div>';
            } else {
                echo '<div class="test-result error">✗ <strong>$ENCRYPTION_KEY</strong> is not defined or is empty in <strong>includes/key.php</strong>.</div>';
            }
        } else {
            echo '<div class="test-result error"><strong>CRITICAL:</strong> Encryption key file not found at <strong>includes/key.php</strong>!</div>';
        }
        echo '</div>';

        // Test 2: Try to initialize encryption
        echo '<div class="section">';
        echo '<h2>2. Encryption Initialization Test</h2>';
        try {
            $status = PSAUEncryption::getStatus();
            echo '<div class="test-result success">✓ Encryption initialized successfully</div>';
            echo '<pre>';
            echo "Initialized: " . ($status['initialized'] ? 'Yes' : 'No') . "\n";
            echo "Key Length: " . $status['key_length'] . " bytes\n";
            echo "Algorithm: " . $status['algorithm'] . "\n";
            echo "Key Source: " . $status['key_source'] . "\n";
            echo '</pre>';
        } catch (Exception $e) {
            echo '<div class="test-result error">✗ Encryption initialization failed</div>';
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        }
        echo '</div>';

        // Test 3: Test encrypt/decrypt
        echo '<div class="section">';
        echo '<h2>3. Encrypt/Decrypt Test</h2>';
        try {
            $test_data = "test@example.com";
            $encrypted = encryptContactData($test_data);
            $decrypted = decryptContactData($encrypted);
            
            if ($decrypted === $test_data) {
                echo '<div class="test-result success">✓ Encrypt/Decrypt works correctly!</div>';
                echo '<pre>';
                echo "Original: " . htmlspecialchars($test_data) . "\n";
                echo "Encrypted: " . htmlspecialchars(substr($encrypted, 0, 50)) . "...\n";
                echo "Decrypted: " . htmlspecialchars($decrypted) . "\n";
                echo '</pre>';
            } else {
                echo '<div class="test-result error">✗ Decryption returned wrong value!</div>';
                echo '<pre>';
                echo "Original: " . htmlspecialchars($test_data) . "\n";
                echo "Decrypted: " . htmlspecialchars($decrypted) . "\n";
                echo '</pre>';
            }
        } catch (Exception $e) {
            echo '<div class="test-result error">✗ Encrypt/Decrypt test failed</div>';
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        }
        echo '</div>';

        // Test 4: Check database users
        echo '<div class="section">';
        echo '<h2>4. Database Users Check</h2>';
        try {
            $stmt = $conn->prepare("SELECT id, email, mobile_number, first_name, last_name FROM users WHERE is_verified = 1 LIMIT 5");
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            echo '<div class="test-result success">✓ Found ' . count($users) . ' verified user(s)</div>';
            
            foreach ($users as $user) {
                echo '<div style="margin: 15px 0; padding: 10px; background: #f9f9f9; border-radius: 5px;">';
                echo '<strong>User ID: ' . $user['id'] . '</strong><br>';
                
                // Check if email looks encrypted
                $email_encrypted = looks_encrypted($user['email']);
                echo 'Email: ';
                if ($email_encrypted) {
                    echo '<span style="color: orange;">[ENCRYPTED]</span> ';
                    try {
                        $decrypted_email = decryptContactData($user['email']);
                        echo htmlspecialchars($decrypted_email) . ' ✓ Decryption OK';
                    } catch (Exception $e) {
                        echo '<span style="color: red;">✗ Decryption FAILED: ' . htmlspecialchars($e->getMessage()) . '</span>';
                    }
                } else {
                    echo htmlspecialchars($user['email']) . ' [NOT ENCRYPTED]';
                }
                echo '<br>';
                
                // Check if mobile looks encrypted
                if (!empty($user['mobile_number'])) {
                    $mobile_encrypted = looks_encrypted($user['mobile_number']);
                    echo 'Mobile: ';
                    if ($mobile_encrypted) {
                        echo '<span style="color: orange;">[ENCRYPTED]</span> ';
                        try {
                            $decrypted_mobile = decryptContactData($user['mobile_number']);
                            echo htmlspecialchars($decrypted_mobile) . ' ✓ Decryption OK';
                        } catch (Exception $e) {
                            echo '<span style="color: red;">✗ Decryption FAILED: ' . htmlspecialchars($e->getMessage()) . '</span>';
                        }
                    } else {
                        echo htmlspecialchars($user['mobile_number']) . ' [NOT ENCRYPTED]';
                    }
                    echo '<br>';
                }
                
                // Check name
                if (!empty($user['first_name'])) {
                    $name_encrypted = looks_encrypted($user['first_name']);
                    echo 'Name: ';
                    if ($name_encrypted) {
                        echo '<span style="color: orange;">[ENCRYPTED]</span> ';
                        try {
                            $decrypted_name = decryptPersonalData($user['first_name']);
                            echo htmlspecialchars($decrypted_name . ' ' . ($user['last_name'] ?? '')) . ' ✓ Decryption OK';
                        } catch (Exception $e) {
                            echo '<span style="color: red;">✗ Decryption FAILED: ' . htmlspecialchars($e->getMessage()) . '</span>';
                        }
                    } else {
                        echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')) . ' [NOT ENCRYPTED]';
                    }
                }
                
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="test-result error">✗ Database check failed</div>';
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        }
        echo '</div>';

        // Test 5: Login test simulation
        echo '<div class="section">';
        echo '<h2>5. Login Test Simulation</h2>';
        if (isset($_POST['test_email'])) {
            $test_email = trim($_POST['test_email']);
            echo '<div style="margin: 10px 0;">';
            echo '<strong>Testing login lookup for: ' . htmlspecialchars($test_email) . '</strong><br>';
            
            try {
                $found_user = find_user_by_encrypted_identifier($conn, $test_email);
                if ($found_user) {
                    echo '<div class="test-result success">✓ User found!</div>';
                    echo '<pre>User ID: ' . $found_user['id'] . "\n";
                    echo "Email: " . htmlspecialchars($found_user['email'] ?? 'N/A') . "\n";
                    echo "Mobile: " . htmlspecialchars($found_user['mobile_number'] ?? 'N/A') . "\n";
                    echo '</pre>';
                } else {
                    echo '<div class="test-result error">✗ User NOT found</div>';
                }
            } catch (Exception $e) {
                echo '<div class="test-result error">✗ Lookup failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            echo '</div>';
        }
        
        echo '<form method="POST" style="margin-top: 10px;">';
        echo '<input type="text" name="test_email" placeholder="Enter email or mobile to test" style="padding: 8px; width: 300px;">';
        echo '<button type="submit" style="padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer;">Test Lookup</button>';
        echo '</form>';
        echo '</div>';
        ?>

        <div class="section warning">
            <h2>⚠ Important Notes</h2>
            <ul>
                <li>The encryption key is now loaded exclusively from <strong>includes/key.php</strong>.</li>
                <li>Ensure this file exists and the <strong>$ENCRYPTION_KEY</strong> variable is correctly defined.</li>
                <li>If decryption fails, it is likely due to an incorrect or missing key in that file.</li>
                <li>Never commit <strong>includes/key.php</strong> to version control if it contains a production key.</li>
            </ul>
        </div>
    </div>
</body>
</html>

