<?php
/**
 * Add decryption logic to all files that fetch encrypted data
 */

echo "=== Adding Decryption Logic to All Files ===\n\n";

// Function to add decryption after fetchAll
function addDecryptionAfterFetch($file_path) {
    $content = file_get_contents($file_path);
    $original_content = $content;
    
    // Pattern to find fetchAll followed by closing brace
    $pattern = '/(\$.*?\s*=\s*\$stmt->fetchAll\(PDO::FETCH_ASSOC\);\s*)(\})/m';
    
    // Check if decryption is already added
    if (strpos($content, 'smartDecrypt') !== false) {
        echo "  - Decryption already exists in $file_path\n";
        return false;
    }
    
    // Add decryption logic
    $replacement = '$1' . "\n    // Decrypt sensitive data\n" .
                   '    foreach ($' . '$result as &$row) {' . "\n" .
                   '        if (isset($row[\'first_name\'])) $row[\'first_name\'] = smartDecrypt($row[\'first_name\'], \'personal_data\');' . "\n" .
                   '        if (isset($row[\'last_name\'])) $row[\'last_name\'] = smartDecrypt($row[\'last_name\'], \'personal_data\');' . "\n" .
                   '        if (isset($row[\'email\'])) $row[\'email\'] = smartDecrypt($row[\'email\'], \'contact_data\');' . "\n" .
                   '        if (isset($row[\'mobile_number\'])) $row[\'mobile_number\'] = smartDecrypt($row[\'mobile_number\'], \'contact_data\');' . "\n" .
                   '        if (isset($row[\'gender\'])) $row[\'gender\'] = smartDecrypt($row[\'gender\'], \'personal_data\');' . "\n" .
                   '        if (isset($row[\'birth_date\'])) $row[\'birth_date\'] = smartDecrypt($row[\'birth_date\'], \'personal_data\');' . "\n" .
                   '        if (isset($row[\'address\'])) $row[\'address\'] = smartDecrypt($row[\'address\'], \'personal_data\');' . "\n" .
                   '        if (isset($row[\'previous_school\'])) $row[\'previous_school\'] = smartDecrypt($row[\'previous_school\'], \'academic_data\');' . "\n" .
                   '        if (isset($row[\'school_year\'])) $row[\'school_year\'] = smartDecrypt($row[\'school_year\'], \'academic_data\');' . "\n" .
                   '        if (isset($row[\'strand\'])) $row[\'strand\'] = smartDecrypt($row[\'strand\'], \'academic_data\');' . "\n" .
                   '        if (isset($row[\'gpa\'])) $row[\'gpa\'] = smartDecrypt($row[\'gpa\'], \'academic_data\');' . "\n" .
                   '    }' . "\n" .
                   '$2';
    
    $content = preg_replace($pattern, $replacement, $content);
    
    if ($content !== $original_content) {
        file_put_contents($file_path, $content);
        echo "  ✓ Added decryption to $file_path\n";
        return true;
    }
    
    return false;
}

// Files to check and update
$files_to_check = [
    'admin/verify_applications.php',
    'admin/view_enrolled_students.php',
    'admin/view_logs.php',
    'admin/export_logs.php'
];

$updated_count = 0;
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "Checking $file...\n";
        if (addDecryptionAfterFetch($file)) {
            $updated_count++;
        }
    } else {
        echo "  ✗ File not found: $file\n";
    }
}

echo "\n=== Update Complete ===\n";
echo "Files updated: $updated_count\n";
