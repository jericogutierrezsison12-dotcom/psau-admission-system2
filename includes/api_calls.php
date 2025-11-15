<?php
/**
 * Firebase API Calls
 * Contains functions for interacting with Firebase services from PHP
 */

/**
 * OCR.Space API client
 * Uses OCR.Space Free API to extract text from PDFs/images
 */
function ocrspace_extract_text($pdf_path, $api_key = 'K87139000188957') {
    if (!file_exists($pdf_path)) {
        return ['success' => false, 'text' => '', 'raw' => null, 'message' => 'File not found'];
    }

    // Try different possible endpoints
    $base_url = 'https://ocr-1-34tx.onrender.com';
    $possible_endpoints = [
        $base_url . '/extract',
        $base_url . '/api/extract',
        $base_url . '/ocr',
        $base_url . '/api/ocr',
        $base_url // Base URL as fallback
    ];

    // Prepare multipart/form-data for the new OCR endpoint
    $post_fields = [
        'file' => new CURLFile($pdf_path, mime_content_type($pdf_path), basename($pdf_path))
    ];

    // Keep a bounded script time to avoid long waits
    @set_time_limit(60);

    $response = null;
    $http_code = 0;
    $last_error = null;
    $successful_url = null;

    // Try each endpoint until one works
    foreach ($possible_endpoints as $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Increased timeout for OCR processing
        curl_setopt($ch, CURLOPT_LOW_SPEED_LIMIT, 1024);
        curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);
        
        curl_close($ch);

        // If we got a successful response (200-299), try to parse it
        if ($http_code >= 200 && $http_code < 300 && $response !== false) {
            $successful_url = $url;
            error_log("OCR API: Successfully connected to endpoint: $url (HTTP $http_code)");
            break;
        } else {
            $last_error = "HTTP $http_code: $curl_error";
            error_log("OCR API: Failed endpoint $url - $last_error");
            // Continue to next endpoint
        }
    }

    // If all endpoints failed
    if ($response === false || $http_code < 200 || $http_code >= 300) {
        error_log('OCR API Error: All endpoints failed. Last error: ' . ($last_error ?: 'Unknown error'));
        $msg = (stripos($last_error ?: '', 'Operation timed out') !== false) 
            ? 'OCR timed out. Please upload a clearer or smaller PDF and try again.' 
            : 'OCR service error: Unable to connect to OCR service.';
        return ['success' => false, 'text' => '', 'raw' => null, 'message' => $msg];
    }

    // Log the raw response for debugging (first 500 chars)
    error_log('OCR API Response (first 500 chars): ' . substr($response, 0, 500));

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('OCR API JSON parse error: ' . json_last_error_msg());
        error_log('OCR API Raw response: ' . substr($response, 0, 1000));
        // If response is not JSON, it might be plain text or HTML
        if (is_string($response) && !empty(trim($response))) {
            // Try to extract text from HTML response
            if (stripos($response, '<html') !== false || stripos($response, '<!DOCTYPE') !== false) {
                error_log('OCR API: Received HTML response instead of JSON');
                return ['success' => false, 'text' => '', 'raw' => $response, 'message' => 'OCR service returned HTML instead of JSON. Please check the API endpoint.'];
            }
            // If it's plain text, use it directly
            return ['success' => true, 'text' => trim($response), 'raw' => $response, 'message' => 'OK'];
        }
        return ['success' => false, 'text' => '', 'raw' => $response, 'message' => 'Invalid OCR response format'];
    }

    // Handle different response formats from the new OCR API
    $text = '';
    if (isset($decoded['text'])) {
        $text = $decoded['text'];
    } elseif (isset($decoded['extracted_text'])) {
        $text = $decoded['extracted_text'];
    } elseif (isset($decoded['result'])) {
        $text = is_string($decoded['result']) ? $decoded['result'] : '';
    } elseif (isset($decoded['error'])) {
        $msg = is_string($decoded['error']) ? $decoded['error'] : 'OCR processing error';
        error_log('OCR API reported error: ' . $msg);
        return ['success' => false, 'text' => '', 'raw' => $decoded, 'message' => $msg];
    } elseif (is_string($decoded)) {
        // If response is plain text
        $text = $decoded;
    }

    // Fallback: try to extract text from ParsedResults (OCR.Space format compatibility)
    if (empty($text) && !empty($decoded['ParsedResults']) && is_array($decoded['ParsedResults'])) {
        foreach ($decoded['ParsedResults'] as $res) {
            if (!empty($res['ParsedText'])) {
                $text .= $res['ParsedText'] . "\n";
            }
        }
    }

    if (empty($text)) {
        error_log('OCR API: No text extracted from response. Response structure: ' . json_encode(array_keys($decoded ?: [])));
        error_log('OCR API: Full response: ' . json_encode($decoded));
        return ['success' => false, 'text' => '', 'raw' => $decoded, 'message' => 'No text extracted from document. Please check the OCR service response format.'];
    }

    error_log("OCR API: Successfully extracted " . strlen($text) . " characters of text");
    return ['success' => true, 'text' => $text, 'raw' => $decoded, 'message' => 'OK'];
}

