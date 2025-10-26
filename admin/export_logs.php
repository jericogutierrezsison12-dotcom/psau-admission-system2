<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';
require_once '../includes/admin_auth.php';
require_once '../includes/encryption.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get filter parameters
$user_type = $_GET['user_type'] ?? 'all';
$action_filter = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build the base query
$base_query = "
    SELECT 
        al.id,
        al.action,
        al.details,
        al.ip_address,
        al.created_at,
        CASE 
            WHEN a.id IS NOT NULL THEN 'admin'
            WHEN u.id IS NOT NULL THEN 'user'
            ELSE 'unknown'
        END as user_type,
        COALESCE(a.username, u.control_number, 'Unknown') as username,
        COALESCE(a.role, 'user') as role,
        a.username as admin_username,
        u.first_name_encrypted,
        u.last_name_encrypted
    FROM activity_logs al
    LEFT JOIN admins a ON al.user_id = a.id
    LEFT JOIN users u ON al.user_id = u.id
    WHERE 1=1
";

$params = [];
$where_conditions = [];

// Apply user type filter
if ($user_type !== 'all') {
    if ($user_type === 'admin') {
        $where_conditions[] = "a.id IS NOT NULL";
    } elseif ($user_type === 'user') {
        $where_conditions[] = "u.id IS NOT NULL";
    } elseif ($user_type === 'registrar') {
        $where_conditions[] = "a.id IS NOT NULL AND a.role = 'registrar'";
    } elseif ($user_type === 'department') {
        $where_conditions[] = "a.id IS NOT NULL AND a.role = 'department'";
    }
}

// Apply action filter
if (!empty($action_filter)) {
    $where_conditions[] = "al.action LIKE ?";
    $params[] = "%$action_filter%";
}

// Apply date filters
if (!empty($date_from)) {
    $where_conditions[] = "DATE(al.created_at) >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $where_conditions[] = "DATE(al.created_at) <= ?";
    $params[] = $date_to;
}

// Apply search filter
if (!empty($search)) {
    $where_conditions[] = "(al.details LIKE ? OR al.action LIKE ? OR COALESCE(a.username, u.control_number) LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Add where conditions to base query
if (!empty($where_conditions)) {
    $base_query .= " AND " . implode(" AND ", $where_conditions);
}

// Get all logs (no pagination for export)
$logs_query = $base_query . " ORDER BY al.created_at DESC";

$stmt = $conn->prepare($logs_query);
$stmt->execute($params);
$raw_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Decrypt user data and build display names
$logs = [];
foreach ($raw_logs as $log) {
    $display_name = 'Unknown';
    if (!empty($log['admin_username'])) {
        $display_name = $log['admin_username'];
    } elseif (!empty($log['first_name_encrypted']) && !empty($log['last_name_encrypted'])) {
        $first_name = decryptPersonalData($log['first_name_encrypted']);
        $last_name = decryptPersonalData($log['last_name_encrypted']);
        $display_name = trim($first_name . ' ' . $last_name);
    }
    
    $log['display_name'] = $display_name;
    $logs[] = $log;
}

// Set headers for CSV download
$filename = 'activity_logs_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write CSV headers
fputcsv($output, [
    'ID',
    'Date & Time',
    'User Type',
    'Role',
    'Username',
    'Display Name',
    'Action',
    'Details',
    'IP Address'
]);

// Write data rows
foreach ($logs as $log) {
    fputcsv($output, [
        $log['id'],
        $log['created_at'],
        ucfirst($log['user_type']),
        ucfirst($log['role']),
        $log['username'],
        $log['display_name'],
        $log['action'],
        $log['details'],
        $log['ip_address'] ?? 'N/A'
    ]);
}

fclose($output);
exit;
?>
