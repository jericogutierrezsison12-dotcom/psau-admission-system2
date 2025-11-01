<?php
// Guard session start - but don't require it for public index
if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

require_once '../includes/db_connect.php';

// If database connection fails, show error instead of redirecting
if (!$conn) {
    // Show user-friendly error page
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Database Connection Error</title></head><body>';
    echo '<h1>Service Temporarily Unavailable</h1>';
    echo '<p>The database connection is currently unavailable. Please try again later.</p>';
    echo '<p><a href="index.php">Return to Home</a></p>';
    echo '</body></html>';
    exit;
}

try {
    // Fetch announcements
    $stmt = $conn->prepare("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 4");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch courses
    $stmt = $conn->prepare("SELECT * FROM courses ORDER BY course_name ASC");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch exam instructions
    $stmt = $conn->prepare("SELECT * FROM exam_instructions ORDER BY created_at DESC LIMIT 1");
    $stmt->execute();
    $exam_instructions = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch enrollment instructions
    $stmt = $conn->prepare("SELECT * FROM enrollment_instructions ORDER BY created_at DESC LIMIT 1");
    $stmt->execute();
    $enrollment_instructions = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch exam required documents
    $stmt = $conn->prepare("SELECT document_name, description FROM exam_required_documents ORDER BY id ASC");
    $stmt->execute();
    $exam_required_documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch enrollment required documents
    $stmt = $conn->prepare("SELECT document_name, description FROM required_documents ORDER BY id ASC");
    $stmt->execute();
    $enrollment_required_documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Include the HTML template
    ob_start();
    include 'html/index.html';
    $template = ob_get_clean();

    // Replace announcement placeholders
    $announcement_html = '';
    foreach ($announcements as $announcement) {
        $announcement_html .= '
        <div class="announcement-card">
            <div class="announcement-header">
                ' . htmlspecialchars($announcement['title']) . '
            </div>
            <div class="announcement-body">
                <div class="announcement-date">
                    <i class="bi bi-calendar-event"></i> 
                    ' . date('F j, Y', strtotime($announcement['created_at'])) . '
                </div>
                <p>' . nl2br(htmlspecialchars($announcement['content'])) . '</p>
            </div>
        </div>';
    }
    $template = str_replace('<!-- Announcements will be dynamically inserted here -->', $announcement_html, $template);

    // Replace course placeholders
    $course_html = '';
    foreach ($courses as $course) {
        $course_html .= '
        <div class="course-card">
            <div class="course-slot-header">' . $course['slots'] . ' slots</div>
            <h4>' . htmlspecialchars($course['course_name']) . ' (' . htmlspecialchars($course['course_code']) . ')</h4>
            <p>' . htmlspecialchars($course['description']) . '</p>
        </div>';
    }
    $template = str_replace('<!-- Courses will be dynamically inserted here -->', $course_html, $template);

    // Replace exam instructions placeholder
    $exam_html = '';
    // Exam Instructions:
    $exam_html .= '<!-- Instructions: -->';
    if ($exam_instructions) {
        $exam_html .= '<div class="mb-3"><strong><i class="bi bi-info-circle"></i> Instructions:</strong><br>' . nl2br(htmlspecialchars($exam_instructions['instruction_text'])) . '</div>';
    } else {
        $exam_html .= '<p>Exam instructions will be posted soon.</p>';
    }
    // Required Documents for Exam
    if (!empty($exam_required_documents)) {
        // Required Documents:
        $exam_html .= '<!-- Required Documents: -->';
        $exam_html .= '<div class="mt-3"><strong><i class="bi bi-folder-check"></i> Required Documents:</strong><ul>';
        foreach ($exam_required_documents as $doc) {
            $exam_html .= '<li><strong>' . htmlspecialchars($doc['document_name']) . ':</strong> ' . htmlspecialchars($doc['description']) . '</li>';
        }
        $exam_html .= '</ul></div>';
    }
    $template = str_replace('<!-- Exam instructions will be dynamically inserted here -->', $exam_html, $template);

    // Replace enrollment instructions placeholder
    $enrollment_html = '';
    // Enrollment Instructions:
    $enrollment_html .= '<!-- Instructions: -->';
    if ($enrollment_instructions) {
        $enrollment_html .= '<div class="mb-3"><strong><i class="bi bi-info-circle"></i> Instructions:</strong><br>' . nl2br(htmlspecialchars($enrollment_instructions['instruction_text'])) . '</div>';
    } else {
        $enrollment_html .= '<p>Enrollment instructions will be posted soon.</p>';
    }
    // Required Documents for Enrollment
    if (!empty($enrollment_required_documents)) {
        // Required Documents:
        $enrollment_html .= '<!-- Required Documents: -->';
        $enrollment_html .= '<div class="mt-3"><strong><i class="bi bi-folder-check"></i> Required Documents:</strong><ul>';
        foreach ($enrollment_required_documents as $doc) {
            $enrollment_html .= '<li><strong>' . htmlspecialchars($doc['document_name']) . ':</strong> ' . htmlspecialchars($doc['description']) . '</li>';
        }
        $enrollment_html .= '</ul></div>';
    }
    $template = str_replace('<!-- Enrollment instructions will be dynamically inserted here -->', $enrollment_html, $template);

    // Output the complete page
    echo $template;

} catch (PDOException $e) {
    // Log the error and display a user-friendly message
    error_log("Database Error: " . $e->getMessage());
    include 'html/error.html'; // You should create this file with a user-friendly error message
}
