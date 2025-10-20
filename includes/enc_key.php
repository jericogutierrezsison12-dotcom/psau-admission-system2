<?php
/**
 * Application Encryption Key
 *
 * Strategy (in order):
 * 1) Use env var APP_ENC_KEY_B64 if set
 * 2) Else load from includes/.app_key (auto-generated on first run)
 * 3) Else fallback to constant (generated and persisted during this request)
 */

// Try environment variable first
$envKey = getenv('APP_ENC_KEY_B64');

// Path to persisted key file (outside web root preferred; here kept in includes)
$persistPath = __DIR__ . DIRECTORY_SEPARATOR . '.app_key';

// Lazy-generate and persist a key if none exists
function ensure_persisted_app_key($persistPath) {
    if (!file_exists($persistPath)) {
        try {
            $raw = random_bytes(32);
            $b64 = base64_encode($raw);
            // Restrictive permissions when possible
            @file_put_contents($persistPath, $b64, LOCK_EX);
            @chmod($persistPath, 0600);
            return $b64;
        } catch (Exception $e) {
            error_log('Failed generating APP_ENC_KEY_B64: ' . $e->getMessage());
            return null;
        }
    }
    $b64 = trim(@file_get_contents($persistPath) ?: '');
    return $b64 !== '' ? $b64 : null;
}

$fileKey = ensure_persisted_app_key($persistPath);

$selectedB64 = $envKey ?: $fileKey;

if (!defined('APP_ENC_KEY_B64')) {
    define('APP_ENC_KEY_B64', $selectedB64 ?: '');
}

/**
 * Get raw 32-byte encryption key
 * @return string|null 32-byte key or null if invalid
 */
function get_app_encryption_key() {
    $b64 = APP_ENC_KEY_B64;
    if ($b64 === '' || $b64 === null) {
        error_log('APP_ENC_KEY_B64 is not set and could not be generated');
        return null;
    }
    $key = base64_decode($b64, true);
    if ($key === false || $key === null || strlen($key) !== 32) {
        error_log('Invalid APP_ENC_KEY_B64: must be base64 of 32 random bytes');
        return null;
    }
    return $key;
}
?>

