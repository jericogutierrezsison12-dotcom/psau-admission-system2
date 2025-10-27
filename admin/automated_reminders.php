<?php
require_once '../includes/aes_encryption.php';
/**
 * Automated Reminder System
 * This script checks for upcoming entrance exams and enrollment schedules
 * and sends automated reminders to applicants.
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../firebase/firebase_email.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log script start
error_log("Starting automated reminder check at " . date('Y-m-d H:i:s'));

/**
 * Send automated reminders for entrance exams
 */
function sendEntranceExamReminders($conn) {
    try {
        // Get all entrance exams scheduled for tomorrow (align with schema: exams + exam_schedules)
        $query = "
            SELECT 
                u.id AS user_id,
                u.email, u.first_name, u.last_name, u.control_number,
                es.exam_date, es.exam_time, es.venue
            FROM exams e
            JOIN applications a ON e.application_id = a.id
            JOIN users u ON a.user_id = u.id
            JOIN exam_schedules es ON e.exam_schedule_id = es.id
            WHERE DATE(es.exam_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
              AND TIME(es.exam_time) >= TIME(NOW())
              AND NOT EXISTS (
                SELECT 1 FROM reminder_logs r 
                WHERE r.user_id = u.id 
                  AND r.reminder_type = 'exam_reminder' 
                  AND DATE(r.created_at) = CURDATE()
              )
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($exams) . " entrance exams scheduled for tomorrow (24-hour reminder)");
        
        foreach ($exams as $exam) {
            // Format date and time
            $exam_date = date('l, F j, Y', strtotime($exam['exam_date']));
            $exam_time = date('h:i A', strtotime($exam['exam_time']));
            
            // Create email message
            $subject = "Your PSAU Entrance Exam is Tomorrow";
            $message = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
                    <h2>Pampanga State Agricultural University</h2>
                </div>
                <div style='padding: 20px; border: 1px solid #ddd;'>
                    <p>Dear " . htmlspecialchars($exam['first_name']) . ",</p>
                    
                    <p style='color: #d32f2f; font-weight: bold;'>This is a 24-hour reminder that your entrance examination is scheduled for tomorrow.</p>
                    
                    <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #2E7D32; margin: 20px 0;'>
                        <h3 style='margin-top: 0; color: #2E7D32;'>Exam Details</h3>
                        <p><strong>Date:</strong> {$exam_date}</p>
                        <p><strong>Time:</strong> {$exam_time}</p>
                        <p><strong>Venue:</strong> " . htmlspecialchars($exam['venue']) . "</p>
                        <p><strong>Control Number:</strong> " . htmlspecialchars($exam['control_number']) . "</p>
                    </div>
                    
                    <h4>Important Reminders:</h4>
                    <ul>
                        <li>Arrive at least 30 minutes before the exam time</li>
                        <li>Bring valid identification</li>
                        <li>Bring pencils, black/blue pens, and a basic calculator</li>
                        <li>No electronic devices allowed during the exam</li>
                    </ul>
                    
                    <p>If you cannot attend the exam tomorrow, please contact the admissions office immediately.</p>
                    
                    <p>Best regards,<br>PSAU Admissions Team</p>
                </div>
                <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
                    <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
                </div>
            </div>";
            
            // Send email
            try {
                $result = firebase_send_email($exam['email'], $subject, $message);
                
                if ($result['success']) {
                    // Log the reminder
                    $log_query = "INSERT INTO reminder_logs (user_id, reminder_type, sent_by, status) 
                                VALUES (:user_id, 'exam_reminder', 0, 'sent')";
                    $log_stmt = $conn->prepare($log_query);
                    $log_stmt->execute([
                        ':user_id' => $exam['user_id']
                    ]);
                    
                    error_log("Sent exam reminder to: {$exam['email']}");
                }
            } catch (Exception $e) {
                error_log("Failed to send exam reminder to {$exam['email']}: " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        error_log("Error in sendEntranceExamReminders: " . $e->getMessage());
    }
}

/**
 * Send automated reminders for enrollment
 */
function sendEnrollmentReminders($conn) {
    try {
        // Get all enrollments scheduled for tomorrow (align with schema: enrollment_assignments + enrollment_schedules)
        $query = "
            SELECT 
                u.id AS user_id,
                u.email, u.first_name, u.last_name, u.control_number,
                c.course_name, c.course_code,
                es.enrollment_date AS schedule_date,
                es.start_time AS schedule_time,
                es.venue
            FROM enrollment_assignments ea
            JOIN users u ON ea.student_id = u.id
            JOIN courses c ON ea.student_id = u.id AND c.id = ea.schedule_id /* fallback join, adjust if needed */
            JOIN enrollment_schedules es ON ea.schedule_id = es.id
            WHERE DATE(es.enrollment_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
              AND TIME(es.start_time) >= TIME(NOW())
              AND NOT EXISTS (
                SELECT 1 FROM reminder_logs r 
                WHERE r.user_id = u.id 
                  AND r.reminder_type = 'enrollment_reminder' 
                  AND DATE(r.created_at) = CURDATE()
              )
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($enrollments) . " enrollments scheduled for tomorrow (24-hour reminder)");
        
        foreach ($enrollments as $enrollment) {
            // Format date and time
            $enroll_date = date('l, F j, Y', strtotime($enrollment['schedule_date']));
            $enroll_time = date('h:i A', strtotime($enrollment['schedule_time']));
            
            // Create email message
            $subject = "Your PSAU Enrollment is Tomorrow";
            $message = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
                    <h2>Pampanga State Agricultural University</h2>
                </div>
                <div style='padding: 20px; border: 1px solid #ddd;'>
                    <p>Dear " . htmlspecialchars($enrollment['first_name']) . ",</p>
                    
                    <p style='color: #d32f2f; font-weight: bold;'>This is a 24-hour reminder that your enrollment is scheduled for tomorrow.</p>
                    
                    <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #2E7D32; margin: 20px 0;'>
                        <h3 style='margin-top: 0; color: #2E7D32;'>Enrollment Details</h3>
                        <p><strong>Date:</strong> {$enroll_date}</p>
                        <p><strong>Time:</strong> {$enroll_time}</p>
                        <p><strong>Course:</strong> " . htmlspecialchars($enrollment['course_name']) . " (" . htmlspecialchars($enrollment['course_code']) . ")</p>
                        <p><strong>Control Number:</strong> " . htmlspecialchars($enrollment['control_number']) . "</p>
                    </div>
                    
                    <h4>Required Documents:</h4>
                    <ul>
                        <li>Original and photocopy of Form 138 (Report Card)</li>
                        <li>Certificate of Good Moral Character</li>
                        <li>PSA Birth Certificate</li>
                        <li>2x2 ID Pictures (4 pieces)</li>
                        <li>Medical Certificate</li>
                    </ul>
                    
                    <p style='color: #d32f2f;'><strong>Note:</strong> Failure to complete enrollment tomorrow may result in forfeiture of your slot.</p>
                    
                    <p>If you have any questions or cannot attend tomorrow, please contact the admissions office immediately.</p>
                    
                    <p>Best regards,<br>PSAU Admissions Team</p>
                </div>
                <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
                    <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
                </div>
            </div>";
            
            // Send email
            try {
                $result = firebase_send_email($enrollment['email'], $subject, $message);
                
                if ($result['success']) {
                    // Log the reminder
                    $log_query = "INSERT INTO reminder_logs (user_id, reminder_type, sent_by, status) 
                                VALUES (:user_id, 'enrollment_reminder', 0, 'sent')";
                    $log_stmt = $conn->prepare($log_query);
                    $log_stmt->execute([
                        ':user_id' => $enrollment['user_id']
                    ]);
                    
                    error_log("Sent enrollment reminder to: {$enrollment['email']}");
                }
            } catch (Exception $e) {
                error_log("Failed to send enrollment reminder to {$enrollment['email']}: " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        error_log("Error in sendEnrollmentReminders: " . $e->getMessage());
    }
}

// Run the reminder checks
try {
    sendEntranceExamReminders($conn);
    sendEnrollmentReminders($conn);
    error_log("Automated reminder check completed successfully");
} catch (Exception $e) {
    error_log("Error running automated reminders: " . $e->getMessage());
}
?> 