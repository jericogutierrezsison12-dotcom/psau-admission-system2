<?php
/**
 * PSAU Admission System - End-to-End Encryption Library
 * Provides AES-256-GCM encryption for sensitive data
 */

class PSAUEncryption {
    private static $encryption_key = null;
    private static $initialized = false;
    
    /**
     * Initialize encryption with key generation/retrieval
     */
    private static function initialize() {
        if (self::$initialized) {
            return;
        }

        // Strictly load encryption key from includes/key.php
        $keyPath = __DIR__ . '/key.php';
        if (!file_exists($keyPath)) {
            throw new Exception('Encryption key file not found. Please create includes/key.php');
        }

        // key.php must define $ENCRYPTION_KEY
        require $keyPath;
        if (!isset($ENCRYPTION_KEY) || empty($ENCRYPTION_KEY)) {
            throw new Exception('ENCRYPTION_KEY is not defined or is empty in includes/key.php');
        }

        $key = $ENCRYPTION_KEY;

        // Allow base64-encoded key or raw 32-byte key
        $decoded_key = base64_decode($key, true);
        if ($decoded_key !== false && strlen($decoded_key) === 32) {
            $key = $decoded_key;
        } elseif (strlen($key) === 32) {
            // Use raw 32-byte key as-is
        } else {
            error_log('CRITICAL ERROR: ENCRYPTION_KEY is not valid base64 or not 32 bytes!');
            error_log('Key length: ' . strlen($key));
            throw new Exception('Invalid ENCRYPTION_KEY format. Must be a base64-encoded 32-byte key or raw 32-byte string.');
        }

        if (strlen($key) !== 32) {
            error_log('CRITICAL ERROR: Encryption key length is ' . strlen($key) . ' bytes, expected 32 bytes!');
            throw new Exception('Invalid encryption key length. Must be 32 bytes.');
        }

        self::$encryption_key = $key;
        self::$initialized = true;

        // Log key status (first 4 chars only for security)
        $key_preview = base64_encode(substr($key, 0, 4));
        error_log('Encryption initialized successfully. Key preview: ' . $key_preview . '... (Key loaded from includes/key.php)');
    }
    
    /**
     * Generate a new encryption key
     */
    private static function generateEncryptionKey() {
        return random_bytes(32); // 256 bits
    }
    
    /**
     * Encrypt sensitive data
     * @param string $data Data to encrypt
     * @param string $context Additional context for authentication
     * @return string Encrypted data with IV and tag (base64 encoded)
     */
    public static function encrypt($data, $context = '') {
        self::initialize();
        
        if (empty($data)) {
            return '';
        }
        
        // Check if key is available for encryption
        if (self::$encryption_key === null) {
            throw new Exception('Encryption key is not available. Please check includes/key.php.');
        }
        
        // Generate random IV (12 bytes for GCM)
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
            $tag,
            $aad
        );
        
        if ($encrypted === false) {
            throw new Exception("Encryption failed: " . openssl_error_string());
        }
        
        // Combine IV + tag + encrypted data
        $result = $iv . $tag . $encrypted;
        
