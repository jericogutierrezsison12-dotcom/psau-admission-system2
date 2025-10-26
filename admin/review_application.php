<?php
/**
 * PSAU Admission System - Review Application
 * Page for administrators to review and verify/reject individual applications
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable output buffering to capture any early errors
ob_start();

/* Debug information block - commented out for production
echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; margin-bottom: 10px;'>";
echo "<h3>Debug Information</h3>";
echo "<p>Application ID from URL: " . (isset($_GET['id']) ? htmlspecialchars($_GET['id']) : 'No ID provided') . "</p>";
echo "<p>Request Method: " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "</div>";
*/

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/functions.php';
require_once '../includes/encryption.php';

// Email System - IMPORTANT: The system now uses Firebase for sending emails
// Firebase email functions - this is the primary email system
require_once '../firebase/firebase_email.php';

// Check if admin is logged in
is_admin_logged_in('login.php');

// Get admin details
try {
    $admin = get_current_admin($conn);
} catch (Exception $e) {
    // Set a default admin array if unable to get admin details
    $admin = ['id' => 1, 'username' => 'system'];
    error_log("Error getting admin details: " . $e->getMessage());
}

// Check if application ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $error_message = "No application ID provided!";
    include 'html/review_application.html';
    exit;
}

$application_id = $_GET['id'];
$application = null;
$user = null;
$documents = [];

