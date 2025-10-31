<?php
// Quick diagnostics for encryption/decryption and headers
header('Content-Type: text/plain');
require_once '../includes/db_connect.php';
require_once '../includes/encryption.php';

echo "Encryption diagnostics\n";

// Key check
try {
    $probe = enc_contact('test@example.com');
    $back = dec_contact($probe);
    echo "Key OK: roundtrip contact works => $back\n";
} catch (Exception $e) {
    echo "Key ERROR: " . $e->getMessage() . "\n";
}

// Sample user decrypt
try {
    $stmt = $conn->query("SELECT id, first_name, last_name, email, mobile_number FROM users ORDER BY id DESC LIMIT 3");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        echo "No users found.\n";
    } else {
        foreach ($rows as $r) {
            $fn = '';$ln='';$em='';$mn='';
            try { $fn = dec_personal($r['first_name'] ?? ''); } catch (Exception $e) {}
            try { $ln = dec_personal($r['last_name'] ?? ''); } catch (Exception $e) {}
            try { $em = dec_contact($r['email'] ?? ''); } catch (Exception $e) {}
            try { $mn = dec_contact($r['mobile_number'] ?? ''); } catch (Exception $e) {}
            echo "User {$r['id']}: $fn $ln | $em | $mn\n";
        }
    }
} catch (Exception $e) {
    echo "User decrypt ERROR: " . $e->getMessage() . "\n";
}

echo "\nDone\n";

