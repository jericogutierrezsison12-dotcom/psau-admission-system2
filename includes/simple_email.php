<?php
/**
 * Simple Email Service Fallback
 * Uses PHP's built-in mail() function as a fallback when Firebase email fails
 */

/**
 * Send email using PHP's built-in mail() function
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email body (HTML)
 * @param array $options Additional options
 * @return bool True if email was sent successfully, false otherwise
 */
function send_simple_email($to, $subject, $message, $options = []) {
    // Validate email
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid recipient email: $to");
        return false;
    }
    
    // Set default headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: PSAU Admissions <noreply@psau-admission-system2.onrender.com>',
        'Reply-To: ' . ($options['replyTo'] ?? 'noreply@psau-admission-system2.onrender.com'),
        'X-Mailer: PSAU Admission System'
    ];
    
    // Add CC if specified
    if (!empty($options['cc'])) {
        $headers[] = 'Cc: ' . $options['cc'];
    }
    
    // Convert headers array to string
    $headers_string = implode("\r\n", $headers);
    
    // Log the attempt
    error_log("Attempting to send simple email to: $to, Subject: $subject");
    
    // Send email
    $result = mail($to, $subject, $message, $headers_string);
    
    if ($result) {
        error_log("Simple email sent successfully to: $to");
        return true;
    } else {
        error_log("Failed to send simple email to: $to");
        return false;
    }
}

/**
 * Send email with fallback mechanism
 * Tries Firebase first, then falls back to simple email
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email body (HTML)
 * @param array $options Additional options
 * @return bool True if email was sent successfully, false otherwise
 */
function send_email_with_fallback($to, $subject, $message, $options = []) {
    // First try Firebase email
    try {
        require_once __DIR__ . '/../firebase/firebase_email.php';
        $result = firebase_send_email($to, $subject, $message, $options);
        if ($result) {
            return true;
        }
    } catch (Exception $e) {
        error_log("Firebase email failed: " . $e->getMessage());
    }
    
    // Fallback to simple email
    error_log("Falling back to simple email for: $to");
    return send_simple_email($to, $subject, $message, $options);
}