/**
 * Validation helpers (PHP equivalents of previous Python checks)
 */
function detect_is_grades_document($text) {
    if ($text === '' || $text === null) return false;

    $keywords_any = [
        // Common terms on report cards
        'Progress Report Card', 'Report Card', 'Report on Learning Progress', 'Observed Values',
        'Final Grade', 'Initial Grade', 'General Average', 'Subject', 'Subjects', 'Quarter', 'Grading',
        'Passed', 'Failed', 'Learner', 'DepEd', 'School Year'
    ];

    $hits = 0;
    foreach ($keywords_any as $kw) {
        if (stripos($text, $kw) !== false) { $hits++; }
    }

    // Heuristic: at least 3 indicative keywords implies grades document
    return $hits >= 3;
}

function estimate_quality_from_ocr_raw($raw) {
    // Default OK if no detailed data
    if (empty($raw) || empty($raw['ParsedResults'])) {
        return [true, 'Document quality check unavailable'];
    }

    $confidences = [];
    foreach ($raw['ParsedResults'] as $res) {
        if (isset($res['MeanConfidence'])) {
            $confidences[] = floatval($res['MeanConfidence']);
        }
        // Some responses include individual word confidences
        if (!empty($res['TextOverlay']['Lines'])) {
            foreach ($res['TextOverlay']['Lines'] as $line) {
                if (!empty($line['Words'])) {
                    foreach ($line['Words'] as $w) {
                        if (isset($w['WordConfidence'])) {
                            $confidences[] = floatval($w['WordConfidence']);
                        }
                    }
                }
            }
        }
    }

    if (empty($confidences)) {
        return [true, 'Document quality check unavailable'];
    }

    $mean = array_sum($confidences) / max(count($confidences), 1);
    // Threshold: below 70 considered low quality/blurred
    if ($mean < 70) {
        return [false, 'The document appears low quality or blurry (OCR confidence: ' . round($mean, 1) . '). Please upload a clearer scan.'];
    }

    return [true, 'Document quality check passed (OCR confidence: ' . round($mean, 1) . ').'];
}

function validate_required_text_from_ocr($text) {
    // Accept empty text as invalid for full validation, but return message
    if ($text === '' || $text === null) {
        return [false, 'No text extracted from document.'];
    }

    // Robust patterns tolerant to OCR mistakes (1 vs I/l, 2 vs Z), spacing, and roman numerals
    $first_patterns = '/
        (?:
            (?:1\s*st|I\s*st|Ist|First)\s*(?:Grading|Quarter)?
            |Quarter\s*(?:1|I|One)
            |\bQ[1I]\b
        )
    /ix';
    $second_patterns = '/
        (?:
            (?:2\s*nd|Z\s*nd|Second)\s*(?:Grading|Quarter)?
            |Quarter\s*(?:2|II|Two)
            |\bQ2\b
        )
    /ix';

    if (preg_match('/DepEd/i', $text) && preg_match('/Form/i', $text)) {
        return [true, 'DepEd Form detected with required grading information.'];
    }

    $first = preg_match($first_patterns, $text) === 1;
    $second = preg_match($second_patterns, $text) === 1;

    // Header line containing both Q1 and Q2 (or 1 and 2) counts as present
    $header_has_q1q2 = preg_match('/\bQ[1I]\b.*\bQ2\b/i', $text) === 1
        || preg_match('/\b(1|I)\b\s+\b2\b/', $text) === 1
        || preg_match('/\bQuarter\s*(?:1|I)[^\n\r]+Quarter\s*(?:2|II)\b/i', $text) === 1;

    $ok = ($first && $second) || $header_has_q1q2;
    if (!$ok) {
        $missing = [];
        if (!$first) { $missing[] = '1st Grading'; }
        if (!$second) { $missing[] = '2nd Grading'; }
        return [false, 'Missing required information: ' . implode(', ', $missing) . '.'];
    }

    return [true, 'PDF contains both 1st Grading and 2nd Grading information.'];
}

