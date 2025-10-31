<?php
/**
 * PSAU Admission System - Encryption Library v2
 * Unified AES-256-GCM with explicit prefix and backward compatibility.
 * New format: psau:v2:<b64(iv(12) + tag(16) + ciphertext)>
 */

final class PSAUEncryption {
    private const PREFIX = 'psau:v2:';
    private static $encryption_key = null;
    private static $initialized = false;

    private static function initialize() {
        if (self::$initialized) return;
        $keyBytes = null;
        $keyB64 = getenv('ENCRYPTION_KEY_B64') ?: ($_ENV['ENCRYPTION_KEY_B64'] ?? '');
        if (!empty($keyB64)) {
            $decoded = base64_decode($keyB64, true);
            if ($decoded !== false) { $keyBytes = $decoded; }
        }
        if ($keyBytes === null) {
            $legacy = getenv('ENCRYPTION_KEY') ?: ($_ENV['ENCRYPTION_KEY'] ?? '');
            if (!empty($legacy)) {
                $decoded = base64_decode($legacy, true);
                if ($decoded !== false) { $keyBytes = $decoded; }
            }
        }
        if ($keyBytes === null) {
            $secretPath = __DIR__ . '/secret_key.php';
            if (file_exists($secretPath)) {
                include $secretPath;
                if (isset($ENCRYPTION_KEY_B64) && !empty($ENCRYPTION_KEY_B64)) {
                    $decoded = base64_decode($ENCRYPTION_KEY_B64, true);
                    if ($decoded !== false) { $keyBytes = $decoded; }
                }
            }
        }
        if ($keyBytes === null || strlen($keyBytes) !== 32) {
            throw new Exception('Missing or invalid ENCRYPTION_KEY_B64. Provide a base64-encoded 32-byte key.');
        }
        self::$encryption_key = $keyBytes;
        self::$initialized = true;
    }

    private static function isNewFormat($value) {
        if (!is_string($value)) return false;
        return substr($value, 0, strlen(self::PREFIX)) === self::PREFIX;
    }

    public static function encrypt($data, $context = '') {
        self::initialize();
        if ($data === null || $data === '') return '';
        // Avoid double-encrypting if already in new format
        if (self::isNewFormat($data)) return $data;
        $iv = random_bytes(12);
        $aad = hash('sha256', $context . self::$encryption_key, true);
        $cipher = openssl_encrypt($data, 'aes-256-gcm', self::$encryption_key, OPENSSL_RAW_DATA, $iv, $tag, $aad);
        if ($cipher === false) throw new Exception('Encryption failed');
        return self::PREFIX . base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt($value, $context = '') {
        self::initialize();
        if ($value === null || $value === '') return '';
        // New format
        if (self::isNewFormat($value)) {
            $encoded = substr($value, strlen(self::PREFIX));
            $raw = base64_decode($encoded, true);
            if ($raw === false || strlen($raw) < 28) throw new Exception('Invalid ciphertext');
            $iv = substr($raw, 0, 12);
            $tag = substr($raw, 12, 16);
            $cipher = substr($raw, 28);
            $aad = hash('sha256', $context . self::$encryption_key, true);
            $plain = openssl_decrypt($cipher, 'aes-256-gcm', self::$encryption_key, OPENSSL_RAW_DATA, $iv, $tag, $aad);
            if ($plain === false) throw new Exception('Decryption failed');
            return $plain;
        }
        // Backward compatibility: try legacy raw base64(iv+tag+cipher)
        $raw = base64_decode($value, true);
        if (is_string($raw) && strlen($raw) >= 28) {
            $iv = substr($raw, 0, 12);
            $tag = substr($raw, 12, 16);
            $cipher = substr($raw, 28);
            $aad = hash('sha256', $context . self::$encryption_key, true);
            $plain = openssl_decrypt($cipher, 'aes-256-gcm', self::$encryption_key, OPENSSL_RAW_DATA, $iv, $tag, $aad);
            if ($plain !== false) return $plain;
        }
        // Treat as plaintext if nothing worked
        return $value;
    }

    public static function encryptFile($content, $filePath) {
        return self::encrypt($content, 'file_' . basename($filePath));
    }
    public static function decryptFile($content, $filePath) {
        return self::decrypt($content, 'file_' . basename($filePath));
    }
}

function enc_personal($v){ return PSAUEncryption::encrypt($v, 'personal'); }
function dec_personal($v){ return PSAUEncryption::decrypt($v, 'personal'); }
function enc_contact($v){ return PSAUEncryption::encrypt($v, 'contact'); }
function dec_contact($v){ return PSAUEncryption::decrypt($v, 'contact'); }
function enc_academic($v){ return PSAUEncryption::encrypt($v, 'academic'); }
function dec_academic($v){ return PSAUEncryption::decrypt($v, 'academic'); }
function enc_application($v){ return PSAUEncryption::encrypt($v, 'application'); }
function dec_application($v){ return PSAUEncryption::decrypt($v, 'application'); }
