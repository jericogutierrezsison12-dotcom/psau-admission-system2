<?php
/**
 * PSAU Admission System - AES Encryption Library
 * Provides AES-256-GCM encryption for sensitive data without database changes
 */

class PSAUAESEncryption {
    private static $encryption_key = null;
    private static $initialized = false;

    /**
     * Initialize encryption with key generation/retrieval
     */
    private static function initialize() {
        if (self::$initialized) {
            return;
        }
        
        // Get encryption key from environment
        $key = getenv('AES_ENCRYPTION_KEY');
        if (empty($key)) {
            // Try to load from .env file
            $env_file = __DIR__ . '/../.env';
            if (file_exists($env_file)) {
                $env_content = file_get_contents($env_file);
                if (preg_match('/AES_ENCRYPTION_KEY=(.+)/', $env_content, $matches)) {
                    $key = trim($matches[1]);
                }
            }
            
            if (empty($key)) {
                // Use a default key for testing (in production, this should be set in environment)
                $key = 'MuKrgKrmyUOpKzSRKqy3SflowFG5xWcqCdjLu0sSV8I='; // Generated key
                error_log("Using default AES encryption key. Please set AES_ENCRYPTION_KEY in your .env file for production.");
                $key = base64_decode($key);
            } else {
                $key = base64_decode($key);
            }
        } else {
            $key = base64_decode($key);
        }
        
        if (strlen($key) !== 32) {
            throw new Exception("Invalid AES encryption key length. Must be 32 bytes.");
        }
        
        self::$encryption_key = $key;
        self::$initialized = true;
    }

    /**
     * Encrypt sensitive data
     * @param string $data Data to encrypt
     * @param string $context Context for additional authentication data
     * @return string Encrypted data with IV and tag (base64 encoded)
     */
    public static function encrypt($data, $context = '') {
        self::initialize();
        
        if (empty($data)) {
            return '';
        }
        
        // Generate random IV
        $iv = random_bytes(12);
        
        // Create additional authenticated data
        $aad = hash('sha256', $context . self::$encryption_key, true);
        
        // Encrypt the data
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-gcm',
            self::$encryption_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($encrypted === false) {
            throw new Exception("AES encryption failed: " . openssl_error_string());
        }
        
        // Combine IV + tag + encrypted data
        $result = $iv . $tag . $encrypted;
        
        return base64_encode($result);
    }

    /**
     * Decrypt sensitive data
     * @param string $encrypted_data Base64 encoded encrypted data
     * @param string $context Context used for encryption
     * @return string Decrypted data
     */
    public static function decrypt($encrypted_data, $context = '') {
        self::initialize();
        
        if (empty($encrypted_data)) {
            return '';
        }
        
        $data = base64_decode($encrypted_data);
        if ($data === false) {
            throw new Exception("Invalid base64 encrypted data");
        }
        
        // Extract IV (first 12 bytes), tag (next 16 bytes), and encrypted data
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $encrypted = substr($data, 28);
        
        $aad = hash('sha256', $context . self::$encryption_key, true);
        
        // Decrypt the data
        $decrypted = openssl_decrypt(
            $encrypted,
            'aes-256-gcm',
            self::$encryption_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );
        
        if ($decrypted === false) {
            $error = openssl_error_string();
            throw new Exception("AES decryption failed: " . ($error ?: 'Unknown error'));
        }
        
        return $decrypted;
    }
}

/**
 * Helper functions for easy encryption/decryption
 */

/**
 * Encrypt personal information
 * @param string $data Personal data to encrypt
 * @return string Encrypted data
 */
function encryptPersonalData($data) {
    if (empty($data)) return '';
    try {
        return PSAUAESEncryption::encrypt($data, 'personal_data');
    } catch (Exception $e) {
        error_log("Encrypt personal data error: " . $e->getMessage());
        throw $e; // Throw exception if encryption fails
    }
}

/**
 * Decrypt personal information
 * @param string $encrypted_data Encrypted personal data
 * @return string Decrypted data
 */
function decryptPersonalData($encrypted_data) {
    if (empty($encrypted_data)) return '';
    try {
        return PSAUAESEncryption::decrypt($encrypted_data, 'personal_data');
    } catch (Exception $e) {
        error_log("Decrypt personal data error: " . $e->getMessage());
        return $encrypted_data; // Return encrypted data if decryption fails
    }
}

/**
 * Encrypt contact information
 * @param string $data Contact data to encrypt
 * @return string Encrypted data
 */
function encryptContactData($data) {
    if (empty($data)) return '';
    try {
        return PSAUAESEncryption::encrypt($data, 'contact_data');
    } catch (Exception $e) {
        error_log("Encrypt contact data error: " . $e->getMessage());
        throw $e; // Throw exception if encryption fails
    }
}

/**
 * Decrypt contact information
 * @param string $encrypted_data Encrypted contact data
 * @return string Decrypted data
 */
function decryptContactData($encrypted_data) {
    if (empty($encrypted_data)) return '';
    try {
        return PSAUAESEncryption::decrypt($encrypted_data, 'contact_data');
    } catch (Exception $e) {
        error_log("Decrypt contact data error: " . $e->getMessage());
        return $encrypted_data; // Return encrypted data if decryption fails
    }
}

/**
 * Encrypt academic records
 * @param string $data Academic data to encrypt
 * @return string Encrypted data
 */
function encryptAcademicData($data) {
    if (empty($data)) return '';
    try {
        return PSAUAESEncryption::encrypt($data, 'academic_data');
    } catch (Exception $e) {
        error_log("Encrypt academic data error: " . $e->getMessage());
        throw $e; // Throw exception if encryption fails
    }
}

/**
 * Decrypt academic records
 * @param string $encrypted_data Encrypted academic data
 * @return string Decrypted data
 */
function decryptAcademicData($encrypted_data) {
    if (empty($encrypted_data)) return '';
    try {
        return PSAUAESEncryption::decrypt($encrypted_data, 'academic_data');
    } catch (Exception $e) {
        error_log("Decrypt academic data error: " . $e->getMessage());
        return $encrypted_data; // Return encrypted data if decryption fails
    }
}

/**
 * Smart decrypt function that tries to decrypt but returns original if it fails
 * @param string $data Data that might be encrypted
 * @param string $context Context for decryption
 * @return string Decrypted or original data
 */
function smartDecrypt($data, $context = 'personal_data') {
    if (empty($data)) return '';
    
    try {
        // Try to decrypt
        $decrypted = PSAUAESEncryption::decrypt($data, $context);
        return $decrypted;
    } catch (Exception $e) {
        // If decryption fails, assume it's not encrypted and return original
        return $data;
    }
}
