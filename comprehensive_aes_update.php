<?php
/**
 * Comprehensive AES Encryption Update Script
 * Updates all files to use AES encryption properly
 */

echo "=== Comprehensive AES Encryption Update ===\n\n";

// Files that need AES encryption include
$files_to_update = [
    // Public files
    'public/login.php',
    'public/dashboard.php',
    'public/application_progress.php',
    'public/application_submitted.php',
    'public/course_selection.php',
    'public/registration_success.php',
    
    // Admin files (already updated)
    'admin/view_all_applicants.php',
    'admin/review_application.php',
    'admin/verify_applications.php',
    'admin/view_enrolled_students.php',
    'admin/view_logs.php',
    'admin/export_logs.php',
    'admin/manual_score_entry.php',
    'admin/bulk_score_upload.php',
    'admin/course_assignment.php',
    'admin/course_management.php',
    'admin/schedule_exam.php',
    'admin/auto_schedule_exam.php',
    'admin/enrollment_schedule.php',
    'admin/enrollment_completion.php',
    'admin/manage_announcement.php',
    'admin/manage_content.php',
    'admin/manage_faqs.php',
    'admin/manage_venues.php',
    'admin/send_reminder.php',
    'admin/automated_reminders.php',
    'admin/clear_attempts.php',
    'admin/generate_score_template.php',
    'admin/get_exam_applicants.php',
    'admin/get_recent_uploads.php',
    'admin/view_courses.php',
    'admin/view_all_users.php'
];

$updated_count = 0;
$skipped_count = 0;

foreach ($files_to_update as $file) {
    if (file_exists($file)) {
        echo "Checking $file...\n";
        
        $content = file_get_contents($file);
        
        // Add AES encryption include if not present
        if (strpos($content, "require_once '../includes/aes_encryption.php';") === false && 
            strpos($content, "require_once 'aes_encryption.php';") === false) {
            
            // Determine the correct path based on file location
            if (strpos($file, 'public/') === 0) {
                $include_path = "require_once '../includes/aes_encryption.php';";
            } elseif (strpos($file, 'admin/') === 0) {
                $include_path = "require_once '../includes/aes_encryption.php';";
            } else {
                $include_path = "require_once 'includes/aes_encryption.php';";
            }
            
            // Add after other includes
            if (strpos($content, "require_once '../includes/session_checker.php';") !== false) {
                $content = str_replace(
                    "require_once '../includes/session_checker.php';",
                    "require_once '../includes/session_checker.php';\n" . $include_path,
                    $content
                );
            } elseif (strpos($content, "require_once '../includes/db_connect.php';") !== false) {
                $content = str_replace(
                    "require_once '../includes/db_connect.php';",
                    "require_once '../includes/db_connect.php';\n" . $include_path,
                    $content
                );
            } else {
                // Add at the beginning after opening PHP tag
                $content = preg_replace(
                    '/^<\?php\s*\n/',
                    "<?php\n" . $include_path . "\n",
                    $content
                );
            }
            
            file_put_contents($file, $content);
            echo "✓ Added AES encryption include to $file\n";
            $updated_count++;
        } else {
            echo "- AES encryption already included in $file\n";
            $skipped_count++;
        }
    } else {
        echo "✗ File not found: $file\n";
    }
}

echo "\n=== Update Complete ===\n";
echo "Files updated: $updated_count\n";
echo "Files skipped: $skipped_count\n";
echo "Total files processed: " . count($files_to_update) . "\n\n";

echo "Next steps:\n";
echo "1. Generate AES encryption key: php generate_aes_key.php\n";
echo "2. Add the key to your .env file\n";
echo "3. Test the application\n";
echo "4. Commit and push changes\n";
