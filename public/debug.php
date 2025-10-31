<?php
// Temporary debug endpoint - remove after troubleshooting
header('Content-Type: text/plain');
require_once __DIR__ . '/../includes/db_connect.php';

echo "PHP OK\n";
try {
    $stmt = $conn->query('SELECT DATABASE() as db');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Connected DB: " . ($row['db'] ?? '(unknown)') . "\n";

    $tables = [
        'users','admins','applications','documents','courses','course_assignments','course_selections',
        'venues','exam_schedules','exams','enrollment_schedules','enrollment_assignments','entrance_exam_scores',
        'status_history','announcements','faqs','unanswered_questions','enrollment_instructions','required_documents',
        'activity_logs','reminder_logs','application_attempts','login_attempts','admin_login_attempts','otp_requests',
        'otp_codes','otp_attempts','remember_tokens','admin_remember_tokens','ai_document_analysis'
    ];
    foreach ($tables as $t) {
        $q = $conn->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $q->execute([$t]);
        $exists = (int)$q->fetchColumn() > 0;
        echo ($exists ? '[OK]   ' : '[MISS] ') . $t . "\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>


