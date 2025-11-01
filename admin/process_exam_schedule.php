<?php
/**
 * Process Exam Schedule
 * Handles the creation of exam schedules and assignment of applicants
 */

require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../firebase/firebase_email.php';

// Check if admin is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

// Process exam schedule creation
if (isset($_POST['action']) && $_POST['action'] === 'create_schedule') {
    // Validate required fields
    $required_fields = ['exam_date', 'exam_time', 'exam_time_end', 'venue_id', 'capacity'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $response['message'] = "Please fill in all required fields: " . implode(', ', $missing_fields);
    } else {
        // Get form data
        $exam_date = $_POST['exam_date'];
        $exam_time = $_POST['exam_time'];
        $exam_time_end = $_POST['exam_time_end'];
        $venue_id = (int) $_POST['venue_id'];
        $capacity = $_POST['capacity'];
        $instructions = $_POST['instructions'] ?? '';
        $requirements = $_POST['requirements'] ?? '';
        
        // Validate data
        $errors = [];
        
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
            $stmt = $conn->prepare("SELECT name FROM venues WHERE id = ? AND is_active = 1");
            $stmt->execute([$venue_id]);
            $venue = $stmt->fetch();
            
            if (!$venue) {
                $errors[] = "Selected venue is not valid or active";
            } else {
                // Check if there are any overlapping schedules for this venue
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as count,
                           GROUP_CONCAT(CONCAT(exam_time, '-', exam_time_end) SEPARATOR ', ') as conflicting_times
                    FROM exam_schedules 
                    WHERE venue_id = ? 
                    AND exam_date = ? 
                    AND is_active = 1
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
                    $exam_time_end, $exam_time,      // New schedule overlaps with existing
                    $exam_time, $exam_time_end,      // Existing schedule overlaps with new
                    $exam_time, $exam_time_end,      // New schedule is completely within existing
                    $exam_time, $exam_time_end       // Existing schedule is completely within new
                ]);
                $overlapping = $stmt->fetch();
                
                if ($overlapping['count'] > 0) {
                    $errors[] = "Venue conflict detected! There are already {$overlapping['count']} exam schedule(s) at this venue on {$exam_date} with overlapping times: {$overlapping['conflicting_times']}";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Error validating venue: " . $e->getMessage();
        }
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Get venue name for logs and emails
                $venue_name = $venue['name'];
                
                $stmt = $conn->prepare("
                    INSERT INTO exam_schedules 
                    (exam_date, exam_time, exam_time_end, venue, venue_id, capacity, current_count, instructions, requirements, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)
                ");
                $stmt->execute([
                    $exam_date, $exam_time, $exam_time_end, $venue_name, $venue_id, $capacity, $instructions, $requirements, $_SESSION['admin_id']
                ]);
                $schedule_id = $conn->lastInsertId();
                // Auto-assign earliest verified applicants not already scheduled for a future exam
                $now = date('Y-m-d H:i:s');
                
                // Calculate the date after tomorrow (2 days from now) for the scheduling rule
                $future_date = date('Y-m-d', strtotime('+2 days'));
                
                // Get exam date for scheduling rule check
                $exam_date_timestamp = strtotime($exam_date);
                $today_timestamp = strtotime(date('Y-m-d'));
                $tomorrow_timestamp = strtotime('+1 day', $today_timestamp);
                
                // Only auto-assign if exam date is beyond tomorrow
                if ($exam_date_timestamp > $tomorrow_timestamp) {
                    $limit_value = intval($capacity);
                    $stmt = $conn->prepare("
                        SELECT a.id, a.user_id, u.first_name, u.last_name, u.email, u.mobile_number
                        FROM applications a
                        JOIN users u ON a.user_id = u.id
                        LEFT JOIN exams e ON a.id = e.application_id
                        WHERE a.status = 'Verified' 
                        AND (e.id IS NULL OR e.exam_date >= ?)
                        GROUP BY a.id
                        HAVING COUNT(e.id) = 0
                        ORDER BY a.verified_at ASC, a.created_at ASC
                        LIMIT ?
                    ");
                    $stmt->bindParam(1, $exam_date, PDO::PARAM_STR);
                    $stmt->bindParam(2, $limit_value, PDO::PARAM_INT);
                    $stmt->execute();
                    $auto_applicants = $stmt->fetchAll();
                    
                    // Decrypt user data
                    require_once __DIR__ . '/../includes/encryption.php';
                    foreach ($auto_applicants as &$applicant) {
                        $applicant['first_name'] = safeDecryptField($applicant['first_name'] ?? '', 'users', 'first_name');
                        $applicant['last_name'] = safeDecryptField($applicant['last_name'] ?? '', 'users', 'last_name');
                        $applicant['email'] = safeDecryptField($applicant['email'] ?? '', 'users', 'email');
                        $applicant['mobile_number'] = safeDecryptField($applicant['mobile_number'] ?? '', 'users', 'mobile_number');
                    }
                    unset($applicant);
                    
                    $assigned_count = 0;
                    
                    foreach ($auto_applicants as $applicant) {
                        // Check if we've reached the capacity
                        if ($assigned_count >= $capacity) {
                            break;
                        }
                        
                        // Update application status
                        $stmt2 = $conn->prepare("UPDATE applications SET status = 'Exam Scheduled' WHERE id = ? AND status = 'Verified'");
                        $stmt2->execute([$applicant['id']]);
                        
                        if ($stmt2->rowCount() > 0) {
                            // Insert into exams
                            $stmt2 = $conn->prepare("INSERT INTO exams (application_id, exam_schedule_id, exam_date, exam_time, exam_time_end, venue, venue_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            $stmt2->execute([
                                $applicant['id'], $schedule_id, $exam_date, $exam_time, $exam_time_end, $venue_name, $venue_id
                            ]);
                            
                            // Send email (include end time)
                            send_exam_schedule_email($applicant, [
                                'exam_date' => $exam_date,
                                'exam_time' => $exam_time,
                                'exam_time_end' => $exam_time_end,
                                'venue' => $venue_name,
                                'instructions' => $instructions,
                                'requirements' => $requirements
                            ]);
                            
                            $assigned_count++;
                        }
                    }
                } else {
                    // Exam is today or tomorrow, no auto-assignment
                    $assigned_count = 0;
                    log_admin_activity(
                        $conn,
                        $_SESSION['admin_id'],
                        'exam_schedule_notice',
                        "Created exam schedule for {$exam_date} at {$exam_time}, but no auto-assignment as the exam is within 2 days"
                    );
                }
                
                // Update current_count
                $stmt = $conn->prepare("UPDATE exam_schedules SET current_count = ? WHERE id = ?");
                $stmt->execute([$assigned_count, $schedule_id]);
                log_admin_activity(
                    $conn,
                    $_SESSION['admin_id'],
                    'create_exam_schedule',
                    "Created exam schedule for {$exam_date} at {$exam_time} in {$venue_name} with capacity {$capacity} (auto-assigned {$assigned_count} applicants)"
                );
                $conn->commit();
                $response['success'] = true;
                $response['message'] = "Exam schedule created successfully! {$assigned_count} applicants auto-assigned.";
                $response['redirect'] = "schedule_exam.php?tab=schedules";
            } catch (PDOException $e) {
                $conn->rollBack();
                $response['message'] = "Database error: " . $e->getMessage();
            }
        } else {
            $response['message'] = "Validation errors: " . implode(', ', $errors);
        }
    }
}

