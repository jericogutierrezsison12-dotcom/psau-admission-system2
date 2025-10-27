<?php
/**
 * Firebase Email Integration
 * This file handles sending emails through Firebase Cloud Functions
 */

// Include Firebase configuration
require_once __DIR__ . '/config.php';

/**
 * Send email using Firebase Cloud Functions
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email body (HTML)
 * @param array $options Additional options
 * @return bool True if email request was successful, false otherwise
 */
function firebase_send_email($to, $subject, $message, $options = []) {
    global $firebase_config;
    
    // Log the attempt
    error_log("Attempting to send email via Firebase to: $to, Subject: $subject");
    
    // Validate email
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid recipient email: $to");
        throw new Exception("Invalid recipient email address");
    }
    
    // Validate Firebase config
    if (empty($firebase_config['email_function_url'])) {
        error_log("Firebase email function URL is not configured");
        throw new Exception("Firebase email service is not properly configured");
    }
    
    // Build email payload
    $payload = [
        'to' => $to,
        'subject' => $subject,
        'html' => $message,
        'from' => $options['from'] ?? 'PSAU Admissions <jericogutierrezsison12@gmail.com>',
    ];
    
    // Add CC if specified
    if (!empty($options['cc'])) {
        $payload['cc'] = $options['cc'];
    }
    
    // Add any additional options
    if (!empty($options['replyTo'])) {
        $payload['replyTo'] = $options['replyTo'];
    }
    
    // Convert payload to JSON
    $json_payload = json_encode($payload);
    if ($json_payload === false) {
        error_log("JSON encode error: " . json_last_error_msg());
        throw new Exception("Failed to encode email data");
    }
    
    // Log the request
    error_log("Sending request to Firebase: " . $firebase_config['email_function_url']);
    error_log("Payload: " . $json_payload);
    
    // Set up cURL to call Firebase Cloud Function
    $ch = curl_init($firebase_config['email_function_url']);
    if ($ch === false) {
        error_log("Failed to initialize cURL");
        throw new Exception("Failed to initialize email service");
    }
    
    // Set cURL options with error checking
    $curl_options = [
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $json_payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_payload),
            'User-Agent: PSAU-Admission-System/1.0'
        ],
        CURLOPT_VERBOSE => false,
        CURLOPT_FAILONERROR => false
    ];
    
    if (curl_setopt_array($ch, $curl_options) === false) {
        $error = curl_error($ch);
        curl_close($ch);
        error_log("Failed to set cURL options: " . $error);
        throw new Exception("Failed to configure email service");
    }
    
    // Execute the request with network error handling
    $start_time = microtime(true);
    $result = curl_exec($ch);
    $end_time = microtime(true);
    
    // Get cURL info and errors
    $info = curl_getinfo($ch);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    $status_code = $info['http_code'];
    $total_time = round(($end_time - $start_time) * 1000); // in milliseconds
    
    // Log request timing
    error_log("Request timing: {$total_time}ms, Connect: {$info['connect_time']}s, Total: {$info['total_time']}s");
    
    // Close cURL handle
    curl_close($ch);
    
    // Handle cURL errors
    if ($result === false) {
        $error_message = "cURL Error ($curl_errno): $curl_error";
        error_log($error_message);
        
        // Provide more specific error messages
        switch ($curl_errno) {
            case CURLE_OPERATION_TIMEDOUT:
                throw new Exception("Email service timed out. Please try again.");
            case CURLE_COULDNT_CONNECT:
                throw new Exception("Could not connect to email service. Please check your internet connection.");
            case CURLE_SSL_CONNECT_ERROR:
                throw new Exception("Secure connection failed. Please try again.");
            default:
                throw new Exception("Network error: " . $curl_error);
        }
    }
    
    // Try to parse the response
    $response_data = json_decode($result, true);
    if ($response_data === null && json_last_error() !== JSON_ERROR_NONE) {
        error_log("Failed to parse response: " . json_last_error_msg());
        error_log("Raw response: " . substr($result, 0, 1000));
        throw new Exception("Invalid response from email service");
    }
    
    // Check HTTP status code
    if ($status_code < 200 || $status_code >= 300) {
        $error_message = isset($response_data['error']) ? $response_data['error'] : "HTTP Error $status_code";
        error_log("Firebase API Error: $error_message");
        throw new Exception("Email service error: " . $error_message);
    }
    
    // Log success
    error_log("Email sent successfully to: $to (Status: $status_code)");
    return [
        'success' => true,
        'message' => 'Email sent successfully',
        'messageId' => $response_data['messageId'] ?? null
    ];
}

