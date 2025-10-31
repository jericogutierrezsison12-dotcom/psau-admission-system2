<?php
// Secret values loaded by db_connect.php
// NOTE: This file is temporarily tracked to deploy the key. Replace with env var ENCRYPTION_KEY_B64 in production.

// Base64-encoded 32-byte encryption key for PSAUEncryption
// Generated via: openssl rand -base64 32
$ENCRYPTION_KEY_B64 = 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=';

// Token to protect the provisioning endpoint
$PROVISION_TOKEN = 'psau-provision-setup-2025';
?>


