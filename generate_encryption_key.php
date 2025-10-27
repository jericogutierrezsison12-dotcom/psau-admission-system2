<?php
/**
 * Generate Encryption Key for PSAU Admission System
 * Run this script once to generate your ENCRYPTION_KEY
 */

// Generate a secure 32-byte key
$key = random_bytes(32);

// Base64 encode for easy storage
$encoded_key = base64_encode($key);

echo "===========================================\n";
echo "ENCRYPTION KEY GENERATED\n";
echo "===========================================\n\n";
echo "Add this to your .env file:\n\n";
echo "ENCRYPTION_KEY=$encoded_key\n\n";
echo "===========================================\n";
echo "IMPORTANT: Keep this key secure!\n";
echo "===========================================\n";

// Try to add to .env file automatically
$env_file = __DIR__ . '/.env';
$env_content = '';

if (file_exists($env_file)) {
    $env_content = file_get_contents($env_file);
    
    // Check if ENCRYPTION_KEY already exists
    if (preg_match('/ENCRYPTION_KEY=/', $env_content)) {
        // Replace existing key
        $env_content = preg_replace('/ENCRYPTION_KEY=.*/', "ENCRYPTION_KEY=$encoded_key", $env_content);
    } else {
        // Append new key
        $env_content .= "\nENCRYPTION_KEY=$encoded_key\n";
    }
    
    file_put_contents($env_file, $env_content);
    echo "\n✅ Key automatically added to .env file!\n";
} else {
    echo "\n⚠️  .env file not found. Please create it manually with:\n";
    echo "ENCRYPTION_KEY=$encoded_key\n";
}

echo "\n";