// Process applicant assignment
else if (isset($_POST['action']) && $_POST['action'] === 'assign_applicants') {
    if (empty($_POST['schedule_id'])) {
        $response['message'] = "No exam schedule selected.";
    } else if (empty($_POST['applicant_ids'])) {
        $response['message'] = "No applicants selected.";
    } else {
        $schedule_id = $_POST['schedule_id'];
        $applicant_ids = $_POST['applicant_ids'];
        
        // Begin transaction
        try {
            $conn->beginTransaction();
            
            // Get the exam schedule details with venue information and current count
            $stmt = $conn->prepare("
                SELECT es.*, v.name as venue_name, v.id as venue_id,
                       (SELECT COUNT(*) FROM exams WHERE exam_schedule_id = es.id) as actual_count
                FROM exam_schedules es
                LEFT JOIN venues v ON es.venue_id = v.id
                WHERE es.id = ?
            ");
            $stmt->execute([$schedule_id]);
            $schedule = $stmt->fetch();
            
            if (!$schedule) {
                throw new Exception("Invalid exam schedule.");
            }
            
            // Check if the exam date is today or tomorrow (scheduling rule)
            $exam_date_timestamp = strtotime($schedule['exam_date']);
            $today_timestamp = strtotime(date('Y-m-d'));
            $tomorrow_timestamp = strtotime('+1 day', $today_timestamp);
            
            if ($exam_date_timestamp <= $tomorrow_timestamp) {
                throw new Exception("Cannot assign applicants to exams today or tomorrow. Please choose a later exam date.");
            }
            
            // Use actual count from exams table for capacity check
            $available_slots = $schedule['capacity'] - $schedule['actual_count'];
            if (count($applicant_ids) > $available_slots) {
                throw new Exception("Not enough capacity. Available slots: $available_slots, Selected applicants: " . count($applicant_ids));
            }
            
            $successful_assignments = 0;
            
            foreach ($applicant_ids as $applicant_id) {
                // Get applicant details
                $stmt = $conn->prepare("
                    SELECT a.*, u.first_name, u.last_name, u.email 
                    FROM applications a
                    JOIN users u ON a.user_id = u.id
                    WHERE a.id = ? AND a.status = 'Verified'
                ");
                $stmt->execute([$applicant_id]);
                $applicant = $stmt->fetch();
                
                // Decrypt user data
                if ($applicant) {
                    require_once __DIR__ . '/../includes/encryption.php';
                    $applicant['first_name'] = safeDecryptField($applicant['first_name'] ?? '', 'users', 'first_name');
                    $applicant['last_name'] = safeDecryptField($applicant['last_name'] ?? '', 'users', 'last_name');
                    $applicant['email'] = safeDecryptField($applicant['email'] ?? '', 'users', 'email');
                }
                
                if ($applicant) {
                    // Update application status
                    $stmt = $conn->prepare("UPDATE applications SET status = 'Exam Scheduled' WHERE id = ?");
                    $stmt->execute([$applicant_id]);
                    
                    // Insert into exams table
                    $stmt = $conn->prepare("
                        INSERT INTO exams (
                            application_id, exam_schedule_id, exam_date, exam_time, exam_time_end,
                            venue, venue_id
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $applicant_id,
                        $schedule_id,
                        $schedule['exam_date'],
                        $schedule['exam_time'],
                        $schedule['exam_time_end'],
                        $schedule['venue_name'],
                        $schedule['venue_id']
                    ]);
                    
                    // Send email notification (include end time)
                    send_exam_schedule_email($applicant, [
                        'exam_date' => $schedule['exam_date'],
                        'exam_time' => $schedule['exam_time'],
                        'exam_time_end' => $schedule['exam_time_end'],
                        'venue' => $schedule['venue_name'],
                        'instructions' => $schedule['instructions'],
                        'requirements' => $schedule['requirements']
                    ]);
                    
                    $successful_assignments++;
                }
            }
            
            // Update the current count based on actual count in exams table
            $stmt = $conn->prepare("
                UPDATE exam_schedules 
                SET current_count = (
                    SELECT COUNT(*) 
                    FROM exams 
                    WHERE exam_schedule_id = ?
                )
                WHERE id = ?
            ");
            $stmt->execute([$schedule_id, $schedule_id]);
            
            $conn->commit();
            $response['success'] = true;
            $response['message'] = "Successfully assigned $successful_assignments applicant(s) to the exam schedule.";
            $response['redirect'] = "schedule_exam.php?tab=schedules";
            
        } catch (Exception $e) {
            $conn->rollBack();
            $response['message'] = $e->getMessage();
        }
    }
}

// Process schedule deletion
else if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $schedule_id = (int) $_GET['id'];
    
    try {
        $conn->beginTransaction();
        
        // Get schedule details first for logging
        $stmt = $conn->prepare("
            SELECT * FROM exam_schedules 
            WHERE id = ?
        ");
        $stmt->execute([$schedule_id]);
        $schedule = $stmt->fetch();
        
        if (!$schedule) {
            throw new Exception("Schedule not found");
        }
        
        // Check if it's in the past
        if (strtotime($schedule['exam_date'] . ' ' . $schedule['exam_time']) < time()) {
            throw new Exception("Cannot delete a past exam schedule");
        }
        
        // Find any applicants assigned to this schedule and revert their status
        $affected_applicants = 0;
        
        // Get applications associated with this exam schedule
        $stmt = $conn->prepare("
            SELECT application_id FROM exams 
            WHERE exam_schedule_id = ?
        ");
        $stmt->execute([$schedule_id]);
        $applicant_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($applicant_rows as $row) {
            // Update application status back to 'Verified'
            $stmt = $conn->prepare("
                UPDATE applications 
                SET status = 'Verified' 
                WHERE id = ? AND status = 'Exam Scheduled'
            ");
            $stmt->execute([$row['application_id']]);
            
            if ($stmt->rowCount() > 0) {
                $affected_applicants++;
                
                // Record status change in history
                $stmt = $conn->prepare("
                    INSERT INTO status_history 
                    (application_id, status, description, performed_by) 
                    VALUES (?, 'Verified', ?, ?)
                ");
                $stmt->execute([
                    $row['application_id'],
                    "Exam schedule was deleted. Application returned to verification status.",
                    "Admin: " . $_SESSION['admin_id']
                ]);
            }
        }
        
        // Delete entries in the exams table
        $stmt = $conn->prepare("DELETE FROM exams WHERE exam_schedule_id = ?");
        $stmt->execute([$schedule_id]);
        
        // Delete the schedule
        $stmt = $conn->prepare("DELETE FROM exam_schedules WHERE id = ?");
        $stmt->execute([$schedule_id]);
        
        // Log activity
        log_admin_activity(
            $conn,
            $_SESSION['admin_id'],
            'delete_exam_schedule',
            "Admin deleted exam schedule ID: {$schedule_id}"
        );
        
        $conn->commit();
        
        $_SESSION['message'] = "Exam schedule deleted successfully. {$affected_applicants} applicants returned to 'Verified' status.";
        $_SESSION['message_type'] = 'success';
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    safe_redirect('schedule_exam.php?tab=schedules');
}

// Return JSON response for AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Redirect for form submissions
if (isset($response['redirect']) && !empty($response['redirect'])) {
    $_SESSION['message'] = $response['message'];
    $_SESSION['message_type'] = $response['success'] ? 'success' : 'danger';
    safe_redirect($response['redirect']);
}

// If no redirect specified, go back to schedule_exam.php
$_SESSION['message'] = $response['message'];
$_SESSION['message_type'] = $response['success'] ? 'success' : 'danger';
safe_redirect('schedule_exam.php');
?> 