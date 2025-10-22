<?php
/**
 * PSAU Admission System - Encrypted File Storage
 * Handles secure storage and retrieval of uploaded files
 */

require_once 'encryption.php';

class EncryptedFileStorage {
    private $storage_path;
    private $temp_path;
    
    public function __construct($storage_path = '../uploads/encrypted/') {
        $this->storage_path = rtrim($storage_path, '/') . '/';
        $this->temp_path = $this->storage_path . 'temp/';
        
        // Create directories if they don't exist
        $this->createDirectories();
    }
    
    /**
     * Create necessary directories
     */
    private function createDirectories() {
        if (!is_dir($this->storage_path)) {
            mkdir($this->storage_path, 0755, true);
        }
        
        if (!is_dir($this->temp_path)) {
            mkdir($this->temp_path, 0755, true);
        }
        
        // Create .htaccess to prevent direct access
        $htaccess_content = "Order Deny,Allow\nDeny from all";
        $htaccess_file = $this->storage_path . '.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, $htaccess_content);
        }
    }
    
    /**
     * Store a file with encryption
     * @param array $file $_FILES array element
     * @param string $user_id User ID for organization
     * @param string $document_type Type of document
     * @return array File information
     */
    public function storeFile($file, $user_id, $document_type) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception("Invalid file upload");
        }
        
        // Generate unique filename
        $original_name = $file['name'];
        $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $encrypted_filename = $this->generateEncryptedFilename($user_id, $document_type, $file_extension);
        
        // Read file content
        $file_content = file_get_contents($file['tmp_name']);
        if ($file_content === false) {
            throw new Exception("Failed to read uploaded file");
        }
        
        // Encrypt file content
        $encrypted_content = PSAUEncryption::encryptFile($file_content, $original_name);
        
        // Store encrypted file
        $encrypted_path = $this->storage_path . $encrypted_filename;
        if (file_put_contents($encrypted_path, $encrypted_content) === false) {
            throw new Exception("Failed to store encrypted file");
        }
        
        // Clean up temporary file
        unlink($file['tmp_name']);
        
        return [
            'original_name' => $original_name,
            'encrypted_filename' => $encrypted_filename,
            'encrypted_path' => $encrypted_path,
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
            'document_type' => $document_type,
            'user_id' => $user_id,
            'stored_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Retrieve and decrypt a file
     * @param string $encrypted_filename Encrypted filename
     * @param string $original_name Original filename for context
     * @return string Decrypted file content
     */
    public function retrieveFile($encrypted_filename, $original_name = '') {
        $encrypted_path = $this->storage_path . $encrypted_filename;
        
        if (!file_exists($encrypted_path)) {
            throw new Exception("File not found");
        }
        
        // Read encrypted content
        $encrypted_content = file_get_contents($encrypted_path);
        if ($encrypted_content === false) {
            throw new Exception("Failed to read encrypted file");
        }
        
        // Decrypt content
        return PSAUEncryption::decryptFile($encrypted_content, $original_name ?: $encrypted_filename);
    }
    
    /**
     * Get file information without decrypting
     * @param string $encrypted_filename Encrypted filename
     * @return array File information
     */
    public function getFileInfo($encrypted_filename) {
        $encrypted_path = $this->storage_path . $encrypted_filename;
        
        if (!file_exists($encrypted_path)) {
            return null;
        }
        
        return [
            'filename' => $encrypted_filename,
            'path' => $encrypted_path,
            'size' => filesize($encrypted_path),
            'modified' => date('Y-m-d H:i:s', filemtime($encrypted_path)),
            'is_readable' => is_readable($encrypted_path)
        ];
    }
    
    /**
     * Delete an encrypted file
     * @param string $encrypted_filename Encrypted filename
     * @return bool Success status
     */
    public function deleteFile($encrypted_filename) {
        $encrypted_path = $this->storage_path . $encrypted_filename;
        
        if (file_exists($encrypted_path)) {
            return unlink($encrypted_path);
        }
        
        return true;
    }
    
    /**
     * List files for a user
     * @param string $user_id User ID
     * @return array Array of file information
     */
    public function listUserFiles($user_id) {
        $files = [];
        $pattern = $this->storage_path . $user_id . '_*';
        
        foreach (glob($pattern) as $file_path) {
            $filename = basename($file_path);
            $files[] = $this->getFileInfo($filename);
        }
        
        return $files;
    }
    
    /**
     * Generate encrypted filename
     * @param string $user_id User ID
     * @param string $document_type Document type
     * @param string $extension File extension
     * @return string Encrypted filename
     */
    private function generateEncryptedFilename($user_id, $document_type, $extension) {
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        $base_name = "{$user_id}_{$document_type}_{$timestamp}_{$random}";
        
        // Encrypt the base name
        $encrypted_name = PSAUEncryption::encrypt($base_name, 'filename');
        
        // Convert to filesystem-safe string
        $safe_name = base64_encode($encrypted_name);
        $safe_name = preg_replace('/[^a-zA-Z0-9]/', '_', $safe_name);
        
        return $safe_name . '.' . $extension;
    }
    
    /**
     * Decrypt filename to get original information
     * @param string $encrypted_filename Encrypted filename
     * @return array Original file information
     */
    public function decryptFilename($encrypted_filename) {
        $extension = pathinfo($encrypted_filename, PATHINFO_EXTENSION);
        $base_name = pathinfo($encrypted_filename, PATHINFO_FILENAME);
        
        // Convert back from filesystem-safe string
        $base64_name = str_replace('_', '/', $base_name);
        $base64_name = str_pad($base64_name, strlen($base64_name) + (4 - strlen($base64_name) % 4) % 4, '=');
        
        try {
            $encrypted_name = base64_decode($base64_name);
            $decrypted_name = PSAUEncryption::decrypt($encrypted_name, 'filename');
            
            // Parse the decrypted name
            $parts = explode('_', $decrypted_name);
            if (count($parts) >= 4) {
                return [
                    'user_id' => $parts[0],
                    'document_type' => $parts[1],
                    'timestamp' => $parts[2],
                    'random' => $parts[3],
                    'extension' => $extension
                ];
            }
        } catch (Exception $e) {
            // If decryption fails, return basic info
        }
        
        return [
            'user_id' => 'unknown',
            'document_type' => 'unknown',
            'timestamp' => time(),
            'random' => 'unknown',
            'extension' => $extension
        ];
    }
    
    /**
     * Create a secure download link
     * @param string $encrypted_filename Encrypted filename
     * @param string $original_name Original filename
     * @return string Download URL
     */
    public function createDownloadLink($encrypted_filename, $original_name) {
        $token = PSAUEncryption::generateToken(32);
        $expires = time() + 3600; // 1 hour
        
        // Store token in session or database
        $_SESSION['download_tokens'][$token] = [
            'filename' => $encrypted_filename,
            'original_name' => $original_name,
            'expires' => $expires
        ];
        
        return "download_encrypted_file.php?token=" . urlencode($token);
    }
    
    /**
     * Validate download token
     * @param string $token Download token
     * @return array|null File information or null if invalid
     */
    public function validateDownloadToken($token) {
        if (!isset($_SESSION['download_tokens'][$token])) {
            return null;
        }
        
        $file_info = $_SESSION['download_tokens'][$token];
        
        if ($file_info['expires'] < time()) {
            unset($_SESSION['download_tokens'][$token]);
            return null;
        }
        
        return $file_info;
    }
    
    /**
     * Clean up expired download tokens
     */
    public function cleanupExpiredTokens() {
        if (!isset($_SESSION['download_tokens'])) {
            return;
        }
        
        $current_time = time();
        foreach ($_SESSION['download_tokens'] as $token => $file_info) {
            if ($file_info['expires'] < $current_time) {
                unset($_SESSION['download_tokens'][$token]);
            }
        }
    }
    
    /**
     * Get storage statistics
     * @return array Storage statistics
     */
    public function getStorageStats() {
        $total_files = 0;
        $total_size = 0;
        $file_types = [];
        
        $files = glob($this->storage_path . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $total_files++;
                $total_size += filesize($file);
                
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $file_types[$extension] = ($file_types[$extension] ?? 0) + 1;
            }
        }
        
        return [
            'total_files' => $total_files,
            'total_size' => $total_size,
            'total_size_mb' => round($total_size / 1024 / 1024, 2),
            'file_types' => $file_types,
            'storage_path' => $this->storage_path
        ];
    }
}

// Global instance for easy access
$encrypted_storage = new EncryptedFileStorage();
?>
