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
                       first_name_encrypted, last_name_encrypted, 
                       email_encrypted, mobile_number_encrypted,
                       address_encrypted, birth_date_encrypted, gender_encrypted,
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
            'first_name' => decryptPersonalData($user['first_name_encrypted']),
            'last_name' => decryptPersonalData($user['last_name_encrypted']),
            'email' => decryptContactData($user['email_encrypted']),
            'mobile_number' => decryptContactData($user['mobile_number_encrypted']),
            'address' => decryptPersonalData($user['address_encrypted']),
            'birth_date' => decryptPersonalData($user['birth_date_encrypted']),
            'gender' => decryptPersonalData($user['gender_encrypted']),
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
            $fields[] = 'first_name_encrypted = ?';
            $values[] = encryptPersonalData($data['first_name']);
        }
        
        if (isset($data['last_name'])) {
            $fields[] = 'last_name_encrypted = ?';
            $values[] = encryptPersonalData($data['last_name']);
        }
        
        if (isset($data['email'])) {
            $fields[] = 'email_encrypted = ?';
            $values[] = encryptContactData($data['email']);
        }
        
        if (isset($data['mobile_number'])) {
            $fields[] = 'mobile_number_encrypted = ?';
            $values[] = encryptContactData($data['mobile_number']);
        }
        
        if (isset($data['address'])) {
            $fields[] = 'address_encrypted = ?';
            $values[] = encryptPersonalData($data['address']);
        }
        
        if (isset($data['birth_date'])) {
            $fields[] = 'birth_date_encrypted = ?';
            $values[] = encryptPersonalData($data['birth_date']);
        }
        
        if (isset($data['gender'])) {
            $fields[] = 'gender_encrypted = ?';
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
                       gpa_encrypted, strand_encrypted, 
                       school_name_encrypted, school_address_encrypted,
                       essay_response_encrypted, personal_statement_encrypted,
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
            'gpa' => decryptAcademicData($app['gpa_encrypted']),
            'strand' => decryptAcademicData($app['strand_encrypted']),
            'school_name' => decryptAcademicData($app['school_name_encrypted']),
            'school_address' => decryptAcademicData($app['school_address_encrypted']),
            'essay_response' => decryptApplicationData($app['essay_response_encrypted']),
            'personal_statement' => decryptApplicationData($app['personal_statement_encrypted']),
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
            $fields[] = 'gpa_encrypted = ?';
            $values[] = encryptAcademicData($data['gpa']);
        }
        
        if (isset($data['strand'])) {
            $fields[] = 'strand_encrypted = ?';
            $values[] = encryptAcademicData($data['strand']);
        }
        
        if (isset($data['school_name'])) {
            $fields[] = 'school_name_encrypted = ?';
            $values[] = encryptAcademicData($data['school_name']);
        }
        
        if (isset($data['school_address'])) {
            $fields[] = 'school_address_encrypted = ?';
            $values[] = encryptAcademicData($data['school_address']);
        }
        
        if (isset($data['essay_response'])) {
            $fields[] = 'essay_response_encrypted = ?';
            $values[] = encryptApplicationData($data['essay_response']);
        }
        
        if (isset($data['personal_statement'])) {
            $fields[] = 'personal_statement_encrypted = ?';
            $values[] = encryptApplicationData($data['personal_statement']);
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
                       file_name_encrypted, file_path_encrypted, 
                       file_content_encrypted, ocr_text_encrypted,
                       created_at
                FROM documents WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$document_id]);
        $doc = $stmt->fetch();
        
        if (!$doc) {
            return null;
        }
        
        // Decrypt sensitive fields
        return [
            'id' => $doc['id'],
            'application_id' => $doc['application_id'],
            'document_type' => $doc['document_type'],
            'file_name' => decryptPersonalData($doc['file_name_encrypted']),
            'file_path' => decryptPersonalData($doc['file_path_encrypted']),
            'file_content' => !empty($doc['file_content_encrypted']) ? 
                PSAUEncryption::decryptFile($doc['file_content_encrypted'], $doc['file_name_encrypted']) : null,
            'ocr_text' => decryptPersonalData($doc['ocr_text_encrypted']),
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
                                     file_name_encrypted, file_path_encrypted, 
                                     file_content_encrypted, ocr_text_encrypted) 
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
        $encrypted_email = encryptContactData($email);
        
        $sql = "SELECT id, control_number, 
                       first_name_encrypted, last_name_encrypted, 
                       email_encrypted, mobile_number_encrypted,
                       is_verified, created_at
                FROM users WHERE email_encrypted = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$encrypted_email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return null;
        }
        
        // Decrypt sensitive fields
        return [
            'id' => $user['id'],
            'control_number' => $user['control_number'],
            'first_name' => decryptPersonalData($user['first_name_encrypted']),
            'last_name' => decryptPersonalData($user['last_name_encrypted']),
            'email' => decryptContactData($user['email_encrypted']),
            'mobile_number' => decryptContactData($user['mobile_number_encrypted']),
            'is_verified' => $user['is_verified'],
            'created_at' => $user['created_at']
        ];
    }
    
    /**
     * Find admin by encrypted email
     * @param string $email Email to search for
     * @return array|null Admin data or null
     */
    public function findAdminByEmail($email) {
        $encrypted_email = encryptContactData($email);
        
        $sql = "SELECT id, username_encrypted, email_encrypted, 
                       mobile_number_encrypted, role, created_at
                FROM admins WHERE email_encrypted = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$encrypted_email]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            return null;
        }
        
        // Decrypt sensitive fields
        return [
            'id' => $admin['id'],
            'username' => decryptPersonalData($admin['username_encrypted']),
            'email' => decryptContactData($admin['email_encrypted']),
            'mobile_number' => decryptContactData($admin['mobile_number_encrypted']),
            'role' => $admin['role'],
            'created_at' => $admin['created_at']
        ];
    }
    
    /**
     * Search users by encrypted name
     * @param string $search_term Search term
     * @return array Array of matching users
     */
    public function searchUsersByName($search_term) {
        // For encrypted data, we need to search by hashed values
        // This is a simplified approach - in production, you might want to use
        // a more sophisticated search solution like Elasticsearch with encrypted fields
        
        $sql = "SELECT id, control_number, 
                       first_name_encrypted, last_name_encrypted, 
                       email_encrypted, is_verified, created_at
                FROM users 
                WHERE first_name_encrypted LIKE ? 
                   OR last_name_encrypted LIKE ?";
        
        $search_pattern = '%' . $search_term . '%';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$search_pattern, $search_pattern]);
        $users = $stmt->fetchAll();
        
        $results = [];
        foreach ($users as $user) {
            $first_name = decryptPersonalData($user['first_name_encrypted']);
            $last_name = decryptPersonalData($user['last_name_encrypted']);
            
            // Check if the decrypted name matches the search term
            if (stripos($first_name, $search_term) !== false || 
                stripos($last_name, $search_term) !== false) {
                $results[] = [
                    'id' => $user['id'],
                    'control_number' => $user['control_number'],
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => decryptContactData($user['email_encrypted']),
                    'is_verified' => $user['is_verified'],
                    'created_at' => $user['created_at']
                ];
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
        $sql = "INSERT INTO activity_logs (action, user_id, details_encrypted, ip_address_encrypted) 
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
        $sql = "SELECT id, action, user_id, details_encrypted, 
                       ip_address_encrypted, created_at
                FROM activity_logs 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$limit]);
        $logs = $stmt->fetchAll();
        
        $results = [];
        foreach ($logs as $log) {
            $results[] = [
                'id' => $log['id'],
                'action' => $log['action'],
                'user_id' => $log['user_id'],
                'details' => decryptPersonalData($log['details_encrypted']),
                'ip_address' => decryptPersonalData($log['ip_address_encrypted']),
                'created_at' => $log['created_at']
            ];
        }
        
        return $results;
    }
}

// Global instance for easy access
$encrypted_data = new EncryptedDataAccess($conn);
?>