// Fetch application details
try {
    $stmt = $conn->prepare("SELECT 
                          a.*, 
                          u.id as user_id,
                          u.control_number,
                          u.first_name_encrypted,
                          u.last_name_encrypted,
                          u.email_encrypted,
                          u.mobile_number_encrypted,
                          u.is_verified,
                          u.created_at as user_created_at,
                          COALESCE(a.document_file_size, 0) as document_file_size,
                          COALESCE(a.image_2x2_size, 0) as image_2x2_size
                          FROM applications a 
                          JOIN users u ON a.user_id = u.id 
                          WHERE a.id = :app_id");
    $stmt->bindParam(':app_id', $application_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $application = $result;
        
        // Decrypt user data
        $user = [
            'id' => $result['user_id'],
            'control_number' => $result['control_number'],
            'first_name' => decryptPersonalData($result['first_name_encrypted']),
            'last_name' => decryptPersonalData($result['last_name_encrypted']),
            'email' => decryptContactData($result['email_encrypted']),
            'mobile_number' => decryptContactData($result['mobile_number_encrypted']),
            'is_verified' => $result['is_verified'],
            'created_at' => $result['user_created_at']
        ];
        
        // Format file sizes to human-readable format
        $application['document_file_size_formatted'] = $application['document_file_size'] ? number_format($application['document_file_size'] / 1024, 2) . ' KB' : 'N/A';
        $application['image_2x2_size_formatted'] = $application['image_2x2_size'] ? number_format($application['image_2x2_size'] / 1024, 2) . ' KB' : 'N/A';
        
        // Debug info for application data
        error_log("Application data for ID {$application_id}: " . json_encode($application));
        
        // Ensure address field is properly loaded
        if (!isset($application['address']) || empty($application['address'])) {
            error_log("Address is empty or missing in application data");
            
            // Try to fetch address separately to confirm it exists in the database
            $addr_stmt = $conn->prepare("SELECT address FROM applications WHERE id = :id");
            $addr_stmt->bindParam(':id', $application_id);
            $addr_stmt->execute();
            $addr_result = $addr_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($addr_result && isset($addr_result['address'])) {
                $application['address'] = $addr_result['address'];
                error_log("Retrieved address separately: {$application['address']}");
            } else {
                error_log("Address field cannot be found in database");
            }
        }
    } else {
        $error_message = "No application found with ID " . htmlspecialchars($application_id);
        include 'html/review_application.html';
        exit;
    }
    
    // Fetch documents
    try {
    $stmt = $conn->prepare("SELECT * FROM documents WHERE application_id = :app_id");
    $stmt->bindParam(':app_id', $application_id);
    $stmt->execute();
    $documents = $stmt->fetchAll();
    } catch (PDOException $e) {
        // If documents table doesn't exist, just log the error
        $documents = [];
        error_log("Documents table error: " . $e->getMessage());
    }
    
} catch (PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
    error_log("Application Review Error: " . $e->getMessage());
    include 'html/review_application.html';
    exit;
}

// Discard any buffered output to ensure no output before header redirects
if (ob_get_level() > 0) {
    ob_end_clean();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            $action = $_POST['action'];
            
            if ($action === 'verify') {
                // Verify the application
                $stmt = $conn->prepare("UPDATE applications SET status = 'Verified', verified_at = NOW() WHERE id = :app_id");
                $stmt->bindParam(':app_id', $application_id);
                $stmt->execute();
                
                // Log the activity
                log_activity($conn, 'verify_application', "Admin verified application for " . $user['first_name'] . ' ' . $user['last_name'], $admin['id'] ?? 1);
                
                // Send email notification using Firebase
                error_log("Preparing to send verification email to: " . $user['email']);
                
                try {
                    // Check if Firebase email functions are available
                    if (!function_exists('firebase_send_email')) {
                        error_log("Firebase email functions not available - they should have been included at the top of the script");
                    }
                    
                    // Send email using Firebase
                    $email_sent = false;
                    
                    if (function_exists('send_verification_email')) {
                        $email_result = send_verification_email($user);
                        if ($email_result) {
                            $email_sent = true;
                            error_log("Verification email sent successfully using Firebase");
                        } else {
                            error_log("Firebase email sending returned false");
                        }
                    }
                    
                    // Log the final result
                    if ($email_sent) {
                        // Add a record in the activity logs
                        $stmt = $conn->prepare("INSERT INTO activity_logs (action, user_id, details) VALUES ('email_sent', :user_id, :details)");
                        $details = "Verification email sent to " . $user['email'] . " via Firebase";
                        $stmt->bindParam(':user_id', $user['id']);
                        $stmt->bindParam(':details', $details);
                        $stmt->execute();
                    } else {
                        error_log("WARNING: Firebase email sending failed for user: " . $user['email']);
                    }
                    
                } catch (Exception $email_error) {
                    // Log error but continue with the process
                    error_log("Email sending error: " . $email_error->getMessage());
                    error_log("Email error trace: " . $email_error->getTraceAsString());
                }
                
                // Redirect with success message
                safe_redirect('verify_applications.php?success=1');
                
            } elseif ($action === 'verify_no_email') {
                // Verify the application without sending email
                $stmt = $conn->prepare("UPDATE applications SET status = 'Verified', verified_at = NOW() WHERE id = :app_id");
                $stmt->bindParam(':app_id', $application_id);
                $stmt->execute();
                
                // Log the activity
                log_activity($conn, 'verify_application', "Admin verified application (no email) for " . $user['first_name'] . ' ' . $user['last_name'], $admin['id'] ?? 1);
                
                // Redirect with success message
                safe_redirect('verify_applications.php?success=1');
            } elseif ($action === 'reject') {
                // Get rejection reason
                $rejection_reason = $_POST['rejection_reason'] ?? 'Missing or incomplete requirements';
                
                // Update application status
                $stmt = $conn->prepare("UPDATE applications SET status = 'Rejected', rejection_reason = :reason WHERE id = :app_id");
                $stmt->bindParam(':reason', $rejection_reason);
                $stmt->bindParam(':app_id', $application_id);
                $stmt->execute();
                
                // Log the activity
                log_activity($conn, 'reject_application', "Admin rejected application for " . $user['first_name'] . ' ' . $user['last_name'] . ". Reason: " . $rejection_reason, $admin['id'] ?? 1);
                
                // Send rejection email using Firebase
                error_log("Preparing to send rejection email to: " . $user['email'] . " with reason: " . $rejection_reason);
                
                try {
                    // Check if Firebase email functions are available
                    if (!function_exists('firebase_send_email')) {
                        error_log("Firebase email functions not available - they should have been included at the top of the script");
                    }
                    
                    // Send email using Firebase
                    $email_sent = false;
                    
                    if (function_exists('send_resubmission_email')) {
                        $email_result = send_resubmission_email($user, $rejection_reason);
                        if ($email_result) {
                            $email_sent = true;
                            error_log("Rejection email sent successfully using Firebase");
                        } else {
                            error_log("Firebase email sending returned false");
                        }
                    }
                    
                    // Log the final result
                    if ($email_sent) {
                        // Add a record in the activity logs
                        $stmt = $conn->prepare("INSERT INTO activity_logs (action, user_id, details) VALUES ('email_sent', :user_id, :details)");
                        $details = "Rejection email sent to " . $user['email'] . " via Firebase. Reason: " . $rejection_reason;
                        $stmt->bindParam(':user_id', $user['id']);
                        $stmt->bindParam(':details', $details);
                        $stmt->execute();
                    } else {
                        error_log("WARNING: Firebase email sending failed for rejection notification to user: " . $user['email']);
                    }
                    
                } catch (Exception $email_error) {
                    // Log error but continue with the process
                    error_log("Rejection email sending error: " . $email_error->getMessage());
                    error_log("Rejection email error trace: " . $email_error->getTraceAsString());
                }
                
                // Redirect with rejection message
                safe_redirect('verify_applications.php?rejected=1');
            } elseif ($action === 'test_email') {
                // Get test email address
                $test_email = $_POST['test_email'] ?? $user['email'];
                
                // Run diagnostics check
                $diagnostics = [];
                $diagnostics['firebase_email_file'] = file_exists('../firebase_email.php') ? 'Found' : 'Not Found';
                
                // Check if Firebase test function is available
                if (!function_exists('test_firebase_email')) {
                    error_log("Firebase test function not available - make sure firebase_email.php was included");
                }
                
                // Create diagnostics HTML
                $diagnostics_html = "<div class='mt-3'>";
                $diagnostics_html .= "<h6>Email System Diagnostics:</h6>";
                $diagnostics_html .= "<ul class='list-group'>";
                $diagnostics_html .= "<li class='list-group-item d-flex justify-content-between align-items-center'>Firebase Email Integration: <span class='badge ".(function_exists('firebase_send_email') ? "bg-success" : "bg-danger")."'>".(function_exists('firebase_send_email') ? "Available" : "Not Available")."</span></li>";
                $diagnostics_html .= "<li class='list-group-item d-flex justify-content-between align-items-center'>Firebase Email File: <span class='badge ".($diagnostics['firebase_email_file'] == 'Found' ? "bg-success" : "bg-danger")."'>".$diagnostics['firebase_email_file']."</span></li>";
                $diagnostics_html .= "<li class='list-group-item d-flex justify-content-between align-items-center'>cURL Extension: <span class='badge ".(function_exists('curl_init') ? "bg-success" : "bg-danger")."'>".(function_exists('curl_init') ? "Available" : "Not Available")."</span></li>";
                $diagnostics_html .= "</ul>";
                $diagnostics_html .= "</div>";
                
                // Test sending email via Firebase
                if (function_exists('test_firebase_email')) {
                    $test_result = test_firebase_email($test_email);
                    
                    if ($test_result === true) {
                        echo "<div class='alert alert-success mt-3'>Test email sent successfully to $test_email via Firebase</div>";
                        echo $diagnostics_html;
                    } else {
                        // Display diagnostic information
                        echo "<div class='alert alert-danger mt-3'>Firebase email test failed. Diagnostic information:<br>";
                        echo "<pre>".htmlspecialchars(is_string($test_result) ? $test_result : "Unknown error")."</pre>";
                        echo "</div>";
                        echo $diagnostics_html;
                    }
                } else {
                    echo "<div class='alert alert-danger mt-3'>Firebase email test function not available. Make sure firebase_email.php is properly included.</div>";
                    echo $diagnostics_html;
                }
            }
            
        } catch (Exception $e) {
            // Log detailed error information
            error_log("Application Action Error: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            
            // Show detailed error message in development environment
            $error_message = "There was a problem processing the application: " . $e->getMessage();
        }
    }
}

