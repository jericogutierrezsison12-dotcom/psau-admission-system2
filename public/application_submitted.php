<?php
/**
 * PSAU Admission System - Application Submitted Confirmation
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/encryption.php';

// Check if user is logged in
is_user_logged_in();

// Get user details
$user = get_current_user_data($conn);
if (!$user || !isset($user['id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $user['id'];

// Check if application ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$application_id = intval($_GET['id']);

// Verify that the application belongs to the current user
try {
    $stmt = $conn->prepare("SELECT * FROM applications WHERE id = ? AND user_id = ?");
    $stmt->execute([$application_id, $user_id]);
    
    if ($stmt->rowCount() === 0) {
        // Application not found or doesn't belong to the user
        header('Location: dashboard.php');
        exit;
    }
    
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Decrypt application data if needed (only if looks encrypted)
    if ($application) {
        try {
            require_once '../includes/functions.php'; // For looks_encrypted
            
            if (!empty($application['previous_school'])) {
                if (looks_encrypted($application['previous_school'])) {
                    try {
                        $application['previous_school'] = decryptAcademicData($application['previous_school']);
                    } catch (Exception $e) {
                        // Use as-is if decryption fails
                    }
                }
            }
            if (!empty($application['strand'])) {
                if (looks_encrypted($application['strand'])) {
                    try {
                        $application['strand'] = decryptAcademicData($application['strand']);
                    } catch (Exception $e) {
                        // Use as-is if decryption fails
                    }
                }
            }
            if (!empty($application['gpa'])) {
                if (looks_encrypted($application['gpa'])) {
                    try {
                        $application['gpa'] = decryptAcademicData($application['gpa']);
                    } catch (Exception $e) {
                        // Use as-is if decryption fails
                    }
                }
            }
            if (!empty($application['address'])) {
                if (looks_encrypted($application['address'])) {
                    try {
                        $application['address'] = decryptAcademicData($application['address']);
                    } catch (Exception $e) {
                        // Use as-is if decryption fails
                    }
                }
            }
            if (!empty($application['school_year'])) {
                if (looks_encrypted($application['school_year'])) {
                    try {
                        $application['school_year'] = decryptAcademicData($application['school_year']);
                    } catch (Exception $e) {
                        // Use as-is if decryption fails
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Warning: Could not decrypt application data in application_submitted: " . $e->getMessage());
        }
    }
} catch (PDOException $e) {
    // Handle database error
    error_log('Error retrieving application: ' . $e->getMessage());
    header('Location: dashboard.php');
    exit;
}

// Include HTML template
include_once __DIR__ . '/html/application_submitted.html';

// Debug: Log application data
error_log('Application data: ' . print_r($application, true));

// Pass data to JavaScript
echo '<script>
    const userData = ' . json_encode([
        'first_name' => $user['first_name'] ?? '',
        'last_name' => $user['last_name'] ?? '',
        'email' => $user['email'] ?? ''
    ]) . ';
    const applicationData = {
        application: ' . json_encode($application) . '
    };
    console.log("PHP Debug - Application data:", applicationData);
</script>';
?> 