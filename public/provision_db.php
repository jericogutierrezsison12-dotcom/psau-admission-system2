<?php
// Protected DB provisioning endpoint
// Usage: /provision_db.php?token=YOUR_TOKEN
// Set PROVISION_TOKEN in .env or includes/secret_key.php (as $PROVISION_TOKEN) before using.

require_once __DIR__ . '/../includes/db_connect.php';

// Load token from env or secret file
$token_env = $_ENV['PROVISION_TOKEN'] ?? getenv('PROVISION_TOKEN') ?? '';
if (file_exists(__DIR__ . '/../includes/secret_key.php')) {
    include_once __DIR__ . '/../includes/secret_key.php';
    if (empty($token_env) && !empty($PROVISION_TOKEN)) {
        $token_env = $PROVISION_TOKEN;
    }
}

$token = $_GET['token'] ?? '';
header('Content-Type: text/plain');
if (empty($token_env) || $token !== $token_env) {
    http_response_code(403);
    echo "Forbidden: invalid token";
    exit;
}

// Minimal smoke test: ensure CREATE privilege and DB selected
try {
    $stmt = $conn->query('SELECT DATABASE() as db');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Connected to DB: " . ($row['db'] ?? '(unknown)') . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Connection check failed: " . $e->getMessage() . "\n";
    exit;
}

// Include the full provision script (it runs immediately and echoes status)
try {
    require __DIR__ . '/../scripts/provision_database.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo "Provisioning failed: " . $e->getMessage() . "\n";
    exit;
}
// Done
?>


