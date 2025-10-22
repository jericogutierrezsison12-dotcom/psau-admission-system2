<?php
/**
 * PSAU Admission System - Admin Registration
 * Allows creating admin/registrar/department accounts with mobile OTP verification.
 */

require_once '../includes/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

$errors = [];
$success = null;

// Step handling similar to public registration
$step = 1; // 1: form, 2: OTP verify

// Form fields
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$mobile_number = trim($_POST['mobile_number'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role = $_POST['role'] ?? 'registrar';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$postedStep = isset($_POST['step']) ? (int)$_POST['step'] : 1;
	if ($postedStep === 1) {
		// Basic validations
		if ($username === '') {
			$errors['username'] = 'Username is required';
		}
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'Valid email is required';
		} elseif ($email !== 'jericogutierrezsison12@gmail.com') {
			$errors['email'] = 'Only jericogutierrezsison12@gmail.com is allowed for admin registration';
		}
		// Mobile number is optional for admin registration
		$mobile_number = ''; // Set to empty since we're using email OTP
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
		if ($confirm_password === '' || $confirm_password !== $password) {
			$errors['confirm_password'] = 'Passwords do not match';
		}
		$allowed_roles = ['admin','registrar','department'];
		if (!in_array($role, $allowed_roles, true)) {
			$errors['role'] = 'Invalid role selected';
		}

		if (!$errors) {
			try {
				// Debug: Log the registration attempt
				error_log("Admin registration attempt - Username: $username, Email: $email, Role: $role");
				
				// Ensure admins table has mobile_number column (MariaDB supports IF NOT EXISTS)
				$conn->exec("ALTER TABLE admins ADD COLUMN IF NOT EXISTS mobile_number varchar(20) NOT NULL AFTER email");

				// Check unique username/email/mobile
				$chk = $conn->prepare('SELECT COUNT(*) FROM admins WHERE username = ? OR email = ? OR mobile_number = ?');
				$chk->execute([$username, $email, $mobile_number]);
				$exists = (int)$chk->fetchColumn();
				if ($exists > 0) {
					$errors['exists'] = 'Username, email, or mobile already exists';
				} else {
					// Generate and send email OTP
					$otp_code = sprintf('%06d', mt_rand(100000, 999999));
					$_SESSION['admin_email_otp'] = $otp_code;
					
					// Send email OTP
					error_log("Attempting to include firebase_email.php");
					require_once '../firebase/firebase_email.php';
					error_log("Firebase email file included successfully");
					
					$subject = 'Admin Registration OTP - PSAU Admission System';
					$message = "
					<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
						<div style='background-color: #2E7D32; color: white; padding: 20px; text-align: center;'>
							<h2>Pampanga State Agricultural University</h2>
						</div>
						<div style='padding: 20px; border: 1px solid #ddd;'>
							<p>Dear Admin,</p>
							<p>Your OTP code for admin registration is:</p>
							<div style='background-color: #f8f9fa; padding: 15px; text-align: center; border: 2px solid #2E7D32; margin: 20px 0;'>
								<h1 style='color: #2E7D32; margin: 0; font-size: 32px; letter-spacing: 5px;'>{$otp_code}</h1>
							</div>
							<p>This code will expire in 10 minutes. Please enter it to complete your admin registration.</p>
							<p>If you did not request this registration, please ignore this email.</p>
							<p>Best regards,<br>PSAU Admissions Team</p>
						</div>
						<div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
							<p>&copy; " . date('Y') . " PSAU Admission System. All rights reserved.</p>
						</div>
					</div>";
					
					try {
						$result = firebase_send_email($email, $subject, $message);
						if (is_array($result) && isset($result['success']) && $result['success']) {
							// Store in session then go to OTP step
							$_SESSION['admin_registration'] = [
								'username' => $username,
								'email' => $email,
								'mobile_number' => $mobile_number,
								'password' => $password,
								'role' => $role,
							];
							$step = 2;
						} else {
							error_log('Firebase email failed: ' . json_encode($result));
							$errors['email'] = 'Failed to send OTP email. Please try again.';
						}
					} catch (Exception $e) {
						error_log('Admin email OTP error: ' . $e->getMessage());
						$errors['email'] = 'Failed to send OTP email. Please try again.';
					} catch (Error $e) {
						error_log('Admin email OTP fatal error: ' . $e->getMessage());
						$errors['email'] = 'Failed to send OTP email. Please try again.';
					}
				}
			} catch (PDOException $e) {
				error_log('Admin registration validation error: ' . $e->getMessage());
				$errors['server'] = 'Server error. Please try again later.';
			}
		}
	} elseif ($postedStep === 2) {
		// Email OTP verification submit
		$otp_code = trim($_POST['otp_code'] ?? '');
		if ($otp_code === '') {
			$errors['otp'] = 'OTP code is required';
		} elseif (!isset($_SESSION['admin_email_otp']) || $_SESSION['admin_email_otp'] !== $otp_code) {
			$errors['otp'] = 'Invalid OTP code. Please try again.';
		}

		if (!$errors) {
			try {
				$reg = $_SESSION['admin_registration'] ?? null;
				if (!$reg) {
					throw new Exception('Registration session expired.');
				}
				$hash = password_hash($reg['password'], PASSWORD_DEFAULT);

				// Ensure mobile column still exists
				$conn->exec("ALTER TABLE admins ADD COLUMN IF NOT EXISTS mobile_number varchar(20) NOT NULL AFTER email");

				$ins = $conn->prepare('INSERT INTO admins (username, email, mobile_number, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
				$ins->execute([$reg['username'], $reg['email'], $reg['mobile_number'], $hash, $reg['role']]);

				$success = 'Account created successfully. You can now login.';
				// Clear temp data and reset form
				unset($_SESSION['admin_registration']);
				unset($_SESSION['admin_email_otp']);
				$username = $email = $mobile_number = '';
				$role = 'registrar';
				$step = 1;
			} catch (Exception $e) {
				error_log('Admin registration error: ' . $e->getMessage());
				$errors['server'] = 'Server error. Please try again later.';
				$step = 1;
			}
		} else {
			$step = 2;
		}
	}
}

include 'html/register.html';


?>


