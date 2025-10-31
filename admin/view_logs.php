<?php
/**
 * PSAU Admission System - Activity Logs Viewer
 * Admin-only page to view all system activities
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/admin_auth.php';
require_once '../includes/encryption.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Ensure only admin users can access view logs
require_page_access('view_logs');

// Get filter parameters
$user_type = $_GET['user_type'] ?? 'all';
$action_filter = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Get tab parameter for switching between logs
$tab = $_GET['tab'] ?? 'activity';

// Initialize variables
$error_message = null;
$logs = [];
$total_logs = 0;
$total_pages = 0;
$actions = [];
$user_type_counts = [];

// OTP-specific variables
$otp_logs = [];
$otp_total_logs = 0;
$otp_total_pages = 0;
$otp_search = $_GET['otp_search'] ?? '';
$otp_page = max(1, intval($_GET['otp_page'] ?? 1));
$otp_offset = ($otp_page - 1) * $per_page;

try {
    // Build SQL with filters and user enrichment
    $where = [];
    $params = [];

    if ($user_type !== 'all') {
        $where[] = "CASE WHEN a.id IS NOT NULL THEN a.role ELSE 'user' END = :user_type";
        $params[':user_type'] = $user_type;
    }
    if (!empty($action_filter)) {
        $where[] = "al.action = :action";
        $params[':action'] = $action_filter;
    }
    if (!empty($date_from)) {
        $where[] = "DATE(al.created_at) >= :date_from";
        $params[':date_from'] = $date_from;
    }
    if (!empty($date_to)) {
        $where[] = "DATE(al.created_at) <= :date_to";
        $params[':date_to'] = $date_to;
    }
    if (!empty($search)) {
        $where[] = "(al.details LIKE :search OR al.action LIKE :search OR a.username LIKE :search OR u.control_number LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
        $params[':search'] = "%$search%";
    }

    $where_sql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

    // Total count
    $count_sql = "
        SELECT COUNT(*)
        FROM activity_logs al
        LEFT JOIN admins a ON al.user_id = a.id
        LEFT JOIN users u ON al.user_id = u.id
        $where_sql
    ";
    $count_stmt = $conn->prepare($count_sql);
    foreach ($params as $k => $v) { $count_stmt->bindValue($k, $v); }
    $count_stmt->execute();
    $total_logs = (int)$count_stmt->fetchColumn();

    if ($total_logs === 0) {
        $error_message = "No activity logs found in the database. Activities will appear here as users interact with the system.";
        $logs = [];
        $total_pages = 0;
    } else {
        $total_pages = (int)ceil($total_logs / $per_page);

        // Data query with pagination
        $data_sql = "
            SELECT 
                al.*, 
                COALESCE(a.username, u.control_number, 'Unknown') AS username,
                TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) AS display_name,
                COALESCE(a.role, 'user') AS role,
                CASE WHEN a.id IS NOT NULL THEN a.role ELSE 'user' END AS user_type
            FROM activity_logs al
            LEFT JOIN admins a ON al.user_id = a.id
            LEFT JOIN users u ON al.user_id = u.id
            $where_sql
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $data_stmt = $conn->prepare($data_sql);
        foreach ($params as $k => $v) { $data_stmt->bindValue($k, $v); }
        $data_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $data_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $data_stmt->execute();
        $logs = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        // Decrypt display_name parts if present in raw fields
        foreach ($logs as &$log) {
            // Attempt to rebuild display name from encrypted user fields if available
            if (isset($log['display_name']) && trim($log['display_name']) !== '') {
                // nothing
            }
        }

        // Populate distinct actions for filter dropdown (from all logs)
        $actions_stmt = $conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC");
        $actions = array_column($actions_stmt->fetchAll(PDO::FETCH_ASSOC), 'action');

        // User type counts (from current filtered set)
        $counts = [];
        $counts_sql = "
            SELECT CASE WHEN a.id IS NOT NULL THEN a.role ELSE 'user' END AS user_type, COUNT(*) AS cnt
            FROM activity_logs al
            LEFT JOIN admins a ON al.user_id = a.id
            LEFT JOIN users u ON al.user_id = u.id
            $where_sql
            GROUP BY user_type
        ";
        $counts_stmt = $conn->prepare($counts_sql);
        foreach ($params as $k => $v) { $counts_stmt->bindValue($k, $v); }
        $counts_stmt->execute();
        $user_type_counts = $counts_stmt->fetchAll(PDO::FETCH_ASSOC);
        usort($user_type_counts, function($a, $b) { return $b['cnt'] - $a['cnt']; });
        // Normalize keys to match template expectations
        $user_type_counts = array_map(function($row){ return ['user_type'=>$row['user_type'], 'count'=>(int)$row['cnt']]; }, $user_type_counts);
    }

    // Process OTP logs if on OTP tab
    if ($tab === 'otp') {
        // Get OTP requests
        $otp_where = [];
        $otp_params = [];
        
        if (!empty($otp_search)) {
            $otp_where[] = "(email LIKE :otp_search OR purpose LIKE :otp_search OR ip_address LIKE :otp_search)";
            $otp_params[':otp_search'] = "%$otp_search%";
        }
        
        $otp_where_sql = empty($otp_where) ? '' : ('WHERE ' . implode(' AND ', $otp_where));
        
        // Count OTP requests
        $otp_count_sql = "SELECT COUNT(*) FROM otp_requests $otp_where_sql";
        $otp_count_stmt = $conn->prepare($otp_count_sql);
        foreach ($otp_params as $k => $v) { $otp_count_stmt->bindValue($k, $v); }
        $otp_count_stmt->execute();
        $otp_total_logs = (int)$otp_count_stmt->fetchColumn();
        
        if ($otp_total_logs > 0) {
            $otp_total_pages = (int)ceil($otp_total_logs / $per_page);
            
            // Get OTP requests data
            $otp_data_sql = "
                SELECT 
                    'request' as type,
                    id,
                    email,
                    purpose,
                    ip_address,
                    user_agent,
                    created_at,
                    NULL as otp_code,
                    NULL as attempts,
                    NULL as is_used,
                    NULL as expires_at
                FROM otp_requests 
                $otp_where_sql
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            $otp_data_stmt = $conn->prepare($otp_data_sql);
            foreach ($otp_params as $k => $v) { $otp_data_stmt->bindValue($k, $v); }
            $otp_data_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
            $otp_data_stmt->bindValue(':offset', $otp_offset, PDO::PARAM_INT);
            $otp_data_stmt->execute();
            $otp_requests = $otp_data_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get OTP codes (if table exists)
            $otp_codes = [];
            try {
                $otp_codes_sql = "
                    SELECT 
                        'code' as type,
                        id,
                        email,
                        purpose,
                        ip_address,
                        NULL as user_agent,
                        created_at,
                        otp_code,
                        attempts,
                        is_used,
                        expires_at
                    FROM otp_codes 
                    $otp_where_sql
                    ORDER BY created_at DESC
                    LIMIT :limit OFFSET :offset
                ";
                $otp_codes_stmt = $conn->prepare($otp_codes_sql);
                foreach ($otp_params as $k => $v) { $otp_codes_stmt->bindValue($k, $v); }
                $otp_codes_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
                $otp_codes_stmt->bindValue(':offset', $otp_offset, PDO::PARAM_INT);
                $otp_codes_stmt->execute();
                $otp_codes = $otp_codes_stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // OTP codes table might not exist, that's okay
                $otp_codes = [];
            }
            
            // Combine and sort OTP logs
            $otp_logs = array_merge($otp_requests, $otp_codes);
            usort($otp_logs, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
        }
    }

} catch (PDOException $e) {
    error_log("Database error in view_logs.php: " . $e->getMessage());
    $error_message = "Database error occurred. Please check the error logs for details.";
    $logs = [];
    $total_logs = 0;
    $total_pages = 0;
}

// Calculate total logs count for display
$total_logs_count = array_sum(array_column($user_type_counts, 'count'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - PSAU Admission System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        .log-table th {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }
        .user-type-admin { background-color: #e3f2fd; }
        .user-type-registrar { background-color: #f3e5f5; }
        .user-type-department { background-color: #e8f5e8; }
        .user-type-user { background-color: #fff3e0; }
        .log-details {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .log-details:hover {
            white-space: normal;
            overflow: visible;
            position: relative;
            z-index: 100;
            background: white;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'templates/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="bi bi-journal-text me-2"></i>System Logs
                </h1>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" onclick="exportLogs()">
                        <i class="bi bi-download me-1"></i>Export
                    </button>
                    <button class="btn btn-outline-primary" onclick="refreshLogs()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                    </button>
                </div>
            </div>

            <!-- Tab Navigation -->
            <ul class="nav nav-tabs mb-4" id="logTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $tab === 'activity' ? 'active' : ''; ?>" 
                            id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" 
                            type="button" role="tab" aria-controls="activity" 
                            aria-selected="<?php echo $tab === 'activity' ? 'true' : 'false'; ?>"
                            onclick="switchTab('activity')">
                        <i class="bi bi-journal-text me-1"></i>Activity Logs
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $tab === 'otp' ? 'active' : ''; ?>" 
                            id="otp-tab" data-bs-toggle="tab" data-bs-target="#otp" 
                            type="button" role="tab" aria-controls="otp" 
                            aria-selected="<?php echo $tab === 'otp' ? 'true' : 'false'; ?>"
                            onclick="switchTab('otp')">
                        <i class="bi bi-shield-lock me-1"></i>OTP Logs
                    </button>
                </li>
            </ul>

            <!-- Error/Info Message Display -->
            <?php if (isset($error_message)): ?>
            <div class="alert alert-<?php echo strpos($error_message, 'No activity logs found') !== false ? 'info' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo strpos($error_message, 'No activity logs found') !== false ? 'info-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Tab Content -->
            <div class="tab-content" id="logTabsContent">
                <!-- Activity Logs Tab -->
                <div class="tab-pane fade <?php echo $tab === 'activity' ? 'show active' : ''; ?>" id="activity" role="tabpanel" aria-labelledby="activity-tab">
                    <!-- Summary Cards -->
                    <?php if (!empty($user_type_counts)): ?>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Total Logs</h6>
                                            <h3 class="mb-0"><?php echo number_format($total_logs_count); ?></h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-journal-text fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php foreach ($user_type_counts as $type_count): ?>
                        <div class="col-md-3">
                            <div class="card bg-<?php echo $type_count['user_type'] === 'admin' ? 'success' : ($type_count['user_type'] === 'registrar' ? 'info' : ($type_count['user_type'] === 'department' ? 'warning' : 'secondary')); ?> text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title"><?php echo ucfirst($type_count['user_type']); ?> Logs</h6>
                                            <h3 class="mb-0"><?php echo number_format($type_count['count']); ?></h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="bi bi-<?php echo $type_count['user_type'] === 'admin' ? 'person-badge' : ($type_count['user_type'] === 'registrar' ? 'person-check' : ($type_count['user_type'] === 'department' ? 'building' : 'person')); ?> fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Filters -->
                    <?php if (!empty($actions)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <input type="hidden" name="tab" value="activity">
                                <div class="col-md-2">
                                    <label for="user_type" class="form-label">User Type</label>
                                    <select class="form-select" id="user_type" name="user_type">
                                        <option value="all" <?php echo $user_type === 'all' ? 'selected' : ''; ?>>All Users</option>
                                        <option value="admin" <?php echo $user_type === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="registrar" <?php echo $user_type === 'registrar' ? 'selected' : ''; ?>>Registrar</option>
                                        <option value="department" <?php echo $user_type === 'department' ? 'selected' : ''; ?>>Department</option>
                                        <option value="user" <?php echo $user_type === 'user' ? 'selected' : ''; ?>>Regular Users</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="action" class="form-label">Action</label>
                                    <select class="form-select" id="action" name="action">
                                        <option value="">All Actions</option>
                                        <?php foreach ($actions as $action): ?>
                                        <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $action_filter === $action ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($action); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="date_from" class="form-label">From Date</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="date_to" class="form-label">To Date</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="search" class="form-label">Search</label>
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Search logs..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-search me-1"></i>Filter
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Activity Logs Table -->
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-list-ul me-2"></i>Activity Logs
                                    <span class="badge bg-secondary ms-2"><?php echo number_format($total_logs); ?> total logs</span>
                                </h5>
                                <?php if ($total_pages > 1): ?>
                                <div class="d-flex gap-2">
                                    <span class="text-muted">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                <table class="table table-hover mb-0 log-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>User</th>
                                            <th>Type</th>
                                            <th>Action</th>
                                            <th>Details</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <i class="bi bi-inbox fs-1 text-muted"></i>
                                                <p class="text-muted mt-2">No logs found matching your criteria</p>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($logs as $log): ?>
                                        <tr class="user-type-<?php echo $log['user_type']; ?>">
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($log['created_at'])); ?><br>
                                                    <strong><?php echo date('g:i A', strtotime($log['created_at'])); ?></strong>
                                                </small>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($log['display_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($log['username']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = 'bg-secondary';
                                                $icon = 'person';
                                                switch ($log['user_type']) {
                                                    case 'admin':
                                                        $badge_class = 'bg-success';
                                                        $icon = 'person-badge';
                                                        break;
                                                    case 'registrar':
                                                        $badge_class = 'bg-info';
                                                        $icon = 'person-check';
                                                        break;
                                                    case 'department':
                                                        $badge_class = 'bg-warning';
                                                        $icon = 'building';
                                                        break;
                                                    case 'user':
                                                        $badge_class = 'bg-primary';
                                                        $icon = 'person';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <i class="bi bi-<?php echo $icon; ?> me-1"></i>
                                                    <?php echo ucfirst($log['user_type']); ?>
                                                </span>
                                                <?php if ($log['role'] && $log['role'] !== 'user'): ?>
                                                <br><small class="text-muted"><?php echo ucfirst($log['role']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <code class="text-primary"><?php echo htmlspecialchars($log['action']); ?></code>
                                            </td>
                                            <td>
                                                <div class="log-details" title="<?php echo htmlspecialchars($log['details']); ?>">
                                                    <?php 
                                                    $details = htmlspecialchars($log['details']);
                                                    // Truncate long details for better display
                                                    if (strlen($details) > 100) {
                                                        echo substr($details, 0, 100) . '...';
                                                    } else {
                                                        echo $details;
                                                    }
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                // Determine status based on action type
                                                $status_class = 'bg-secondary';
                                                $status_text = 'Info';
                                                $status_icon = 'info-circle';
                                                
                                                if (strpos($log['action'], 'login') !== false) {
                                                    $status_class = 'bg-success';
                                                    $status_text = 'Success';
                                                    $status_icon = 'check-circle';
                                                } elseif (strpos($log['action'], 'error') !== false || strpos($log['action'], 'fail') !== false) {
                                                    $status_class = 'bg-danger';
                                                    $status_text = 'Error';
                                                    $status_icon = 'exclamation-triangle';
                                                } elseif (strpos($log['action'], 'create') !== false || strpos($log['action'], 'add') !== false) {
                                                    $status_class = 'bg-primary';
                                                    $status_text = 'Created';
                                                    $status_icon = 'plus-circle';
                                                } elseif (strpos($log['action'], 'update') !== false || strpos($log['action'], 'edit') !== false) {
                                                    $status_class = 'bg-warning';
                                                    $status_text = 'Updated';
                                                    $status_icon = 'pencil-square';
                                                } elseif (strpos($log['action'], 'delete') !== false || strpos($log['action'], 'remove') !== false) {
                                                    $status_class = 'bg-danger';
                                                    $status_text = 'Deleted';
                                                    $status_icon = 'trash';
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <i class="bi bi-<?php echo $status_icon; ?> me-1"></i>
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="card-footer">
                            <nav aria-label="Logs pagination">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            <i class="bi bi-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                                    </li>
                                    <?php if ($start_page > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?php echo $total_pages; ?></a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                            Next <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- OTP Logs Tab -->
                <div class="tab-pane fade <?php echo $tab === 'otp' ? 'show active' : ''; ?>" id="otp" role="tabpanel" aria-labelledby="otp-tab">
                    <!-- OTP Search -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-search me-2"></i>Search OTP Logs</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <input type="hidden" name="tab" value="otp">
                                <div class="col-md-8">
                                    <label for="otp_search" class="form-label">Search</label>
                                    <input type="text" class="form-control" id="otp_search" name="otp_search" 
                                           placeholder="Search by email, purpose, or IP address..." 
                                           value="<?php echo htmlspecialchars($otp_search); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-search me-1"></i>Search
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- OTP Logs Table -->
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-shield-lock me-2"></i>OTP Logs
                                    <span class="badge bg-secondary ms-2"><?php echo number_format($otp_total_logs); ?> total logs</span>
                                </h5>
                                <?php if ($otp_total_pages > 1): ?>
                                <div class="d-flex gap-2">
                                    <span class="text-muted">Page <?php echo $otp_page; ?> of <?php echo $otp_total_pages; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                <table class="table table-hover mb-0 log-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Type</th>
                                            <th>Date & Time</th>
                                            <th>Email</th>
                                            <th>Purpose</th>
                                            <th>OTP Code</th>
                                            <th>IP Address</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($otp_logs)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="bi bi-shield-lock fs-1 text-muted"></i>
                                                <p class="text-muted mt-2">No OTP logs found</p>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($otp_logs as $otp_log): ?>
                                        <tr>
                                            <td>
                                                <?php if ($otp_log['type'] === 'request'): ?>
                                                <span class="badge bg-info">
                                                    <i class="bi bi-send me-1"></i>Request
                                                </span>
                                                <?php else: ?>
                                                <span class="badge bg-primary">
                                                    <i class="bi bi-key me-1"></i>Code
                                                </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($otp_log['created_at'])); ?><br>
                                                    <strong><?php echo date('g:i A', strtotime($otp_log['created_at'])); ?></strong>
                                                </small>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($otp_log['email']); ?></code>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($otp_log['purpose']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($otp_log['otp_code']): ?>
                                                <code class="text-primary"><?php echo htmlspecialchars($otp_log['otp_code']); ?></code>
                                                <?php else: ?>
                                                <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars($otp_log['ip_address']); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($otp_log['type'] === 'code'): ?>
                                                    <?php if ($otp_log['is_used']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle me-1"></i>Used
                                                    </span>
                                                    <?php elseif ($otp_log['expires_at'] && strtotime($otp_log['expires_at']) < time()): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-clock me-1"></i>Expired
                                                    </span>
                                                    <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="bi bi-hourglass-split me-1"></i>Pending
                                                    </span>
                                                    <?php endif; ?>
                                                    <?php if ($otp_log['attempts']): ?>
                                                    <br><small class="text-muted"><?php echo $otp_log['attempts']; ?> attempts</small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                <span class="badge bg-info">
                                                    <i class="bi bi-info-circle me-1"></i>Requested
                                                </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- OTP Pagination -->
                        <?php if ($otp_total_pages > 1): ?>
                        <div class="card-footer">
                            <nav aria-label="OTP logs pagination">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($otp_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['otp_page' => $otp_page - 1])); ?>">
                                            <i class="bi bi-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $otp_start_page = max(1, $otp_page - 2);
                                    $otp_end_page = min($otp_total_pages, $otp_page + 2);
                                    
                                    if ($otp_start_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['otp_page' => 1])); ?>">1</a>
                                    </li>
                                    <?php if ($otp_start_page > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $otp_start_page; $i <= $otp_end_page; $i++): ?>
                                    <li class="page-item <?php echo $i === $otp_page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['otp_page' => $i])); ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($otp_end_page < $otp_total_pages): ?>
                                    <?php if ($otp_end_page < $otp_total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['otp_page' => $otp_total_pages])); ?>"><?php echo $otp_total_pages; ?></a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($otp_page < $otp_total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['otp_page' => $otp_page + 1])); ?>">
                                            Next <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function refreshLogs() {
            window.location.reload();
        }
        
        function exportLogs() {
            // Get current filter parameters
            const params = new URLSearchParams(window.location.search);
            params.set('export', '1');
            
            // Create export URL
            const exportUrl = 'export_logs.php?' + params.toString();
            window.open(exportUrl, '_blank');
        }
        
        function switchTab(tab) {
            const params = new URLSearchParams(window.location.search);
            params.set('tab', tab);
            window.location.href = '?' + params.toString();
        }
        
        // Auto-refresh every 30 seconds
        setInterval(refreshLogs, 30000);
        
        // Add tooltip functionality for truncated details
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
    
    <style>
        .log-table th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
        }
        
        .log-details {
            max-width: 200px;
            word-wrap: break-word;
            line-height: 1.4;
        }
        
        .user-type-admin {
            border-left: 4px solid #28a745;
        }
        
        .user-type-registrar {
            border-left: 4px solid #17a2b8;
        }
        
        .user-type-department {
            border-left: 4px solid #ffc107;
        }
        
        .user-type-user {
            border-left: 4px solid #007bff;
        }
        
        .table-responsive {
            border-radius: 0.375rem;
        }
        
        .badge {
            font-size: 0.75em;
        }
        
        code {
            font-size: 0.875em;
            background-color: #f8f9fa;
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
        }
        
        .log-table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
    </style>
</body>
</html>
