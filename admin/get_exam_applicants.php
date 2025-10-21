<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Validate input
if (!isset($_GET['schedule_id']) || !is_numeric($_GET['schedule_id'])) {
    echo '<div class="alert alert-danger">Invalid request. Schedule ID is required.</div>';
    exit;
}

$schedule_id = $_GET['schedule_id'];

try {
    // Get exam schedule details
    $stmt = $conn->prepare("
        SELECT * FROM exam_schedules WHERE id = ?
    ");
    $stmt->execute([$schedule_id]);
    $schedule = $stmt->fetch();
    
    if (!$schedule) {
        echo '<div class="alert alert-danger">Exam schedule not found.</div>';
        exit;
    }
    
    // Get applicants assigned to this schedule
    $stmt = $conn->prepare("
        SELECT e.*, a.status, u.control_number, u.first_name, u.last_name, u.email, u.mobile_number
        FROM exams e
        JOIN applications a ON e.application_id = a.id
        JOIN users u ON a.user_id = u.id
        WHERE e.exam_schedule_id = ?
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute([$schedule_id]);
    $applicants = $stmt->fetchAll();
    
    // Display header info
    echo '<div class="mb-3">';
    echo '<h5>Exam Details:</h5>';
    echo '<p><strong>Date:</strong> ' . date('F j, Y', strtotime($schedule['exam_date'])) . '</p>';
    echo '<p><strong>Time:</strong> ' . date('h:i A', strtotime($schedule['exam_time'])) . '</p>';
    echo '<p><strong>Venue:</strong> ' . htmlspecialchars($schedule['venue']) . '</p>';
    echo '<p><strong>Capacity:</strong> ' . $schedule['current_count'] . ' / ' . $schedule['capacity'] . '</p>';
    echo '</div>';
    
    if (empty($applicants)) {
        echo '<div class="alert alert-info">No applicants have been assigned to this exam schedule yet.</div>';
    } else {
        echo '<h5>Registered Applicants (' . count($applicants) . '):</h5>';
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Control Number</th>';
        echo '<th>Name</th>';
        echo '<th>Contact</th>';
        echo '<th>Status</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($applicants as $applicant) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($applicant['control_number']) . '</td>';
            echo '<td>' . htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']) . '</td>';
            echo '<td>';
            echo htmlspecialchars($applicant['email']) . '<br>';
            echo htmlspecialchars($applicant['mobile_number']);
            echo '</td>';
            echo '<td>' . htmlspecialchars($applicant['status']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
}
?> 