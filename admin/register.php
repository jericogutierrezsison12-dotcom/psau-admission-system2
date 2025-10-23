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

				// Check unique username/email (mobile_number is empty for admin registration)
				$chk = $conn->prepare('SELECT COUNT(*) FROM admins WHERE username = ? OR email = ?');
				$chk->execute([$username, $email]);
				$exists = (int)$chk->fetchColumn();
				if ($exists > 0) {
					$errors['exists'] = 'Username or email already exists';
				} else {
					// Store in session and move to OTP step
					$_SESSION['admin_registration'] = [
						'username' => $username,
						'email' => $email,
						'mobile_number' => $mobile_number,
						'password' => $password,
						'role' => $role,
					];
					$step = 2;
				}
			} catch (PDOException $e) {
				error_log('Admin registration validation error: ' . $e->getMessage());
				$errors['server'] = 'Server error. Please try again later.';
			} catch (Exception $e) {
				error_log('Admin registration error: ' . $e->getMessage());
				$errors['server'] = 'Server error. Please try again later.';
			}
		}
	} elseif ($postedStep === 2) {
		// Email OTP verification submit
		$otp_code = trim($_POST['otp_code'] ?? '');
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

				// Ensure mobile column still exists
				$conn->exec("ALTER TABLE admins ADD COLUMN IF NOT EXISTS mobile_number varchar(20) NOT NULL AFTER email");

				$ins = $conn->prepare('INSERT INTO admins (username, email, mobile_number, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
				$ins->execute([$reg['username'], $reg['email'], $reg['mobile_number'], $hash, $reg['role']]);

				// Commit transaction
				$conn->commit();

				$success = 'Account created successfully. You can now login.';
				// Clear temp data and reset form
				unset($_SESSION['admin_registration']);
				unset($_SESSION['admin_email_otp']);
				$username = $email = $mobile_number = '';
				$role = 'registrar';
				$step = 1;
			} catch (PDOException $e) {
				if ($conn->inTransaction()) {
					$conn->rollBack();
				}
				error_log('Admin registration database error: ' . $e->getMessage());
				$errors['server'] = 'Server error. Please try again later.';
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
			$step = 2;
		}
	}
}

include 'html/register.html';


?>


