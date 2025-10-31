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
        
        // Get encryption key from environment or generate new one
        $key = getenv('ENCRYPTION_KEY');
        if (empty($key)) {
            // Generate a new key if none exists
            $key = self::generateEncryptionKey();
            error_log("Generated new encryption key. Please save this to your .env file: ENCRYPTION_KEY=" . base64_encode($key));
        } else {
            $key = base64_decode($key);
        }
        
        if (strlen($key) !== 32) {
            throw new Exception("Invalid encryption key length. Must be 32 bytes.");
        }
        
        self::$encryption_key = $key;
        self::$initialized = true;
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
?>