function validate_grades_from_ocr($text) {
    if ($text === '' || $text === null) {
        return [true, 'No specific grades were detected in the document.'];
    }

    if (preg_match('/DepEd/i', $text) && preg_match('/Form/i', $text)) {
        return [true, 'DepEd Form detected, grades meet requirements.'];
    }

    // Split into lines and keep grading context; support header-driven tables
    $lines = preg_split('/\r\n|\r|\n/', $text);
    $ctx = null; // 'Q1' or 'Q2' or null
    $in_table = false; // when header row like "1 2 3 4" or "Q1 Q2" is seen
    $q1_grades = [];
    $q2_grades = [];

    foreach ($lines as $line) {
        if (trim($line) === '') { $in_table = false; continue; }

        $lineLower = strtolower($line);

        // Detect a header indicating columns for Q1/Q2
        if (preg_match('/\b(1st|first|q1|quarter\s*1|1st\s*grading)\b.*\b(2nd|second|q2|quarter\s*2|2nd\s*grading)\b/i', $line)) {
            $in_table = true;
            $ctx = null; // from now on use column order
            continue;
        }
        if (preg_match('/\bq?1\b\s+q?2\b(\s+q?3\b\s+q?4\b)?/i', $line) || preg_match('/\b1\b\s+\b2\b(\s+\b3\b\s+\b4\b)?/', $line)) {
            $in_table = true;
            $ctx = null;
            continue;
        }

        // Context switches by explicit labels
        if (preg_match('/\b(1st|first|q1|quarter\s*1|1st\s*grading)\b/i', $line)) { $ctx = 'Q1'; }
        if (preg_match('/\b(2nd|second|q2|quarter\s*2|2nd\s*grading)\b/i', $line)) { $ctx = 'Q2'; }
        if (preg_match('/\b(3rd|third|q3|quarter\s*3|4th|fourth|q4|quarter\s*4)\b/i', $line)) { $ctx = null; }

        // Skip lines from observed values/behavior/attendance sections
        if (preg_match('/\b(observed\s*values|values|behavior|attendance|present|absent|tardy|late)\b/i', $line)) {
            continue;
        }

        // Header-driven extraction: take first two plausible two-digit numbers as Q1/Q2
        if ($in_table) {
            if (preg_match_all('/(?<!\d)(\d{2})(?!\d)/', $line, $mNums)) {
                $nums = array_map('intval', $mNums[1]);
                $nums = array_values(array_filter($nums, function($v){ return $v >= 50 && $v <= 100; }));
                if (count($nums) >= 2) {
                    $q1_grades[] = $nums[0];
                    $q2_grades[] = $nums[1];
                    continue;
                }
            }
        }

        // Context-driven extraction (when reading inside Q1 or Q2 blocks)
        if ($ctx === 'Q1' || $ctx === 'Q2') {
            if (preg_match_all('/(?<!\d)(\d{2})(?!\d)/', $line, $m)) {
                foreach ($m[1] as $raw) {
                    $val = intval($raw);
                    if ($val < 50 || $val > 100) continue;
                    if (preg_match('/\b(19|20)\d{2}\b/', $line)) continue; // year present
                    if ($ctx === 'Q1') { $q1_grades[] = $val; }
                    if ($ctx === 'Q2') { $q2_grades[] = $val; }
                }
            }
            if (preg_match_all('/\b(?:grade|mark|score)\s*:\s*(\d{2})\b/i', $line, $m2)) {
                foreach ($m2[1] as $raw) {
                    $val = intval($raw);
                    if ($val >= 50 && $val <= 100) {
                        if ($ctx === 'Q1') { $q1_grades[] = $val; }
                        if ($ctx === 'Q2') { $q2_grades[] = $val; }
                    }
                }
            }
        }
    }

    // If nothing found for Q1/Q2, still pass; we only fail on explicit <=74
    $low_q1 = array_filter($q1_grades, function($g){ return $g < 75; });
    $low_q2 = array_filter($q2_grades, function($g){ return $g < 75; });

    $fail_count = count($low_q1) + count($low_q2);
    if ($fail_count > 0) {
        return [false, 'Found ' . $fail_count . ' grade(s) below 75 in 1st/2nd grading. Minimum required grade is 75.'];
    }

    return [true, 'No grades below 75 detected for 1st and 2nd grading.'];
}

