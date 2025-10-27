<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/db_connect.php';
require_once '../includes/aes_encryption.php';
require_once '../includes/functions.php';
require_once '../firebase/firebase_email.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Test database connection
try {
    $conn->query("SELECT 1");
    error_log("Database connection successful");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error. Please try again later.',
        'error' => $e->getMessage()
    ]);
    exit();
}

// Log request data
error_log("Received reminder request: " . json_encode($_POST));

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Only POST requests are allowed.'
    ]);
    exit();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    error_log("Unauthorized access attempt: No admin_id in session");
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['user_id']) || !isset($_POST['reminder_type'])) {
    error_log("Missing parameters: " . json_encode($_POST));
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit();
}

$user_id = $_POST['user_id'];
$reminder_type = $_POST['reminder_type'];
$admin_id = $_SESSION['admin_id'];

try {
    // Log the start of the process
    error_log("Starting reminder process for user_id: $user_id, reminder_type: $reminder_type");

    // Validate database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Check cooldown period (24 hours = 1 day)
    $cooldown_query = "SELECT created_at 
                      FROM reminder_logs 
                      WHERE user_id = :user_id 
                      AND reminder_type = :reminder_type 
                      AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                      ORDER BY created_at DESC 
                      LIMIT 1";
    $cooldown_stmt = $conn->prepare($cooldown_query);
    $cooldown_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $cooldown_stmt->bindParam(':reminder_type', $reminder_type, PDO::PARAM_STR);
    $cooldown_stmt->execute();
    
    if ($last_reminder = $cooldown_stmt->fetch(PDO::FETCH_ASSOC)) {
        $last_reminder_time = strtotime($last_reminder['created_at']);
        $time_remaining = 86400 - (time() - $last_reminder_time); // 86400 seconds = 24 hours
        
        if ($time_remaining > 0) {
            // Format time remaining in a human-readable format
            $hours = floor($time_remaining / 3600);
            $minutes = floor(($time_remaining % 3600) / 60);
            
            error_log("Cooldown period active for user_id: $user_id. Time remaining: {$hours}h {$minutes}m");
            
            echo json_encode([
                'success' => false,
                'cooldown' => true,
                'message' => "Please wait {$hours} hours and {$minutes} minutes before sending another reminder.",
                'time_remaining' => $time_remaining
            ]);
            exit();
        }
    }

    // Get user email and details
    $user_query = "SELECT u.email, u.first_name, u.last_name, u.control_number
                  FROM users u 
                  WHERE u.id = :user_id";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        error_log("User not found for user_id: $user_id");
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit();
    }

    error_log("Retrieved user details for user_id: $user_id, email: {$user['email']}");

    // Get reminder count for this user and type
    $count_query = "SELECT COUNT(*) as reminder_count 
                    FROM reminder_logs 
                    WHERE user_id = :user_id 
                    AND reminder_type = :reminder_type";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $count_stmt->bindParam(':reminder_type', $reminder_type, PDO::PARAM_STR);
    $count_stmt->execute();
    $reminder_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['reminder_count'];

    error_log("Previous reminder count for user_id: $user_id is $reminder_count");

    // Prepare email content based on reminder type
    $subject = '';
    $message = '';
    $urgency = $reminder_count > 1 ? "URGENT: " : "";
    
    switch ($reminder_type) {
        case 'application_submission':
            $subject = $urgency . 'Reminder: Complete Your PSAU Application';
            $message = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
                    <h2>Pampanga State Agricultural University</h2>
                </div>
                <div style='padding: 20px; border: 1px solid #ddd;'>
                    <p>Dear " . htmlspecialchars($user['first_name']) . ",</p>
                    " . ($reminder_count > 1 ? 
                    "<p style='color: #d32f2f; font-weight: bold;'>This is an urgent follow-up reminder regarding your PSAU Application. Your immediate attention is required.</p>" : 
                    "") . "
                    <p>Please log in to your account and submit the required documents for your PSAU Admission application.</p>
                    <p>If you're experiencing any difficulties, please don't hesitate to contact our support team.</p>
                    <p>Best regards,<br>PSAU Admissions Team</p>
                </div>
                <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
                    <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
                </div>
            </div>";
            break;

        case 'course_selection':
            $subject = $urgency . 'Reminder: Select Your Preferred Courses';
            $message = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
                    <h2>Pampanga State Agricultural University</h2>
                </div>
                <div style='padding: 20px; border: 1px solid #ddd;'>
                    <p>Dear " . htmlspecialchars($user['first_name']) . ",</p>
                    " . ($reminder_count > 1 ? 
                    "<p style='color: #d32f2f; font-weight: bold;'>This is an urgent follow-up reminder. Your course selection is pending and required for proceeding with the admission process.</p>" : 
                    "") . "
                    <p>Please log in to your account to select your preferred courses for enrollment.</p>
                    <p>This is an important step that needs to be completed to proceed with your admission.</p>
                    <p>Best regards,<br>PSAU Admissions Team</p>
                </div>
                <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
                    <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
                </div>
            </div>";
            break;

        case 'enrollment_completion':
            $subject = $urgency . 'Reminder: Complete Your Enrollment';
            $message = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
                    <h2>Pampanga State Agricultural University</h2>
                </div>
                <div style='padding: 20px; border: 1px solid #ddd;'>
                    <p>Dear " . htmlspecialchars($user['first_name']) . ",</p>
                    " . ($reminder_count > 1 ? 
                    "<p style='color: #d32f2f; font-weight: bold;'>This is an urgent reminder regarding your pending enrollment completion.</p>" : 
                    "") . "
                    <p>This is a reminder to complete your enrollment process. Please check your account for the enrollment schedule and requirements.</p>
                    <p>Completing your enrollment is crucial to secure your slot in your assigned course.</p>
                    <p>Best regards,<br>PSAU Admissions Team</p>
                </div>
                <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
                    <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
                </div>
            </div>";
            break;
        default:
            throw new Exception("Invalid reminder type: $reminder_type");
    }

    error_log("Attempting to send email to {$user['email']} with subject: $subject");

    // Send email using Firebase email function
    try {
        $email_result = firebase_send_email($user['email'], $subject, $message);
        error_log("Firebase email response: " . json_encode($email_result));

        if ($email_result['success']) {
            error_log("Email sent successfully to {$user['email']}");

            try {
                // Insert into reminder_logs (without message_id)
                $insert_query = "INSERT INTO reminder_logs (user_id, reminder_type, sent_by, status) 
                                VALUES (:user_id, :reminder_type, :sent_by, 'sent')";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $insert_stmt->bindParam(':reminder_type', $reminder_type, PDO::PARAM_STR);
                $insert_stmt->bindParam(':sent_by', $admin_id, PDO::PARAM_INT);
                $insert_stmt->execute();

                error_log("Reminder log inserted successfully");

                // Log activity
                $reminder_text = $reminder_count > 1 ? "Follow-up reminder" : "First reminder";
                logActivity('reminder_sent', $admin_id, "{$reminder_text} sent to {$user['first_name']} {$user['last_name']} for {$reminder_type}", $_SERVER['REMOTE_ADDR']);
                
                echo json_encode([
                    'success' => true, 
                    'message' => "Reminder sent successfully to {$user['email']}",
                    'messageId' => $email_result['messageId'] ?? null
                ]);
            } catch (PDOException $e) {
                error_log("Database error after sending email: " . $e->getMessage());
                // Even if logging fails, we still sent the email
                echo json_encode([
                    'success' => true,
                    'message' => "Reminder sent successfully to {$user['email']}",
                    'messageId' => $email_result['messageId'] ?? null,
                    'warning' => 'Failed to log the reminder: Database error'
                ]);
            } catch (Exception $e) {
                error_log("General error after sending email: " . $e->getMessage());
                echo json_encode([
                    'success' => true,
                    'message' => "Reminder sent successfully to {$user['email']}",
                    'messageId' => $email_result['messageId'] ?? null,
                    'warning' => 'Failed to log the reminder: ' . $e->getMessage()
                ]);
            }
        } else {
            error_log("Failed to send email to {$user['email']}");
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to send email. Please try again.',
                'error' => $email_result['error'] ?? 'Unknown error'
            ]);
        }
    } catch (Exception $e) {
        error_log("Firebase email error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'error' => 'Email service error'
        ]);
    }

} catch (Exception $e) {
    error_log("Error in send_reminder.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'error' => 'Server error'
    ]);
}
?> 