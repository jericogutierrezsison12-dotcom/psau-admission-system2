<?php
// Run from CLI: php scripts/re_encrypt_all.php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/encryption.php';

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain');
}

function out($msg){ echo $msg . (php_sapi_name()==='cli' ? PHP_EOL : "\n"); }

out("PSAU Re-Encrypt All (v2 format)\n===============================");

function reenc_rows(PDO $conn, string $table, array $columns, callable $encMap) {
    $count = 0; $updated = 0; $errors = 0;
    try {
        $stmt = $conn->query("SELECT * FROM `$table`");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $count++;
            $new = $row;
            $changed = false;
            foreach ($columns as $col) {
                if (!array_key_exists($col, $row)) continue;
                $val = $row[$col];
                try {
                    $encVal = $encMap($col, $val);
                    if ($encVal !== $val) { $new[$col] = $encVal; $changed = true; }
                } catch (Exception $e) {
                    $errors++;
                }
            }
            if ($changed) {
                $set = [];
                $params = [];
                foreach ($columns as $col) {
                    if (array_key_exists($col, $new) && $new[$col] !== $row[$col]) {
                        $set[] = "`$col` = ?";
                        $params[] = $new[$col];
                    }
                }
                if (!empty($set)) {
                    $pk = array_key_exists('id', $row) ? 'id' : key($row);
                    $params[] = $row[$pk];
                    $sql = "UPDATE `$table` SET " . implode(',', $set) . " WHERE `$pk` = ?";
                    $u = $conn->prepare($sql);
                    $u->execute($params);
                    $updated++;
                }
            }
        }
    } catch (Exception $e) {
        out("$table ERROR: " . $e->getMessage());
    }
    out(sprintf("%s: scanned=%d updated=%d errors=%d", $table, $count, $updated, $errors));
}

// Users table
reenc_rows($conn, 'users', ['first_name','last_name','email','mobile_number','address','birth_date','gender'], function($col,$val){
    if ($val === null || $val === '') return '';
    switch ($col) {
        case 'first_name':
        case 'last_name':
        case 'address':
        case 'birth_date':
        case 'gender':
            $plain = PSAUEncryption::decrypt($val, 'personal');
            return PSAUEncryption::encrypt($plain, 'personal');
        case 'email':
        case 'mobile_number':
            $plain = PSAUEncryption::decrypt($val, 'contact');
            return PSAUEncryption::encrypt($plain, 'contact');
        default:
            return $val;
    }
});

// Applications table
reenc_rows($conn, 'applications', ['strand','gpa','address'], function($col,$val){
    if ($val === null || $val === '') return '';
    if ($col === 'address') { $plain = PSAUEncryption::decrypt($val, 'personal'); return PSAUEncryption::encrypt($plain, 'personal'); }
    $plain = PSAUEncryption::decrypt($val, 'academic');
    return PSAUEncryption::encrypt($plain, 'academic');
});

// Reminder logs (email)
reenc_rows($conn, 'reminder_logs', ['email'], function($col,$val){
    if ($val === null || $val === '') return '';
    $plain = PSAUEncryption::decrypt($val, 'contact');
    return PSAUEncryption::encrypt($plain, 'contact');
});

// OTP requests (email)
reenc_rows($conn, 'otp_requests', ['email'], function($col,$val){
    if ($val === null || $val === '') return '';
    $plain = PSAUEncryption::decrypt($val, 'contact');
    return PSAUEncryption::encrypt($plain, 'contact');
});

out("Done.");