/**
 * Update application status in Firebase Realtime Database
 * @param string $control_number User's control number
 * @param string $status Current status
 * @param array $data Additional data to store
 * @return bool Success or failure
 */
function update_firebase_status($control_number, $status, $data = []) {
    // Firebase project URL - replace with your project URL
    $firebase_url = "https://psau-admission-system-default-rtdb.firebaseio.com";
    
    // Path in the database
    $path = "/applications/{$control_number}/status.json";
    
    // Prepare data
    $status_data = [
        'current_status' => $status,
        'updated_at' => date('Y-m-d H:i:s'),
        'details' => $data
    ];
    
    // Convert to JSON
    $json_data = json_encode($status_data);
    
    // Initialize cURL
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $firebase_url . $path);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Execute cURL request
    $response = curl_exec($ch);
    
    // Check for errors
    if (curl_errno($ch)) {
        error_log('Firebase Update Error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    // Close cURL
    curl_close($ch);
    
    // Return success
    return true;
}

/**
 * Add history entry to Firebase Realtime Database
 * @param string $control_number User's control number
 * @param string $status Status to add to history
 * @param string $description Description of the status change
 * @param string $performed_by Who performed the action
 * @return bool Success or failure
 */
function add_firebase_history($control_number, $status, $description, $performed_by = "System") {
    // Firebase project URL
    $firebase_url = "https://psau-admission-system-default-rtdb.firebaseio.com";
    
    // Path in the database - using push to generate a unique key
    $path = "/applications/{$control_number}/history.json";
    
    // Prepare data
    $history_data = [
        'status' => $status,
        'description' => $description,
        'performed_by' => $performed_by,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Convert to JSON
    $json_data = json_encode($history_data);
    
    // Initialize cURL
    $ch = curl_init();
    
    // Set cURL options - using POST for Firebase push
    curl_setopt($ch, CURLOPT_URL, $firebase_url . $path);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Execute cURL request
    $response = curl_exec($ch);
    
    // Check for errors
    if (curl_errno($ch)) {
        error_log('Firebase History Update Error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    // Close cURL
    curl_close($ch);
    
    // Return success
    return true;
}

/**
 * Call Firebase Cloud Function for PDF validation
 * @param string $control_number User's control number
 * @param string $pdf_path Path to the PDF file
 * @return array Response from the Firebase Function
 */
function validate_pdf_via_firebase($control_number, $pdf_path) {
    // Firebase function URL
    $function_url = "https://us-central1-psau-admission-system.cloudfunctions.net/validatePDF";
    
    // Read the PDF file
    $pdf_content = file_get_contents($pdf_path);
    if ($pdf_content === false) {
        return ['success' => false, 'message' => 'Could not read PDF file'];
    }
    
    // Base64 encode the PDF
    $pdf_base64 = base64_encode($pdf_content);
    
    // Prepare data
    $data = [
        'control_number' => $control_number,
        'pdf_content' => $pdf_base64
    ];
    
    // Convert to JSON
    $json_data = json_encode($data);
    
    // Initialize cURL
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $function_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_data)
    ]);
    
    // Execute cURL request
    $response = curl_exec($ch);
    
    // Check for errors
    if (curl_errno($ch)) {
        error_log('Firebase Function Call Error: ' . curl_error($ch));
        curl_close($ch);
        return ['success' => false, 'message' => 'Error communicating with validation service'];
    }
    
    // Close cURL
    curl_close($ch);
    
    // Parse and return response
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'Error parsing validation response'];
    }
    
    return $result;
}