/**
 * Send verification email to applicant
 * @param array $user User data
 * @return bool True if email was sent successfully
 */
function send_verification_email($user) {
    if (!is_array($user) || empty($user['email'])) {
        error_log("Invalid user data for verification email");
        return false;
    }
    
    $to = $user['email'];
    $subject = "PSAU Admission System: Application Verified";
    
    // Create HTML message
    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
            <h2>Pampanga State Agricultural University</h2>
        </div>
        <div style='padding: 20px; border: 1px solid #ddd;'>
            <p>Dear " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . ",</p>
            <p>We are pleased to inform you that your application to Pampanga State Agricultural University has been verified and approved.</p>
            <p>Your application has been verified successfully and is now moving to the next stage of the admission process.</p>
            <p>You will receive further instructions about the entrance examination schedule soon.</p>
            <p>Thank you for choosing PSAU!</p>
            <p>Best regards,<br>PSAU Admissions Team</p>
        </div>
        <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
            <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
        </div>
    </div>";
    
    return firebase_send_email($to, $subject, $message);
}

/**
 * Send exam schedule notification email to applicant
 * @param array $user User data
 * @param array $schedule Exam schedule data
 * @return bool True if email was sent successfully
 */
function send_exam_schedule_email($user, $schedule) {
    if (!is_array($user) || empty($user['email']) || !is_array($schedule)) {
        error_log("Invalid data for exam schedule email");
        return false;
    }
    
    $to = $user['email'];
    $subject = "PSAU Admission System: Entrance Exam Schedule";
    
    // Format date and time with defensive checks
    $exam_date = date('l, F j, Y', strtotime($schedule['exam_date'] ?? ''));
    $exam_time_start = '';
    if (!empty($schedule['exam_time'])) {
        $exam_time_start = date('h:i A', strtotime($schedule['exam_time']));
    }
    $exam_time_end = '';
    if (!empty($schedule['exam_time_end'])) {
        $exam_time_end = date('h:i A', strtotime($schedule['exam_time_end']));
    }
    
    // Create HTML message
    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
            <h2>Pampanga State Agricultural University</h2>
        </div>
        <div style='padding: 20px; border: 1px solid #ddd;'>
            <p>Dear " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . ",</p>
            <p>We are pleased to inform you that your entrance examination has been scheduled.</p>
            
            <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #2E7D32; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #2E7D32;'>Exam Details</h3>
                <p><strong>Date:</strong> {$exam_date}</p>
                <p><strong>Time:</strong> {$exam_time_start} - {$exam_time_end}</p>
                <p><strong>Duration:</strong> " . round((strtotime($schedule['exam_time_end']) - strtotime($schedule['exam_time'])) / 60) . " minutes</p>
                <p><strong>Venue:</strong> " . htmlspecialchars($schedule['venue']) . "</p>
            </div>
            
            <h4>Important Instructions:</h4>
            <p>" . nl2br(htmlspecialchars($schedule['instructions'] ?? 'Please arrive 30 minutes before the exam time.')) . "</p>
            
            <h4>Required Items to Bring:</h4>
            <p>" . nl2br(htmlspecialchars($schedule['requirements'] ?? 'Valid ID, Blue/Black pen, Pencil, and Calculator (if needed).')) . "</p>
            
            <div style='margin-top: 30px; background: #e8f5e9; padding: 15px; border-left: 4px solid #2E7D32;'>
                <h4 style='margin-top: 0;'>What's Next?</h4>
                <ul style='margin-bottom:0;'>
                    <li>Prepare all required items and documents for your exam day.</li>
                    <li>Arrive at the venue at least 15 minutes before your scheduled time.</li>
                    <li>After the exam, check your email for your results and next steps.</li>
                    <li>If you have questions, contact the admissions office.</li>
                </ul>
            </div>
            
            <p style='margin-top: 20px;'>Best regards,<br>PSAU Admissions Team</p>
        </div>
        <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
            <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
        </div>
    </div>";
    
    return firebase_send_email($to, $subject, $message);
}

