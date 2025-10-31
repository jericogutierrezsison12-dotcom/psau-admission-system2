<?php
// Auto setup endpoint to run on first deploy. Safe to call multiple times.
// You can set this as a Health Check path in your hosting, or just visit once.

header('Content-Type: text/plain');

require_once __DIR__ . '/../includes/db_connect.php';

try {
    $stmt = $conn->query('SELECT DATABASE() AS db');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $dbName = $row['db'] ?? '(unknown)';
    echo "Connected to DB: {$dbName}\n";

    // Check if core table exists
    $chk = $conn->prepare("SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'");
    $chk->execute();
    $hasUsers = (int)$chk->fetchColumn() > 0;

    if ($hasUsers) {
        echo "Schema already present. No action taken.\n";
        exit(0);
    }

    echo "Running provisioning...\n";
    require __DIR__ . '/../scripts/provision_database.php';
    // provision_database.php prints its own status
} catch (Throwable $e) {
    http_response_code(500);
    echo "Auto setup failed: " . $e->getMessage() . "\n";
    exit(1);
}


