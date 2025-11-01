<?php
// Admin-only Enrollment Completion Tool
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/admin_auth.php';
require_once '../includes/functions.php';
require_once '../firebase/firebase_email.php';

check_admin_login();
require_page_access('enrollment_completion');

$admin_id = $_SESSION['admin_id'];

$success = '';
$error = '';
$results = [];

function find_user_by_control_number(PDO $conn, $control_number) {
    $stmt = $conn->prepare('SELECT id, first_name, last_name FROM users WHERE control_number = ?');
    $stmt->execute([$control_number]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Decrypt user data
    if ($user) {
        require_once __DIR__ . '/../includes/encryption.php';
        $user['first_name'] = safeDecryptField($user['first_name'] ?? '', 'users', 'first_name');
        $user['last_name'] = safeDecryptField($user['last_name'] ?? '', 'users', 'last_name');
    }
    
    return $user;
}

function mark_enrollment(PDO $conn, $user_id, $status, $admin_name) {
    // status: completed => Enrolled, cancelled => Enrollment Cancelled
    // Find latest application for user
    $stmt = $conn->prepare('SELECT id, status FROM applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$user_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$application) {
        throw new Exception('No application found for user.');
    }

    // Update enrollment_assignments (latest assignment for this user regardless of status)
    $stmt = $conn->prepare('SELECT id, schedule_id, status FROM enrollment_assignments WHERE student_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$user_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($assignment) {
        $newAssignmentStatus = $status === 'completed' ? 'completed' : 'cancelled';
        $previousStatus = $assignment['status'] ?? null;
        $stmt2 = $conn->prepare('UPDATE enrollment_assignments SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt2->execute([$newAssignmentStatus, $assignment['id']]);

        // If cancelled (from pending/completed), restore course slot only (don't affect schedule count)
        if ($newAssignmentStatus === 'cancelled' && $previousStatus !== 'cancelled') {
            // Find course from the schedule
            $stmtC = $conn->prepare('SELECT es.course_id FROM enrollment_schedules es WHERE es.id = ?');
            $stmtC->execute([$assignment['schedule_id']]);
            $courseId = $stmtC->fetchColumn();
            if ($courseId) {
                // Restore available slot and adjust enrolled_students
                $stmtU = $conn->prepare('UPDATE courses SET slots = slots + 1, enrolled_students = GREATEST(0, enrolled_students - 1) WHERE id = ?');
                $stmtU->execute([$courseId]);
            }
            // Note: We do NOT update enrollment_schedules.current_count to keep schedule occupancy unchanged
        }
    }

    // Update application status
    // Only update to 'Enrolled' when completed; on cancellation, keep current application status
    if ($status === 'completed') {
        $newAppStatus = 'Enrolled';
        $stmt3 = $conn->prepare('UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?');
        $successStatus = $stmt3->execute([$newAppStatus, $application['id']]);
        if (!$successStatus) {
            $err = $stmt3->errorInfo();
            error_log('Enrollment Completion: Failed to update applications.status: ' . print_r($err, true));
            global $error;
            $error = ($error ? $error.' ' : '') . 'Database error: could not update status. Contact IT.';
        }
    }

    // Log status history
    $stmt4 = $conn->prepare('INSERT INTO status_history (application_id, status, description, performed_by, created_at) VALUES (?, ?, ?, ?, NOW())');
    $desc = $status === 'completed' ? 'Enrollment marked completed by admin' : 'Enrollment cancelled by admin';
    $historyStatus = $status === 'completed' ? 'Enrolled' : 'Enrollment Cancelled';
    $stmt4->execute([$application['id'], $historyStatus, $desc, $admin_name]);

    // EMAIL NOTIFICATION ADDED
    $stmtU = $conn->prepare('SELECT email, first_name, last_name FROM users WHERE id = ?');
    $stmtU->execute([$user_id]);
    $userInfo = $stmtU->fetch(PDO::FETCH_ASSOC);
    
    // Decrypt user data
    if ($userInfo) {
        require_once __DIR__ . '/../includes/encryption.php';
        $userInfo['email'] = safeDecryptField($userInfo['email'] ?? '', 'users', 'email');
        $userInfo['first_name'] = safeDecryptField($userInfo['first_name'] ?? '', 'users', 'first_name');
        $userInfo['last_name'] = safeDecryptField($userInfo['last_name'] ?? '', 'users', 'last_name');
    }
    
    if ($userInfo && function_exists('firebase_send_email')) {
        $subject = ($status === 'completed') ?
            'Enrollment Completed - PSAU Admission System' :
            'Enrollment Cancelled - PSAU Admission System';
        $bodyMsg = ($status === 'completed') ?
            "<p>Dear {$userInfo['first_name']} {$userInfo['last_name']},</p><p>Your enrollment has been successfully completed. Welcome to PSAU!</p>" :
            "<p>Dear {$userInfo['first_name']} {$userInfo['last_name']},</p><p>Your enrollment was cancelled. For details, please contact admissions.</p>";
        try {
            $email_sent_result = firebase_send_email($userInfo['email'], $subject, $bodyMsg);
            if (!isset($email_sent_result['success']) || !$email_sent_result['success']) {
                error_log("Failed to send enrollment status email: " . json_encode($email_sent_result));
                global $error;
                $error = ($error ? $error.' ' : '') . 'Warning: Enrollment completion email was not sent.';
            }
        } catch (Exception $e) {
            error_log('Enrollment Completion Email error: ' . $e->getMessage());
            global $error;
            $error = ($error ? $error.' ' : '') . 'Warning: Enrollment completion email could not be sent.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $admin_name = $_SESSION['admin_username'] ?? 'admin';

    try {
        if ($action === 'manual') {
            $control_number = trim($_POST['control_number'] ?? '');
            $decision = $_POST['decision'] ?? 'completed';
            if ($control_number === '') {
                throw new Exception('Control number is required.');
            }
            $conn->beginTransaction();
            $user = find_user_by_control_number($conn, $control_number);
            if (!$user) {
                throw new Exception('Control number not found.');
            }
            mark_enrollment($conn, (int)$user['id'], $decision, $admin_name);
            $conn->commit();
            $success = 'Successfully updated enrollment for ' . htmlspecialchars($control_number) . '.';
        } elseif ($action === 'csv') {
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Please upload a valid CSV file.');
            }
            $file = $_FILES['csv_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                throw new Exception('Invalid file type. Please upload a .csv file.');
            }
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                throw new Exception('Unable to read uploaded file.');
            }
            // Expect headers: control_number, decision
            $headers = fgetcsv($handle);
            $headers = array_map(function($h){ return strtolower(trim($h)); }, $headers ?: []);
            $idx_cn = array_search('control_number', $headers);
            $idx_dec = array_search('decision', $headers);
            if ($idx_cn === false || $idx_dec === false) {
                throw new Exception("CSV must contain headers: control_number, decision");
            }
            $processed = 0; $failed = 0;
            $row_num = 1;
            $conn->beginTransaction();
            while (($row = fgetcsv($handle)) !== false) {
                $row_num++;
                if (!is_array($row) || count(array_filter($row, fn($v)=>$v!==null && $v!=='')) === 0) continue;
                $control_number = trim($row[$idx_cn] ?? '');
                $decision = strtolower(trim($row[$idx_dec] ?? ''));
                if ($control_number === '' || !in_array($decision, ['completed','cancelled'], true)) {
                    $failed++;
                    $results[] = [ 'row' => $row_num, 'control_number' => $control_number, 'error' => 'Invalid row data' ];
                    continue;
                }
                try {
                    $user = find_user_by_control_number($conn, $control_number);
                    if (!$user) {
                        $failed++;
                        $results[] = [ 'row' => $row_num, 'control_number' => $control_number, 'error' => 'Control number not found' ];
                        continue;
                    }
                    mark_enrollment($conn, (int)$user['id'], $decision, $admin_name);
                    $processed++;
                } catch (Exception $e) {
                    $failed++;
                    $results[] = [ 'row' => $row_num, 'control_number' => $control_number, 'error' => $e->getMessage() ];
                }
            }
            fclose($handle);
            $conn->commit();
            $success = "Processed {$processed} rows. Failed: {$failed}.";
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) { $conn->rollBack(); }
        $error = $e->getMessage();
    }
}

include 'html/enrollment_completion.html';
?>


