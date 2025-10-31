<?php
// Usage:
// 1) Provide your own base64 key (32 bytes when decoded):
//    php scripts/set_encryption_key.php YOUR_BASE64_KEY
// 2) Or run without args to generate a new key and write includes/secret_key.php

declare(strict_types=1);

$baseDir = dirname(__DIR__);
$secretFile = $baseDir . '/includes/secret_key.php';

$keyB64 = $argv[1] ?? '';
if ($keyB64 === '') {
    $raw = random_bytes(32);
    $keyB64 = base64_encode($raw);
    echo "Generated new ENCRYPTION_KEY (base64): \n{$keyB64}\n\n";
}

$raw = base64_decode($keyB64, true);
if ($raw === false || strlen($raw) !== 32) {
    fwrite(STDERR, "Invalid key. Provide a base64-encoded 32-byte key.\n");
    exit(1);
}

$php = <<<'PHP'
<?php
// This file is auto-generated. Do NOT commit publicly.
// Base64-encoded 32-byte encryption key used by includes/encryption.php
$ENCRYPTION_KEY_B64 = %s;
PHP;

$content = sprintf($php, var_export($keyB64, true) . ';');

if (!is_dir($baseDir . '/includes')) {
    mkdir($baseDir . '/includes', 0755, true);
}

file_put_contents($secretFile, $content);
echo "Wrote encryption key to includes/secret_key.php\n";
exit(0);


