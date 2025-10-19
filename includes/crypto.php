<?php
/**
 * Crypto helpers using AES-256-GCM
 */

require_once __DIR__ . '/enc_key.php';

/**
 * Encrypt plaintext using AES-256-GCM.
 * Returns URL-safe base64 string containing iv:tag:ciphertext.
 *
 * @param string $plaintext
 * @param string $aad Additional authenticated data (optional)
 * @return string|null
 */
function encrypt_aes_gcm($plaintext, $aad = '') {
    $key = get_app_encryption_key();
    if ($key === null) {
        return null;
    }

    $iv = random_bytes(12); // 96-bit nonce recommended for GCM
    $tag = '';
    $ciphertext = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        $aad,
        16 // tag length
    );

    if ($ciphertext === false) {
        error_log('openssl_encrypt failed');
        return null;
    }

    $payload = base64_encode($iv) . ':' . base64_encode($tag) . ':' . base64_encode($ciphertext);
    // Make URL-safe
    $payload = rtrim(strtr($payload, '+/', '-_'), '=');
    return $payload;
}

/**
 * Decrypt AES-256-GCM payload produced by encrypt_aes_gcm.
 *
 * @param string $payload URL-safe base64 iv:tag:ciphertext
 * @param string $aad
 * @return string|null
 */
function decrypt_aes_gcm($payload, $aad = '') {
    $key = get_app_encryption_key();
    if ($key === null) {
        return null;
    }

    // Restore padding and split components
    $b64 = strtr($payload, '-_', '+/');
    $pad = strlen($b64) % 4;
    if ($pad) {
        $b64 .= str_repeat('=', 4 - $pad);
    }
    $parts = explode(':', $b64);
    if (count($parts) !== 3) {
        return null;
    }

    $iv = base64_decode($parts[0], true);
    $tag = base64_decode($parts[1], true);
    $ciphertext = base64_decode($parts[2], true);

    if ($iv === false || $tag === false || $ciphertext === false) {
        return null;
    }

    $plaintext = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        $aad
    );

    if ($plaintext === false) {
        return null;
    }

    return $plaintext;
}
?>

