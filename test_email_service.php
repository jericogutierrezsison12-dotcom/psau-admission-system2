<?php
/**
 * Email Service Test Script
 * Tests the Firebase email function and verifies credentials
 */

require_once __DIR__ . '/firebase/firebase_email.php';

// Test email address
$test_email = 'siriyaporn.kwangusan@gmail.com';

echo "<h2>Email Service Test</h2>";
echo "<pre>";

// Test 1: Check Firebase configuration
echo "=== Test 1: Firebase Configuration ===\n";
global $firebase_config;
echo "Email Function URL: " . ($firebase_config['email_function_url'] ?? 'NOT SET') . "\n";
echo "Status: " . (!empty($firebase_config['email_function_url']) ? "✅ OK" : "❌ MISSING") . "\n\n";

// Test 2: Test sending email via Firebase
echo "=== Test 2: Sending Test Email ===\n";
echo "To: $test_email\n";
echo "Subject: PSAU Admission System - Email Service Test\n";
echo "Sending...\n\n";

try {
    $subject = "PSAU Admission System - Email Service Test";
    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
            <h2>Pampanga State Agricultural University</h2>
        </div>
        <div style='padding: 20px; border: 1px solid #ddd;'>
            <h3>Email Service Test</h3>
            <p>This is a test email to verify that the email service is working correctly.</p>
            <p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p>If you received this email, the email service is functioning properly.</p>
        </div>
        <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
            <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
        </div>
    </div>";
    
    $result = firebase_send_email($test_email, $subject, $message);
    
    if (is_array($result) && !empty($result['success'])) {
        echo "✅ SUCCESS: Email sent successfully!\n";
        echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ FAILED: Email sending failed\n";
        echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "This error indicates:\n";
    echo "- Firebase function may not be accessible\n";
    echo "- SMTP credentials may be incorrect\n";
    echo "- Network connectivity issues\n";
}

echo "\n=== Test 3: Direct Firebase Function Test ===\n";
$function_url = $firebase_config['email_function_url'] ?? '';
if (!empty($function_url)) {
    echo "Testing function URL: $function_url\n";
    
    $test_payload = [
        'to' => $test_email,
        'subject' => 'Direct Function Test',
        'html' => '<p>This is a direct function test.</p>'
    ];
    
    $ch = curl_init($function_url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($test_payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Status: $http_code\n";
    if ($curl_error) {
        echo "cURL Error: $curl_error\n";
    }
    echo "Response: " . substr($response, 0, 500) . "\n";
    
    if ($http_code === 200) {
        echo "✅ Function is accessible\n";
    } else {
        echo "❌ Function returned error code: $http_code\n";
    }
} else {
    echo "❌ Function URL not configured\n";
}

echo "\n=== Recommendations ===\n";
echo "1. Check Firebase logs: firebase functions:log\n";
echo "2. Verify SMTP_USER secret: firebase functions:secrets:access SMTP_USER\n";
echo "3. Verify SMTP_PASS secret: firebase functions:secrets:access SMTP_PASS\n";
echo "4. Ensure App Password is 16 characters (no spaces)\n";
echo "5. Ensure 2-Step Verification is enabled on Gmail account\n";

echo "</pre>";
?>


