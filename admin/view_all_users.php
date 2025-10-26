<?php
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/admin_auth.php';
require_once '../includes/functions.php';
require_once '../includes/encryption.php';

is_admin_logged_in('login.php');
require_page_access('view_all_users');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    try {
        // Auto-flag-high-attempts action (optional explicit write)
        if ($action === 'auto_flag_high_attempts') {
            $stmt = $conn->query("UPDATE users u SET is_flagged = 1, updated_at = NOW() WHERE EXISTS (SELECT 1 FROM application_attempts aa WHERE aa.user_id = u.id GROUP BY aa.user_id HAVING COUNT(*) > 10)");
            $success = 'Auto-flagged users with more than 10 application attempts.';
        } elseif ($action === 'flag') {
            $stmt = $conn->prepare('UPDATE users SET is_flagged = 1, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$user_id]);
            $success = 'User flagged.';
        } elseif ($action === 'unflag') {
            $stmt = $conn->prepare('UPDATE users SET is_flagged = 0, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$user_id]);
            $success = 'User unflagged.';
        } elseif ($action === 'block') {
            $reason = trim($_POST['reason'] ?? 'Policy violation');
            $stmt = $conn->prepare('UPDATE users SET is_blocked = 1, block_reason = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$reason, $user_id]);
            $success = 'User blocked.';
        } elseif ($action === 'unblock') {
            $stmt = $conn->prepare('UPDATE users SET is_blocked = 0, block_reason = NULL, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$user_id]);
            $success = 'User unblocked.';
        }
    } catch (Exception $e) {
        $error = 'Operation failed: ' . $e->getMessage();
    }
}

// Auto-flag users with more than 10 application attempts (no button needed)
try {
    $conn->query("UPDATE users u SET is_flagged = 1, updated_at = NOW() WHERE EXISTS (
        SELECT 1 FROM application_attempts aa WHERE aa.user_id = u.id GROUP BY aa.user_id HAVING COUNT(*) > 10
    )");
} catch (PDOException $e) {}

// Fetch users with application attempt counts
$users = [];
// Search term (accurate multi-field search)
$q = trim($_GET['q'] ?? '');
try {
    $sql = 'SELECT u.id, u.control_number, 
                   u.first_name_encrypted, u.last_name_encrypted, 
                   u.email_encrypted, u.mobile_number_encrypted,
                   u.is_verified, u.is_flagged, u.is_blocked, u.block_reason, u.created_at, 
                   COALESCE(a.attempts, 0) AS attempt_count
            FROM users u
            LEFT JOIN (
              SELECT user_id, COUNT(*) AS attempts
              FROM application_attempts
              GROUP BY user_id
            ) a ON a.user_id = u.id';
    $params = [];
    if ($q !== '') {
        // For encrypted search, we need to search both encrypted and unencrypted fields
        $sql .= ' WHERE (
            u.control_number LIKE :q OR
            u.first_name LIKE :q OR
            u.last_name LIKE :q OR
            u.email LIKE :q OR
            u.mobile_number LIKE :q OR
            CONCAT(u.first_name, " ", u.last_name) LIKE :q
        )';
        $params[':q'] = "%$q%";
    }
    $sql .= ' ORDER BY attempt_count DESC, u.created_at DESC';
    $stmt = $conn->prepare($sql);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->execute();
    $raw_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decrypt user data
    foreach ($raw_users as $user) {
        $users[] = [
            'id' => $user['id'],
            'control_number' => $user['control_number'],
            'first_name' => decryptPersonalData($user['first_name_encrypted']),
            'last_name' => decryptPersonalData($user['last_name_encrypted']),
            'email' => decryptContactData($user['email_encrypted']),
            'mobile_number' => decryptContactData($user['mobile_number_encrypted']),
            'is_verified' => $user['is_verified'],
            'is_flagged' => $user['is_flagged'],
            'is_blocked' => $user['is_blocked'],
            'block_reason' => $user['block_reason'],
            'created_at' => $user['created_at'],
            'attempt_count' => $user['attempt_count']
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
}

include 'html/view_all_users.html';
?>


