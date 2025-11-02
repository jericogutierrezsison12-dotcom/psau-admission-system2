<?php
/**
 * Session Checker
 * Verifies if the user is logged in and redirects as needed
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include encryption functions
require_once __DIR__ . '/encryption.php';

// Check remember me cookie if session not active
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    try {
        require_once 'db_connect.php';
        require_once 'functions.php';
        
        // Check if database connection is available
        if (!isset($conn) || !$conn) {
            // Database connection failed, clear cookie to prevent loops
            clear_remember_cookie();
        } else {
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
    } catch (Exception $e) {
        // Database error, clear cookie to prevent loops
        error_log("Remember me check error: " . $e->getMessage());
        clear_remember_cookie();
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
        // Get user data from users table
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if (!$user) {
            return null;
        }
        
        // Fetch educational background from applications table
        $app_stmt = $conn->prepare("SELECT previous_school, school_year, strand, gpa, age, address 
                                   FROM applications 
                                   WHERE user_id = :user_id 
                                   ORDER BY created_at DESC 
                                   LIMIT 1");
        $app_stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $app_stmt->execute();
        $education = $app_stmt->fetch();
        
        // Decrypt user data
        $user = decrypt_user_data($user);
        
        // Merge educational background data with user data and decrypt
        if ($education) {
            $education = decrypt_application_data($education);
            $user['previous_school'] = $education['previous_school'];
            $user['school_year'] = $education['school_year'];
            $user['strand'] = $education['strand'];
            $user['gpa'] = $education['gpa'];
            $user['age'] = $education['age'];
            // Use application address if user address is empty
            if (empty($user['address']) && !empty($education['address'])) {
                $user['address'] = $education['address'];
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
                    'course_assignment.php',
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