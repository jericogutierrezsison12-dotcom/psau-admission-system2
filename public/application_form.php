<?php
/**
 * PSAU Admission System - Application Form
 * Allows applicants to submit their application with PDF upload
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/session_checker.php';
require_once '../includes/api_calls.php';
require_once '../includes/validation_functions.php';

// Function to verify document path was saved correctly
function verify_document_path($conn, $application_id) {
    try {
        $stmt = $conn->prepare("SELECT id, document_file_path, pdf_file FROM applications WHERE id = ?");
        $stmt->execute([$application_id]);
        if ($stmt->rowCount() > 0) {
            $app = $stmt->fetch();
            $path = $app['document_file_path'];
            $pdf_file = $app['pdf_file'];
            
            // Check if document_file_path is empty but pdf_file exists
            if (empty($path) && !empty($pdf_file)) {
                // Generate a correct path from the pdf_file field
                $correct_path = 'uploads/' . $pdf_file;
                
                // Update the document path
                $update = $conn->prepare("UPDATE applications SET document_file_path = ? WHERE id = ?");
                $update->execute([$correct_path, $app['id']]);
                
                error_log("Created missing document path as '$correct_path' for application ID: " . $app['id']);
            }
            // Check if path doesn't start with 'uploads/'
            else if (!empty($path) && strpos($path, 'uploads/') !== 0) {
                // Fix the path by adding 'uploads/' prefix
                $correct_path = 'uploads/' . basename($path);
                
                // Update the document path
                $update = $conn->prepare("UPDATE applications SET document_file_path = ? WHERE id = ?");
                $update->execute([$correct_path, $app['id']]);
                
                error_log("Fixed document path from '$path' to '$correct_path' for application ID: " . $app['id']);
            }
            
            // Verify the file actually exists
            if (!empty($path) && file_exists('../' . $path)) {
                error_log("Document file exists at path: ../$path for application ID: " . $app['id']);
            } else if (!empty($path)) {
                error_log("WARNING: Document file does not exist at path: ../$path for application ID: " . $app['id']);
            }
        } else {
            error_log("No application found for ID: " . $application_id);
        }
    } catch (PDOException $e) {
        error_log("Document path verification error: " . $e->getMessage());
    }
}

// Check if user is logged in
is_user_logged_in();

// Get user details
$user = get_current_user_data($conn);

// Initialize variables
$message = '';
$messageType = '';
$maxAttempts = 5;
$disableUpload = false;
$applicationStatus = '';

// Fetch existing application data to pre-fill form
$existing_application = null;
if ($user) {
    // Check submission attempts and eligibility
    $attemptCheck = check_submission_attempts($conn, $user['id'], $maxAttempts);
    $canSubmit = $attemptCheck['can_submit'];
    $submissionAttempts = $attemptCheck['attempts'] ?? 0;
    
    // Set message if cannot submit
    if (!$canSubmit) {
        $message = $attemptCheck['message'];
        $messageType = $attemptCheck['message_type'];
    }
    
    // Check if user has an existing application and get its data
    $stmt = $conn->prepare("SELECT * FROM applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$user['id']]);
    if ($stmt->rowCount() > 0) {
        $existing_application = $stmt->fetch();
        $applicationStatus = $existing_application['status'];
        // Disable upload if application is submitted and not rejected
        if ($applicationStatus !== 'Rejected' && $applicationStatus !== '') {
            $disableUpload = true;
        }
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canSubmit) {
    // Get form data for educational background
    $previous_school = $_POST['previous_school'] ?? '';
    $school_year = $_POST['school_year'] ?? '';
    $strand = $_POST['strand'] ?? '';
    $gpa = $_POST['gpa'] ?? '';
    $address = $_POST['address'] ?? '';
    $age = $_POST['age'] ?? '';
    
    // Validate required fields
    if (empty($previous_school) || empty($school_year) || empty($strand) || empty($gpa) || empty($address) || empty($age)) {
        $message = 'Please fill in all required fields.';
        $messageType = 'danger';
    }
    // Validate school year format (YYYY-YYYY)
    elseif (!preg_match('/^\d{4}-\d{4}$/', $school_year)) {
        $message = 'School year must be in the format YYYY-YYYY (e.g., 2023-2024).';
        $messageType = 'danger';
    }
    // Validate that the first year is less than the second year
    elseif (substr($school_year, 0, 4) >= substr($school_year, 5, 4)) {
        $message = 'Invalid school year range. The start year must be less than the end year.';
        $messageType = 'danger';
    }
    // Validate GPA
    elseif (!is_numeric($gpa) || $gpa < 75 || $gpa > 100) {
        $message = 'GPA must be a number between 75 and 100.';
        $messageType = 'danger';
    }
    // Validate age
    elseif (!is_numeric($age) || $age < 16 || $age > 100) {
        $message = 'Age must be a number between 16 and 100.';
        $messageType = 'danger';
    }
    // Check if PDF file was uploaded    
    elseif (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please select a PDF file to upload.';
        $messageType = 'danger';
    }
    // Check if 2x2 image was uploaded
    elseif (!isset($_FILES['image_2x2']) || $_FILES['image_2x2']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please select a 2x2 ID picture to upload.';
        $messageType = 'danger';
    }
    else {
        $file_name = $_FILES['pdf_file']['name'];
        $file_tmp = $_FILES['pdf_file']['tmp_name'];
        $file_size = $_FILES['pdf_file']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Get image information
        $image_name = $_FILES['image_2x2']['name'];
        $image_tmp = $_FILES['image_2x2']['tmp_name'];
        $image_size = $_FILES['image_2x2']['size'];
        $image_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        
        // Validate file extension
        if ($file_ext !== 'pdf') {
            $message = 'Only PDF files are allowed.';
            $messageType = 'danger';
        }
        // Validate file size (max 5MB)
        elseif ($file_size > 5 * 1024 * 1024) {
            $message = 'File size must be less than 5MB.';
            $messageType = 'danger';
        }
        // Validate image extension
        elseif (!in_array($image_ext, ['jpg', 'jpeg', 'png'])) {
            $message = 'Only JPG, JPEG, or PNG images are allowed for 2x2 ID picture.';
            $messageType = 'danger';
        }
        // Validate image size (max 5MB)
        elseif ($image_size > 5 * 1024 * 1024) {
            $message = '2x2 ID picture size must be less than 5MB.';
            $messageType = 'danger';
        }
        else {
            try {
                // Create timestamp to use for both files to ensure consistency
                $timestamp = time();
                
                // Generate unique filename for PDF
                $new_filename = $user['control_number'] . '_' . $timestamp . '.pdf';
                $upload_path = '../uploads/' . $new_filename;
                
                // Generate unique filename for 2x2 image - use same timestamp
                $new_imagename = $user['control_number'] . '_' . $timestamp . '.' . $image_ext;
                $image_path = '../images/' . $new_imagename;
                
                // Check if directories exist, create if they don't
                if (!file_exists('../uploads/')) {
                    mkdir('../uploads/', 0755, true);
                }
                if (!file_exists('../images/')) {
                    mkdir('../images/', 0755, true);
                }
                
                // Move files to respective directories
                $pdf_uploaded = move_uploaded_file($file_tmp, $upload_path);
                $image_uploaded = move_uploaded_file($image_tmp, $image_path);
                
                if ($pdf_uploaded && $image_uploaded) {
                    // Start transaction
                    $conn->beginTransaction();
                    
                    // Validate PDF using the validation function
                    $validation_result = validate_pdf($upload_path);
                    $is_successful = ($validation_result && isset($validation_result['isValid']) && $validation_result['isValid'] === true);
                    $pdf_message = ($validation_result && isset($validation_result['message'])) ? $validation_result['message'] : 'PDF validation failed';
                    
                    // Log submission attempt
                    log_submission_attempt($conn, $user['id'], $is_successful, $pdf_message);
                    
                    // Check if PDF passed all validation requirements
                    if (!$is_successful) {
                        // PDF validation failed
                        $conn->commit(); // Commit the attempt increment
                        
                        $message = 'PDF validation failed: ' . $pdf_message;
                        $message .= ' Remaining attempts: ' . ($maxAttempts - $submissionAttempts - 1);
                        $messageType = 'danger';
                    } else {
                        // Check if this is a resubmission of a rejected application
                        $stmt = $conn->prepare("SELECT * FROM applications WHERE user_id = ? AND status = 'Rejected' ORDER BY created_at DESC LIMIT 1");
                        $stmt->execute([$user['id']]);
                        $existing_rejected = $stmt->fetch();
                        
                        if ($existing_rejected) {
                            // Update existing application if it was rejected
                            $sql = "UPDATE applications SET 
                                pdf_file = ?, 
                                pdf_validated = ?, 
                                validation_message = ?, 
                                document_file_path = ?,
                                document_file_size = ?,
                                document_upload_date = NOW(),
                                image_2x2_path = ?,
                                image_2x2_name = ?,
                                image_2x2_size = ?,
                                image_2x2_type = ?,
                                previous_school = ?,
                                school_year = ?,
                                strand = ?,
                                gpa = ?,
                                address = ?,
                                age = ?,
                                status = 'Submitted', 
                                updated_at = NOW() 
                                WHERE id = ?";
                            
                            // Ensure the document path has uploads/ prefix
                            $document_path = 'uploads/' . $new_filename;
                            // Ensure the image path has images/ prefix
                            $image_path_db = 'images/' . $new_imagename;
                            
                            $stmt = $conn->prepare($sql);
                            $stmt->execute([
                                $new_filename,
                                $validation_result['isValid'] ?? false,
                                $validation_result['message'] ?? 'PDF validation failed',
                                $document_path,
                                $file_size,
                                $image_path_db,
                                $new_imagename,
                                $image_size,
                                $image_ext,
                                $previous_school,
                                $school_year,
                                $strand,
                                $gpa,
                                $address,
                                $age,
                                $existing_rejected['id']
                            ]);
                            
                            $application_id = $existing_rejected['id'];
                            
                            // Debug log
                            error_log("Updated application ID: " . $application_id . " with document path: " . $document_path . " and image path: " . $image_path_db);
                            
                            // Verify document path was saved correctly
                            verify_document_path($conn, $application_id);
                        } else {
                            // Insert new application with document info and educational background
                            $sql = "INSERT INTO applications (
                                user_id, 
                                pdf_file, 
                                pdf_validated, 
                                validation_message, 
                                document_file_path, 
                                document_file_size, 
                                document_upload_date,
                                image_2x2_path,
                                image_2x2_name,
                                image_2x2_size,
                                image_2x2_type,
                                previous_school,
                                school_year,
                                strand,
                                gpa,
                                address,
                                age,
                                status,
                                created_at,
                                updated_at
                            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Submitted', NOW(), NOW())";
                            
                            // Ensure the document path has uploads/ prefix
                            $document_path = 'uploads/' . $new_filename;
                            // Ensure the image path has images/ prefix
                            $image_path_db = 'images/' . $new_imagename;
                            
                            $stmt = $conn->prepare($sql);
                            $stmt->execute([
                                $user['id'],
                                $new_filename,
                                $validation_result['isValid'] ?? false,
                                $validation_result['message'] ?? 'PDF validation failed',
                                $document_path,
                                $file_size,
                                $image_path_db,
                                $new_imagename,
                                $image_size,
                                $image_ext,
                                $previous_school,
                                $school_year,
                                $strand,
                                $gpa,
                                $address,
                                $age
                            ]);
                            
                            $application_id = $conn->lastInsertId();
                            
                            // Debug log
                            error_log("Created new application ID: " . $application_id . " with document path: " . $document_path . " and image path: " . $image_path_db);
                        }
                        
                        // Log the status change in history
                        $sql = "INSERT INTO status_history (application_id, status, description, performed_by) VALUES (?, 'Submitted', ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([
                            $application_id,
                            'Application submitted with PDF upload',
                            $user['first_name'] . ' ' . $user['last_name']
                        ]);
                        
                        // Update user profile with the provided information
                        $update_user = $conn->prepare("UPDATE users SET 
                            address = ?, 
                            updated_at = NOW() 
                            WHERE id = ?");
                        
                        $update_user->execute([
                            $address,
                            $user['id']
                        ]);
                        
                        // Update Firebase Realtime Database with status
                        update_firebase_status($user['control_number'], 'Submitted', [
                            'application_id' => $application_id,
                            'pdf_validated' => $validation_result['isValid'] ?? false
                        ]);
                        
                        // Add entry to Firebase history
                        add_firebase_history($user['control_number'], 'Submitted', 'Application submitted with PDF upload', $user['first_name'] . ' ' . $user['last_name']);
                        
                        // Commit transaction
                        $conn->commit();
                        
                        // Set success message
                        $message = 'Your application has been submitted successfully.';
                        $messageType = 'success';
                        
                        // Redirect to send email notification
                        header('Location: application_submitted.php?id=' . $application_id);
                        exit;
                    }
                } else {
                    $message = 'Failed to upload file. Please try again.';
                    $messageType = 'danger';
                }
            } catch (PDOException $e) {
                // Rollback transaction on error
                $conn->rollBack();
                
                error_log("Application submission error: " . $e->getMessage());
                $message = 'An error occurred while submitting your application. Please try again.';
                $messageType = 'danger';
            }
        }
    }
}

// Include the HTML template
include_once 'html/application_form.html';

// Pass user data and existing application data to JavaScript
echo '<script>
    const userData = ' . json_encode([
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email']
    ]) . ';
    const existingApplication = ' . json_encode($existing_application) . ';
    
    // Pre-fill form fields if existing application data exists
    if (existingApplication) {
        document.addEventListener("DOMContentLoaded", function() {
            if (existingApplication.previous_school) {
                document.getElementById("previous_school").value = existingApplication.previous_school;
            }
            if (existingApplication.school_year) {
                document.getElementById("school_year").value = existingApplication.school_year;
            }
            if (existingApplication.strand) {
                document.getElementById("strand").value = existingApplication.strand;
            }
            if (existingApplication.gpa) {
                document.getElementById("gpa").value = existingApplication.gpa;
            }
            if (existingApplication.address) {
                document.getElementById("address").value = existingApplication.address;
            }
            if (existingApplication.age) {
                document.getElementById("age").value = existingApplication.age;
            }
        });
    }
</script>'; 