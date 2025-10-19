<?php
/**
 * Python API Integration
 * Handles communication with the Python Flask service running on Replit
 */

// Configuration for Python API service
function get_python_api_url() {
    // Check if we're in production (InfinityFree)
    if (strpos($_SERVER['HTTP_HOST'] ?? '', 'infinityfree') !== false) {
        return 'https://your-replit-app.replit.dev'; // Replace with your actual Replit URL
    }
    
    // Development/local environment
    return 'http://localhost:5000';
}

/**
 * Call Python OCR service
 * @param string $file_path Path to the uploaded file
 * @return array Response from Python service
 */
function call_python_ocr_service($file_path) {
    $python_url = get_python_api_url() . '/ocr_service';
    
    // Prepare the file for upload
    $post_data = [
        'file' => new CURLFile($file_path, mime_content_type($file_path), basename($file_path))
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $python_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log('Python OCR Service Error: ' . curl_error($ch));
        curl_close($ch);
        return ['success' => false, 'error' => 'Failed to connect to OCR service'];
    }
    
    curl_close($ch);
    
    if ($http_code !== 200) {
        error_log('Python OCR Service HTTP Error: ' . $http_code);
        return ['success' => false, 'error' => 'OCR service returned error code: ' . $http_code];
    }
    
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Python OCR Service JSON Error: ' . json_last_error_msg());
        return ['success' => false, 'error' => 'Invalid response from OCR service'];
    }
    
    return $result;
}

/**
 * Call Python AI chatbot service
 * @param string $question User's question
 * @return array Response from Python service
 */
function call_python_chatbot($question) {
    $python_url = get_python_api_url() . '/ask_question';
    
    $data = json_encode(['question' => $question]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $python_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log('Python Chatbot Service Error: ' . curl_error($ch));
        curl_close($ch);
        return ['answer' => 'Sorry, the AI chatbot is not available at the moment.', 'confidence' => 0.0];
    }
    
    curl_close($ch);
    
    if ($http_code !== 200) {
        error_log('Python Chatbot Service HTTP Error: ' . $http_code);
        return ['answer' => 'Sorry, the AI chatbot is not available at the moment.', 'confidence' => 0.0];
    }
    
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Python Chatbot Service JSON Error: ' . json_last_error_msg());
        return ['answer' => 'Sorry, the AI chatbot is not available at the moment.', 'confidence' => 0.0];
    }
    
    return $result;
}

/**
 * Call Python course recommendation service
 * @param array $data Recommendation parameters
 * @return array Response from Python service
 */
function call_python_recommendation($data) {
    $python_url = get_python_api_url() . '/api/recommend';
    
    $json_data = json_encode($data);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $python_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_data)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log('Python Recommendation Service Error: ' . curl_error($ch));
        curl_close($ch);
        return ['error' => 'Recommendation service is not available'];
    }
    
    curl_close($ch);
    
    if ($http_code !== 200) {
        error_log('Python Recommendation Service HTTP Error: ' . $http_code);
        return ['error' => 'Recommendation service returned error code: ' . $http_code];
    }
    
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Python Recommendation Service JSON Error: ' . json_last_error_msg());
        return ['error' => 'Invalid response from recommendation service'];
    }
    
    return $result;
}

/**
 * Check if Python service is available
 * @return bool True if service is available
 */
function check_python_service_health() {
    $python_url = get_python_api_url() . '/health';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $python_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    return $http_code === 200;
}
?>
