<?php
header('Content-Type: text/plain');
require_once '../includes/db_connect.php';
require_once '../includes/encryption.php';
require_once '../includes/functions.php';

function line($msg){ echo $msg . "\n"; }

line("PSAU Encryption Full Check\n============================\n");

// 1) Key roundtrip
try {
    $probe = enc_contact('test@example.com');
    $back = dec_contact($probe);
    line("Key roundtrip OK: $back");
} catch (Exception $e) {
    line("Key roundtrip ERROR: " . $e->getMessage());
}

// 2) Users sample
try {
    $stmt = $conn->query("SELECT id, control_number, first_name, last_name, email, mobile_number, created_at FROM users ORDER BY id DESC LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) { line("Users: none"); }
    foreach ($rows as $r) {
        $dr = decrypt_user_row($r);
        line(sprintf("User %d: %s %s | %s | %s | %s",
            $dr['id'],
            $dr['first_name'] ?? '',
            $dr['last_name'] ?? '',
            $dr['email'] ?? '',
            $dr['mobile_number'] ?? '',
            $dr['control_number'] ?? ''
        ));
    }
} catch (Exception $e) { line("Users ERROR: " . $e->getMessage()); }

// 3) Applications sample
try {
    $stmt = $conn->query("SELECT id, user_id, status, strand, gpa, address, created_at FROM applications ORDER BY id DESC LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) { line("Applications: none"); }
    foreach ($rows as $r) {
        $dr = decrypt_application_row($r);
        line(sprintf("App %d (user %d): status=%s strand=%s gpa=%s address=%s",
            $dr['id'], $dr['user_id'], $dr['status'] ?? '', $dr['strand'] ?? '', $dr['gpa'] ?? '', $dr['address'] ?? ''
        ));
    }
} catch (Exception $e) { line("Applications ERROR: " . $e->getMessage()); }

// 4) Entrance exam scores
try {
    $stmt = $conn->query("SELECT id, control_number, stanine_score, created_at FROM entrance_exam_scores ORDER BY id DESC LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) { line("Scores: none"); }
    foreach ($rows as $r) {
        line(sprintf("Score %d: %s => %s",
            $r['id'], $r['control_number'] ?? '', $r['stanine_score'] ?? ''
        ));
    }
} catch (Exception $e) { line("Scores ERROR: " . $e->getMessage()); }

// 5) Course assignments join (decrypt names)
try {
    $stmt = $conn->query("SELECT ca.id, u.first_name, u.last_name, c.course_code, c.course_name, ca.created_at FROM course_assignments ca JOIN users u ON ca.user_id=u.id JOIN courses c ON ca.course_id=c.id ORDER BY ca.id DESC LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) { line("Course assignments: none"); }
    foreach ($rows as $r) {
        try { $r['first_name'] = dec_personal($r['first_name'] ?? ''); } catch (Exception $e) {}
        try { $r['last_name'] = dec_personal($r['last_name'] ?? ''); } catch (Exception $e) {}
        line(sprintf("CA %d: %s %s -> %s %s",
            $r['id'], $r['first_name'] ?? '', $r['last_name'] ?? '', $r['course_code'] ?? '', $r['course_name'] ?? ''
        ));
    }
} catch (Exception $e) { line("Course assignments ERROR: " . $e->getMessage()); }

// 6) Reminders sample (emails)
try {
    $stmt = $conn->query("SELECT id, user_id, email, reminder_type, created_at FROM reminder_logs ORDER BY id DESC LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) { line("Reminders: none"); }
    foreach ($rows as $r) {
        $em = '';
        try { $em = dec_contact($r['email'] ?? ''); } catch (Exception $e) {}
        line(sprintf("Reminder %d: %s -> %s",
            $r['id'], $r['reminder_type'] ?? '', $em
        ));
    }
} catch (Exception $e) { line("Reminders ERROR: " . $e->getMessage()); }

line("\nAll checks done.");

