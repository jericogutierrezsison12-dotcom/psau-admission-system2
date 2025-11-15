<?php
/**
 * Database Connection Test Script
 * Tests connection to Railway MySQL database
 */

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Get database credentials from environment variables or defaults
$host = $_ENV['DB_HOST'] ?? $_ENV['MYSQL_HOST'] ?? 'ballast.proxy.rlwy.net';
$dbname = $_ENV['DB_NAME'] ?? $_ENV['MYSQL_DATABASE'] ?? 'railway';
$username = $_ENV['DB_USER'] ?? $_ENV['MYSQL_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? $_ENV['MYSQL_PASSWORD'] ?? 'dVBlhdVopIpMhmYnxAsyldOkxaiXTHLi';
$port = $_ENV['DB_PORT'] ?? $_ENV['MYSQL_PORT'] ?? 10649;

echo "<h1>Database Connection Test</h1>";
echo "<h2>Connection Details:</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";
echo "<tr><td>Host</td><td>" . htmlspecialchars($host) . "</td></tr>";
echo "<tr><td>Port</td><td>" . htmlspecialchars($port) . "</td></tr>";
echo "<tr><td>Database</td><td>" . htmlspecialchars($dbname) . "</td></tr>";
echo "<tr><td>Username</td><td>" . htmlspecialchars($username) . "</td></tr>";
echo "<tr><td>Password</td><td>" . (strlen($password) > 0 ? str_repeat('*', min(10, strlen($password))) : '(empty)') . "</td></tr>";
echo "</table>";

echo "<h2>Connection Test:</h2>";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    echo "<p style='color: green; font-weight: bold;'>✅ Connection successful!</p>";
    
    // Test query
    $stmt = $conn->query("SELECT VERSION() as version, DATABASE() as current_db, NOW() as server_time");
    $result = $stmt->fetch();
    
    echo "<h3>Database Information:</h3>";
    echo "<ul>";
    echo "<li><strong>MySQL Version:</strong> " . htmlspecialchars($result['version']) . "</li>";
    echo "<li><strong>Current Database:</strong> " . htmlspecialchars($result['current_db']) . "</li>";
    echo "<li><strong>Server Time:</strong> " . htmlspecialchars($result['server_time']) . "</li>";
    echo "</ul>";
    
    // Check if tables exist
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Tables Found:</h3>";
    if (count($tables) > 0) {
        echo "<p style='color: green;'>✅ Found " . count($tables) . " table(s):</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ No tables found in database. Database might be empty.</p>";
    }
    
    // Test a simple query on users table if it exists
    if (in_array('users', $tables)) {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "<p><strong>Users table:</strong> " . $result['count'] . " record(s)</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Connection failed!</p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Check if Railway database is running</li>";
    echo "<li>Verify database credentials in Railway dashboard</li>";
    echo "<li>Check if database name is correct</li>";
    echo "<li>Verify network connectivity</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><small>Test completed at: " . date('Y-m-d H:i:s') . "</small></p>";
?>

