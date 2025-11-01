<?php
/**
 * PSAU Admission System - Encrypted Data Access Layer
 * Provides secure access to encrypted database fields
 */

require_once 'encryption.php';

class EncryptedDataAccess {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get user data with automatic decryption
     * @param int $user_id User ID
     * @return array Decrypted user data
     */
    public function getUserData($user_id) {
        $sql = "SELECT id, control_number, 
                       first_name, last_name, 
                       email, mobile_number,
                       address, birth_date, gender,
                       is_verified, created_at
                FROM users WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return null;
        }
        
        // Decrypt sensitive fields
        return [
            'id' => $user['id'],
            'control_number' => $user['control_number'],
            'first_name' => !empty($user['first_name']) ? decryptPersonalData($user['first_name']) : '',
            'last_name' => !empty($user['last_name']) ? decryptPersonalData($user['last_name']) : '',
            'email' => !empty($user['email']) ? decryptContactData($user['email']) : '',
            'mobile_number' => !empty($user['mobile_number']) ? decryptContactData($user['mobile_number']) : '',
            'address' => !empty($user['address']) ? decryptPersonalData($user['address']) : '',
            'birth_date' => !empty($user['birth_date']) ? decryptPersonalData($user['birth_date']) : '',
            'gender' => !empty($user['gender']) ? decryptPersonalData($user['gender']) : '',
            'is_verified' => $user['is_verified'],
            'created_at' => $user['created_at']
        ];
    }
    
    /**
     * Update user data with automatic encryption
     * @param int $user_id User ID
     * @param array $data User data to update
     * @return bool Success status
     */
    public function updateUserData($user_id, $data) {
        $fields = [];
        $values = [];
        
        if (isset($data['first_name'])) {
            $fields[] = 'first_name = ?';
            $values[] = encryptPersonalData($data['first_name']);
        }
        
        if (isset($data['last_name'])) {
            $fields[] = 'last_name = ?';
            $values[] = encryptPersonalData($data['last_name']);
        }
        
        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $values[] = encryptContactData($data['email']);
        }
        
        if (isset($data['mobile_number'])) {
            $fields[] = 'mobile_number = ?';
            $values[] = encryptContactData($data['mobile_number']);
        }
        
        if (isset($data['address'])) {
            $fields[] = 'address = ?';
            $values[] = encryptPersonalData($data['address']);
        }
        
        if (isset($data['birth_date'])) {
            $fields[] = 'birth_date = ?';
            $values[] = encryptPersonalData($data['birth_date']);
        }
        
        if (isset($data['gender'])) {
            $fields[] = 'gender = ?';
            $values[] = encryptPersonalData($data['gender']);
        }
        
        if (empty($fields)) {
            return true;
        }
        
        $values[] = $user_id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Get application data with automatic decryption
     * @param int $application_id Application ID
     * @return array Decrypted application data
     */
    public function getApplicationData($application_id) {
        $sql = "SELECT id, user_id, course_id, status,
                       gpa, strand, previous_school, address,
                       created_at, updated_at
                FROM applications WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$application_id]);
        $app = $stmt->fetch();
        
        if (!$app) {
            return null;
        }
        
        // Decrypt sensitive fields
        return [
            'id' => $app['id'],
            'user_id' => $app['user_id'],
            'course_id' => $app['course_id'],
            'status' => $app['status'],
            'gpa' => !empty($app['gpa']) ? decryptAcademicData($app['gpa']) : '',
            'strand' => !empty($app['strand']) ? decryptAcademicData($app['strand']) : '',
            'previous_school' => !empty($app['previous_school']) ? decryptAcademicData($app['previous_school']) : '',
            'address' => !empty($app['address']) ? decryptAcademicData($app['address']) : '',
            'created_at' => $app['created_at'],
            'updated_at' => $app['updated_at']
        ];
    }
    
    /**
     * Update application data with automatic encryption
     * @param int $application_id Application ID
     * @param array $data Application data to update
     * @return bool Success status
     */
    public function updateApplicationData($application_id, $data) {
        $fields = [];
        $values = [];
        
        if (isset($data['gpa'])) {
            $fields[] = 'gpa = ?';
            $values[] = encryptAcademicData($data['gpa']);
        }
        
        if (isset($data['strand'])) {
            $fields[] = 'strand = ?';
            $values[] = encryptAcademicData($data['strand']);
        }
        
        if (isset($data['previous_school'])) {
            $fields[] = 'previous_school = ?';
            $values[] = encryptAcademicData($data['previous_school']);
        }
        
        if (isset($data['address'])) {
            $fields[] = 'address = ?';
            $values[] = encryptAcademicData($data['address']);
        }
        
        if (empty($fields)) {
            return true;
        }
        
        $values[] = $application_id;
        $sql = "UPDATE applications SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Get document data with automatic decryption
     * @param int $document_id Document ID
     * @return array Decrypted document data
     */
    public function getDocumentData($document_id) {
        $sql = "SELECT id, application_id, document_type,
                       file_name, file_path, 
                       file_content, ocr_text,
                       created_at
                FROM documents WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$document_id]);
        $doc = $stmt->fetch();
        
        if (!$doc) {
            return null;
        }
        
        // Decrypt sensitive fields
        $decrypted_file_name = !empty($doc['file_name']) ? decryptPersonalData($doc['file_name']) : '';
        return [
            'id' => $doc['id'],
            'application_id' => $doc['application_id'],
            'document_type' => $doc['document_type'],
            'file_name' => $decrypted_file_name,
            'file_path' => !empty($doc['file_path']) ? decryptPersonalData($doc['file_path']) : '',
            'file_content' => !empty($doc['file_content']) ? 
                PSAUEncryption::decryptFile($doc['file_content'], $decrypted_file_name) : null,
            'ocr_text' => !empty($doc['ocr_text']) ? decryptPersonalData($doc['ocr_text']) : '',
            'created_at' => $doc['created_at']
        ];
    }
    
    /**
     * Store document with automatic encryption
     * @param array $data Document data to store
     * @return int Document ID
     */
    public function storeDocument($data) {
        $sql = "INSERT INTO documents (application_id, document_type, 
                                     file_name, file_path, 
                                     file_content, ocr_text) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $data['application_id'],
            $data['document_type'],
            encryptPersonalData($data['file_name']),
            encryptPersonalData($data['file_path']),
            !empty($data['file_content']) ? 
                PSAUEncryption::encryptFile($data['file_content'], $data['file_name']) : null,
            encryptPersonalData($data['ocr_text'])
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Find user by encrypted email
     * @param string $email Email to search for
     * @return array|null User data or null
     */
    public function findUserByEmail($email) {
        // Since email is encrypted, we need to check all users and decrypt
        // Use the helper function instead
        require_once 'functions.php';
        return find_user_by_encrypted_identifier($GLOBALS['conn'], $email);
    }
    
    /**
     * Find admin by encrypted email
     * @param string $email Email to search for
     * @return array|null Admin data or null
     */
    public function findAdminByEmail($email) {
        // Since email is encrypted, we need to check all admins and decrypt
        $stmt = $this->conn->prepare("SELECT * FROM admins");
        $stmt->execute();
        $admins = $stmt->fetchAll();
        
        foreach ($admins as $admin) {
            try {
                $decrypted_email = !empty($admin['email']) ? decryptContactData($admin['email']) : '';
                if ($decrypted_email === $email) {
                    // Decrypt all fields
                    return [
                        'id' => $admin['id'],
                        'username' => !empty($admin['username']) ? decryptPersonalData($admin['username']) : '',
                        'email' => $decrypted_email,
                        'mobile_number' => !empty($admin['mobile_number']) ? decryptContactData($admin['mobile_number']) : '',
                        'role' => $admin['role'],
                        'created_at' => $admin['created_at']
                    ];
                }
            } catch (Exception $e) {
                // If decryption fails, compare directly
                if ($admin['email'] === $email) {
                    return $admin;
                }
            }
        }
        return null;
    }
    
    /**
     * Search users by encrypted name
     * @param string $search_term Search term
     * @return array Array of matching users
     */
    public function searchUsersByName($search_term) {
        // For encrypted data, we need to search by decrypting all users
        // This is a simplified approach - in production, you might want to use
        // a more sophisticated search solution like Elasticsearch with encrypted fields
        
        $sql = "SELECT id, control_number, 
                       first_name, last_name, 
                       email, is_verified, created_at
                FROM users";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        $results = [];
        foreach ($users as $user) {
            try {
                $first_name = !empty($user['first_name']) ? decryptPersonalData($user['first_name']) : '';
                $last_name = !empty($user['last_name']) ? decryptPersonalData($user['last_name']) : '';
                
                // Check if the decrypted name matches the search term
                if (stripos($first_name, $search_term) !== false || 
                    stripos($last_name, $search_term) !== false) {
                    $results[] = [
                        'id' => $user['id'],
                        'control_number' => $user['control_number'],
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => !empty($user['email']) ? decryptContactData($user['email']) : '',
                        'is_verified' => $user['is_verified'],
                        'created_at' => $user['created_at']
                    ];
                }
            } catch (Exception $e) {
                // Skip if decryption fails
                continue;
            }
        }
        
        return $results;
    }
    
    /**
     * Log activity with encrypted details
     * @param string $action Action performed
     * @param int $user_id User ID
     * @param string $details Activity details
     * @param string $ip_address IP address
     * @return bool Success status
     */
    public function logActivity($action, $user_id, $details, $ip_address) {
        $sql = "INSERT INTO activity_logs (action, user_id, details, ip_address) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $action,
            $user_id,
            encryptPersonalData($details),
            encryptPersonalData($ip_address)
        ]);
    }
    
    /**
     * Get activity logs with decrypted details
     * @param int $limit Number of logs to retrieve
     * @return array Array of activity logs
     */
    public function getActivityLogs($limit = 100) {
        $sql = "SELECT id, action, user_id, details, 
                       ip_address, created_at
                FROM activity_logs 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$limit]);
        $logs = $stmt->fetchAll();
        
        $results = [];
        foreach ($logs as $log) {
            try {
                $results[] = [
                    'id' => $log['id'],
                    'action' => $log['action'],
                    'user_id' => $log['user_id'],
                    'details' => !empty($log['details']) ? decryptPersonalData($log['details']) : '',
                    'ip_address' => !empty($log['ip_address']) ? decryptPersonalData($log['ip_address']) : '',
                    'created_at' => $log['created_at']
                ];
            } catch (Exception $e) {
                // If decryption fails, use as-is
                $results[] = [
                    'id' => $log['id'],
                    'action' => $log['action'],
                    'user_id' => $log['user_id'],
                    'details' => $log['details'],
                    'ip_address' => $log['ip_address'],
                    'created_at' => $log['created_at']
                ];
            }
        }
        
        return $results;
    }
}

// Global instance for easy access (only create if $conn is available)
if (isset($conn) && $conn instanceof PDO) {
    $encrypted_data = new EncryptedDataAccess($conn);
}
?>
