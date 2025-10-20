<?php
/**
 * Deployment Script for InfinityFree Hosting
 * This script helps prepare the application for deployment to InfinityFree
 */

echo "PSAU Admission System - InfinityFree Deployment Script\n";
echo "====================================================\n\n";

// Check if we're in the right directory
if (!file_exists('composer.json')) {
    die("Error: composer.json not found. Please run this script from the project root directory.\n");
}

echo "1. Checking project structure...\n";

// Check required directories
$required_dirs = ['admin', 'public', 'includes', 'database', 'uploads'];
foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        echo "   ❌ Missing directory: $dir\n";
    } else {
        echo "   ✅ Directory exists: $dir\n";
    }
}

echo "\n2. Checking required files...\n";

// Check required files
$required_files = [
    'includes/db_connect.php',
    'database/psau_admission.sql',
    'config_production.php',
    'composer.json'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        echo "   ❌ Missing file: $file\n";
    } else {
        echo "   ✅ File exists: $file\n";
    }
}

echo "\n3. Checking uploads directory permissions...\n";
if (is_writable('uploads')) {
    echo "   ✅ Uploads directory is writable\n";
} else {
    echo "   ⚠️  Uploads directory is not writable - you may need to set permissions to 755\n";
}

echo "\n4. Checking vendor directory...\n";
if (is_dir('vendor')) {
    echo "   ✅ Vendor directory exists\n";
} else {
    echo "   ⚠️  Vendor directory not found - you need to run 'composer install --no-dev'\n";
}

echo "\n5. Deployment Checklist:\n";
echo "   □ Update database credentials in includes/db_connect.php\n";
echo "   □ Update Firebase configuration in firebase/config.php\n";
echo "   □ Update BASE_URL in config_production.php\n";
echo "   □ Upload all files to InfinityFree htdocs directory\n";
echo "   □ Import database schema from database/psau_admission.sql\n";
echo "   □ Set uploads directory permissions to 755\n";
echo "   □ Test the application\n";

echo "\n6. InfinityFree Specific Notes:\n";
echo "   • Python/OCR functionality may not work on shared hosting\n";
echo "   • File upload limits: Check InfinityFree's limits\n";
echo "   • Cron jobs: Limited support\n";
echo "   • PHP execution time: May be limited\n";

echo "\n7. Security Recommendations:\n";
echo "   • Remove or secure any test/development files\n";
echo "   • Ensure sensitive config files are not web-accessible\n";
echo "   • Use strong database passwords\n";
echo "   • Enable HTTPS if using custom domain\n";

echo "\nDeployment script completed!\n";
echo "Please follow the checklist above before going live.\n";
?>
