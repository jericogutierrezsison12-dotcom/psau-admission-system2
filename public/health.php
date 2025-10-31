<?php
header('Content-Type: text/plain');
echo "OK\n";
require_once __DIR__ . '/../includes/db_connect.php';
try {
    $stmt = $conn->query('SELECT DATABASE() as db');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'DB=' . ($row['db'] ?? '(unknown)') . "\n";
} catch (Throwable $e) {
    echo 'DB_ERROR=' . $e->getMessage() . "\n";
}
?>


