<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../firebase/firebase_email.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_schedule') {
    // Collect and sanitize input
    $enrollment_date = trim($_POST['enrollment_date'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $venue_id = trim($_POST['venue_id'] ?? '');
    $capacity = trim($_POST['capacity'] ?? '');
    $course_id = trim($_POST['course_id'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $is_auto_assign = isset($_POST['is_auto_assign']) ? 1 : 0;
    $created_by = $_SESSION['admin_id'];

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

    // Additional validation for capacity against venue capacity
    if ($venue_id && $capacity) {
        $stmt = $conn->prepare('SELECT capacity FROM venues WHERE id = ?');
        $stmt->execute([$venue_id]);
        $venue_capacity = $stmt->fetchColumn();
        if ($capacity > $venue_capacity) {
            $errors[] = "Capacity cannot exceed venue's maximum capacity of $venue_capacity.";
        }
    }

    // Check if course has enough available slots
    if ($course_id && $capacity) {
        $stmt = $conn->prepare('SELECT slots FROM courses WHERE id = ?');
        $stmt->execute([$course_id]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$course) {
            $errors[] = 'Course not found.';
        } else {
            if ($capacity > (int)$course['slots']) {
                $errors[] = "Schedule capacity ($capacity) cannot exceed available slots ({$course['slots']}) for this course.";
            }
            if ((int)$course['slots'] <= 0) {
                $errors[] = "Cannot create enrollment schedule. This course has no available slots (0 remaining).";
            }
        }
    }

    // Validate time format and logic
    if ($start_time && $end_time) {
        if (strtotime($end_time) <= strtotime($start_time)) {
            $errors[] = 'End time must be after start time.';
        }
    }

    // Check for scheduling conflicts
    if ($enrollment_date && $start_time && $end_time && $venue_id && empty($errors)) {
        try {
            // Check for venue conflicts (same venue, same date, overlapping times)
            $stmt = $conn->prepare("
                SELECT COUNT(*) as conflict_count, 
                       GROUP_CONCAT(CONCAT(c.course_code, ' - ', c.course_name) SEPARATOR ', ') as conflicting_courses
                FROM enrollment_schedules es
                JOIN courses c ON es.course_id = c.id
                WHERE es.venue_id = ? 
                AND es.enrollment_date = ? 
                AND es.is_active = 1
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
                $end_time, $start_time,      // New schedule overlaps with existing
                $start_time, $end_time,      // Existing schedule overlaps with new
                $start_time, $end_time,      // New schedule is completely within existing
                $start_time, $end_time       // Existing schedule is completely within new
            ]);
            $venue_conflict = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($venue_conflict['conflict_count'] > 0) {
                $errors[] = "Venue conflict detected! There are already {$venue_conflict['conflict_count']} enrollment schedule(s) at this venue on {$enrollment_date} with overlapping times. Conflicting courses: {$venue_conflict['conflicting_courses']}";
            }

            // Check for course conflicts (same course, same date, any time overlap)
            $stmt = $conn->prepare("
                SELECT COUNT(*) as conflict_count,
                       GROUP_CONCAT(CONCAT(es.start_time, '-', es.end_time) SEPARATOR ', ') as conflicting_times
                FROM enrollment_schedules es
                WHERE es.course_id = ? 
                AND es.enrollment_date = ? 
                AND es.is_active = 1
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

    if (count($errors) > 0) {
        $_SESSION['admin_message'] = implode('<br>', $errors);
        $_SESSION['admin_message_type'] = 'danger';
        header('Location: enrollment_schedule.php?tab=create');
        exit;
    }

    try {
        // Get venue name and capacity
        $stmt = $conn->prepare('SELECT name, capacity FROM venues WHERE id = ?');
        $stmt->execute([$venue_id]);
        $venue = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$venue) {
            throw new Exception('Venue not found.');
        }
        $venue_name = $venue['name'];

        // Get course info
        $stmt = $conn->prepare('SELECT course_code, course_name FROM courses WHERE id = ?');
        $stmt->execute([$course_id]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$course) {
            throw new Exception('Course not found.');
        }

        // Insert new enrollment schedule
        $stmt = $conn->prepare('INSERT INTO enrollment_schedules (course_id, enrollment_date, start_time, end_time, venue, venue_id, capacity, current_count, is_active, is_auto_assign, created_by, instructions, requirements) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 1, ?, ?, ?, ?)');
        $stmt->execute([
            $course_id,
            $enrollment_date,
            $start_time,
            $end_time,
            $venue_name,
            $venue_id,
            $capacity,
            $is_auto_assign,
            $created_by,
            $instructions,
            $requirements
        ]);
        $schedule_id = $conn->lastInsertId();

        // Note: Available slots now decrease per student assignment, not at schedule creation

        // Auto-assign eligible applicants if enabled
        if ($is_auto_assign) {
            // Only assign if schedule is NOT today or tomorrow
            $today = date('Y-m-d');
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            if ($enrollment_date !== $today && $enrollment_date !== $tomorrow) {
                try {
                    $conn->beginTransaction();
                    
                    // Get system user ID for auto-assignment
                    $system_user_id = null;
                    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                    $stmt->execute(['system@psau.edu.ph']);
                    $system_user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($system_user) {
                        $system_user_id = $system_user['id'];
                    } else {
                        // Fallback: use first user
                        $stmt = $conn->query('SELECT id FROM users ORDER BY id ASC LIMIT 1');
                        $system_user_id = $stmt->fetchColumn();
                    }

                    // Get applicants with status 'Course Assigned', matching course, and not already assigned to any enrollment schedule
                    $stmt = $conn->prepare('
                        SELECT a.id AS application_id, a.user_id, u.first_name, u.last_name, u.email, u.control_number
                        FROM applications a
                        JOIN users u ON a.user_id = u.id
                        JOIN course_assignments ca ON ca.application_id = a.id
                        WHERE a.status = "Course Assigned"
                          AND ca.course_id = ?
                          AND NOT EXISTS (
                              SELECT 1 FROM enrollment_assignments ea
                              WHERE ea.student_id = a.user_id
                          )
                        ORDER BY ca.created_at ASC
                    ');
                    $stmt->execute([$course_id]);
                    $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $current_count = 0;
                    foreach ($applicants as $applicant) {
                        // Stop if we've reached capacity
                        if ($current_count >= $capacity) {
                            break;
                        }

                        // Avoid duplicate assignment of the same student to the same schedule
                        $stmt = $conn->prepare('SELECT 1 FROM enrollment_assignments WHERE student_id = ? AND schedule_id = ? LIMIT 1');
                        $stmt->execute([$applicant['user_id'], $schedule_id]);
                        $alreadyAssigned = (bool)$stmt->fetchColumn();
                        if ($alreadyAssigned) {
                            continue;
                        }

                        // Assign applicant to this schedule
                        $stmt = $conn->prepare('INSERT INTO enrollment_assignments (student_id, schedule_id, assigned_by, is_auto_assigned, status) VALUES (?, ?, ?, 1, "pending")');
                        $stmt->execute([$applicant['user_id'], $schedule_id, $created_by]);

                        // Decrease available slots and increase enrolled_students (scheduled count per your definition)
                        $stmt = $conn->prepare('UPDATE courses SET slots = GREATEST(0, slots - 1), enrolled_students = enrolled_students + 1 WHERE id = ?');
                        $stmt->execute([$course_id]);

                        // Update application status
                        $stmt = $conn->prepare('UPDATE applications SET status = "Enrollment Scheduled" WHERE id = ?');
                        $stmt->execute([$applicant['application_id']]);

                        // Send email notification
                        $schedule_data = [
                            'enrollment_date' => $enrollment_date,
                            'enrollment_time' => $start_time,
                            'end_time' => $end_time,
                            'venue' => $venue_name,
                            'course_code' => $course['course_code'],
                            'course_name' => $course['course_name'],
                            'instructions' => $instructions,
                            'requirements' => $requirements
                        ];
                        send_enrollment_schedule_email($applicant, $schedule_data);
                        $current_count++;
                    }

                    // Update current_count in schedule
                    $stmt = $conn->prepare('UPDATE enrollment_schedules SET current_count = current_count + ? WHERE id = ?');
                    $stmt->execute([$current_count, $schedule_id]);

                    $conn->commit();
                    $_SESSION['admin_message'] = "Enrollment schedule created successfully! Auto-assigned {$current_count} applicants.";
                } catch (Exception $e) {
                    $conn->rollBack();
                    $_SESSION['admin_message'] = 'Error during auto-assignment: ' . $e->getMessage();
                }
            } else {
                $_SESSION['admin_message'] = 'Enrollment schedule created successfully! (Auto-assign skipped for schedules within 2 days)';
            }
        } else {
            $_SESSION['admin_message'] = 'Enrollment schedule created successfully!';
        }
        $_SESSION['admin_message_type'] = 'success';
        header('Location: enrollment_schedule.php?tab=schedules');
        exit;
    } catch (Exception $e) {
        $_SESSION['admin_message'] = 'Error: ' . $e->getMessage();
        $_SESSION['admin_message_type'] = 'danger';
        header('Location: enrollment_schedule.php?tab=create');
        exit;
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manual_assign') {
    $applicant_ids = $_POST['applicant_ids'] ?? [];
    $created_by = $_SESSION['admin_id'] ?? null;
    if (!$created_by) {
        $_SESSION['admin_message'] = 'Your admin session expired. Please log in again.';
        $_SESSION['admin_message_type'] = 'danger';
        header('Location: login.php');
        exit;
    }
    if (!empty($applicant_ids)) {
        $assigned_count = 0;
        $skipped_count = 0;
        $skipped_applicants = [];
        // Get system user ID for assignment
        $system_user_id = null;
        $stmt_sys = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt_sys->execute(['system@psau.edu.ph']);
        $system_user = $stmt_sys->fetch(PDO::FETCH_ASSOC);
        if ($system_user) {
            $system_user_id = $system_user['id'];
        } else {
            // Fallback: use first user
            $stmt_sys = $conn->query('SELECT id FROM users ORDER BY id ASC LIMIT 1');
            $system_user_id = $stmt_sys->fetchColumn();
        }
        foreach ($applicant_ids as $applicant_id) {
            // Get applicant's assigned course
            $stmt_check = $conn->prepare('SELECT ca.course_id, a.id as application_id FROM course_assignments ca JOIN applications a ON ca.application_id = a.id WHERE a.user_id = ?');
            $stmt_check->execute([$applicant_id]);
            $app_row = $stmt_check->fetch(PDO::FETCH_ASSOC);
            if (!$app_row) {
                $skipped_count++;
                $skipped_applicants[] = $applicant_id;
                continue;
            }
            $course_id = $app_row['course_id'];
            $application_id = $app_row['application_id'];
            // Find earliest available schedule for this course
            $stmt_sched = $conn->prepare('SELECT * FROM enrollment_schedules WHERE course_id = ? AND is_active = 1 AND current_count < capacity AND enrollment_date >= CURDATE() ORDER BY enrollment_date ASC, start_time ASC LIMIT 1');
            $stmt_sched->execute([$course_id]);
            $schedule = $stmt_sched->fetch(PDO::FETCH_ASSOC);
            if (!$schedule) {
                $skipped_count++;
                $skipped_applicants[] = $applicant_id;
                continue;
            }
            // Avoid duplicate assignment of the same student to the same schedule
            $stmt2 = $conn->prepare('SELECT 1 FROM enrollment_assignments WHERE student_id = ? AND schedule_id = ? LIMIT 1');
            $stmt2->execute([$applicant_id, $schedule['id']]);
            if ($stmt2->fetchColumn()) {
                $skipped_count++;
                continue;
            }
            // Assign applicant
            $stmt2 = $conn->prepare('INSERT INTO enrollment_assignments (student_id, schedule_id, assigned_by, is_auto_assigned, status) VALUES (?, ?, ?, 0, "pending")');
            $stmt2->execute([$applicant_id, $schedule['id'], $created_by]);
            // Update course counters per assignment
            $stmt2 = $conn->prepare('UPDATE courses SET slots = GREATEST(0, slots - 1), enrolled_students = enrolled_students + 1 WHERE id = ?');
            $stmt2->execute([$course_id]);
            // Update application status
            $stmt2 = $conn->prepare('UPDATE applications SET status = "Enrollment Scheduled" WHERE user_id = ?');
            $stmt2->execute([$applicant_id]);
            // Fetch full user info (include control number for emails)
            $stmt3 = $conn->prepare('SELECT first_name, last_name, email, control_number FROM users WHERE id = ?');
            $stmt3->execute([$applicant_id]);
            $user = $stmt3->fetch(PDO::FETCH_ASSOC);
            // Fetch full schedule info (with venue, course_code, course_name, instructions, requirements)
            $stmt4 = $conn->prepare('SELECT es.*, c.course_code, c.course_name FROM enrollment_schedules es LEFT JOIN courses c ON es.course_id = c.id WHERE es.id = ?');
            $stmt4->execute([$schedule['id']]);
            $full_schedule = $stmt4->fetch(PDO::FETCH_ASSOC);
            if ($user && $full_schedule) {
                $schedule_data = [
                    'enrollment_date' => $full_schedule['enrollment_date'],
                    'enrollment_time' => $full_schedule['start_time'],
                    'venue' => $full_schedule['venue'],
                    'course_code' => $full_schedule['course_code'],
                    'course_name' => $full_schedule['course_name'],
                    'instructions' => $full_schedule['instructions'],
                    'requirements' => $full_schedule['requirements']
                ];
                send_enrollment_schedule_email($user, $schedule_data);
            }
            // Update current_count in schedule
            $stmt2 = $conn->prepare('UPDATE enrollment_schedules SET current_count = current_count + 1 WHERE id = ?');
            $stmt2->execute([$schedule['id']]);
            $assigned_count++;
        }
        $msg = "Applicants assigned: $assigned_count.";
        if ($skipped_count > 0) {
            $msg .= " Skipped: $skipped_count (no available schedule).";
        }
        $_SESSION['admin_message'] = $msg;
        $_SESSION['admin_message_type'] = 'success';
        header('Location: enrollment_schedule.php?tab=schedules');
        exit;
    } else {
        $_SESSION['admin_message'] = 'No applicants selected.';
        $_SESSION['admin_message_type'] = 'danger';
        header('Location: enrollment_schedule.php?tab=assign');
        exit;
    }
} else if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $schedule_id = intval($_GET['id']);
    try {
        // Delete related assignments first (to avoid FK constraint errors)
        $stmt = $conn->prepare('DELETE FROM enrollment_assignments WHERE schedule_id = ?');
        $stmt->execute([$schedule_id]);
        // Delete the schedule
        $stmt = $conn->prepare('DELETE FROM enrollment_schedules WHERE id = ?');
        $stmt->execute([$schedule_id]);
        $_SESSION['admin_message'] = 'Enrollment schedule deleted successfully!';
        $_SESSION['admin_message_type'] = 'success';
    } catch (Exception $e) {
        $_SESSION['admin_message'] = 'Error deleting schedule: ' . $e->getMessage();
        $_SESSION['admin_message_type'] = 'danger';
    }
    header('Location: enrollment_schedule.php?tab=schedules');
    exit;
} else {
    header('Location: enrollment_schedule.php');
    exit;
}
