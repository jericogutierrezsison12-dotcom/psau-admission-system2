<?php
// Run: php scripts/provision_database.php
// Creates all required tables in the configured MySQL (Railway) database

declare(strict_types=1);

require_once __DIR__ . '/../includes/db_connect.php';

function runStatement(PDO $conn, string $sql): void {
    $conn->exec($sql);
}

try {
    $conn->beginTransaction();

    // ADMINS
    runStatement($conn, "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username TEXT NULL,
        email TEXT NULL,
        mobile_number TEXT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'staff',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // USERS
    runStatement($conn, "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        control_number VARCHAR(50) UNIQUE,
        first_name TEXT NULL,
        last_name TEXT NULL,
        email TEXT NULL,
        mobile_number TEXT NULL,
        password VARCHAR(255) NOT NULL,
        address TEXT NULL,
        gender TEXT NULL,
        birth_date TEXT NULL,
        is_verified TINYINT(1) NOT NULL DEFAULT 0,
        is_flagged TINYINT(1) NOT NULL DEFAULT 0,
        is_blocked TINYINT(1) NOT NULL DEFAULT 0,
        block_reason TEXT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // APPLICATIONS
    runStatement($conn, "CREATE TABLE IF NOT EXISTS applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_id INT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'Submitted',
        document_file_path TEXT NULL,
        pdf_validated TINYINT(1) NULL,
        validation_message TEXT NULL,
        verified_at DATETIME NULL,
        gpa TEXT NULL,
        strand TEXT NULL,
        school_name TEXT NULL,
        school_address TEXT NULL,
        essay_response LONGTEXT NULL,
        personal_statement LONGTEXT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_app_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // DOCUMENTS
    runStatement($conn, "CREATE TABLE IF NOT EXISTS documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        document_type VARCHAR(100) NOT NULL,
        file_name TEXT NULL,
        file_path TEXT NULL,
        file_content LONGBLOB NULL,
        ocr_text LONGTEXT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_doc_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // COURSES
    runStatement($conn, "CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_code VARCHAR(50) UNIQUE,
        course_name VARCHAR(255) NOT NULL,
        description TEXT NULL,
        total_capacity INT NOT NULL DEFAULT 0,
        enrolled_students INT NOT NULL DEFAULT 0,
        slots INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // COURSE ASSIGNMENTS
    runStatement($conn, "CREATE TABLE IF NOT EXISTS course_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        course_id INT NOT NULL,
        assigned_by INT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_ca_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
        CONSTRAINT fk_ca_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // COURSE SELECTIONS
    runStatement($conn, "CREATE TABLE IF NOT EXISTS course_selections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        preference_order INT NOT NULL,
        CONSTRAINT fk_cs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_cs_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // VENUES
    runStatement($conn, "CREATE TABLE IF NOT EXISTS venues (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        capacity INT NOT NULL DEFAULT 0,
        description TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // EXAM SCHEDULES
    runStatement($conn, "CREATE TABLE IF NOT EXISTS exam_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_date DATE NOT NULL,
        exam_time TIME NULL,
        exam_time_end TIME NULL,
        venue VARCHAR(255) NULL,
        venue_id INT NULL,
        capacity INT NOT NULL DEFAULT 0,
        current_count INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_by INT NULL,
        CONSTRAINT fk_es_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // EXAMS
    runStatement($conn, "CREATE TABLE IF NOT EXISTS exams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        exam_schedule_id INT NOT NULL,
        exam_date DATE NULL,
        exam_time TIME NULL,
        exam_time_end TIME NULL,
        venue VARCHAR(255) NULL,
        venue_id INT NULL,
        CONSTRAINT fk_ex_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
        CONSTRAINT fk_ex_es FOREIGN KEY (exam_schedule_id) REFERENCES exam_schedules(id) ON DELETE CASCADE,
        CONSTRAINT fk_ex_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // ENROLLMENT SCHEDULES
    runStatement($conn, "CREATE TABLE IF NOT EXISTS enrollment_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        enrollment_date DATE NOT NULL,
        start_time TIME NULL,
        end_time TIME NULL,
        venue VARCHAR(255) NULL,
        venue_id INT NULL,
        capacity INT NOT NULL DEFAULT 0,
        current_count INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        is_auto_assign TINYINT(1) NOT NULL DEFAULT 0,
        created_by INT NULL,
        instructions TEXT NULL,
        requirements TEXT NULL,
        CONSTRAINT fk_en_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        CONSTRAINT fk_en_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // ENROLLMENT ASSIGNMENTS
    runStatement($conn, "CREATE TABLE IF NOT EXISTS enrollment_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        schedule_id INT NOT NULL,
        assigned_by INT NULL,
        is_auto_assigned TINYINT(1) NOT NULL DEFAULT 0,
        status VARCHAR(50) NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_ea_user FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_ea_sched FOREIGN KEY (schedule_id) REFERENCES enrollment_schedules(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // ENTRANCE EXAM SCORES
    runStatement($conn, "CREATE TABLE IF NOT EXISTS entrance_exam_scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        control_number VARCHAR(50) NOT NULL,
        application_id INT NULL,
        score INT NULL,
        uploaded_by INT NULL,
        upload_date DATETIME NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // STATUS HISTORY (normalized to old/new form; other inserts can map to notes)
    runStatement($conn, "CREATE TABLE IF NOT EXISTS status_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        old_status VARCHAR(50) NULL,
        new_status VARCHAR(50) NULL,
        status VARCHAR(50) NULL,
        notes TEXT NULL,
        description TEXT NULL,
        performed_by INT NULL,
        created_by INT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_sh_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // ANNOUNCEMENTS
    runStatement($conn, "CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        created_by INT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // FAQS & unanswered_questions
    runStatement($conn, "CREATE TABLE IF NOT EXISTS faqs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question TEXT NOT NULL,
        answer TEXT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    runStatement($conn, "CREATE TABLE IF NOT EXISTS unanswered_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question TEXT NOT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // INSTRUCTIONS / REQUIRED DOCS (exam/enrollment)
    runStatement($conn, "CREATE TABLE IF NOT EXISTS enrollment_instructions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        instruction_text TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    runStatement($conn, "CREATE TABLE IF NOT EXISTS required_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        document_name VARCHAR(255) NOT NULL,
        description TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    runStatement($conn, "CREATE TABLE IF NOT EXISTS exam_instructions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        instruction_text TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    runStatement($conn, "CREATE TABLE IF NOT EXISTS exam_required_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        document_name VARCHAR(255) NOT NULL,
        description TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // ACTIVITY LOGS
    runStatement($conn, "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action VARCHAR(100) NOT NULL,
        user_id INT NULL,
        details TEXT NULL,
        ip_address VARCHAR(64) NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // REMINDER LOGS
    runStatement($conn, "CREATE TABLE IF NOT EXISTS reminder_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        reminder_type VARCHAR(100) NOT NULL,
        sent_by VARCHAR(100) NULL,
        status VARCHAR(50) NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // ATTEMPTS & OTP
    runStatement($conn, "CREATE TABLE IF NOT EXISTS application_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        attempt_date DATETIME NOT NULL,
        was_successful TINYINT(1) NOT NULL DEFAULT 0,
        pdf_message TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    runStatement($conn, "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NULL,
        ip_address VARCHAR(64) NULL,
        user_agent TEXT NULL,
        is_successful TINYINT(1) NOT NULL DEFAULT 0,
        attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    runStatement($conn, "CREATE TABLE IF NOT EXISTS admin_login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NULL,
        ip_address VARCHAR(64) NULL,
        user_agent TEXT NULL,
        is_successful TINYINT(1) NOT NULL DEFAULT 0,
        attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    runStatement($conn, "CREATE TABLE IF NOT EXISTS otp_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        purpose VARCHAR(100) NOT NULL,
        ip_address VARCHAR(64) NULL,
        user_agent TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    runStatement($conn, "CREATE TABLE IF NOT EXISTS otp_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        purpose VARCHAR(100) NOT NULL,
        otp_code VARCHAR(20) NOT NULL,
        ip_address VARCHAR(64) NULL,
        user_agent TEXT NULL,
        expires_at DATETIME NOT NULL,
        attempts INT NOT NULL DEFAULT 0,
        is_used TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    runStatement($conn, "CREATE TABLE IF NOT EXISTS otp_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        purpose VARCHAR(100) NOT NULL,
        otp_request_id INT NULL,
        otp_code VARCHAR(20) NULL,
        is_successful TINYINT(1) NOT NULL DEFAULT 0,
        ip_address VARCHAR(64) NULL,
        user_agent TEXT NULL,
        attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // REMEMBER TOKENS
    runStatement($conn, "CREATE TABLE IF NOT EXISTS remember_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        selector VARCHAR(64) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires DATETIME NOT NULL,
        UNIQUE(selector)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    runStatement($conn, "CREATE TABLE IF NOT EXISTS admin_remember_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        selector VARCHAR(64) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires DATETIME NOT NULL,
        UNIQUE(selector)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // AI DOCUMENT ANALYSIS
    runStatement($conn, "CREATE TABLE IF NOT EXISTS ai_document_analysis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        is_valid TINYINT(1) NULL,
        result_message TEXT NULL,
        detected_fields JSON NULL,
        raw_data JSON NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_ai_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->commit();
    echo "Database provisioning completed successfully.\n";
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo "Provisioning failed: " . $e->getMessage() . "\n";
    exit(1);
}


