<?php
/**
 * Session Checker
 * Verifies if the user is logged in and redirects as needed
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include encryption for decryption
require_once __DIR__ . '/encryption.php';
require_once __DIR__ . '/functions.php'; // For looks_encrypted function

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
        // Get user data from users table
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if (!$user) {
            return null;
        }
        
        // Decrypt sensitive user data (only if it looks encrypted)
        try {
            // Check if functions are available
            if (!function_exists('looks_encrypted')) {
                require_once __DIR__ . '/functions.php';
            }
            
            // Only decrypt if data looks encrypted
            if (!empty($user['first_name'])) {
                if (looks_encrypted($user['first_name'])) {
                    $user['first_name'] = decryptPersonalData($user['first_name']);
                }
                // Otherwise use as-is (unencrypted data)
            }
            
            if (!empty($user['last_name'])) {
                if (looks_encrypted($user['last_name'])) {
                    $user['last_name'] = decryptPersonalData($user['last_name']);
                }
            }
            
            if (!empty($user['email'])) {
                if (looks_encrypted($user['email'])) {
                    $user['email'] = decryptContactData($user['email']);
                }
            }
            
            if (!empty($user['mobile_number'])) {
                if (looks_encrypted($user['mobile_number'])) {
                    $user['mobile_number'] = decryptContactData($user['mobile_number']);
                }
            }
            
            if (!empty($user['address'])) {
                if (looks_encrypted($user['address'])) {
                    $user['address'] = decryptPersonalData($user['address']);
                }
            }
            
            if (!empty($user['gender'])) {
                if (looks_encrypted($user['gender'])) {
                    try {
                        $user['gender'] = decryptPersonalData($user['gender']);
                    } catch (Exception $e) {
                        // If decryption fails, use as-is
                        error_log("Warning: Could not decrypt gender: " . $e->getMessage());
                    }
                }
            }
            
            if (!empty($user['birth_date'])) {
                if (looks_encrypted($user['birth_date'])) {
                    $user['birth_date'] = decryptPersonalData($user['birth_date']);
                }
            }
        } catch (Exception $e) {
            // If decryption fails, data might be unencrypted, use as-is
            error_log("Warning: Could not decrypt user data, using as-is: " . $e->getMessage());
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
        
        // Merge educational background data with user data (decrypt if needed)
        if ($education) {
            try {
                // Only decrypt if data looks encrypted
                if (!empty($education['previous_school'])) {
                    $user['previous_school'] = looks_encrypted($education['previous_school']) ? decryptAcademicData($education['previous_school']) : $education['previous_school'];
                } else {
                    $user['previous_school'] = '';
                }
                
                if (!empty($education['school_year'])) {
                    $user['school_year'] = looks_encrypted($education['school_year']) ? decryptAcademicData($education['school_year']) : $education['school_year'];
                } else {
                    $user['school_year'] = '';
                }
                
                if (!empty($education['strand'])) {
                    $user['strand'] = looks_encrypted($education['strand']) ? decryptAcademicData($education['strand']) : $education['strand'];
                } else {
                    $user['strand'] = '';
                }
                
                if (!empty($education['gpa'])) {
                    $user['gpa'] = looks_encrypted($education['gpa']) ? decryptAcademicData($education['gpa']) : $education['gpa'];
                } else {
                    $user['gpa'] = '';
                }
                
                if (!empty($education['age'])) {
                    $user['age'] = looks_encrypted($education['age']) ? decryptAcademicData($education['age']) : $education['age'];
                } else {
                    $user['age'] = '';
                }
                
                // Use application address if user address is empty
                if (empty($user['address']) && !empty($education['address'])) {
                    $user['address'] = looks_encrypted($education['address']) ? decryptAcademicData($education['address']) : $education['address'];
                }
            } catch (Exception $e) {
                // If decryption fails, use as-is (backwards compatibility)
                error_log("Warning: Could not decrypt education data: " . $e->getMessage());
                $user['previous_school'] = $education['previous_school'] ?? '';
                $user['school_year'] = $education['school_year'] ?? '';
                $user['strand'] = $education['strand'] ?? '';
                $user['gpa'] = $education['gpa'] ?? '';
                $user['age'] = $education['age'] ?? '';
                if (empty($user['address']) && !empty($education['address'])) {
                    $user['address'] = $education['address'];
                }
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