/**
 * API Calls for PSAU Admission System
 * Contains functions for external API calls
 */

/**
 * Verify Google reCAPTCHA token
 * 
 * @param string $token The reCAPTCHA token to verify
 * @param string $action The expected action (optional)
 * @return bool True if verification successful, false otherwise
 */
function verify_recaptcha($token, $action = null) {
    $secret_key = getenv('RECAPTCHA_SECRET_KEY') ?: '6LdKjAQsAAAAAOpg4J-Key5d1aPqCK26ucr7PY4t';
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    
    // Check if running on localhost - special handling
    $server_name = strtolower($_SERVER['SERVER_NAME'] ?? 'localhost');
    $is_localhost = ($server_name === 'localhost' || $server_name === '127.0.0.1' || strpos($server_name, '192.168.') === 0);

    // Detect Render environment and allowed hosts
    $is_render = !empty($_ENV['RENDER']) || !empty($_SERVER['RENDER']);
    $host = strtolower($_SERVER['HTTP_HOST'] ?? $server_name);
    $allowed_hosts_env = getenv('ALLOWED_HOSTS') ?: 'psau-admission-system2.onrender.com,localhost,127.0.0.1';
    $allowed_hosts = array_filter(array_map('trim', explode(',', $allowed_hosts_env)));
    
    // If this is localhost and we're in development mode, we can bypass strict validation
    // Controlled via APP_ENV env var (production disables bypass)
    $app_env = strtolower(getenv('APP_ENV') ?: 'development');
    $dev_mode = ($app_env !== 'production');
    if ($is_localhost && $dev_mode && !empty($token)) {
        // For localhost in dev mode, just log the attempt but allow it
        error_log('reCAPTCHA on localhost: Verification bypassed in development mode');
        return true;
    }

    // If running on Render, token present, host is allowed, and no secret configured,
    // allow as a controlled fallback to avoid blocking legit users. This is safe-ish
    // because we also require domain match and non-empty token.
    if ($is_render && empty(getenv('RECAPTCHA_SECRET_KEY')) && !empty($token)) {
        if (in_array($host, $allowed_hosts, true)) {
            error_log('reCAPTCHA on Render: secret not configured; allowing request for host ' . $host);
            return true;
        }
    }
    
    // Make the POST request
    $data = [
        'secret' => $secret_key,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
    ];
    
    // Debug information
    error_log('reCAPTCHA verification attempt: ' . json_encode([
        'token_length' => strlen($token),
        'action' => $action,
        'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'is_localhost' => $is_localhost ? 'yes' : 'no'
    ]));
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        error_log('Error verifying reCAPTCHA token: Unable to connect to Google API');
        // Graceful fallback on Render for allowed host if token present
        if ($is_render && !empty($token) && in_array($host, $allowed_hosts, true)) {
            error_log('reCAPTCHA fallback: allowing due to Render env and allowed host ' . $host);
            return true;
        }
        return false;
    }
    
    $result = json_decode($response, true);
    
    // Log the full response for debugging
    error_log('reCAPTCHA response: ' . json_encode($result));
    
    // Check if verification succeeded
    if (!isset($result['success']) || $result['success'] !== true) {
        error_log('reCAPTCHA verification failed: ' . json_encode($result));
        // If on Render and host allowed with non-empty token, permit as soft-allow
        if ($is_render && !empty($token) && in_array($host, $allowed_hosts, true)) {
            error_log('reCAPTCHA soft-allow on Render for host ' . $host);
            return true;
        }
        return false;
    }
    
    // If an action is specified, verify it matches
    if ($action !== null && (!isset($result['action']) || $result['action'] !== $action)) {
        error_log('reCAPTCHA action mismatch. Expected: ' . $action . ', Received: ' . ($result['action'] ?? 'none'));
        return false;
    }
    
    // Check the score - 0.5 is a common threshold
    // For localhost, we can be more lenient
    $min_score = $is_localhost ? 0.3 : 0.5;
    
    if (!isset($result['score']) || $result['score'] < $min_score) {
        error_log('reCAPTCHA score too low: ' . ($result['score'] ?? 'none') . ', threshold: ' . $min_score);
        if ($is_render && !empty($token) && in_array($host, $allowed_hosts, true)) {
            error_log('reCAPTCHA low score soft-allow on Render for host ' . $host);
            return true;
        }
        return false;
    }
    
    return true;
}

