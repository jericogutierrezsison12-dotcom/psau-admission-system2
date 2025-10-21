<?php
/**
 * PSAU Admission System - General Functions
 * Contains general utility functions for the application
 */

require_once 'db_connect.php';
require_once 'validation_functions.php';
require_once 'session_checker.php';
require_once 'api_calls.php';
require_once 'generate_control_number.php';

/**
 * Creates a remember me token for a user
 * @param PDO $conn Database connection
 * @param int $user_id User ID
 * @return array|boolean Array with selector and token on success, false on failure
 */
function create_remember_token($conn, $user_id) {
    try {
        // Generate a random selector (for lookup)
        $selector = bin2hex(random_bytes(16));
        
        // Generate a random token (to verify)
        $token = random_bytes(32);
        $hashed_token = password_hash($token, PASSWORD_DEFAULT);
        
        // Set expiration (30 days from now)
        $expires = new DateTime();
        $expires->add(new DateInterval('P30D'));
        
        // Delete any existing tokens for this user
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Insert new token
        $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, selector, token, expires) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $selector,
            $hashed_token,
            $expires->format('Y-m-d H:i:s')
        ]);
        
        // Return both selector and unhashed token for cookie creation
        return [
            'selector' => $selector,
            'token' => bin2hex($token)
        ];
    } catch (Exception $e) {
        error_log("Error creating remember token: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify a remember me token
 * @param PDO $conn Database connection
 * @param string $selector Token selector
 * @param string $token Remember token
 * @return int|boolean User ID on success, false on failure
 */
function verify_remember_token($conn, $selector, $token) {
    try {
        // Find the token record
        $stmt = $conn->prepare("SELECT * FROM remember_tokens WHERE selector = ? AND expires > NOW()");
        $stmt->execute([$selector]);
        $remember = $stmt->fetch();
        
        if (!$remember) {
            return false; // No valid token found
        }
        
        // Verify the token
        $token_bin = hex2bin($token);
        
        if (!$token_bin || !password_verify($token_bin, $remember['token'])) {
            return false; // Invalid token
        }
        
        return $remember['user_id']; // Success, return user ID
    } catch (Exception $e) {
        error_log("Error verifying remember token: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear remember token for a user
 * @param PDO $conn Database connection
 * @param int $user_id User ID
 * @return boolean True on success, false on failure
 */
function clear_remember_token($conn, $user_id) {
    try {
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return true;
    } catch (Exception $e) {
        error_log("Error clearing remember token: " . $e->getMessage());
        return false;
    }
}

/**
 * Set remember me cookie
 * @param array $token_data Token data from create_remember_token
 * @return void
 */
function set_remember_cookie($token_data) {
    if (!$token_data) return;
    
    $cookie_value = $token_data['selector'] . ':' . $token_data['token'];
    $cookie_expiry = time() + (30 * 24 * 60 * 60); // 30 days
    
    setcookie(
        'remember_me',
        $cookie_value,
        [
            'expires' => $cookie_expiry,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );
}

/**
 * Clear remember me cookie
 * @return void
 */
function clear_remember_cookie() {
    if (isset($_COOKIE['remember_me'])) {
        setcookie(
            'remember_me',
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }
}

/**
 * Creates a remember me token for an admin
 * @param PDO $conn Database connection
 * @param int $admin_id Admin ID
 * @return array|boolean Array with selector and token on success, false on failure
 */
function create_admin_remember_token($conn, $admin_id) {
    try {
        // Generate a random selector (for lookup)
        $selector = bin2hex(random_bytes(16));
        
        // Generate a random token (to verify)
        $token = random_bytes(32);
        $hashed_token = password_hash($token, PASSWORD_DEFAULT);
        
        // Set expiration (30 days from now)
        $expires = new DateTime();
        $expires->add(new DateInterval('P30D'));
        
        // Delete any existing tokens for this admin
        $stmt = $conn->prepare("DELETE FROM admin_remember_tokens WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        
        // Insert new token
        $stmt = $conn->prepare("INSERT INTO admin_remember_tokens (admin_id, selector, token, expires) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $admin_id,
            $selector,
            $hashed_token,
            $expires->format('Y-m-d H:i:s')
        ]);
        
        // Return both selector and unhashed token for cookie creation
        return [
            'selector' => $selector,
            'token' => bin2hex($token)
        ];
    } catch (Exception $e) {
        error_log("Error creating admin remember token: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify an admin remember me token
 * @param PDO $conn Database connection
 * @param string $selector Token selector
 * @param string $token Remember token
 * @return int|boolean Admin ID on success, false on failure
 */
function verify_admin_remember_token($conn, $selector, $token) {
    try {
        // Find the token record
        $stmt = $conn->prepare("SELECT * FROM admin_remember_tokens WHERE selector = ? AND expires > NOW()");
        $stmt->execute([$selector]);
        $remember = $stmt->fetch();
        
        if (!$remember) {
            return false; // No valid token found
        }
        
        // Verify the token
        $token_bin = hex2bin($token);
        
        if (!$token_bin || !password_verify($token_bin, $remember['token'])) {
            return false; // Invalid token
        }
        
        return $remember['admin_id']; // Success, return admin ID
    } catch (Exception $e) {
        error_log("Error verifying admin remember token: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear remember token for an admin
 * @param PDO $conn Database connection
 * @param int $admin_id Admin ID
 * @return boolean True on success, false on failure
 */
function clear_admin_remember_token($conn, $admin_id) {
    try {
        $stmt = $conn->prepare("DELETE FROM admin_remember_tokens WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        return true;
    } catch (Exception $e) {
        error_log("Error clearing admin remember token: " . $e->getMessage());
        return false;
    }
}

/**
 * Set admin remember me cookie
 * @param array $token_data Token data from create_admin_remember_token
 * @return void
 */
function set_admin_remember_cookie($token_data) {
    if (!$token_data) return;
    
    $cookie_value = $token_data['selector'] . ':' . $token_data['token'];
    $cookie_expiry = time() + (30 * 24 * 60 * 60); // 30 days
    
    setcookie(
        'admin_remember_me',
        $cookie_value,
        [
            'expires' => $cookie_expiry,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );
}

/**
 * Clear admin remember me cookie
 * @return void
 */
function clear_admin_remember_cookie() {
    if (isset($_COOKIE['admin_remember_me'])) {
        setcookie(
            'admin_remember_me',
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }
}

/**
 * Get the count of applicants for each exam status
 * @param PDO $conn Database connection
 * @return array Array with counts for each status
 */
function get_exam_status_counts($conn) {
    $counts = [
        'total' => 0,
        'verified' => 0,
        'scheduled' => 0,
        'scored' => 0
    ];
    
    try {
        // Get total applications
        $stmt = $conn->query("SELECT COUNT(*) FROM applications");
        $counts['total'] = $stmt->fetchColumn();
        
        // Get verified applications not yet scheduled
        $stmt = $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'Verified'");
        $counts['verified'] = $stmt->fetchColumn();
        
        // Get scheduled exams
        $stmt = $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'Exam Scheduled'");
        $counts['scheduled'] = $stmt->fetchColumn();
        
        // Get scored exams
        $stmt = $conn->query("SELECT COUNT(*) FROM applications WHERE status = 'Score Posted'");
        $counts['scored'] = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error getting exam stats: " . $e->getMessage());
    }
    
    return $counts;
}

/**
 * Get upcoming exam schedules
 * @param PDO $conn Database connection
 * @param int $limit Number of records to return (default: 5)
 * @return array Array of upcoming exam schedules
 */
function get_upcoming_exam_schedules($conn, $limit = 5) {
    $schedules = [];
    $today = date('Y-m-d');
    
    try {
        $stmt = $conn->prepare("
            SELECT es.*, a.username as admin_name,
            (SELECT COUNT(*) FROM exams WHERE exam_schedule_id = es.id) as applicant_count
            FROM exam_schedules es
            JOIN admins a ON es.created_by = a.id
            WHERE es.exam_date >= ?
            ORDER BY es.exam_date ASC, es.exam_time ASC
            LIMIT ?
        ");
        $limit_value = intval($limit);
        $stmt->bindParam(1, $today, PDO::PARAM_STR);
        $stmt->bindParam(2, $limit_value, PDO::PARAM_INT);
        $stmt->execute();
        $schedules = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting upcoming exam schedules: " . $e->getMessage());
    }
    
    return $schedules;
}

/**
 * Get available exam schedules with capacity
 * @param PDO $conn Database connection
 * @return array Array of available exam schedules
 */
function get_available_exam_schedules($conn) {
    $schedules = [];
    $today = date('Y-m-d');
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM exam_schedules
            WHERE exam_date >= ? 
            AND current_count < capacity
            AND is_active = 1
            ORDER BY exam_date ASC, exam_time ASC
        ");
        $stmt->execute([$today]);
        $schedules = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting available exam schedules: " . $e->getMessage());
    }
    
    return $schedules;
}

/**
 * Get verified applicants ready for exam scheduling
 * @param PDO $conn Database connection
 * @return array Array of verified applicants
 */
function get_verified_applicants($conn) {
    $applicants = [];
    
    try {
        $stmt = $conn->prepare("
            SELECT a.*, u.first_name, u.last_name, u.email, u.mobile_number, u.control_number
            FROM applications a
            JOIN users u ON a.user_id = u.id
            WHERE a.status = 'Verified'
            ORDER BY a.verified_at ASC, a.created_at ASC
        ");
        $stmt->execute();
        $applicants = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting verified applicants: " . $e->getMessage());
    }
    
    return $applicants;
}

/**
 * Log admin activity
 * @param PDO $conn Database connection
 * @param int $admin_id Admin ID
 * @param string $action Action performed
 * @param string $details Details of the action
 * @return bool True if successful, false otherwise
 */
function log_admin_activity($conn, $admin_id, $action, $details) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$admin_id, $action, $details, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    } catch (PDOException $e) {
        error_log("Error logging admin activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Update application status
 * @param PDO $conn Database connection
 * @param int $application_id Application ID
 * @param string $new_status New status
 * @param int $admin_id Admin ID who made the change
 * @param string $notes Notes about the status change
 * @return bool True if successful, false otherwise
 */
function update_application_status($conn, $application_id, $new_status, $admin_id, $notes = '') {
    try {
        $conn->beginTransaction();
        
        // Get current status
        $stmt = $conn->prepare("SELECT status FROM applications WHERE id = ?");
        $stmt->execute([$application_id]);
        $old_status = $stmt->fetchColumn();
        
        if (!$old_status) {
            throw new Exception("Application not found");
        }
        
        // Update application status
        $stmt = $conn->prepare("
            UPDATE applications 
            SET status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$new_status, $application_id]);
        
        // Record status history
        $stmt = $conn->prepare("
            INSERT INTO status_history 
            (application_id, old_status, new_status, notes, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $application_id,
            $old_status,
            $new_status,
            $notes,
            $admin_id
        ]);
        
        // Get user control number for Firebase update
        $stmt = $conn->prepare("
            SELECT u.control_number 
            FROM applications a
            JOIN users u ON a.user_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$application_id]);
        $control_number = $stmt->fetchColumn();
        
        if ($control_number) {
            // Update Firebase status (from api_calls.php)
            update_firebase_status($control_number, $new_status);
            
            // Add to Firebase history
            add_firebase_history($control_number, $new_status, $notes, "Admin");
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error updating application status: " . $e->getMessage());
        return false;
    }
}

/**
 * Get earliest verified applicants not already scheduled for a future exam
 * @param PDO $conn
 * @param string $exam_date (Y-m-d)
 * @param int $limit
 * @return array
 */
function get_earliest_verified_applicants_for_schedule($conn, $exam_date, $limit) {
    $stmt = $conn->prepare("
        SELECT a.id, a.user_id, u.first_name, u.last_name, u.email, u.mobile_number
        FROM applications a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN exams e ON a.id = e.application_id AND e.exam_date >= ?
        WHERE a.status = 'Verified' AND e.id IS NULL
        ORDER BY a.created_at ASC
        LIMIT ?
    ");
    $limit_value = intval($limit);
    $stmt->bindParam(1, $exam_date, PDO::PARAM_STR);
    $stmt->bindParam(2, $limit_value, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get user's full name
 */
function get_user_fullname($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT first_name, last_name 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return 'N/A';
    }
    
    return trim($user['first_name'] . ' ' . $user['last_name']);
}

/**
 * Get user's student ID/control number
 */
function get_user_student_id($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT control_number 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    return $user ? $user['control_number'] : 'Pending';
}

/**
 * Get user's assigned course
 */
function get_user_course($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT c.course_code, c.course_name
        FROM applications a
        JOIN course_assignments ca ON ca.application_id = a.id
        JOIN courses c ON c.id = ca.course_id
        WHERE a.user_id = ?
        ORDER BY a.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $course = $stmt->fetch();
    
    return $course ? $course['course_code'] . ' - ' . $course['course_name'] : 'Not yet assigned';
}
?> 