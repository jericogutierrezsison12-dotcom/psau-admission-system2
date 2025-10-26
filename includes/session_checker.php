<?php
/**
 * Session Checker
 * Verifies if the user is logged in and redirects as needed
 */

// Include required files
require_once 'encryption.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check remember me cookie if session not active
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    require_once 'db_connect.php';
    require_once 'functions.php';
    
    $cookie_parts = explode(':', $_COOKIE['remember_me']);
    
    if (count($cookie_parts) === 2) {
        $selector = $cookie_parts[0];
        $token = $cookie_parts[1];
        
        $user_id = verify_remember_token($conn, $selector, $token);
        
        if ($user_id) {
            // Valid remember me token, set session
            $_SESSION['user_id'] = $user_id;
        } else {
            // Invalid remember me token, clear the cookie
            clear_remember_cookie();
        }
    }
}

/**
 * Check if user is logged in
 * @param string $redirect_url URL to redirect to if not logged in
 * @return bool True if logged in, redirects if not
 */
function is_user_logged_in($redirect_url = '../public/login.php') {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header("Location: $redirect_url");
        exit;
    }
    return true;
}

/**
 * Check if admin is logged in
 * @param string $redirect_url URL to redirect to if not logged in
 * @return bool True if logged in, redirects if not
 */
function is_admin_logged_in($redirect_url = 'login.php') {
    // Always start a session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
        header("Location: $redirect_url");
        exit;
    }
    return true;
}

/**
 * Redirect if already logged in
 * @param string $redirect_url URL to redirect to if logged in
 * @param string $type Type of user to check ('user' or 'admin')
 */
function redirect_if_logged_in($redirect_url, $type = 'user') {
    // Always start a session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($type === 'user' && isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        header("Location: $redirect_url");
        exit;
    } elseif ($type === 'admin' && isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Get current user information
 * @param PDO $conn Database connection
 * @return array|null User data or null if not logged in
 */
function get_current_user_data($conn) {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return null;
    }
    
    try {
        // First try to get encrypted data, fallback to unencrypted for backward compatibility
        $stmt = $conn->prepare("SELECT id, control_number, 
                                       first_name, last_name, 
                                       email, mobile_number,
                                       address, birth_date, gender,
                                       first_name_encrypted, last_name_encrypted, 
                                       email_encrypted, mobile_number_encrypted,
                                       address_encrypted, birth_date_encrypted, gender_encrypted,
                                       is_verified, created_at, updated_at
                                FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if (!$user) {
            return null;
        }
        
        // Use encrypted fields if available, otherwise fall back to unencrypted
        $result = [
            'id' => $user['id'],
            'control_number' => $user['control_number'],
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'mobile_number' => '',
            'address' => '',
            'birth_date' => '',
            'gender' => '',
            'is_verified' => $user['is_verified'],
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at']
        ];
        
        // Try to decrypt, if that fails, use unencrypted data
        if (!empty($user['first_name_encrypted'])) {
            try {
                $result['first_name'] = decryptPersonalData($user['first_name_encrypted']);
            } catch (Exception $e) {
                $result['first_name'] = $user['first_name'] ?? '';
            }
        } else {
            $result['first_name'] = $user['first_name'] ?? '';
        }
        
        if (!empty($user['last_name_encrypted'])) {
            try {
                $result['last_name'] = decryptPersonalData($user['last_name_encrypted']);
            } catch (Exception $e) {
                $result['last_name'] = $user['last_name'] ?? '';
            }
        } else {
            $result['last_name'] = $user['last_name'] ?? '';
        }
        
        if (!empty($user['email_encrypted'])) {
            try {
                $result['email'] = decryptContactData($user['email_encrypted']);
            } catch (Exception $e) {
                $result['email'] = $user['email'] ?? '';
            }
        } else {
            $result['email'] = $user['email'] ?? '';
        }
        
        if (!empty($user['mobile_number_encrypted'])) {
            try {
                $result['mobile_number'] = decryptContactData($user['mobile_number_encrypted']);
            } catch (Exception $e) {
                $result['mobile_number'] = $user['mobile_number'] ?? '';
            }
        } else {
            $result['mobile_number'] = $user['mobile_number'] ?? '';
        }
        
        if (!empty($user['address_encrypted'])) {
            try {
                $result['address'] = decryptPersonalData($user['address_encrypted']);
            } catch (Exception $e) {
                $result['address'] = $user['address'] ?? '';
            }
        } else {
            $result['address'] = $user['address'] ?? '';
        }
        
        if (!empty($user['birth_date_encrypted'])) {
            try {
                $result['birth_date'] = decryptPersonalData($user['birth_date_encrypted']);
            } catch (Exception $e) {
                $result['birth_date'] = $user['birth_date'] ?? '';
            }
        } else {
            $result['birth_date'] = $user['birth_date'] ?? '';
        }
        
        if (!empty($user['gender_encrypted'])) {
            try {
                $result['gender'] = decryptPersonalData($user['gender_encrypted']);
            } catch (Exception $e) {
                $result['gender'] = $user['gender'] ?? '';
            }
        } else {
            $result['gender'] = $user['gender'] ?? '';
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error fetching user: " . $e->getMessage());
        return null;
    }
}

/**
 * Get current admin information
 * @param PDO $conn Database connection
 * @return array|null Admin data or null if not logged in
 */
function get_current_admin($conn) {
    if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE id = :admin_id");
        $stmt->bindParam(':admin_id', $_SESSION['admin_id'], PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching admin: " . $e->getMessage());
        return null;
    }
} 

// Enforce admin role-based route access when in admin area
try {
    if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
        $scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $isInAdmin = (strpos($scriptDir, '/admin') !== false || strpos($scriptDir, "\\admin") !== false);
        if ($isInAdmin) {
            $role = $_SESSION['admin_role'] ?? 'admin';
            if ($role === 'registrar') {
                $allowed = [
                    'verify_applications.php',
                    'review_application.php',
                    'courses_overview.php',
                    'login.php',
                    'logout.php',
                    'register.php',
                ];
                if ($scriptName && !in_array($scriptName, $allowed, true)) {
                    header('Location: verify_applications.php');
                    exit;
                }
            } elseif ($role === 'department') {
                $allowed = [
                    'courses_overview.php',
                    'login.php',
                    'logout.php',
                ];
                if ($scriptName && !in_array($scriptName, $allowed, true)) {
                    header('Location: courses_overview.php');
                    exit;
                }
            }
        }
    }
} catch (Exception $e) {
    // Fail open on route enforcement error
}