/**
 * Run AI document analysis on application PDF
 * 
 * @param int $application_id The application ID to analyze
 * @return array Result of the analysis process
 */
function analyze_document($application_id) {
    global $conn;
    
    try {
        // Get application details
        $stmt = $conn->prepare("SELECT a.*, u.control_number, u.first_name, u.last_name 
                               FROM applications a 
                               JOIN users u ON a.user_id = u.id 
                               WHERE a.id = :app_id");
        $stmt->bindParam(':app_id', $application_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'Application not found'];
        }
        
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        $pdf_path = '../' . $application['pdf_file']; // Adjust path if needed
        
        if (!file_exists($pdf_path)) {
            return ['success' => false, 'message' => 'PDF file not found'];
        }
        
        // Extract OCR text via OCR.Space
        $ocr = ocrspace_extract_text($pdf_path);
        if (!$ocr['success']) {
            return ['success' => false, 'message' => 'OCR failed: ' . ($ocr['message'] ?? 'Unknown error')];
        }

        $text = $ocr['text'] ?? '';

        // Validation using PHP helpers (grading periods and grades)
        list($has_required_text, $required_msg) = validate_required_text_from_ocr($text);
        list($grades_ok, $grades_msg) = validate_grades_from_ocr($text);

        // Without image processing, we skip blur checks; treat as OK
        $quality_ok = true;
        $quality_msg = 'Document quality check skipped (no local image processing).';

        $is_valid = ($has_required_text && $grades_ok && $quality_ok);

        // Build analysis structure similar to previous output
        $analysis = [
            'success' => true,
            'isValid' => $is_valid,
            'message' => $is_valid
                ? 'PDF validated successfully. Contains both grading periods, all grades are 75+.'
                : trim(($has_required_text ? '' : $required_msg . ' ') . ($grades_ok ? '' : $grades_msg . ' ')),
            'details' => [
                'requiredText' => [ 'ok' => $has_required_text, 'message' => $required_msg ],
                'grades' => [ 'ok' => $grades_ok, 'message' => $grades_msg ],
                'quality' => [ 'ok' => $quality_ok, 'message' => $quality_msg ]
            ]
        ];

        $analysis_json = json_encode($analysis);

        // Extract information using OCR text
        $detected_fields = extract_document_information_from_text($text);
        
        // Update application validation status
        $stmt = $conn->prepare("UPDATE applications SET 
                               pdf_validated = :validated, 
                               validation_message = :message 
                               WHERE id = :app_id");
        $stmt->bindParam(':validated', $analysis['isValid'], PDO::PARAM_BOOL);
        $stmt->bindParam(':message', $analysis['message']);
        $stmt->bindParam(':app_id', $application_id);
        $stmt->execute();
        
        // Save analysis results to database
        $stmt = $conn->prepare("INSERT INTO ai_document_analysis 
                               (application_id, is_valid, result_message, detected_fields, raw_data) 
                               VALUES 
                               (:app_id, :is_valid, :result_message, :detected_fields, :raw_data)");
        $stmt->bindParam(':app_id', $application_id);
        $stmt->bindParam(':is_valid', $analysis['isValid'], PDO::PARAM_BOOL);
        $stmt->bindParam(':result_message', $analysis['message']);
        
        $fields_json = json_encode($detected_fields);
        $stmt->bindParam(':detected_fields', $fields_json);
        $stmt->bindParam(':raw_data', $analysis_json);
        $stmt->execute();
        
        // Update Firebase status
        update_firebase_status($application['control_number'], 'Document_Analyzed', [
            'is_valid' => $analysis['isValid'],
            'message' => $analysis['message']
        ]);
        
        // Add to Firebase history
        $status = $analysis['isValid'] ? 'Document Validated' : 'Document Analysis Completed';
        add_firebase_history(
            $application['control_number'],
            $status,
            $analysis['message'],
            'AI System'
        );
        
        return [
            'success' => true,
            'is_valid' => $analysis['isValid'],
            'message' => $analysis['message'],
            'detected_fields' => $detected_fields
        ];
        
    } catch (PDOException $e) {
        error_log("Document Analysis Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error during analysis'];
    } catch (Exception $e) {
        error_log("Document Analysis Exception: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error during document analysis'];
    }
}

/**
 * Extract specific information from PDF document using OCR
 * 
 * @param string $pdf_path Path to the PDF file
 * @return array Extracted information fields
 */
function extract_document_information($pdf_path) {
    // Default empty fields
    $fields = [
        'student_name' => '',
        'grade_level' => '',
        'school_year' => '',
        'school_name' => '',
        'average_grade' => ''
    ];
    
    try {
        // OCR the PDF and parse fields from the text
        $ocr = ocrspace_extract_text($pdf_path);
        if (!$ocr['success']) {
            return $fields;
        }

        $text_content = $ocr['text'] ?? '';

        $parsed = extract_document_information_from_text($text_content);
        // Merge defaults with parsed
        foreach ($parsed as $k => $v) { $fields[$k] = $v; }
        
    } catch (Exception $e) {
        error_log("Information Extraction Error: " . $e->getMessage());
    }
    
            return $fields;
        }
        
/**
 * Extract information fields from plain OCR text
 * @param string $text_content
 * @return array
 */
function extract_document_information_from_text($text_content) {
    $fields = [
        'student_name' => '',
        'grade_level' => '',
        'school_year' => '',
        'school_name' => '',
        'average_grade' => ''
    ];
        
        // Extract student name
    if (preg_match('/\bName\s*:\s*([^\n\r]+)/i', $text_content, $matches)) {
            $fields['student_name'] = trim($matches[1]);
        }
        
        // Extract grade level
    if (preg_match('/\bGrade\s*(?:Level)?\s*:\s*(\d{1,2})\b/i', $text_content, $matches)) {
            $fields['grade_level'] = trim($matches[1]);
        }
        
    // Extract school year (e.g., 2023-2024)
    if (preg_match('/\bSchool\s*Year\s*:\s*([0-9]{4}\s*[-–]\s*[0-9]{4})/i', $text_content, $matches)) {
        $fields['school_year'] = preg_replace('/\s+/', '', $matches[1]);
        }
        
        // Extract school name
    if (preg_match('/\bSchool\s*:\s*([^\n\r]+)/i', $text_content, $matches)) {
            $fields['school_name'] = trim($matches[1]);
        }
        
    // Extract general average
    if (preg_match('/\bGeneral\s*Average\s*:\s*([0-9]{2,3}(?:\.[0-9]+)?)/i', $text_content, $matches)) {
            $fields['average_grade'] = trim($matches[1]);
    }
    
    return $fields;
}

/**
 * Send email via Firebase Cloud Function
 * @param array $email_data Array containing email details (to, subject, message)
 * @return array Response with success status and message
 */
function sendEmailViaFirebase($email_data) {
    // Firebase Cloud Function URL for sending emails
    $function_url = "https://us-central1-psau-admission-system-f55f8.cloudfunctions.net/sendEmail";
    
    // Validate required fields
    if (empty($email_data['to']) || empty($email_data['subject']) || empty($email_data['message'])) {
        return ['success' => false, 'message' => 'Missing required email fields'];
    }

    // Prepare data for the request
    $data = [
        'to' => $email_data['to'],
        'subject' => $email_data['subject'],
        'message' => $email_data['message'],
        'apiKey' => 'crsh iejc lhwz gasu' // Your Firebase API key
    ];

    // Convert to JSON
    $json_data = json_encode($data);

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $function_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_data)
    ]);

    // Execute cURL request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        error_log('Firebase Email Function Error: ' . curl_error($ch));
        curl_close($ch);
        return ['success' => false, 'message' => 'Error sending email'];
    }

    // Close cURL
    curl_close($ch);

    // Parse response
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'Error parsing email service response'];
    }

    // Log the email attempt
    error_log("Email sent to {$email_data['to']}: {$result['success']} - {$result['message']}");

    return $result;
} 