/**
 * Send resubmission email to applicant
 * @param array $user User data
 * @param string $reason Reason for rejection
 * @return bool True if email was sent successfully
 */
function send_resubmission_email($user, $reason = '') {
    if (!is_array($user) || empty($user['email'])) {
        error_log("Invalid user data for resubmission email");
        return false;
    }
    
    $to = $user['email'];
    $subject = "PSAU Admission System: Application Requires Resubmission";
    
    // Set default reason if none provided
    if (empty($reason)) {
        $reason = "Missing or incomplete requirements";
    }
    
    // Create HTML message
    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
            <h2>Pampanga State Agricultural University</h2>
        </div>
        <div style='padding: 20px; border: 1px solid #ddd;'>
            <p>Dear " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . ",</p>
            <p>Thank you for your application to Pampanga State Agricultural University.</p>
            <p>After reviewing your application, we need you to make some corrections before we can proceed.</p>
            <p><strong>Reason for rejection:</strong> " . htmlspecialchars($reason) . "</p>
            <p>Please log in to your account and resubmit your application with the necessary corrections.</p>
            <p>If you have any questions, please contact our admissions office.</p>
            <p>Best regards,<br>PSAU Admissions Team</p>
        </div>
        <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
            <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
        </div>
    </div>";
    
    return firebase_send_email($to, $subject, $message);
}

/**
 * Send score notification email to applicant
 * @param array $user User data (email, first_name, last_name, control_number)
 * @param string $control_number Student's control number
 * @param int $stanine_score Student's stanine score  
 * @return bool True if email was sent successfully
 */
function send_score_notification_email($user, $control_number, $stanine_score) {
    if (!is_array($user) || empty($user['email'])) {
        error_log("Invalid user data for score notification email");
        return false;
    }
    
    $to = $user['email'];
    $subject = "PSAU Admission System: Entrance Exam Score Posted";
    
    // Create course selection URL with control number parameter
    $course_selection_url = 'https://psau-admission-system-16ip.onrender.com/select_course.php?control_number=' . urlencode($control_number);
    
    // Create HTML message
    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
            <h2>Pampanga State Agricultural University</h2>
        </div>
        <div style='padding: 20px; border: 1px solid #ddd;'>
            <p>Dear " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . ",</p>
            <p>Your entrance examination score has been posted.</p>
            
            <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #2E7D32; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #2E7D32;'>Exam Results</h3>
                <p><strong>Control Number:</strong> " . htmlspecialchars($control_number) . "</p>
                <p><strong>Stanine Score:</strong> " . htmlspecialchars($stanine_score) . "</p>
            </div>
            
            <p>Based on your score, you are eligible to proceed with course selection.</p>
            <p>Please click the link below to select your preferred course:</p>
            <p><a href='" . htmlspecialchars($course_selection_url) . "' style='background-color: #2E7D32; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Select Your Course</a></p>
            
            <p>If you have any questions about your score or the course selection process, please contact our admissions office.</p>
            
            <p>Best regards,<br>PSAU Admissions Team</p>
        </div>
        <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
            <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
        </div>
    </div>";
    
    return firebase_send_email($to, $subject, $message);
}

/**
 * Send course assignment email to applicant
 * @param array $user User data
 * @param array $course Course data
 * @param string $reason Reason for assignment (optional)
 * @param array $application Application data with document_file_path, gpa, and strand (optional)
 * @return bool True if email was sent successfully
 */