// Function to log admin activity
function log_activity($conn, $action, $details, $admin_id) {
    try {
        $stmt = $conn->prepare("INSERT INTO activity_logs (action, details, user_id) VALUES (:action, :details, :admin_id)");
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':details', $details);
        $stmt->bindParam(':admin_id', $admin_id);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("Activity Log Error: " . $e->getMessage());
    }
}

// Helper function to diagnose email configuration issues
function get_email_diagnostics() {
    $diagnostics = [];
    
    // Check if mail() function is available
    $diagnostics['mail_function'] = function_exists('mail');
    
    // Check if PHPMailer is available
    $diagnostics['phpmailer_available'] = class_exists('PHPMailer\PHPMailer\PHPMailer', false);
    
    // Check if SMTP ports are accessible
    $diagnostics['smtp_port_587'] = false;
    $diagnostics['smtp_port_465'] = false;
    
    // Try connecting to SMTP server
    $smtp_host = 'smtp.gmail.com';
    try {
        $socket = @fsockopen($smtp_host, 587, $errno, $errstr, 3);
        if ($socket) {
            $diagnostics['smtp_port_587'] = true;
            fclose($socket);
        }
    } catch (Exception $e) {
        // Connection failed
    }
    
    try {
        $socket = @fsockopen($smtp_host, 465, $errno, $errstr, 3);
        if ($socket) {
            $diagnostics['smtp_port_465'] = true;
            fclose($socket);
        }
    } catch (Exception $e) {
        // Connection failed
    }
    
    // Check Gmail credentials
    $diagnostics['gmail_credentials'] = [
        'username' => !empty('jericogutierrezsison12@gmail.com'),
        'password' => !empty('crsh iejc lhwz gasu')
    ];
    
    return $diagnostics;
}

// Helper function for status colors
function get_status_color($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'verified':
            return 'success';
        case 'rejected':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Include the HTML template
include 'html/review_application.html'; 