        // Return base64 encoded result
        return base64_encode($result);
    }
    
    /**
     * Decrypt sensitive data
     * @param string $encrypted_data Base64 encoded encrypted data or plaintext
     * @param string $context Additional context for authentication
     * @return string Decrypted data or original if not encrypted
     */
    public static function decrypt($encrypted_data, $context = '') {
        self::initialize();
        
        if (empty($encrypted_data)) {
            return '';
        }
        
        // Check if it looks like encrypted data (base64, long enough)
        // Encrypted data is typically base64 and longer than 60 characters
        $looks_encrypted = (strlen($encrypted_data) > 60 && preg_match('/^[A-Za-z0-9+\/=]+$/', $encrypted_data));
        
        if (!$looks_encrypted) {
            // Doesn't look encrypted, return as-is (plaintext)
            return $encrypted_data;
        }
        
        // If no encryption key available, can't decrypt - return as-is
        if (self::$encryption_key === null) {
            error_log('WARNING: Cannot decrypt data - Encryption key not set. Returning as-is.');
            return $encrypted_data;
        }
        
        // Try to decode base64
        $data = base64_decode($encrypted_data, true);
        if ($data === false || strlen($data) < 28) {
            // Not valid base64 or too short - likely plaintext
            return $encrypted_data;
        }
        
        // Extract IV (first 12 bytes), tag (next 16 bytes), and encrypted data
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $encrypted = substr($data, 28);
        
        // Create additional authenticated data
        $aad = hash('sha256', $context . self::$encryption_key, true);
        
        // Try to decrypt the data
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
            // Decryption failed - might be plaintext or wrong key, return as-is
            error_log("Decryption failed for data (context: $context), returning as-is");
            return $encrypted_data;
        }
        
        return $decrypted;
    }
    
    /**
     * Encrypt data for database storage
     * @param string $data Data to encrypt
     * @param string $table_name Database table name
     * @param string $field_name Database field name
     * @return string Encrypted data
     */
    public static function encryptForDatabase($data, $table_name, $field_name) {
        $context = "db_{$table_name}_{$field_name}";
        return self::encrypt($data, $context);
    }
    
    /**
     * Decrypt data from database storage
     * @param string $encrypted_data Encrypted data from database
     * @param string $table_name Database table name
     * @param string $field_name Database field name
     * @return string Decrypted data
     */
    public static function decryptFromDatabase($encrypted_data, $table_name, $field_name) {
        $context = "db_{$table_name}_{$field_name}";
        return self::decrypt($encrypted_data, $context);
    }
    
    /**
     * Encrypt file content
     * @param string $file_content File content to encrypt
     * @param string $file_path Original file path
     * @return string Encrypted file content
     */
    public static function encryptFile($file_content, $file_path) {
        $context = "file_" . basename($file_path);
        return self::encrypt($file_content, $context);
    }
    
    /**
     * Decrypt file content
     * @param string $encrypted_content Encrypted file content
     * @param string $file_path Original file path
     * @return string Decrypted file content
     */
    public static function decryptFile($encrypted_content, $file_path) {
        $context = "file_" . basename($file_path);
        return self::decrypt($encrypted_content, $context);
    }
    
    /**
     * Encrypt session data
     * @param array $session_data Session data to encrypt
     * @return string Encrypted session data
     */
    public static function encryptSession($session_data) {
        $context = "session_" . session_id();
        return self::encrypt(json_encode($session_data), $context);
    }
    
    /**
     * Decrypt session data
     * @param string $encrypted_session Encrypted session data
     * @return array Decrypted session data
     */
    public static function decryptSession($encrypted_session) {
        $context = "session_" . session_id();
        $decrypted = self::decrypt($encrypted_session, $context);
        return json_decode($decrypted, true) ?: [];
    }
    
    /**
     * Hash sensitive data for searching (one-way)
     * @param string $data Data to hash
     * @return string Hashed data
     */
    public static function hashForSearch($data) {
        self::initialize();
        return hash('sha256', $data . (self::$encryption_key ?? ''));
    }
    
    /**
     * Generate a secure random token
     * @param int $length Token length in bytes
     * @return string Base64 encoded token
     */
    public static function generateToken($length = 32) {
        return base64_encode(random_bytes($length));
    }
    
    /**
     * Verify data integrity
     * @param string $data Original data
     * @param string $encrypted_data Encrypted data
     * @param string $context Context used for encryption
     * @return bool True if data matches
     */
    public static function verifyIntegrity($data, $encrypted_data, $context = '') {
        try {
            $decrypted = self::decrypt($encrypted_data, $context);
            return hash_equals($data, $decrypted);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get encryption status
     * @return array Encryption configuration status
     */
    public static function getStatus() {
        self::initialize();
        
        return [
            'initialized' => self::$initialized,
            'key_length' => self::$encryption_key ? strlen(self::$encryption_key) : 0,
            'algorithm' => 'AES-256-GCM',
            'key_source' => 'includes/key.php'
        ];
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
    return PSAUEncryption::encrypt($data, 'personal_data');
}

/**
 * Decrypt personal information
 * @param string $encrypted_data Encrypted personal data
 * @return string Decrypted data
 */
function decryptPersonalData($encrypted_data) {
    return PSAUEncryption::decrypt($encrypted_data, 'personal_data');
}

/**
 * Encrypt contact information
 * @param string $data Contact data to encrypt
 * @return string Encrypted data
 */
function encryptContactData($data) {
    return PSAUEncryption::encrypt($data, 'contact_data');
}

/**
 * Decrypt contact information
 * @param string $encrypted_data Encrypted contact data
 * @return string Decrypted data
 */
function decryptContactData($encrypted_data) {
    return PSAUEncryption::decrypt($encrypted_data, 'contact_data');
}

/**
 * Encrypt academic records
 * @param string $data Academic data to encrypt
 * @return string Encrypted data
 */
function encryptAcademicData($data) {
    return PSAUEncryption::encrypt($data, 'academic_data');
}

/**
 * Decrypt academic records
 * @param string $encrypted_data Encrypted academic data
 * @return string Decrypted data
 */
function decryptAcademicData($encrypted_data) {
    return PSAUEncryption::decrypt($encrypted_data, 'academic_data');
}

/**
 * Encrypt application data
 * @param string $data Application data to encrypt
 * @return string Encrypted data
 */
function encryptApplicationData($data) {
    return PSAUEncryption::encrypt($data, 'application_data');
}

/**
 * Decrypt application data
 * @param string $encrypted_data Encrypted application data
 * @return string Decrypted data
 */
function decryptApplicationData($encrypted_data) {
    return PSAUEncryption::decrypt($encrypted_data, 'application_data');
}
?>
