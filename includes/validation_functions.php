<?php
/**
 * PSAU Admission System - Validation Functions
 * Utility functions for validating applications and submission attempts
 */

/**
 * Check if a user has reached the maximum number of submission attempts
 * 
 * @param PDO $conn Database connection
 * @param int $user_id The user ID to check
 * @param int $max_attempts Maximum number of attempts allowed (default 5)
 * @return array Array containing 'can_submit' (boolean) and 'message' (string)
 */
function check_submission_attempts($conn, $user_id, $max_attempts = 5) {
    try {
        // Get existing application
        $stmt = $conn->prepare("SELECT * FROM applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $existing_application = $stmt->fetch();
        
        $hasExistingApplication = ($existing_application !== false);
        
        // Check if a new application can be submitted
        // Allow submission only if application was rejected or there isn't one
        if ($hasExistingApplication && 
            $existing_application['status'] !== 'Rejected' && 
            $existing_application['status'] !== 'Submitted') {
            return [
                'can_submit' => false, 
                'message' => 'You already have an active application in progress. You cannot submit a new one at this time.',
                'message_type' => 'warning'
            ];
        }
        
        // Get submission attempts count
        $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM application_attempts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        $submissionAttempts = $result['attempts'] ?? 0;
        
        // Check if user has reached the maximum attempts
        if ($submissionAttempts >= $max_attempts && 
            (!$hasExistingApplication || $existing_application['status'] === 'Submitted')) {
            return [
                'can_submit' => false,
                'message' => 'You have reached the maximum number of submission attempts (' . $max_attempts . '). Please contact the admissions office for assistance.',
                'message_type' => 'danger',
                'attempts' => $submissionAttempts
            ];
        }
        
        return [
            'can_submit' => true,
            'attempts' => $submissionAttempts,
            'remaining_attempts' => $max_attempts - $submissionAttempts
        ];
        
    } catch (PDOException $e) {
        error_log("Validation error: " . $e->getMessage());
        return [
            'can_submit' => false,
            'message' => 'An error occurred while checking submission eligibility. Please try again later.',
            'message_type' => 'danger'
        ];
    }
}

/**
 * Log a submission attempt in the database
 * 
 * @param PDO $conn Database connection
 * @param int $user_id The user ID making the attempt
 * @param bool $was_successful Whether the submission was successful
 * @param string $pdf_message Message from PDF validation
 * @return bool Success status of logging the attempt
 */
function log_submission_attempt($conn, $user_id, $was_successful, $pdf_message = null) {
    try {
        $stmt = $conn->prepare("INSERT INTO application_attempts (user_id, attempt_date, was_successful, pdf_message) VALUES (?, NOW(), ?, ?)");
        $stmt->execute([$user_id, $was_successful ? 1 : 0, $pdf_message]);
        return true;
    } catch (PDOException $e) {
        error_log("Error logging submission attempt: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute the Python PDF validation script
 * 
 * @param string $pdf_path Path to the PDF file
 * @return array Validation result with 'success', 'isValid', and 'message' keys
 * 
 * NOTE: OCR validation has been removed. This function now always returns success
 * to allow immediate submission without OCR checking.
 */
function validate_pdf($pdf_path) {
    // OCR validation removed - always accept PDF
    return [
        'success' => true,
        'isValid' => true,
        'message' => 'PDF accepted without OCR validation'
    ];
}

/**
 * Detect if OCR text represents a report card
 * 
 * @param string $text The OCR extracted text
 * @return array [boolean, string] - [is_report_card, message]
 */
function detect_report_card_from_text($text) {
    if (empty($text)) {
        return [false, 'No text found in document'];
    }
    
    $text = strtolower($text);
    
    // Keywords that indicate a report card
    $report_card_keywords = [
        'report card',
        'grade',
        'semester',
        'quarter',
        'final grade',
        'gpa',
        'grade point average',
        'subject',
        'units',
        'credits',
        'academic year',
        'school year',
        'student id',
        'student number',
        'enrollment',
        'transcript',
        'academic record',
        'marks',
        'scores',
        'percentage',
        'pass',
        'fail',
        'incomplete',
        'withdrawn'
    ];
    
    // Count matching keywords
    $matches = 0;
    foreach ($report_card_keywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            $matches++;
        }
    }
    
    // Check for grade patterns (e.g., "A+", "B-", "85%", "3.5")
    $grade_patterns = [
        '/\b[a-f][+-]?\b/i',  // Letter grades like A+, B-, C
        '/\b\d{1,3}%\b/',     // Percentage grades like 85%, 92%
        '/\b\d\.\d\b/',       // GPA like 3.5, 2.8
        '/\b\d{1,2}\/\d{1,2}\b/', // Fraction grades like 85/100
    ];
    
    $pattern_matches = 0;
    foreach ($grade_patterns as $pattern) {
        if (preg_match($pattern, $text)) {
            $pattern_matches++;
        }
    }
    
    // Determine if it's likely a report card
    $is_report_card = ($matches >= 3) || ($matches >= 2 && $pattern_matches >= 2);
    
    if ($is_report_card) {
        $message = 'Document appears to be a valid report card';
    } else {
        $message = 'Document does not appear to be a report card. Please upload a clear image of your report card or transcript.';
    }
    
    return [$is_report_card, $message];
}