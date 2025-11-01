<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../firebase/firebase_email.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$success_message = '';
$error_message = '';
$schedule = null;

// Get schedule ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: schedule_exam.php');
    exit;
}

$schedule_id = intval($_GET['id']);

// Get schedule details
$stmt = $conn->prepare("
    SELECT es.*, v.name as venue_name
    FROM exam_schedules es
    LEFT JOIN venues v ON es.venue_id = v.id
    WHERE es.id = ?
");
$stmt->execute([$schedule_id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    header('Location: schedule_exam.php');
    exit;
}

// Get venues from database
$venues = [];
try {
    $stmt = $conn->query("SELECT * FROM venues WHERE is_active = 1 ORDER BY name");
    $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching venues: " . $e->getMessage());
}

// Get exam instructions from the database
$exam_instructions = [];
try {
    $stmt = $conn->query("
        SELECT instruction_text 
        FROM exam_instructions 
        ORDER BY id
    ");
    $exam_instructions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching exam instructions: " . $e->getMessage());
}

// Get exam required documents from the database
$exam_required_documents = [];
try {
    $stmt = $conn->query("
        SELECT document_name, description 
        FROM exam_required_documents 
        ORDER BY id
    ");
    $exam_required_documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching exam required documents: " . $e->getMessage());
}

// Format instructions and requirements for display
$formatted_instructions = '';
$formatted_requirements = '';

// Build formatted instructions string
foreach ($exam_instructions as $index => $instruction) {
    $formatted_instructions .= ($index + 1) . ". {$instruction['instruction_text']}\n";
}

// Build formatted requirements string
foreach ($exam_required_documents as $index => $document) {
    $formatted_requirements .= ($index + 1) . ". {$document['document_name']}\n";
    if (!empty($document['description'])) {
        $formatted_requirements .= "   - {$document['description']}\n";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_date = $_POST['exam_date'];
    $exam_time = $_POST['exam_time'];
    $exam_time_end = $_POST['exam_time_end'];
    $venue_id = (int) $_POST['venue_id'];
    $capacity = $_POST['capacity'];
    $instructions = $_POST['instructions'] ?? '';
    $requirements = $_POST['requirements'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    // Validate data
    $errors = [];
    
    if (empty($reason)) {
        $errors[] = "Reason for change is required";
    }
    
    // Validate date and times
    if (strtotime($exam_date.' '.$exam_time) < strtotime(date('Y-m-d H:i:s'))) {
        $errors[] = "Exam date/time cannot be in the past";
    }
    
    // Validate end time is after start time
    if (strtotime($exam_time_end) <= strtotime($exam_time)) {
        $errors[] = "End time must be after start time";
    }
    
    // Validate capacity
    if (!is_numeric($capacity)) {
        $errors[] = "Number of slots must be a number";
    } else {
        $capacity = (int)$capacity;
        if ($capacity < 1) {
            $errors[] = "Number of slots must be at least 1";
        }
    }
    
    // Validate venue ID
    try {
        $stmt = $conn->prepare("SELECT * FROM venues WHERE id = ? AND is_active = 1");
        $stmt->execute([$venue_id]);
        $venue = $stmt->fetch();
        
        if (!$venue) {
            $errors[] = "Invalid venue selected";
        } else if ($capacity > $venue['capacity']) {
            $errors[] = "Capacity cannot exceed venue's maximum capacity of {$venue['capacity']}";
        }
    } catch (PDOException $e) {
        $errors[] = "Error validating venue";
    }

    // Check if new capacity can accommodate already assigned students
    if ($capacity && $schedule['current_count'] > $capacity) {
        $errors[] = "New capacity cannot be less than the current number of assigned students ({$schedule['current_count']})";
    }

    // Check for scheduling conflicts (excluding current schedule)
    if ($exam_date && $exam_time && $exam_time_end && $venue_id && empty($errors)) {
        try {
            // Check for venue conflicts (same venue, same date, overlapping times, excluding current schedule)
            $stmt = $conn->prepare("
                SELECT COUNT(*) as conflict_count,
                       GROUP_CONCAT(CONCAT(exam_time, '-', exam_time_end) SEPARATOR ', ') as conflicting_times
                FROM exam_schedules 
                WHERE venue_id = ? 
                AND exam_date = ? 
                AND is_active = 1
                AND id != ?
                AND (
                    (exam_time < ? AND exam_time_end > ?) OR
                    (exam_time < ? AND exam_time_end > ?) OR
                    (exam_time >= ? AND exam_time_end <= ?) OR
                    (exam_time <= ? AND exam_time_end >= ?)
                )
            ");
            $stmt->execute([
                $venue_id,
                $exam_date,
                $schedule_id,                // Exclude current schedule
                $exam_time_end, $exam_time,  // New schedule overlaps with existing
                $exam_time, $exam_time_end,  // Existing schedule overlaps with new
                $exam_time, $exam_time_end,  // New schedule is completely within existing
                $exam_time, $exam_time_end   // Existing schedule is completely within new
            ]);
            $venue_conflict = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($venue_conflict['conflict_count'] > 0) {
                $errors[] = "Venue conflict detected! There are already {$venue_conflict['conflict_count']} exam schedule(s) at this venue on {$exam_date} with overlapping times: {$venue_conflict['conflicting_times']}";
            }

        } catch (PDOException $e) {
            $errors[] = 'Error checking for scheduling conflicts: ' . $e->getMessage();
        }
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Get venue name for logs and emails
            $venue_name = $venue['name'];
            
            // Update exam schedule
            $stmt = $conn->prepare("
                UPDATE exam_schedules 
                SET exam_date = ?, exam_time = ?, exam_time_end = ?, venue = ?, venue_id = ?, 
                    capacity = ?, instructions = ?, requirements = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $exam_date, $exam_time, $exam_time_end, $venue_name, $venue_id, 
                $capacity, $instructions, $requirements, $schedule_id
            ]);

            // Get all assigned applicants for this schedule
            $stmt = $conn->prepare("
                SELECT e.application_id, u.first_name, u.last_name, u.email, u.control_number
                FROM exams e
                JOIN applications a ON e.application_id = a.id
                JOIN users u ON a.user_id = u.id
                WHERE e.exam_schedule_id = ?
            ");
            $stmt->execute([$schedule_id]);
            $assigned_applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decrypt user data
            require_once '../includes/encryption.php';
            foreach ($assigned_applicants as &$applicant) {
                $applicant['first_name'] = safeDecryptField($applicant['first_name'] ?? '', 'users', 'first_name');
                $applicant['last_name'] = safeDecryptField($applicant['last_name'] ?? '', 'users', 'last_name');
                $applicant['email'] = safeDecryptField($applicant['email'] ?? '', 'users', 'email');
            }
            unset($applicant);

            // Send email notifications to all assigned applicants
            foreach ($assigned_applicants as $applicant) {
                $schedule_data = [
                    'old_date' => $schedule['exam_date'],
                    'old_time' => $schedule['exam_time'],
                    'old_venue' => $schedule['venue'],
                    'new_date' => $exam_date,
                    'new_time' => $exam_time,
                    'new_venue' => $venue_name,
                    'reason' => $reason,
                    'instructions' => $instructions,
                    'requirements' => $requirements
                ];
                
                send_exam_schedule_update_email($applicant, $schedule_data);
            }

            // Log the change
            $change_details = "Exam schedule updated: Date: {$schedule['exam_date']} → {$exam_date}, " .
                             "Time: {$schedule['exam_time']} → {$exam_time}, " .
                             "Venue: {$schedule['venue']} → {$venue_name}, " .
                             "Capacity: {$schedule['capacity']} → {$capacity}. " .
                             "Reason: {$reason}";
            
            log_admin_activity($conn, $_SESSION['admin_id'], 'exam_schedule_updated', $change_details);

            $conn->commit();
            $_SESSION['message'] = 'Exam schedule updated successfully! Email notifications sent to ' . count($assigned_applicants) . ' applicants.';
            $_SESSION['message_type'] = 'success';
            header('Location: schedule_exam.php');
            exit;

        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = 'Error updating schedule: ' . $e->getMessage();
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Include the HTML template
include 'html/edit_exam_schedule.html';
?>
