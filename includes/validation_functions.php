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
 */
function validate_pdf($pdf_path) {
    // Use OCR helpers defined in api_calls.php
    if (!function_exists('ocrspace_extract_text')) {
        require_once __DIR__ . '/api_calls.php';
    }

    try {
        $ocr = ocrspace_extract_text($pdf_path);
        if (!$ocr['success']) {
            return [
                'success' => false,
                'isValid' => false,
                'message' => 'OCR failed: ' . ($ocr['message'] ?? 'Unknown error')
            ];
        }

        $text = $ocr['text'] ?? '';

        // Detect report card using OCR text
        $det = detect_report_card_from_text($text);
        $is_report_card = $det[0];
        $report_msg = $det[1];

        // Quality estimation via OCR confidence (blur only)
        $ql = estimate_quality_from_ocr_raw($ocr['raw']);
        $quality_ok = $ql[0];
        $quality_msg = $ql[1];

        // Validation rule: must be a report card and not blurred
        $is_valid = ($quality_ok && $is_report_card);

        $message = $is_valid ? 'PDF validated successfully as a report card and document quality is good.'
                             : ($is_report_card ? $quality_msg : $report_msg);

        return [
            'success' => true,
            'isValid' => $is_valid,
            'message' => $message
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'isValid' => false,
            'message' => 'Exception during PDF validation: ' . $e->getMessage()
        ];
    }
}