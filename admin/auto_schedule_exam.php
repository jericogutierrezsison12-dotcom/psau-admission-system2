<?php
require_once '../includes/db_connect.php';
require_once '../includes/aes_encryption.php';
require_once '../includes/functions.php';
require_once '../firebase/firebase_email.php';

/**
 * Auto-schedule verified applicants for upcoming exams
 * This script can be called via AJAX or directly
 */

function auto_schedule_verified_applicants($specific_application_id = null) {
    global $conn;
    
    try {
        $conn->beginTransaction();
        
        // Get verified applicants not yet scheduled for an exam
        $sql = "
            SELECT a.id, a.user_id, a.status, a.created_at,
                   u.first_name, u.last_name, u.email, u.mobile_number, u.control_number
            FROM applications a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN exams e ON a.id = e.application_id
            WHERE a.status = 'Verified' AND e.id IS NULL
        ";
        
        // If specific application ID is provided, only schedule that one
        $params = [];
        if ($specific_application_id) {
            $sql .= " AND a.id = ?";
            $params[] = $specific_application_id;
        }
        
        $sql .= " ORDER BY a.created_at ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $verified_applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($verified_applicants)) {
            return ['success' => true, 'message' => 'No new verified applicants to schedule.'];
        }
        
        // Get available exam schedules (not full and in the future)
        // We'll get all future schedules and filter them in PHP
        $stmt = $conn->prepare("
            SELECT es.*, v.name as venue_name, v.id as venue_id
            FROM exam_schedules es
            LEFT JOIN venues v ON es.venue_id = v.id
            WHERE es.exam_date >= CURDATE()
            AND es.current_count < es.capacity
            ORDER BY es.exam_date ASC, es.exam_time ASC
        ");
        $stmt->execute();
        $all_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($all_schedules)) {
            return ['success' => false, 'message' => 'No available exam schedules found.'];
        }

        // Filter schedules based on date
        $available_schedules = [];
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        foreach ($all_schedules as $schedule) {
            $exam_date = $schedule['exam_date'];
            
            // Skip schedules for today and tomorrow
            if ($exam_date == $today || $exam_date == $tomorrow) {
                continue;
            }
            
            $available_schedules[] = $schedule;
        }
        
        if (empty($available_schedules)) {
            return ['success' => false, 'message' => 'No available exam schedules found beyond tomorrow.'];
        }
        
        $assigned_count = 0;
        $failed_assignments = [];
        
        // Try to assign each applicant to the earliest available schedule
        foreach ($verified_applicants as $applicant) {
            $assigned = false;
            
            foreach ($available_schedules as &$schedule) {
                if ($schedule['current_count'] < $schedule['capacity']) {
                    // Update application status
                    $stmt = $conn->prepare("
                        UPDATE applications 
                        SET status = 'Exam Scheduled' 
                        WHERE id = ? AND status = 'Verified'
                    ");
                    $stmt->execute([$applicant['id']]);
                    
                    if ($stmt->rowCount() > 0) {
                        // Insert into exams table
                        $stmt = $conn->prepare("
                            INSERT INTO exams 
                            (application_id, exam_schedule_id, exam_date, exam_time, venue, venue_id) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $applicant['id'],
                            $schedule['id'],
                            $schedule['exam_date'],
                            $schedule['exam_time'],
                            $schedule['venue_name'],
                            $schedule['venue_id']
                        ]);
                        
                        // Record status change
                        $stmt = $conn->prepare("
                            INSERT INTO status_history 
                            (application_id, status, description, performed_by) 
                            VALUES (?, 'Exam Scheduled', ?, 'System')
                        ");
                        $stmt->execute([
                            $applicant['id'],
                            "Automatically scheduled for exam on " . date('F j, Y', strtotime($schedule['exam_date'])) . 
                            " at " . date('h:i A', strtotime($schedule['exam_time'])) . 
                            " at " . $schedule['venue_name']
                        ]);
                        
                        // Send email notification
                        $email_schedule = [
                            'exam_date' => $schedule['exam_date'],
                            'exam_time' => $schedule['exam_time'],
                            'venue' => $schedule['venue_name'],
                            'instructions' => $schedule['instructions'],
                            'requirements' => $schedule['requirements']
                        ];
                        send_exam_schedule_email($applicant, $email_schedule);
                        
                        // Update schedule count
                        $schedule['current_count']++;
                        $stmt = $conn->prepare("
                            UPDATE exam_schedules 
                            SET current_count = current_count + 1 
                            WHERE id = ?
                        ");
                        $stmt->execute([$schedule['id']]);
                        
                        $assigned_count++;
                        $assigned = true;
                        break; // Move to next applicant
                    }
                }
            }
            
            if (!$assigned) {
                $failed_assignments[] = $applicant['control_number'];
            }
        }
        
        $conn->commit();
        
        $message = "Successfully scheduled {$assigned_count} applicant(s) for examination.";
        if (!empty($failed_assignments)) {
            $message .= " Could not schedule: " . implode(", ", $failed_assignments);
        }
        
        return [
            'success' => true,
            'message' => $message
        ];
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Auto-scheduling error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => "Error during auto-scheduling: " . $e->getMessage()
        ];
    }
}

// If called directly
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    $application_id = $_GET['application_id'] ?? null;
    $result = auto_schedule_verified_applicants($application_id);
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // AJAX request
        header('Content-Type: application/json');
        echo json_encode($result);
    } else {
        // Direct access
        echo $result['message'];
    }
}
?> 