<?php
/**
 * Admin Registration - Multi-Step Process
 * Step 1: Verify restricted email (jericogutierrezsison12@gmail.com) with OTP
 * Step 2: Fill admin registration form with their own email
 * Step 3: Verify their own email with OTP
 * Step 4: Complete registration
 */

require_once '../includes/db_connect.php';
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

        // Check for existing username and email
        if (!$errors) {
            try {
                $chk = $conn->prepare('SELECT COUNT(*) FROM admins WHERE username = ? OR email = ?');
                $chk->execute([$username, $email]);
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

                $ins = $conn->prepare('INSERT INTO admins (username, email, mobile_number, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                $ins->execute([$reg['username'], $reg['email'], '', $hash, $reg['role']]);

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

// If on step 3, automatically send OTP if not already sent
if ($step === 3 && !isset($_SESSION['admin_email_otp'])) {
    try {
        $reg = $_SESSION['admin_registration'] ?? null;
        if ($reg && isset($reg['email'])) {
            require_once '../includes/otp_rate_limiting.php';
            $rate_limit = check_otp_rate_limit($reg['email'], 'admin_register');
            
            if ($rate_limit['can_send']) {
                // Generate 6-digit OTP and set 10-minute expiry
                $otp = random_int(100000, 999999);
                $_SESSION['admin_email_otp'] = [
                    'code' => (string)$otp,
                    'expires' => time() + (10 * 60),
                ];
                
                // Build email content
                require_once '../firebase/firebase_email.php';
                $subject = 'PSAU Admin Registration: Your Verification Code';
                $message = "<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>"
                    ."<div style='background-color:#2E7D32;color:#fff;padding:16px;text-align:center;'>"
                    ."<h2 style='margin:0'>Pampanga State Agricultural University</h2>"
                    ."</div>"
                    ."<div style='padding:20px;border:1px solid #ddd;border-top:none'>"
                    ."<p>Dear Admin,</p>"
                    ."<p>Your verification code for admin registration is:</p>"
                    ."<p style='font-size:28px;letter-spacing:6px;font-weight:bold;text-align:center;margin:20px 0'>{$otp}</p>"
                    ."<p>This code will expire in 10 minutes. If you did not request this code, you may ignore this email.</p>"
                    ."<p>Best regards,<br>PSAU Admissions Team</p>"
                    ."</div>"
                    ."<div style='background:#f5f5f5;padding:10px;text-align:center;color:#666;font-size:12px'>&copy; "
                    . date('Y') . " PSAU Admission System</div>"
                    ."</div>";
                
                $result = firebase_send_email($reg['email'], $subject, $message);
                
                // firebase_send_email returns array with 'success' key
                if (is_array($result) && !empty($result['success'])) {
                    // Record OTP request for rate limiting
                    record_otp_request($reg['email'], 'admin_register');
                    error_log("Auto-sent OTP email to: " . $reg['email']);
                } else {
                    error_log("Failed to auto-send OTP to: " . $reg['email'] . ". Result: " . print_r($result, true));
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error auto-sending OTP email: " . $e->getMessage());
    }
}

// Include the HTML template
require_once 'html/register.html';
?>