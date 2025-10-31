<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';
require_once '../includes/encryption.php';
require_once '../includes/functions.php';

$resp = [
    'key_roundtrip' => null,
    'login_probe' => null,
    'pages' => [
        'profile' => null,
        'forgot_password' => null,
        'register_uniqueness' => null,
        'application_form' => null
    ],
    'errors' => []
];

// 1) Key roundtrip
try {
    $enc = enc_contact('probe@example.com');
    $dec = dec_contact($enc);
    $resp['key_roundtrip'] = ($dec === 'probe@example.com');
} catch (Exception $e) {
    $resp['key_roundtrip'] = false;
    $resp['errors'][] = 'key: ' . $e->getMessage();
}

// Helper to safely decrypt user row
function safe_decrypt_user($row){
    try {
        return decrypt_user_row($row);
    } catch (Exception $e) {
        return $row;
    }
}

// 2) Login decryption probe
$identifier = trim($_POST['identifier'] ?? ($_GET['identifier'] ?? ''));
$password = (string)($_POST['password'] ?? ($_GET['password'] ?? ''));
$login_ok = null; $login_detail = '';
if ($identifier !== '' && $password !== '') {
    try {
        // Attempt 1: encrypted equality
        $stmt = $conn->prepare("SELECT * FROM users WHERE (email = ? OR mobile_number = ?) AND is_verified = 1");
        $encId = enc_contact($identifier);
        $stmt->execute([$encId, $encId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            // Attempt 2: decrypt-scan
            $stmt = $conn->prepare("SELECT * FROM users WHERE is_verified = 1");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $needle = strtolower($identifier);
            foreach ($rows as $row) {
                $decEmail = '';
                $decMobile = '';
                try { $decEmail = dec_contact($row['email'] ?? ''); } catch (Exception $e) {}
                try { $decMobile = dec_contact($row['mobile_number'] ?? ''); } catch (Exception $e) {}
                if (strtolower(trim($decEmail)) === $needle || strtolower(trim($decMobile)) === $needle) {
                    $user = $row; break;
                }
            }
        }
        if ($user && password_verify($password, $user['password'])) {
            $login_ok = true; $login_detail = 'match';
        } else {
            $login_ok = false; $login_detail = 'not_found_or_bad_pw';
        }
    } catch (Exception $e) {
        $resp['errors'][] = 'login: ' . $e->getMessage();
        $login_ok = false;
    }
}
$resp['login_probe'] = [ 'identifier' => $identifier, 'result' => $login_ok, 'detail' => $login_detail ];

// 3) Profile page decrypt probe (latest user)
try {
    $stmt = $conn->query("SELECT id, first_name, last_name, gender, birth_date, email, mobile_number FROM users ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $resp['pages']['profile'] = safe_decrypt_user($row);
    } else {
        $resp['pages']['profile'] = 'no_user';
    }
} catch (Exception $e) {
    $resp['pages']['profile'] = false; $resp['errors'][] = 'profile: ' . $e->getMessage();
}

// 4) Forgot password style lookup probe (by email if provided)
try {
    if ($identifier !== '' && filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM users WHERE is_verified = 1");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $needle = strtolower($identifier);
        $found = false;
        foreach ($rows as $row) {
            $decEmail = '';
            try { $decEmail = dec_contact($row['email'] ?? ''); } catch (Exception $e) {}
            if (strtolower(trim($decEmail)) === $needle) { $found = true; break; }
        }
        $resp['pages']['forgot_password'] = $found;
    } else {
        $resp['pages']['forgot_password'] = 'skipped';
    }
} catch (Exception $e) {
    $resp['pages']['forgot_password'] = false; $resp['errors'][] = 'forgot: ' . $e->getMessage();
}

// 5) Register uniqueness checks probe
try {
    if ($identifier !== '') {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([enc_contact($identifier)]);
        $resp['pages']['register_uniqueness'] = (int)$stmt->fetchColumn();
    } else {
        $resp['pages']['register_uniqueness'] = 'skipped';
    }
} catch (Exception $e) {
    $resp['pages']['register_uniqueness'] = false; $resp['errors'][] = 'register: ' . $e->getMessage();
}

// 6) Application form decrypt probe
try {
    $stmt = $conn->query("SELECT id, user_id, strand, gpa, address FROM applications ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $resp['pages']['application_form'] = decrypt_application_row($row);
    } else {
        $resp['pages']['application_form'] = 'no_application';
    }
} catch (Exception $e) {
    $resp['pages']['application_form'] = false; $resp['errors'][] = 'application_form: ' . $e->getMessage();
}

echo json_encode($resp, JSON_PRETTY_PRINT);

