<?php
/**
 * PSAU Admission System - End-to-End Encryption Library
 * AES-256-GCM helpers for field/file encryption
 */

class PSAUEncryption {
    private static $encryption_key = null;
    private static $initialized = false;

    private static function initialize() {
        if (self::$initialized) return;
        $key = getenv('ENCRYPTION_KEY');
        if (empty($key)) {
            $key = random_bytes(32);
            error_log("Generated new encryption key. Save to .env as ENCRYPTION_KEY=" . base64_encode($key));
        } else {
            $key = base64_decode($key);
        }
        if (strlen($key) !== 32) {
            throw new Exception('Invalid encryption key length (must be 32 bytes)');
        }
        self::$encryption_key = $key;
        self::$initialized = true;
    }

    public static function encrypt($data, $context = '') {
        self::initialize();
        if ($data === null || $data === '') return '';
        $iv = random_bytes(12);
        $aad = hash('sha256', $context . self::$encryption_key, true);
        $cipher = openssl_encrypt($data, 'aes-256-gcm', self::$encryption_key, OPENSSL_RAW_DATA, $iv, $tag, $aad);
        if ($cipher === false) throw new Exception('Encryption failed');
        return base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt($encoded, $context = '') {
        self::initialize();
        if ($encoded === null || $encoded === '') return '';
        $raw = base64_decode($encoded);
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $aad = hash('sha256', $context . self::$encryption_key, true);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', self::$encryption_key, OPENSSL_RAW_DATA, $iv, $tag, $aad);
        if ($plain === false) throw new Exception('Decryption failed');
        return $plain;
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
?>
