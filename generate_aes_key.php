<?php
/**
 * Generate AES Encryption Key
 * Run this script to generate a new AES encryption key
 */

echo "=== AES Encryption Key Generator ===\n\n";

// Generate a new 32-byte key
$key = random_bytes(32);
$base64_key = base64_encode($key);

echo "Generated AES Encryption Key:\n";
echo "Base64: " . $base64_key . "\n\n";

echo "Add this to your .env file:\n";
echo "AES_ENCRYPTION_KEY=" . $base64_key . "\n\n";

echo "Or for Render/Railway environment variables:\n";
echo "AES_ENCRYPTION_KEY = " . $base64_key . "\n\n";

echo "Key length: " . strlen($key) . " bytes (32 bytes required)\n";
echo "Base64 length: " . strlen($base64_key) . " characters\n\n";

echo "=== Key Generated Successfully ===\n";
