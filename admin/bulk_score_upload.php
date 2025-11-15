<?php
/**
 * PSAU Admission System - Bulk Score Upload
 * Page for admins to upload entrance exam scores in bulk using Excel files
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/admin_auth.php';
// Try to load Composer autoloader if present
$hasPhpSpreadsheet = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
	require_once __DIR__ . '/../vendor/autoload.php';
	$hasPhpSpreadsheet = class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory');
}
require_once '../firebase/firebase_email.php';

// Do not import with "use" to avoid conditional import issues; use FQCN when needed

// Check if user is logged in as admin
is_admin_logged_in();

// Ensure only admin users can access bulk score upload
require_page_access('bulk_score_upload');

// Get current admin
$admin_id = $_SESSION['admin_id'];

// Check if this is an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_scores'])) {
    try {
        // Validate file upload
        if (!isset($_FILES['score_file']) || $_FILES['score_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Please select a valid Excel file to upload.");
        }

        $file = $_FILES['score_file'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Allow CSV uploads always; allow XLSX/XLS only if PhpSpreadsheet is available
        $allowed_when_phpspreadsheet = ['xlsx', 'xls'];
        $allowed_extensions = $hasPhpSpreadsheet ? array_merge($allowed_when_phpspreadsheet, ['csv']) : ['csv'];
        
        if (!in_array($file_ext, $allowed_extensions)) {
            if ($hasPhpSpreadsheet) {
                throw new Exception("Invalid file format. Allowed: .xlsx, .xls, or .csv");
            } else {
                throw new Exception("Invalid file format. PhpSpreadsheet is not installed; please upload a CSV (.csv) file.");
            }
        }

        // Start transaction
        $conn->beginTransaction();
        
        // Load rows from file (Excel via PhpSpreadsheet, or CSV fallback)
        $rows = [];
        if (in_array($file_ext, ['xlsx', 'xls'])) {
            if (!$hasPhpSpreadsheet) {
                throw new Exception("Excel processing requires PhpSpreadsheet. Please upload a CSV instead.");
            }
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
        } else if ($file_ext === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle === false) {
                throw new Exception("Failed to open uploaded CSV file.");
            }
            // Detect and skip UTF-8 BOM
            $firstBytes = fread($handle, 3);
            if ($firstBytes !== "\xEF\xBB\xBF") {
                // Rewind if no BOM
                fseek($handle, 0);
            }
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
        
        // Validate header row
        $headers = array_map(function($header) {
            return strtolower(trim(str_replace('_', ' ', $header)));
        }, $rows[0]);
        
        // Check if required columns exist
        $control_number_index = array_search('control number', $headers);
        $first_name_index = array_search('first name', $headers);
        $last_name_index = array_search('last name', $headers);
        $stanine_score_index = array_search('stanine score', $headers);
        
        if ($control_number_index === false || $stanine_score_index === false) {
            throw new Exception("Invalid Excel format. Required columns 'Control Number' and 'Stanine Score' not found. First Name and Last Name are optional but recommended.");
        }
        
        // First Name and Last Name are optional but recommended
        if ($first_name_index === false) {
            $first_name_index = null;
        }
        if ($last_name_index === false) {
            $last_name_index = null;
        }
        
        // Process rows
        $success_count = 0;
        $error_count = 0;
        $error_log = [];
        
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            try {
                $control_number = trim($row[$control_number_index]);
                $first_name = $first_name_index !== null && isset($row[$first_name_index]) ? trim($row[$first_name_index]) : null;
                $last_name = $last_name_index !== null && isset($row[$last_name_index]) ? trim($row[$last_name_index]) : null;
                $stanine_score = intval($row[$stanine_score_index]);
                
                // Validate control number exists and get user details
                $stmt = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE control_number = ?");
                $stmt->execute([$control_number]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user_data) {
                    throw new Exception("Invalid control number: $control_number");
                }
                
                // If first_name or last_name provided, validate they match
                if ($first_name !== null && strtolower(trim($user_data['first_name'])) !== strtolower($first_name)) {
                    throw new Exception("First name mismatch for control number: $control_number (Expected: {$user_data['first_name']}, Got: $first_name)");
                }
                if ($last_name !== null && strtolower(trim($user_data['last_name'])) !== strtolower($last_name)) {
                    throw new Exception("Last name mismatch for control number: $control_number (Expected: {$user_data['last_name']}, Got: $last_name)");
                }
                
                // Validate stanine score
                if ($stanine_score < 1 || $stanine_score > 9) {
                    throw new Exception("Invalid stanine score for control number: $control_number");
                }
                
                // Check if score already exists
                $stmt = $conn->prepare("SELECT id FROM entrance_exam_scores WHERE control_number = ?");
                $stmt->execute([$control_number]);
                
                if ($stmt->rowCount() > 0) {
                    // Update existing score
                    $stmt = $conn->prepare("
                        UPDATE entrance_exam_scores 
                        SET stanine_score = ?, 
                            uploaded_by = ?, 
                            upload_date = NOW(), 
                            upload_method = 'bulk'
                        WHERE control_number = ?
                    ");
                    
                    $stmt->execute([
                        $stanine_score,
                        $admin_id,
                        $control_number
                    ]);
                } else {
                    // Insert new score
                    $stmt = $conn->prepare("
                        INSERT INTO entrance_exam_scores 
                        (control_number, stanine_score, uploaded_by, upload_method)
                        VALUES (?, ?, ?, 'bulk')
                    ");
                    
                    $stmt->execute([
                        $control_number,
                        $stanine_score,
                        $admin_id
                    ]);
                }

                // Get user details and application ID
                $stmt = $conn->prepare("
                    SELECT u.*, a.id as application_id 
                    FROM users u 
                    LEFT JOIN applications a ON u.id = a.user_id 
                    WHERE u.control_number = ?
                ");
                $stmt->execute([$control_number]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && $user['application_id']) {
                    // Update application status
                    $stmt = $conn->prepare("
                        UPDATE applications 
                        SET status = 'Score Posted' 
                        WHERE id = ?
                    ");
                    $stmt->execute([$user['application_id']]);

                    // Get admin username from database
                    $stmt = $conn->prepare("SELECT username FROM admins WHERE id = ?");
                    $stmt->execute([$admin_id]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                    $admin_username = $admin['username'];

                    // Add to status history
                    $stmt = $conn->prepare("
                        INSERT INTO status_history 
                        (application_id, status, description, performed_by) 
                        VALUES (?, 'Score Posted', 'Entrance exam score has been posted', ?)
                    ");
                    $stmt->execute([$user['application_id'], $admin_username]);

                    // Send email notification
                    $email_sent = send_score_notification_email(
                        [
                            'first_name' => $user['first_name'],
                            'last_name' => $user['last_name'],
                            'email' => $user['email']
                        ],
                        $control_number,
                        $stanine_score
                    );
                    
                    if (!$email_sent) {
                        $error_log[] = "Warning: Email notification failed for control number: $control_number";
                    }
                }
                
                $success_count++;
            } catch (Exception $e) {
                $error_count++;
                $error_log[] = "Row " . ($i + 1) . ": " . $e->getMessage();
            }
        }
        
        // Log the activity
        $stmt = $conn->prepare("INSERT INTO activity_logs (action, user_id, details) VALUES (?, ?, ?)");
        $stmt->execute([
            'bulk_score_upload',
            $admin_id,
            "Bulk score upload: $success_count successful, $error_count failed"
        ]);
        
        // Commit transaction
        $conn->commit();
        
        $response['success'] = true;
        $response['message'] = "Upload completed: $success_count scores processed successfully, $error_count failed.";
        if ($error_count > 0) {
            $response['errors'] = $error_log;
        }
    } catch (Exception $e) {
        // Roll back transaction only if one is active
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $response['message'] = "Error: " . $e->getMessage();
    }
    
    // Only send JSON response for AJAX requests
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        // For non-AJAX requests, redirect to the same page with a message
        $_SESSION['upload_message'] = $response['message'];
        $_SESSION['upload_success'] = $response['success'];
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get recent uploads
try {
    $query = "
        SELECT 
            ees.control_number,
            ees.stanine_score,
            ees.upload_date,
            u.first_name,
            u.last_name
        FROM entrance_exam_scores ees
        JOIN users u ON ees.control_number = u.control_number
        WHERE ees.upload_method = 'bulk'
        ORDER BY ees.upload_date DESC
        LIMIT 10
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $recent_uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching recent uploads: " . $e->getMessage());
    $recent_uploads = [];
}

// Include the HTML template
include 'html/bulk_score_upload.html'; 