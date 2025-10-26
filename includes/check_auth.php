<?php
/**
 * PSAU Admission System - Authentication Check API
 * Returns JSON response with user authentication status
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db_connect.php';
require_once 'encryption.php';

// Prepare response
$response = [
    'loggedIn' => false,
    'user' => null
];

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $response['loggedIn'] = true;
    
    // Get user data if needed
    try {
        $stmt = $conn->prepare("SELECT id, first_name_encrypted, last_name_encrypted, email_encrypted FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Decrypt sensitive data
            $response['user'] = [
                'id' => $user['id'],
                'first_name' => decryptPersonalData($user['first_name_encrypted']),
                'last_name' => decryptPersonalData($user['last_name_encrypted']),
                'email' => decryptContactData($user['email_encrypted'])
            ];
        }
    } catch (PDOException $e) {
        // Log error but don't expose details to client
        error_log("Error fetching user data: " . $e->getMessage());
    }
}

// Set content type to JSON
header('Content-Type: application/json');

// Return response
echo json_encode($response);
exit; 