<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../firebase/firebase_email.php';
require_once '../includes/encryption.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$success_message = '';
$error_message = '';
$schedule = null;

// Get schedule ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: enrollment_schedule.php');
    exit;
}

$schedule_id = intval($_GET['id']);

// Get schedule details
$stmt = $conn->prepare("
    SELECT es.*, c.course_code, c.course_name, v.name as venue_name
    FROM enrollment_schedules es
    LEFT JOIN courses c ON es.course_id = c.id
    LEFT JOIN venues v ON es.venue_id = v.id
    WHERE es.id = ?
");
$stmt->execute([$schedule_id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    header('Location: enrollment_schedule.php');
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

// Get all courses for dropdown
$courses = [];
try {
    $stmt = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
}

// Get default instructions and requirements for auto-fill
$default_instructions = '';
$default_requirements = '';
try {
    // Get instructions
    $stmt = $conn->query("SELECT instruction_text FROM enrollment_instructions ORDER BY id");
    $instructions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $formatted_instructions = '';
    foreach ($instructions as $index => $instruction) {
        $formatted_instructions .= ($index + 1) . ". {$instruction['instruction_text']}\n";
    }
    $default_instructions = $formatted_instructions;

    // Get requirements
    $stmt = $conn->query("SELECT document_name, description FROM required_documents ORDER BY id");
    $requirements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $formatted_requirements = '';
    foreach ($requirements as $index => $requirement) {
        $formatted_requirements .= ($index + 1) . ". {$requirement['document_name']}\n";
        if (!empty($requirement['description'])) {
            $formatted_requirements .= "   - {$requirement['description']}\n";
        }
    }
    $default_requirements = $formatted_requirements;
} catch (PDOException $e) {
    error_log('Error fetching default instructions/requirements: ' . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment_date = trim($_POST['enrollment_date'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $venue_id = trim($_POST['venue_id'] ?? '');
    $capacity = trim($_POST['capacity'] ?? '');
    $course_id = trim($_POST['course_id'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    $is_auto_assign = isset($_POST['is_auto_assign']) ? 1 : 0;

    // Validate input
    $errors = [];
    if (!$enrollment_date) $errors[] = 'Enrollment date is required.';
    if (!$start_time) $errors[] = 'Start time is required.';
    if (!$end_time) $errors[] = 'End time is required.';
    if (!$venue_id) $errors[] = 'Venue is required.';
    if (!$capacity || !is_numeric($capacity) || $capacity < 1) $errors[] = 'Capacity must be at least 1.';
    if (!$course_id) $errors[] = 'Course is required.';
    if (!$instructions) $errors[] = 'Instructions are required.';
    if (!$requirements) $errors[] = 'Required documents are required.';
    if (!$reason) $errors[] = 'Reason for change is required.';

    // Additional validation for capacity against venue capacity
    if ($venue_id && $capacity) {
        $stmt = $conn->prepare('SELECT capacity FROM venues WHERE id = ?');
        $stmt->execute([$venue_id]);
        $venue_capacity = $stmt->fetchColumn();
        if ($capacity > $venue_capacity) {
            $errors[] = "Capacity cannot exceed venue's maximum capacity of $venue_capacity.";
        }
    }

    // Check if new capacity can accommodate already assigned students
    if ($capacity && $schedule['current_count'] > $capacity) {
        $errors[] = "New capacity cannot be less than the current number of assigned students ({$schedule['current_count']}).";
    }

    // Validate time format and logic
    if ($start_time && $end_time) {
        if (strtotime($end_time) <= strtotime($start_time)) {
            $errors[] = 'End time must be after start time.';
        }
    }

    // Check for scheduling conflicts (excluding current schedule)
    if ($enrollment_date && $start_time && $end_time && $venue_id && empty($errors)) {
        try {
            // Check for venue conflicts (same venue, same date, overlapping times, excluding current schedule)
            $stmt = $conn->prepare("
                SELECT COUNT(*) as conflict_count, 
                       GROUP_CONCAT(CONCAT(c.course_code, ' - ', c.course_name) SEPARATOR ', ') as conflicting_courses
                FROM enrollment_schedules es
                JOIN courses c ON es.course_id = c.id
                WHERE es.venue_id = ? 
                AND es.enrollment_date = ? 
                AND es.is_active = 1
                AND es.id != ?
                AND (
                    (es.start_time < ? AND es.end_time > ?) OR
                    (es.start_time < ? AND es.end_time > ?) OR
                    (es.start_time >= ? AND es.end_time <= ?) OR
                    (es.start_time <= ? AND es.end_time >= ?)
                )
            ");
            $stmt->execute([
                $venue_id,
                $enrollment_date,
                $schedule_id,                // Exclude current schedule
                $end_time, $start_time,      // New schedule overlaps with existing
                $start_time, $end_time,      // Existing schedule overlaps with new
                $start_time, $end_time,      // New schedule is completely within existing
                $start_time, $end_time       // Existing schedule is completely within new
            ]);
            $venue_conflict = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($venue_conflict['conflict_count'] > 0) {
                $errors[] = "Venue conflict detected! There are already {$venue_conflict['conflict_count']} enrollment schedule(s) at this venue on {$enrollment_date} with overlapping times. Conflicting courses: {$venue_conflict['conflicting_courses']}";
            }

            // Check for course conflicts (same course, same date, any time overlap, excluding current schedule)
            $stmt = $conn->prepare("
                SELECT COUNT(*) as conflict_count,
                       GROUP_CONCAT(CONCAT(es.start_time, '-', es.end_time) SEPARATOR ', ') as conflicting_times
                FROM enrollment_schedules es
                WHERE es.course_id = ? 
                AND es.enrollment_date = ? 
                AND es.is_active = 1
                AND es.id != ?
                AND (
                    (es.start_time < ? AND es.end_time > ?) OR
                    (es.start_time < ? AND es.end_time > ?) OR
                    (es.start_time >= ? AND es.end_time <= ?) OR
                    (es.start_time <= ? AND es.end_time >= ?)
                )
            ");
            $stmt->execute([
                $course_id,
                $enrollment_date,
                $schedule_id,                // Exclude current schedule
                $end_time, $start_time,      // New schedule overlaps with existing
                $start_time, $end_time,      // Existing schedule overlaps with new
                $start_time, $end_time,      // New schedule is completely within existing
                $start_time, $end_time       // Existing schedule is completely within new
            ]);
            $course_conflict = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($course_conflict['conflict_count'] > 0) {
                $errors[] = "Course conflict detected! There are already {$course_conflict['conflict_count']} enrollment schedule(s) for this course on {$enrollment_date} with overlapping times: {$course_conflict['conflicting_times']}";
            }

        } catch (PDOException $e) {
            $errors[] = 'Error checking for scheduling conflicts: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Get venue name
            $stmt = $conn->prepare('SELECT name FROM venues WHERE id = ?');
            $stmt->execute([$venue_id]);
            $venue_name = $stmt->fetchColumn();

            // Update enrollment schedule
            $stmt = $conn->prepare('
                UPDATE enrollment_schedules 
                SET enrollment_date = ?, start_time = ?, end_time = ?, venue = ?, venue_id = ?, 
                    capacity = ?, instructions = ?, requirements = ?, is_auto_assign = ?, updated_at = NOW()
                WHERE id = ?
            ');
            $stmt->execute([
                $enrollment_date, $start_time, $end_time, $venue_name, $venue_id,
                $capacity, $instructions, $requirements, $is_auto_assign, $schedule_id
            ]);

            // Get all assigned students for this schedule
            $stmt = $conn->prepare('
                SELECT ea.student_id, u.first_name, u.last_name, u.email, u.control_number
                FROM enrollment_assignments ea
                JOIN users u ON ea.student_id = u.id
                WHERE ea.schedule_id = ?
            ');
            $stmt->execute([$schedule_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $assigned_students = [];
            foreach ($rows as $r) {
                try {
                    $r['first_name'] = dec_personal($r['first_name'] ?? '');
                    $r['last_name'] = dec_personal($r['last_name'] ?? '');
                    $r['email'] = dec_contact($r['email'] ?? '');
                } catch (Exception $e) {}
                $assigned_students[] = $r;
            }

            // Send email notifications to all assigned students
            foreach ($assigned_students as $student) {
                $schedule_data = [
                    'old_date' => $schedule['enrollment_date'],
                    'old_time' => $schedule['start_time'],
                    'old_venue' => $schedule['venue'],
                    'new_date' => $enrollment_date,
                    'new_time' => $start_time,
                    'new_venue' => $venue_name,
                    'course_code' => $schedule['course_code'],
                    'course_name' => $schedule['course_name'],
                    'reason' => $reason,
                    'instructions' => $instructions,
                    'requirements' => $requirements
                ];
                
                send_enrollment_schedule_update_email($student, $schedule_data);
            }

            // Log the change
            $change_details = "Schedule updated: Date: {$schedule['enrollment_date']} → {$enrollment_date}, " .
                             "Time: {$schedule['start_time']} → {$start_time}, " .
                             "Venue: {$schedule['venue']} → {$venue_name}, " .
                             "Capacity: {$schedule['capacity']} → {$capacity}. " .
                             "Reason: {$reason}";
            
            log_admin_activity($conn, $_SESSION['admin_id'], 'enrollment_schedule_updated', $change_details);

            $conn->commit();
            $_SESSION['admin_message'] = 'Enrollment schedule updated successfully! Email notifications sent to ' . count($assigned_students) . ' students.';
            $_SESSION['admin_message_type'] = 'success';
            header('Location: enrollment_schedule.php');
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
include 'html/edit_enrollment_schedule.html';
?>
