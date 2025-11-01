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
        
        // Get encryption key from environment (check both getenv and $_ENV)
        $key = getenv('ENCRYPTION_KEY');
        if (empty($key) && isset($_ENV['ENCRYPTION_KEY'])) {
            $key = $_ENV['ENCRYPTION_KEY'];
        }
        
        if (empty($key)) {
            // CRITICAL: Do NOT generate a new key - this breaks decryption!
            // Instead, throw an error so we know the key is missing
            error_log("CRITICAL ERROR: ENCRYPTION_KEY environment variable is not set!");
            error_log("Please set ENCRYPTION_KEY in your Render environment variables.");
            error_log("If you already have encrypted data, you MUST use the same key that was used to encrypt it.");
            throw new Exception("ENCRYPTION_KEY environment variable is required but not set. Please configure it in your Render dashboard under Environment Variables.");
        } else {
            // Key is base64 encoded, decode it
            $decoded_key = base64_decode($key, true);
            if ($decoded_key === false || strlen($decoded_key) !== 32) {
                // Try using the key directly if base64 decode fails
                if (strlen($key) === 32) {
                    $decoded_key = $key;
                } else {
                    error_log("CRITICAL ERROR: ENCRYPTION_KEY is not valid base64 or not 32 bytes!");
                    error_log("Key length: " . strlen($key));
                    throw new Exception("Invalid ENCRYPTION_KEY format. Must be a base64-encoded 32-byte key.");
                }
            }
            $key = $decoded_key;
        }
        
        if (strlen($key) !== 32) {
            error_log("CRITICAL ERROR: Encryption key length is " . strlen($key) . " bytes, expected 32 bytes!");
            throw new Exception("Invalid encryption key length. Must be 32 bytes.");
        }
        
        self::$encryption_key = $key;
        self::$initialized = true;
        
        // Log key status (first 4 chars only for security)
        $key_preview = base64_encode(substr($key, 0, 4));
        error_log("Encryption initialized successfully. Key preview: " . $key_preview . "... (Key loaded from " . (getenv('ENCRYPTION_KEY') ? 'getenv' : '$_ENV') . ")");
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
     * @param string $encrypted_data Base64 encoded encrypted data
     * @param string $context Additional context for authentication
     * @return string Decrypted data
     */
    public static function decrypt($encrypted_data, $context = '') {
        self::initialize();
        
        if (empty($encrypted_data)) {
            return '';
        }
        
        // Decode base64
        $data = base64_decode($encrypted_data);
        if ($data === false) {
            throw new Exception("Invalid base64 data");
        }
        
        // Extract IV (first 12 bytes), tag (next 16 bytes), and encrypted data
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $encrypted = substr($data, 28);
        
        // Create additional authenticated data
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
            throw new Exception("Decryption failed: " . openssl_error_string());
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
        return hash('sha256', $data . getenv('ENCRYPTION_KEY'));
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
            'key_source' => getenv('ENCRYPTION_KEY') ? 'environment' : 'generated'
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

/**
 * Safely decrypt a single field with graceful fallbacks.
 * Tries context-aware database decryption first, then legacy wrappers,
 * and finally returns the original value if not encrypted.
 *
 * @param string $value
 * @param string $table
 * @param string $field
 * @return string
 */
function safeDecryptField($value, $table, $field) {
    if ($value === null || $value === '') {
        return $value ?? '';
    }

    // Heuristic: looks like base64 and long enough
    $maybeEncrypted = is_string($value) && strlen($value) > 60 && preg_match('/^[A-Za-z0-9+\/=]+$/', $value);

    // Try table/field-based decryption first
    if ($maybeEncrypted) {
        try {
            return PSAUEncryption::decryptFromDatabase($value, $table, $field);
        } catch (Exception $e) {
            // Fall through to legacy context wrappers
        }
    }

    // Legacy wrapper fallbacks per table/field groups
    try {
        switch ($table) {
            case 'users':
                // Contact fields
                if (in_array($field, ['email', 'mobile_number'], true)) {
                    return decryptContactData($value);
                }
                // Personal fields
                if (in_array($field, ['first_name', 'last_name', 'address', 'gender', 'birth_date'], true)) {
                    return decryptPersonalData($value);
                }
                break;
            case 'applications':
                // Academic fields stored with academic context in legacy code
                if (in_array($field, ['previous_school', 'school_year', 'strand', 'gpa', 'age', 'address'], true)) {
                    return decryptAcademicData($value);
                }
                break;
            default:
                // Unknown table: return as-is
                return $value;
        }
    } catch (Exception $e) {
        // If wrapper fails or value is plaintext, return original
        return $value;
    }

    // If no mapping matched, just return original
    return $value;
}

?>
