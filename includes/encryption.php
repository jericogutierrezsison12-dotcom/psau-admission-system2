<?php
/**
 * PSAU Admission System - Encryption Helper
 * Provides encryption and decryption functions for sensitive data
 */

// Encryption key - DO NOT CHANGE AFTER DATA HAS BEEN ENCRYPTED
// This key is used for AES-256-CBC encryption
define('ENCRYPTION_KEY', 'PSAU_2024_ADMISSION_SYSTEM_SECURE_KEY_256BIT_LENGTH_REQUIRED_FOR_AES');
define('ENCRYPTION_METHOD', 'AES-256-CBC');

/**
 * Encrypt sensitive data
 * @param string $data Data to encrypt
 * @return string Encrypted data with IV prefixed
 */
function encrypt_data($data) {
    if (empty($data)) {
        return $data;
    }
    
    try {
        // Generate initialization vector
        $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);
        $iv = openssl_random_pseudo_bytes($iv_length);
        
        // Encrypt the data
        $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
        
        // Prepend IV to encrypted data and encode for storage
        return base64_encode($iv . $encrypted);
    } catch (Exception $e) {
        error_log("Encryption error: " . $e->getMessage());
        return $data; // Return original if encryption fails
    }
}

/**
 * Decrypt sensitive data
 * @param string $encrypted_data Encrypted data with IV prefixed
 * @return string Decrypted data
 */
function decrypt_data($encrypted_data) {
    if (empty($encrypted_data)) {
        return $encrypted_data;
    }
    
    try {
        // Decode from base64
        $data = base64_decode($encrypted_data, true);
        
        if ($data === false) {
            // If decoding fails, might be plain text (for backward compatibility)
            return $encrypted_data;
        }
        
        // Extract IV (first bytes)
        $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        
        // Decrypt the data
        $decrypted = openssl_decrypt($encrypted, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
        
        // If decryption fails, return original (might be plain text)
        return $decrypted !== false ? $decrypted : $encrypted_data;
    } catch (Exception $e) {
        error_log("Decryption error: " . $e->getMessage());
        return $encrypted_data; // Return original if decryption fails
    }
}

/**
 * Encrypt array of data (for multiple fields)
 * @param array $data Array of data to encrypt
 * @return array Encrypted array
 */
function encrypt_array($data) {
    $encrypted = [];
    foreach ($data as $key => $value) {
        $encrypted[$key] = is_string($value) ? encrypt_data($value) : $value;
    }
    return $encrypted;
}

/**
 * Decrypt array of data (for multiple fields)
 * @param array $data Array of encrypted data
 * @return array Decrypted array
 */
function decrypt_array($data) {
    $decrypted = [];
    foreach ($data as $key => $value) {
        $decrypted[$key] = is_string($value) ? decrypt_data($value) : $value;
    }
    return $decrypted;
}

/**
 * Encrypt user personal data
 * @param array $user_data User data array
 * @return array Encrypted user data
 */
function encrypt_user_data($user_data) {
    $fields_to_encrypt = ['first_name', 'last_name', 'email', 'mobile_number', 'address', 'gender', 'birth_date'];
    
    foreach ($fields_to_encrypt as $field) {
        if (isset($user_data[$field]) && !empty($user_data[$field])) {
            $user_data[$field] = encrypt_data($user_data[$field]);
        }
    }
    
    return $user_data;
}

/**
 * Decrypt user personal data
 * @param array $user_data Encrypted user data array
 * @return array Decrypted user data
 */
function decrypt_user_data($user_data) {
    $fields_to_decrypt = ['first_name', 'last_name', 'email', 'mobile_number', 'address', 'gender', 'birth_date'];
    
    foreach ($fields_to_decrypt as $field) {
        if (isset($user_data[$field]) && !empty($user_data[$field])) {
            $user_data[$field] = decrypt_data($user_data[$field]);
        }
    }
    
    return $user_data;
}

/**
 * Encrypt application data
 * @param array $application_data Application data array
 * @return array Encrypted application data
 */
function encrypt_application_data($application_data) {
    $fields_to_encrypt = ['previous_school', 'school_year', 'strand', 'gpa', 'address'];
    
    foreach ($fields_to_encrypt as $field) {
        if (isset($application_data[$field]) && !empty($application_data[$field])) {
            $application_data[$field] = encrypt_data($application_data[$field]);
        }
    }
    
    return $application_data;
}

/**
 * Decrypt application data
 * @param array $application_data Encrypted application data array
 * @return array Decrypted application data
 */
function decrypt_application_data($application_data) {
    $fields_to_decrypt = ['previous_school', 'school_year', 'strand', 'gpa', 'address'];
    
    foreach ($fields_to_decrypt as $field) {
        if (isset($application_data[$field]) && !empty($application_data[$field])) {
            $application_data[$field] = decrypt_data($application_data[$field]);
        }
    }
    
    return $application_data;
}

