<?php
/**
 * Root Index File
 * Redirects to the public directory and runs cleanup on first deploy
 */

// Run cleanup on first deploy in production
if (isset($_ENV['RENDER']) || isset($_ENV['RAILWAY_ENVIRONMENT'])) {
    // Check if cleanup has already been run
    $cleanup_flag = __DIR__ . '/.cleanup_completed';
    
    if (!file_exists($cleanup_flag)) {
        // Run cleanup script
        include_once 'auto_cleanup_on_deploy.php';
        
        // Mark cleanup as completed
        file_put_contents($cleanup_flag, date('Y-m-d H:i:s'));
    }
}

// Redirect to public directory
header('Location: public/');
exit;
