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
        
        // Load .env file if it exists
        if (!isset($_ENV['ENCRYPTION_KEY']) && file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    if (trim($key) === 'ENCRYPTION_KEY') {
                        $_ENV['ENCRYPTION_KEY'] = trim($value);
                    }
                }
            }
        }
        
        // Get encryption key from environment or generate new one
        $key = $_ENV['ENCRYPTION_KEY'] ?? getenv('ENCRYPTION_KEY');
        if (empty($key)) {
            // Generate a new key if none exists
            $key = self::generateEncryptionKey();
            error_log("Generated new encryption key. Please save this to your .env file: ENCRYPTION_KEY=" . base64_encode($key));
        } else {
            $key = base64_decode($key);
        }
        
        if (strlen($key) !== 32) {
            throw new Exception("Invalid encryption key length. Must be 32 bytes. Please check your ENCRYPTION_KEY in .env file.");
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

/**
 * Encrypt user field data
 * @param string $field_name Database field name
 * @param string $data Data to encrypt
 * @return string Encrypted data
 */
function encrypt_user_field($field_name, $data) {
    if (empty($data)) return '';
    return PSAUEncryption::encryptForDatabase($data, 'users', $field_name);
}

/**
 * Decrypt user field data
 * @param string $field_name Database field name
 * @param string $encrypted_data Encrypted data
 * @return string Decrypted data
 */
function decrypt_user_field($field_name, $encrypted_data) {
    if (empty($encrypted_data)) return '';
    try {
        return PSAUEncryption::decryptFromDatabase($encrypted_data, 'users', $field_name);
    } catch (Exception $e) {
        error_log("Decryption error for users.$field_name: " . $e->getMessage());
        return $encrypted_data; // Return original if decryption fails
    }
}

/**
 * Encrypt application field data
 * @param string $field_name Database field name
 * @param string $data Data to encrypt
 * @return string Encrypted data
 */
function encrypt_application_field($field_name, $data) {
    if (empty($data)) return '';
    return PSAUEncryption::encryptForDatabase($data, 'applications', $field_name);
}

/**
 * Decrypt application field data
 * @param string $field_name Database field name
 * @param string $encrypted_data Encrypted data
 * @return string Decrypted data
 */
function decrypt_application_field($field_name, $encrypted_data) {
    if (empty($encrypted_data)) return '';
    try {
        return PSAUEncryption::decryptFromDatabase($encrypted_data, 'applications', $field_name);
    } catch (Exception $e) {
        error_log("Decryption error for applications.$field_name: " . $e->getMessage());
        return $encrypted_data; // Return original if decryption fails
    }
}

/**
 * Decrypt entire user row
 * @param array $user User row from database
 * @return array User row with decrypted fields
 */
function decrypt_user_data($user) {
    if (!$user) return $user;
    
    $decrypted_fields = ['first_name', 'last_name', 'email', 'mobile_number', 'gender', 'birth_date', 'address'];
    
    foreach ($decrypted_fields as $field) {
        if (isset($user[$field])) {
            $user[$field] = decrypt_user_field($field, $user[$field]);
        }
    }
    
    return $user;
}

/**
 * Decrypt entire application row
 * @param array $application Application row from database
 * @return array Application row with decrypted fields
 */
function decrypt_application_data($application) {
    if (!$application) return $application;
    
    $decrypted_fields = ['previous_school', 'school_year', 'strand', 'gpa', 'address', 'age'];
    
    foreach ($decrypted_fields as $field) {
        if (isset($application[$field])) {
            $application[$field] = decrypt_application_field($field, $application[$field]);
        }
    }
    
    return $application;
}

/**
 * Find user by encrypted email
 * @param PDO $conn Database connection
 * @param string $email Email to search
 * @return array|false User data or false
 */
function find_user_by_encrypted_email($conn, $email) {
    try {
        $stmt = $conn->prepare("SELECT * FROM users");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        foreach ($users as $user) {
            try {
                $decrypted_email = decrypt_user_field('email', $user['email']);
                if ($decrypted_email === $email) {
                    return $user;
                }
            } catch (Exception $e) {
                continue;
            }
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error finding user by email: " . $e->getMessage());
        return false;
    }
}

/**
 * Find user by encrypted mobile number
 * @param PDO $conn Database connection
 * @param string $mobile_number Mobile number to search
 * @return array|false User data or false
 */
function find_user_by_encrypted_mobile($conn, $mobile_number) {
    try {
        $stmt = $conn->prepare("SELECT * FROM users");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        foreach ($users as $user) {
            try {
                $decrypted_mobile = decrypt_user_field('mobile_number', $user['mobile_number']);
                if ($decrypted_mobile === $mobile_number) {
                    return $user;
                }
            } catch (Exception $e) {
                continue;
            }
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error finding user by mobile: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if email exists (with encryption)
 * @param PDO $conn Database connection
 * @param string $email Email to check
 * @return bool True if exists
 */
function encrypted_email_exists($conn, $email) {
    return find_user_by_encrypted_email($conn, $email) !== false;
}

/**
 * Check if mobile number exists (with encryption)
 * @param PDO $conn Database connection
 * @param string $mobile_number Mobile number to check
 * @return bool True if exists
 */
function encrypted_mobile_exists($conn, $mobile_number) {
    return find_user_by_encrypted_mobile($conn, $mobile_number) !== false;
}
?>
