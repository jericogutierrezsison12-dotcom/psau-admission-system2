<?php
/**
 * PSAU Admission System - Manage Admin Accounts
 * Admin-only page to list and delete admin/registrar/department accounts
 */

require_once '../includes/db_connect.php';
require_once '../includes/admin_auth.php';

// Ensure admin is logged in
if (!isset($_SESSION)) { session_start(); }
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Restrict to super admin role 'admin'
require_page_access('manage_admins');

$success_message = null;
$error_message = null;

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin_id'])) {
    $targetId = intval($_POST['delete_admin_id']);
    $currentId = intval($_SESSION['admin_id']);

    if ($targetId === $currentId) {
        $error_message = 'You cannot delete your own account.';
    } else {
        try {
            // Get target admin for logging
            $stmt = $conn->prepare('SELECT id, username, role FROM admins WHERE id = ?');
            $stmt->execute([$targetId]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$target) {
                throw new Exception('Account not found.');
            }

            // Attempt to delete
            $stmt = $conn->prepare('DELETE FROM admins WHERE id = ?');
            $stmt->execute([$targetId]);

            // Log activity
            $log = $conn->prepare('INSERT INTO activity_logs (action, user_id, details) VALUES (?, ?, ?)');
            $log->execute(['delete_admin_account', $currentId, 'Deleted admin account: ' . $target['username'] . ' (' . $target['role'] . ')']);

            $success_message = 'Admin account deleted successfully.';
        } catch (PDOException $e) {
            // Likely due to FK constraints
            $error_message = 'Unable to delete this account because it is referenced by other records.';
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Fetch admins
$admins = [];
try {
    $stmt = $conn->query('SELECT id, username, email, role, created_at FROM admins ORDER BY role, username');
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = 'Error fetching admin accounts: ' . $e->getMessage();
}

include 'html/manage_admins.html';
?>


