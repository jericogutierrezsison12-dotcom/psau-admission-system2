<?php
/**
 * Update all admin files to include AES encryption
 */

$admin_files = [
    'admin/view_all_applicants.php',
    'admin/review_application.php',
    'admin/verify_applications.php',
    'admin/view_enrolled_students.php',
    'admin/view_logs.php',
    'admin/export_logs.php'
];

foreach ($admin_files as $file) {
    if (file_exists($file)) {
        echo "Updating $file...\n";
        
        $content = file_get_contents($file);
        
        // Add AES encryption include after other includes
        if (strpos($content, "require_once '../includes/aes_encryption.php';") === false) {
            $content = str_replace(
                "require_once '../includes/functions.php';",
                "require_once '../includes/functions.php';\nrequire_once '../includes/aes_encryption.php';",
                $content
            );
            
            file_put_contents($file, $content);
            echo "✓ Added AES encryption include to $file\n";
        } else {
            echo "- AES encryption already included in $file\n";
        }
    } else {
        echo "✗ File not found: $file\n";
    }
}

echo "\n=== Admin Files Update Complete ===\n";
