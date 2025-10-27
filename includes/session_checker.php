<?php
/**
 * Session Checker
 * Verifies if the user is logged in and redirects as needed
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include encryption helper for decryption functions
require_once 'encryption.php';

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
        // Fetch user data from users table with decryption
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
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return null;
        }
        
        // Use encrypted fields if available, otherwise fall back to unencrypted
        if (!empty($user['first_name_encrypted'])) {
            $decrypted = decryptPersonalData($user['first_name_encrypted']);
            if (!empty($decrypted)) $user['first_name'] = $decrypted;
        }
        
        if (!empty($user['last_name_encrypted'])) {
            $decrypted = decryptPersonalData($user['last_name_encrypted']);
            if (!empty($decrypted)) $user['last_name'] = $decrypted;
        }
        
        if (!empty($user['email_encrypted'])) {
            $decrypted = decryptContactData($user['email_encrypted']);
            if (!empty($decrypted)) $user['email'] = $decrypted;
        }
        
        if (!empty($user['mobile_number_encrypted'])) {
            $decrypted = decryptContactData($user['mobile_number_encrypted']);
            if (!empty($decrypted)) $user['mobile_number'] = $decrypted;
        }
        
        if (!empty($user['gender_encrypted'])) {
            $decrypted = decryptPersonalData($user['gender_encrypted']);
            if (!empty($decrypted)) $user['gender'] = $decrypted;
        }
        
        if (!empty($user['birth_date_encrypted'])) {
            $decrypted = decryptPersonalData($user['birth_date_encrypted']);
            if (!empty($decrypted)) $user['birth_date'] = $decrypted;
        }
        
        if (!empty($user['address_encrypted'])) {
            $decrypted = decryptPersonalData($user['address_encrypted']);
            if (!empty($decrypted)) $user['address'] = $decrypted;
        }
        
        // Fetch educational background from applications table
        $app_stmt = $conn->prepare("SELECT previous_school, school_year, strand, gpa, age, address 
                                   FROM applications 
                                   WHERE user_id = :user_id 
                                   ORDER BY created_at DESC 
                                   LIMIT 1");
        $app_stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $app_stmt->execute();
        $application = $app_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($application) {
            $user['previous_school'] = $application['previous_school'];
            $user['school_year'] = $application['school_year'];
            $user['strand'] = $application['strand'];
            $user['gpa'] = $application['gpa'];
            $user['age'] = $application['age'];
            // Only use application address if user address is not set
            if (empty($user['address']) && !empty($application['address'])) {
                $user['address'] = $application['address'];
            }
        }
        
        return $user;
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