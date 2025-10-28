<?php
/**
 * PSAU Admission System - File Viewer
 * Handles serving PDF and image files for admin review
 */

// Include required files
require_once '../includes/db_connect.php';
require_once '../includes/admin_auth.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    die('Access denied');
}

// Get file parameters
$application_id = $_GET['app_id'] ?? null;
$file_type = $_GET['type'] ?? null; // 'pdf' or 'image'

if (!$application_id || !$file_type) {
    http_response_code(400);
    die('Missing parameters');
}

try {
    // Get application data
    $stmt = $conn->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        http_response_code(404);
        die('Application not found');
    }
    
    $file_path = null;
    $file_name = null;
    $mime_type = null;
    
    if ($file_type === 'pdf') {
        $file_path = $application['document_file_path'];
        $file_name = $application['pdf_file'];
        $mime_type = 'application/pdf';
    } elseif ($file_type === 'image') {
        $file_path = $application['image_2x2_path'];
        $file_name = $application['image_2x2_name'];
        $mime_type = $application['image_2x2_type'] ?? 'image/jpeg';
    } else {
        http_response_code(400);
        die('Invalid file type');
    }
    
    if (!$file_path) {
        http_response_code(404);
        die('File not found in database');
    }
    
    // Check if file exists in filesystem
    $full_path = '../' . $file_path;
    
    if (file_exists($full_path)) {
        // File exists, serve it directly
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: inline; filename="' . $file_name . '"');
        header('Content-Length: ' . filesize($full_path));
        header('Cache-Control: public, max-age=3600');
        
        readfile($full_path);
        exit;
    } else {
        // File doesn't exist, create a placeholder or show error
        if ($file_type === 'pdf') {
            // Create a placeholder PDF
            createPlaceholderPDF($file_name);
        } elseif ($file_type === 'image') {
            // Create a placeholder image
            createPlaceholderImage($file_name, $mime_type);
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}

/**
 * Create a placeholder PDF
 */
function createPlaceholderPDF($filename) {
    // Simple PDF content
    $pdf_content = "%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj

2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj

3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
/Resources <<
/Font <<
/F1 5 0 R
>>
>>
>>
endobj

4 0 obj
<<
/Length 200
>>
stream
BT
/F1 24 Tf
100 700 Td
(Document Not Available) Tj
0 -50 Td
/F1 12 Tf
(This document could not be found) Tj
0 -30 Td
(File: " . $filename . ") Tj
ET
endstream
endobj

5 0 obj
<<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
endobj

xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000274 00000 n 
0000000525 00000 n 
trailer
<<
/Size 6
/Root 1 0 R
>>
startxref
617
%%EOF";
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf_content));
    
    echo $pdf_content;
}

/**
 * Create a placeholder image
 */
function createPlaceholderImage($filename, $mime_type) {
    // Create a simple placeholder image
    $width = 200;
    $height = 200;
    
    $image = imagecreate($width, $height);
    
    // Set colors
    $bg_color = imagecolorallocate($image, 240, 240, 240);
    $text_color = imagecolorallocate($image, 100, 100, 100);
    $border_color = imagecolorallocate($image, 200, 200, 200);
    
    // Fill background
    imagefill($image, 0, 0, $bg_color);
    
    // Draw border
    imagerectangle($image, 0, 0, $width-1, $height-1, $border_color);
    
    // Add text
    $text = "Image Not Available";
    $font_size = 3;
    $text_width = imagefontwidth($font_size) * strlen($text);
    $text_height = imagefontheight($font_size);
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2;
    
    imagestring($image, $font_size, $x, $y, $text, $text_color);
    
    // Output image
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: inline; filename="' . $filename . '"');
    
    if ($mime_type === 'image/png') {
        imagepng($image);
    } elseif ($mime_type === 'image/jpeg') {
        imagejpeg($image);
    } else {
        imagejpeg($image); // Default to JPEG
    }
    
    imagedestroy($image);
}
?>