function send_course_assignment_email($user, $course, $reason = '', $application = []) {
    if (!is_array($user) || empty($user['email']) || !is_array($course)) {
        error_log("Invalid data for course assignment email");
        return false;
    }
    
    $to = $user['email'];
    $subject = "PSAU Admission System: Course Assignment";
    
    // Create document view URL if document_file_path exists
    $document_link = '';
    if (!empty($application['document_file_path'])) {
        $document_link = "
        <div style='margin: 15px 0;'>
            <h4 style='color: #2E7D32;'>Application Documents</h4>
            <p>You can view your submitted documents here:</p>
            <a href='" . htmlspecialchars($application['document_file_path']) . "' 
               style='background-color: #2E7D32; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;'
               target='_blank'>
               View Documents
            </a>
        </div>";
    }

    // Add GPA and strand information if available
    $academic_info = '';
    if (!empty($application['gpa']) || !empty($application['strand'])) {
        $academic_info = "
        <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #2E7D32; margin: 15px 0;'>
            <h4 style='margin-top: 0; color: #2E7D32;'>Academic Information</h4>
            " . (!empty($application['gpa']) ? "<p><strong>GPA:</strong> " . htmlspecialchars($application['gpa']) . "</p>" : "") . "
            " . (!empty($application['strand']) ? "<p><strong>Strand:</strong> " . htmlspecialchars($application['strand']) . "</p>" : "") . "
        </div>";
    }
    
    // Create HTML message
    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
            <h2>Pampanga State Agricultural University</h2>
        </div>
        <div style='padding: 20px; border: 1px solid #ddd;'>
            <p>Dear " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . ",</p>
            <p>We are pleased to inform you about your course assignment at PSAU.</p>
            
            <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #2E7D32; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #2E7D32;'>Course Assignment</h3>
                <p><strong>Course:</strong> " . htmlspecialchars($course['course_name']) . "</p>
                <p><strong>Course Code:</strong> " . htmlspecialchars($course['course_code']) . "</p>
                " . (!empty($reason) ? "<p><strong>Assignment Reason:</strong> " . htmlspecialchars($reason) . "</p>" : "") . "
            </div>
            
            " . $academic_info . "
            " . $document_link . "
            
            <div style='margin-top: 30px; background: #e8f5e9; padding: 15px; border-left: 4px solid #2E7D32;'>
                <h4 style='margin-top: 0;'>What's Next?</h4>
                <ul style='margin-bottom:0;'>
                    <li>Wait for your entrance exam schedule. You will receive another email with your exam details.</li>
                    <li>Check your email regularly for updates.</li>
                    <li>If you have questions, contact the admissions office.</li>
                </ul>
            </div>
            
            <p style='margin-top: 20px;'>Best regards,<br>PSAU Admissions Team</p>
        </div>
        <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
            <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
        </div>
    </div>";
    
    return firebase_send_email($to, $subject, $message);
}

/**
 * Send enrollment schedule email to student
 * @param array $user User data
 * @param array $schedule Schedule data with enrollment_date, enrollment_time, venue, course_code, course_name, instructions, and requirements
 * @return bool True if email was sent successfully
 */
