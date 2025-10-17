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
		}
		// Normalize and validate mobile number to local 09XXXXXXXXX
		if ($mobile_number === '') {
			$errors['mobile_number'] = 'Mobile number is required';
		} else {
			$raw_mobile = preg_replace('/[^0-9+]/', '', $mobile_number);
			if (strpos($raw_mobile, '+63') === 0) {
				$mobile_number = '0' . substr($raw_mobile, 3);
			} else {
				$digits = preg_replace('/[^0-9]/', '', $raw_mobile);
				if (strpos($digits, '63') === 0 && strlen($digits) === 12) {
					$mobile_number = '0' . substr($digits, 2);
				} elseif (strlen($digits) === 10 && $digits[0] === '9') {
					$mobile_number = '0' . $digits;
				} else {
					$mobile_number = $digits;
				}
			}
			if (!preg_match('/^0\d{10}$/', $mobile_number)) {
				$errors['mobile_number'] = 'Please enter a valid mobile number (e.g., 09513472160)';
			}
		}
		if ($password === '' || strlen($password) < 8) {
			$errors['password'] = 'Password must be at least 8 characters';
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
				// Ensure admins table has mobile_number column (MariaDB supports IF NOT EXISTS)
				$conn->exec("ALTER TABLE admins ADD COLUMN IF NOT EXISTS mobile_number varchar(20) NOT NULL AFTER email");

				// Check unique username/email/mobile
				$chk = $conn->prepare('SELECT COUNT(*) FROM admins WHERE username = ? OR email = ? OR mobile_number = ?');
				$chk->execute([$username, $email, $mobile_number]);
				$exists = (int)$chk->fetchColumn();
				if ($exists > 0) {
					$errors['exists'] = 'Username, email, or mobile already exists';
				} else {
					// Store in session then go to OTP step
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
			}
		}
	} elseif ($postedStep === 2) {
		// OTP verification submit
		$otp_code = trim($_POST['otp_code'] ?? '');
		if ($otp_code === '') {
			$errors['otp'] = 'OTP code is required';
		} elseif (!isset($_POST['firebase_verified']) || $_POST['firebase_verified'] !== 'true') {
			$errors['otp'] = 'OTP verification failed. Please try again.';
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


