<?php
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/admin_auth.php';
require_once '../includes/functions.php';
require_once '../includes/aes_encryption.php';

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
    $sql = 'SELECT u.id, u.control_number, u.first_name, u.last_name, u.email, u.mobile_number, u.is_verified, u.is_flagged, u.is_blocked, u.block_reason, u.created_at, COALESCE(a.attempts, 0) AS attempt_count
            FROM users u
            LEFT JOIN (
              SELECT user_id, COUNT(*) AS attempts
              FROM application_attempts
              GROUP BY user_id
            ) a ON a.user_id = u.id';
    $params = [];
    if ($q !== '') {
        $sql .= ' WHERE (
            u.control_number LIKE :q OR
            u.email LIKE :q OR
            u.mobile_number LIKE :q OR
            u.first_name LIKE :q OR
            u.last_name LIKE :q OR
            CONCAT(u.first_name, " ", u.last_name) LIKE :q
        )';
        $params[':q'] = "%$q%";
    }
    $sql .= ' ORDER BY attempt_count DESC, u.created_at DESC';
    $stmt = $conn->prepare($sql);
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decrypt sensitive user data for display
    foreach ($users as &$user) {
        $user['first_name'] = smartDecrypt($user['first_name'], 'personal_data');
        $user['last_name'] = smartDecrypt($user['last_name'], 'personal_data');
        $user['email'] = smartDecrypt($user['email'], 'contact_data');
        $user['mobile_number'] = smartDecrypt($user['mobile_number'], 'contact_data');
    }
} catch (PDOException $e) {}

include 'html/view_all_users.html';
?>