function send_enrollment_schedule_email($user, $schedule) {
    if (!is_array($user) || empty($user['email']) || !is_array($schedule)) {
        error_log("Invalid data for enrollment schedule email");
        return false;
    }
    
    $to = $user['email'];
    $subject = "PSAU Admission System: Enrollment Schedule";
    
    // Format date and time
    $enrollment_date = date('l, F j, Y', strtotime($schedule['enrollment_date']));
    $enrollment_time_start = date('h:i A', strtotime($schedule['enrollment_time']));
    $enrollment_time_end = isset($schedule['end_time']) ? date('h:i A', strtotime($schedule['end_time'])) : '';
    
    // Create HTML message
    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
            <h2>Pampanga State Agricultural University</h2>
        </div>
        
        <div style='padding: 20px; border: 1px solid #ddd; border-top: none;'>
            <h3>Enrollment Schedule Notification</h3>
            
            <p>Dear " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . ",</p>
            
            <p>Your enrollment has been scheduled for:</p>
            
            <div style='background-color: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #2E7D32;'>
                <p style='margin: 5px 0;'><strong>Course:</strong> " . htmlspecialchars($schedule['course_code'] . ' - ' . $schedule['course_name']) . "</p>
                <p style='margin: 5px 0;'><strong>Date:</strong> " . $enrollment_date . "</p>
                <p style='margin: 5px 0;'><strong>Time:</strong> " . $enrollment_time_start . ($enrollment_time_end ? " - $enrollment_time_end" : "") . "</p>
                <p style='margin: 5px 0;'><strong>Venue:</strong> " . htmlspecialchars($schedule['venue']) . "</p>
            </div>
            
            <div style='margin-top: 20px;'>
                <h4>Important Instructions:</h4>
                " . nl2br(htmlspecialchars($schedule['instructions'])) . "
            </div>
            
            <div style='margin-top: 20px;'>
                <h4>Required Documents:</h4>
                " . nl2br(htmlspecialchars($schedule['requirements'])) . "
            </div>
            
            <div style='margin-top: 30px; background: #e8f5e9; padding: 15px; border-left: 4px solid #2E7D32;'>
                <h4 style='margin-top: 0;'>What's Next?</h4>
                <ul style='margin-bottom:0;'>
                    <li>Prepare all required documents and bring them on your enrollment day.</li>
                    <li>Arrive at the venue at least 15 minutes before your scheduled time.</li>
                    <li>Check your email for confirmation and further instructions after enrollment.</li>
                    <li>If you have questions, contact the admissions office.</li>
                </ul>
            </div>
            
            <p style='margin-top: 20px;'>
                Please make sure to bring all the required documents and arrive at least 15 minutes before your scheduled time.
            </p>
            
            <p style='color: #666; font-size: 0.9em; margin-top: 30px;'>
                This is an automated message. Please do not reply to this email.
                If you have any questions, please contact the admissions office.
            </p>
        </div>
        
        <div style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 0.8em; color: #666;'>
            &copy; " . date('Y') . " Pampanga State Agricultural University. All rights reserved.
        </div>
    </div>";
    
    try {
        $result = firebase_send_email($to, $subject, $message);
        if (is_array($result) && !empty($result['success'])) {
            return true;
        } else if ($result === true) {
            return true;
        } else {
            error_log("Failed to send enrollment schedule email to: " . $to . ". Response: " . print_r($result, true));
            return false;
        }
    } catch (Exception $e) {
        error_log("Error sending enrollment schedule email: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email notification for enrollment schedule updates
 * @param array $user User data (first_name, last_name, email, control_number)
 * @param array $schedule_data Schedule update data
 * @return bool True if email was sent successfully
 */
function send_enrollment_schedule_update_email($user, $schedule_data) {
    $to = $user['email'];
    $subject = "PSAU Admission: Enrollment Schedule Updated";
    
    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #FF9800; color: white; padding: 20px; text-align: center;'>
            <h2>Pampanga State Agricultural University</h2>
            <h3>Enrollment Schedule Update</h3>
        </div>
        
        <div style='padding: 20px; border: 1px solid #ddd; border-top: none;'>
            <p>Dear " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . ",</p>
            
            <div style='background-color: #fff3e0; padding: 15px; margin: 15px 0; border-left: 4px solid #FF9800;'>
                <h4 style='margin-top: 0; color: #E65100;'>⚠️ IMPORTANT: Your enrollment schedule has been updated</h4>
                <p style='margin: 5px 0;'><strong>Course:</strong> " . htmlspecialchars($schedule_data['course_code'] . ' - ' . $schedule_data['course_name']) . "</p>
            </div>
            
            <div style='background-color: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #2E7D32;'>
                <h4 style='margin-top: 0;'>Previous Schedule:</h4>
                <p style='margin: 5px 0;'><strong>Date:</strong> " . date('F j, Y', strtotime($schedule_data['old_date'])) . "</p>
                <p style='margin: 5px 0;'><strong>Time:</strong> " . date('h:i A', strtotime($schedule_data['old_time'])) . "</p>
                <p style='margin: 5px 0;'><strong>Venue:</strong> " . htmlspecialchars($schedule_data['old_venue']) . "</p>
            </div>
            
            <div style='background-color: #e8f5e9; padding: 15px; margin: 15px 0; border-left: 4px solid #2E7D32;'>
                <h4 style='margin-top: 0;'>New Schedule:</h4>
                <p style='margin: 5px 0;'><strong>Date:</strong> " . date('F j, Y', strtotime($schedule_data['new_date'])) . "</p>
                <p style='margin: 5px 0;'><strong>Time:</strong> " . date('h:i A', strtotime($schedule_data['new_time'])) . "</p>
                <p style='margin: 5px 0;'><strong>Venue:</strong> " . htmlspecialchars($schedule_data['new_venue']) . "</p>
            </div>
            
            <div style='background-color: #fff3e0; padding: 15px; margin: 15px 0; border-left: 4px solid #FF9800;'>
                <h4 style='margin-top: 0; color: #E65100;'>Reason for Change:</h4>
                <p style='margin: 5px 0;'>" . nl2br(htmlspecialchars($schedule_data['reason'])) . "</p>
            </div>
            
            <div style='margin-top: 20px;'>
                <h4>Updated Instructions:</h4>
                " . nl2br(htmlspecialchars($schedule_data['instructions'])) . "
            </div>
            
            <div style='margin-top: 20px;'>
                <h4>Updated Required Documents:</h4>
                " . nl2br(htmlspecialchars($schedule_data['requirements'])) . "
            </div>
            
            <div style='margin-top: 30px; background: #e8f5e9; padding: 15px; border-left: 4px solid #2E7D32;'>
                <h4 style='margin-top: 0;'>What's Next?</h4>
                <ul style='margin-bottom:0;'>
                    <li>Please note the new date, time, and venue for your enrollment.</li>
                    <li>Prepare all required documents according to the updated requirements.</li>
                    <li>Arrive at the new venue at least 15 minutes before your scheduled time.</li>
                    <li>If you have questions about the schedule change, contact the admissions office.</li>
                </ul>
            </div>
            
            <p style='margin-top: 20px;'>
                We apologize for any inconvenience this change may cause. Please make sure to attend your enrollment on the new scheduled date and time.
            </p>
            
            <p style='color: #666; font-size: 0.9em; margin-top: 30px;'>
                This is an automated message. Please do not reply to this email.
                If you have any questions, please contact the admissions office.
            </p>
        </div>
        
        <div style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 0.8em; color: #666;'>
            &copy; " . date('Y') . " Pampanga State Agricultural University. All rights reserved.
        </div>
    </div>";
    
    try {
        $result = firebase_send_email($to, $subject, $message);
        if (is_array($result) && !empty($result['success'])) {
            return true;
        } else if ($result === true) {
            return true;
        } else {
            error_log("Failed to send enrollment schedule update email to: " . $to . ". Response: " . print_r($result, true));
            return false;
        }
    } catch (Exception $e) {
        error_log("Error sending enrollment schedule update email: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email notification for exam schedule updates
 * @param array $user User data (first_name, last_name, email, control_number)
 * @param array $schedule_data Schedule update data
 * @return bool True if email was sent successfully
 */
function send_exam_schedule_update_email($user, $schedule_data) {
    $to = $user['email'];
    $subject = "PSAU Admission: Entrance Exam Schedule Updated";
    
    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #FF9800; color: white; padding: 20px; text-align: center;'>
            <h2>Pampanga State Agricultural University</h2>
            <h3>Entrance Exam Schedule Update</h3>
        </div>
        
        <div style='padding: 20px; border: 1px solid #ddd; border-top: none;'>
            <p>Dear " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . ",</p>
            
            <div style='background-color: #fff3e0; padding: 15px; margin: 15px 0; border-left: 4px solid #FF9800;'>
                <h4 style='margin-top: 0; color: #E65100;'>⚠️ IMPORTANT: Your entrance exam schedule has been updated</h4>
            </div>
            
            <div style='background-color: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #2E7D32;'>
                <h4 style='margin-top: 0;'>Previous Schedule:</h4>
                <p style='margin: 5px 0;'><strong>Date:</strong> " . date('F j, Y', strtotime($schedule_data['old_date'])) . "</p>
                <p style='margin: 5px 0;'><strong>Time:</strong> " . date('h:i A', strtotime($schedule_data['old_time'])) . "</p>
                <p style='margin: 5px 0;'><strong>Venue:</strong> " . htmlspecialchars($schedule_data['old_venue']) . "</p>
            </div>
            
            <div style='background-color: #e8f5e9; padding: 15px; margin: 15px 0; border-left: 4px solid #2E7D32;'>
                <h4 style='margin-top: 0;'>New Schedule:</h4>
                <p style='margin: 5px 0;'><strong>Date:</strong> " . date('F j, Y', strtotime($schedule_data['new_date'])) . "</p>
                <p style='margin: 5px 0;'><strong>Time:</strong> " . date('h:i A', strtotime($schedule_data['new_time'])) . "</p>
                <p style='margin: 5px 0;'><strong>Venue:</strong> " . htmlspecialchars($schedule_data['new_venue']) . "</p>
            </div>
            
            <div style='background-color: #fff3e0; padding: 15px; margin: 15px 0; border-left: 4px solid #FF9800;'>
                <h4 style='margin-top: 0; color: #E65100;'>Reason for Change:</h4>
                <p style='margin: 5px 0;'>" . nl2br(htmlspecialchars($schedule_data['reason'])) . "</p>
            </div>
            
            <div style='margin-top: 20px;'>
                <h4>Updated Instructions:</h4>
                " . nl2br(htmlspecialchars($schedule_data['instructions'])) . "
            </div>
            
            <div style='margin-top: 20px;'>
                <h4>Updated Required Documents:</h4>
                " . nl2br(htmlspecialchars($schedule_data['requirements'])) . "
            </div>
            
            <div style='margin-top: 30px; background: #e8f5e9; padding: 15px; border-left: 4px solid #2E7D32;'>
                <h4 style='margin-top: 0;'>What's Next?</h4>
                <ul style='margin-bottom:0;'>
                    <li>Please note the new date, time, and venue for your entrance exam.</li>
                    <li>Prepare all required documents according to the updated requirements.</li>
                    <li>Arrive at the new venue at least 30 minutes before your scheduled time.</li>
                    <li>Bring your valid ID and other required documents.</li>
                    <li>If you have questions about the schedule change, contact the admissions office.</li>
                </ul>
            </div>
            
            <p style='margin-top: 20px;'>
                We apologize for any inconvenience this change may cause. Please make sure to attend your entrance exam on the new scheduled date and time.
            </p>
            
            <p style='color: #666; font-size: 0.9em; margin-top: 30px;'>
                This is an automated message. Please do not reply to this email.
                If you have any questions, please contact the admissions office.
            </p>
        </div>
        
        <div style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 0.8em; color: #666;'>
            &copy; " . date('Y') . " Pampanga State Agricultural University. All rights reserved.
        </div>
    </div>";
    
    try {
        $result = firebase_send_email($to, $subject, $message);
        if (is_array($result) && !empty($result['success'])) {
            return true;
        } else if ($result === true) {
            return true;
        } else {
            error_log("Failed to send exam schedule update email to: " . $to . ". Response: " . print_r($result, true));
            return false;
        }
    } catch (Exception $e) {
        error_log("Error sending exam schedule update email: " . $e->getMessage());
        return false;
    }
}

/**
 * Test the Firebase email integration
 * @param string $to Test recipient email address
 * @return bool True if test email was sent successfully
 */
function test_firebase_email($to) {
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid test recipient email");
        return false;
    }
    
    $subject = "PSAU Admission System: Test Email";
    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
            <h2>Pampanga State Agricultural University</h2>
        </div>
        <div style='padding: 20px; border: 1px solid #ddd;'>
            <h3>Test Email</h3>
            <p>This is a test email from the PSAU Admission System.</p>
            <p>If you received this email, the Firebase email integration is working correctly.</p>
            <p>Time sent: " . date('Y-m-d H:i:s') . "</p>
        </div>
        <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
            <p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
        </div>
    </div>";
    
    return firebase_send_email($to, $subject, $message);
}
