<?php
/**
 * Admin Authentication Functions
 * Handles authentication and authorization for admin users
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if admin is logged in
 * @param string $redirect_url URL to redirect to if not logged in
 * @return bool True if logged in, redirects if not
 */
function check_admin_login($redirect_url = 'login.php') {
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
 * Get current admin information
 * @param PDO $conn Database connection
 * @return array|null Admin data or null if not logged in
 */
function get_admin_data($conn) {
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

/**
 * Check if admin has specific permission
 * @param string $permission Permission to check
 * @return bool True if admin has permission, false otherwise
 */
function admin_has_permission($permission) {
    if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
        return false;
    }
    $role = $_SESSION['admin_role'] ?? 'admin';
    // Define role permissions based on requirements
    $permissionsByRole = [
        'admin' => ['*'], // Admin users: See all menu items including "View Logs"
        'registrar' => [
            'dashboard.view',
            'verify_applications.view',
            'verify_applications.update',
            'courses.view'
        ],
        'department' => [
            'courses.view'
        ]
    ];
    $perms = $permissionsByRole[$role] ?? [];
    return in_array('*', $perms, true) || in_array($permission, $perms, true);
}

/**
 * Require one of the given roles, otherwise redirect
 * @param array $allowed_roles
 * @param string $redirect_url
 */
function require_admin_role(array $allowed_roles, $redirect_url = 'dashboard.php') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $role = $_SESSION['admin_role'] ?? 'admin';
    if (!in_array($role, $allowed_roles, true)) {
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Get current admin role (defaults to admin)
 */
function get_current_admin_role() {
    return $_SESSION['admin_role'] ?? 'admin';
}

/**
 * Check if current admin can access a specific page
 * @param string $page_name The page name to check access for
 * @return bool True if admin can access the page, false otherwise
 */
function can_access_page($page_name) {
    $role = get_current_admin_role();
    
    // Define page access by role
    $pageAccess = [
        'admin' => [
            'dashboard', 'verify_applications', 'schedule_exam', 'manual_score_entry',
            'bulk_score_upload', 'course_assignment', 'enrollment_schedule',
            'view_all_applicants', 'courses_overview', 'view_logs', 'course_management',
            'manage_content', 'manage_announcements', 'manage_faqs', 'enrollment_completion',
            'view_all_users', 'view_enrolled_students', 'manage_admins'
        ],
        'registrar' => [
            'verify_applications', 'courses_overview', 'view_enrolled_students'
        ],
        'department' => [
            'courses_overview'
        ]
    ];
    // Admin-only utility pages
    if (!in_array('clear_attempts', $pageAccess['admin'], true)) {
        $pageAccess['admin'][] = 'clear_attempts';
    }
    
    $allowedPages = $pageAccess[$role] ?? [];
    return in_array($page_name, $allowedPages, true);
}

/**
 * Require access to a specific page, redirect if not allowed
 * @param string $page_name The page name to check access for
 * @param string $redirect_url URL to redirect to if access denied
 */
function require_page_access($page_name, $redirect_url = null) {
    if (!can_access_page($page_name)) {
        $role = get_current_admin_role();
        
        // Set default redirect based on role
        if ($redirect_url === null) {
            switch ($role) {
                case 'registrar':
                    $redirect_url = 'verify_applications.php';
                    break;
                case 'department':
                    $redirect_url = 'courses_overview.php';
                    break;
                default:
                    $redirect_url = 'dashboard.php';
            }
        }
        
        header("Location: $redirect_url");
        exit;
    }
}

// Intentionally no log_admin_activity here to avoid duplication with includes/functions.php
?> 