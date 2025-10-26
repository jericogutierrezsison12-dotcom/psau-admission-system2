<?php
/**
 * Admin Registration - Multi-Step Process
 * Step 1: Verify restricted email (jericogutierrezsison12@gmail.com) with OTP
 * Step 2: Fill admin registration form with their own email
 * Step 3: Verify their own email with OTP
 * Step 4: Complete registration
 */

require_once '../includes/db_connect.php';
require_once '../includes/encryption.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$step = 1;
$username = $email = $role = '';
$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedStep = (int)($_POST['step'] ?? 1);
    
    if ($postedStep === 1) {
        // Step 1: Verify restricted email OTP
        $otp_code = trim($_POST['otp_code'] ?? '');
        $recaptcha_verified = $_POST['recaptcha_verified'] ?? '';
        
        // Check reCAPTCHA verification
        if ($recaptcha_verified !== 'true') {
            $errors['recaptcha'] = 'reCAPTCHA verification is required';
        }
        
        if ($otp_code === '') {
            $errors['otp'] = 'OTP code is required';
        } elseif (!preg_match('/^\d{6}$/', $otp_code)) {
            $errors['otp'] = 'Invalid OTP format';
        } elseif (!isset($_SESSION['admin_restricted_email_otp']['code'], $_SESSION['admin_restricted_email_otp']['expires'])) {
            $errors['otp'] = 'No OTP found. Please resend the code.';
        } elseif (time() > (int)$_SESSION['admin_restricted_email_otp']['expires']) {
            $errors['otp'] = 'OTP has expired. Please resend the code.';
        } elseif ($otp_code !== (string)$_SESSION['admin_restricted_email_otp']['code']) {
            $errors['otp'] = 'Incorrect OTP. Please try again.';
        }

        if (!$errors) {
            // Restricted email verified, move to registration form
            $step = 2;
        }
    } elseif ($postedStep === 2) {
        // Step 2: Admin registration form submit
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'registrar';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if ($username === '') {
            $errors['username'] = 'Username is required';
        } elseif (strlen($username) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors['username'] = 'Username can only contain letters, numbers, and underscores';
        }

        if ($email === '') {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if ($role === '') {
            $errors['role'] = 'Role is required';
        } elseif (!in_array($role, ['admin', 'registrar', 'department'])) {
            $errors['role'] = 'Invalid role selected';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = 'Password must contain at least one uppercase letter';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors['password'] = 'Password must contain at least one lowercase letter';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one number';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one special character';
        }

        if ($confirm_password === '') {
            $errors['confirm_password'] = 'Please confirm your password';
        } elseif ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        // Check for existing username and email using encrypted lookup
        if (!$errors) {
            try {
                $encrypted_email = encryptContactData($email);
                $chk = $conn->prepare('SELECT COUNT(*) FROM admins WHERE username = ? OR email_encrypted = ?');
                $chk->execute([$username, $encrypted_email]);
                $exists = (int)$chk->fetchColumn();
                if ($exists > 0) {
                    $errors['exists'] = 'Username or email already exists';
                }
            } catch (PDOException $e) {
                error_log('Admin registration validation error: ' . $e->getMessage());
                $errors['server'] = 'Server error. Please try again later.';
            } catch (Exception $e) {
                error_log('Admin registration error: ' . $e->getMessage());
                $errors['server'] = 'Server error. Please try again later.';
            }
        }

        if (!$errors) {
            // Store in session and move to their email OTP step
            $_SESSION['admin_registration'] = [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => $role,
            ];
            $step = 3;
        }
    } elseif ($postedStep === 3) {
        // Step 3: Verify their own email OTP
        $otp_code = trim($_POST['otp_code'] ?? '');
        $recaptcha_verified = $_POST['recaptcha_verified'] ?? '';
        
        // Check reCAPTCHA verification
        if ($recaptcha_verified !== 'true') {
            $errors['recaptcha'] = 'reCAPTCHA verification is required';
        }
        
        if ($otp_code === '') {
            $errors['otp'] = 'OTP code is required';
        } elseif (!preg_match('/^\d{6}$/', $otp_code)) {
            $errors['otp'] = 'Invalid OTP format';
        } elseif (!isset($_SESSION['admin_email_otp']['code'], $_SESSION['admin_email_otp']['expires'])) {
            $errors['otp'] = 'No OTP found. Please resend the code.';
        } elseif (time() > (int)$_SESSION['admin_email_otp']['expires']) {
            $errors['otp'] = 'OTP has expired. Please resend the code.';
        } elseif ($otp_code !== (string)$_SESSION['admin_email_otp']['code']) {
            $errors['otp'] = 'Incorrect OTP. Please try again.';
        }

        if (!$errors) {
            try {
                $reg = $_SESSION['admin_registration'] ?? null;
                if (!$reg) {
                    throw new Exception('Registration session expired.');
                }
                
                // Start transaction for data integrity
                $conn->beginTransaction();
                
                $hash = password_hash($reg['password'], PASSWORD_DEFAULT);
                
                // Encrypt sensitive data
                $encrypted_username = encryptPersonalData($reg['username']);
                $encrypted_email = encryptContactData($reg['email']);

                $ins = $conn->prepare('INSERT INTO admins (username, email, mobile_number, password, role, created_at, username_encrypted, email_encrypted) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)');
                $ins->execute([$reg['username'], $reg['email'], '', $hash, $reg['role'], $encrypted_username, $encrypted_email]);

                // Commit transaction
                $conn->commit();

                $success = 'Account created successfully. You can now login.';
                // Clear temp data and reset form
                unset($_SESSION['admin_registration']);
                unset($_SESSION['admin_email_otp']);
                unset($_SESSION['admin_restricted_email_otp']);
                $username = $email = '';
                $role = 'registrar';
                $step = 1;
            } catch (PDOException $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                error_log('Admin registration database error: ' . $e->getMessage());
                error_log('Admin registration database error code: ' . $e->getCode());
                error_log('Admin registration database error info: ' . print_r($e->errorInfo, true));
                
                // Check for specific database errors
                if ($e->getCode() == 23000) { // Duplicate entry
                    if (strpos($e->getMessage(), 'username') !== false) {
                        $errors['username'] = 'Username already exists';
                    } elseif (strpos($e->getMessage(), 'email') !== false) {
                        $errors['email'] = 'Email already exists';
                    } else {
                        $errors['server'] = 'Duplicate entry error. Please try again.';
                    }
                } else {
                    $errors['server'] = 'Database error. Please try again later.';
                }
                $step = 1;
            } catch (Exception $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                error_log('Admin registration error: ' . $e->getMessage());
                $errors['server'] = 'Server error. Please try again later.';
                $step = 1;
            }
        } else {
            $step = 3;
        }
    }
}

// Include the HTML template
require_once 'html/register.html';
?>