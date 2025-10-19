-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 11, 2025 at 02:14 AM
-- Server version: 10.4.14-MariaDB
-- PHP Version: 7.4.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `psau_admission`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `details` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `action`, `user_id`, `details`, `ip_address`, `created_at`) VALUES
(366, 'admin_login', 7, 'Admin logged in successfully', '::1', '2025-09-26 08:45:31'),
(367, 'admin_login', 7, 'Admin logged in successfully', '::1', '2025-10-06 08:57:43'),
(368, 'reject_application', 7, 'Admin rejected application for Jerico Sison. Reason: sorry', NULL, '2025-10-06 08:57:59'),
(369, 'email_sent', 34, 'Rejection email sent to jericosison22@gmail.com via Firebase. Reason: sorry', NULL, '2025-10-06 08:58:04'),
(370, 'admin_login', 7, 'Admin logged in successfully', '::1', '2025-10-06 09:46:57'),
(371, 'verify_application', 7, 'Admin verified application for Jerico Sison', NULL, '2025-10-06 09:47:03'),
(372, 'email_sent', 34, 'Verification email sent to jericosison22@gmail.com via Firebase', NULL, '2025-10-06 09:47:06'),
(373, 'admin_login', 7, 'Admin logged in successfully', '::1', '2025-10-08 07:56:42'),
(374, 'clear_attempts', 7, 'Cleared application attempts for PSAU589407', '::1', '2025-10-08 08:45:46'),
(375, 'admin_login', 7, 'Admin logged in successfully', '::1', '2025-10-09 06:15:13'),
(376, 'admin_logout', 7, 'Admin logged out successfully', '::1', '2025-10-09 07:13:05'),
(377, 'admin_login', 7, 'Admin logged in successfully', '::1', '2025-10-09 07:13:29'),
(378, 'verify_application', 7, 'Admin verified application for wew wew', NULL, '2025-10-09 07:25:14'),
(379, 'email_sent', 35, 'Verification email sent to jericosisonpogi@gmail.com via Firebase', NULL, '2025-10-09 07:25:19'),
(380, 'create_exam_schedule', 7, 'Created exam schedule for 2025-10-17 at 05:31 in Main Hall with capacity 3 (auto-assigned 1 applicants)', '::1', '2025-10-09 07:30:21'),
(381, 'score_upload', 7, 'Manual score entry for control number: PSAU225334', NULL, '2025-10-09 07:44:20'),
(382, 'course_selection', 35, 'User selected course preferences', NULL, '2025-10-09 07:46:59'),
(383, 'course_assigned', 7, 'Course BSIT - IT assigned to wew wew (ID: 35)', NULL, '2025-10-09 07:47:34'),
(384, 'add_content', 7, 'Added new enrollment_instructions content', NULL, '2025-10-09 08:06:21'),
(385, 'add_content', 7, 'Added new required_documents content', NULL, '2025-10-09 08:06:38'),
(386, 'admin_login', 7, 'Admin logged in successfully', '::1', '2025-10-09 10:11:22'),
(387, 'admin_login', 7, 'Admin logged in successfully', '::1', '2025-10-09 10:38:56'),
(388, 'admin_login', 7, 'Admin logged in successfully', '::1', '2025-10-10 03:06:25'),
(389, 'admin_login', 7, 'Admin logged in successfully', '::1', '2025-10-10 03:13:51'),
(390, 'verify_application', 7, 'Admin verified application for Jerico Sison', NULL, '2025-10-10 03:13:59'),
(391, 'email_sent', 34, 'Verification email sent to jericosison22@gmail.com via Firebase', NULL, '2025-10-10 03:14:03'),
(392, 'score_upload', 7, 'Manual score entry for control number: PSAU589407', NULL, '2025-10-10 03:14:43'),
(393, 'course_selection', 34, 'User selected course preferences', NULL, '2025-10-10 03:15:22'),
(394, 'course_assigned', 7, 'Course BSIT - IT assigned to Jerico Sison (ID: 34)', NULL, '2025-10-10 03:15:41'),
(395, 'profile_update', 34, 'User updated profile information', '::1', '2025-10-10 03:44:52'),
(396, 'verify_application', 7, 'Admin verified application for Jerico Sison', NULL, '2025-10-10 03:50:12'),
(397, 'email_sent', 34, 'Verification email sent to jericosison22@gmail.com via Firebase', NULL, '2025-10-10 03:50:15'),
(398, 'create_exam_schedule', 7, 'Created exam schedule for 2025-10-24 at 10:50 in Main Hall with capacity 10 (auto-assigned 1 applicants)', '::1', '2025-10-10 03:51:06'),
(399, 'bulk_score_upload', 7, 'Bulk score upload: 1 successful, 8 failed', NULL, '2025-10-10 04:05:12'),
(400, 'course_selection', 34, 'User selected course preferences', NULL, '2025-10-10 04:06:39'),
(401, 'course_assigned', 7, 'Course BSIT - IT assigned to Jerico Sison (ID: 34)', NULL, '2025-10-10 04:06:55'),
(402, 'admin_login', 7, 'Admin logged in successfully', '::1', '2025-10-10 06:57:05'),
(403, 'admin_logout', 7, 'Admin logged out successfully', '::1', '2025-10-10 07:21:43'),
(404, 'admin_login', 7, 'Admin logged in successfully', '::1', '2025-10-10 08:04:03'),
(405, 'admin_logout', 7, 'Admin logged out successfully', '::1', '2025-10-10 08:06:33'),
(406, 'admin_login', 7, 'Admin logged in successfully', '::1', '2025-10-10 11:06:40'),
(407, 'admin_logout', 7, 'Admin logged out successfully', '::1', '2025-10-10 11:18:20'),
(408, 'admin_login', 7, 'Admin logged in successfully', '::1', '2025-10-11 00:02:37'),
(409, 'reject_application', 7, 'Admin rejected application for Mark Obinario. Reason: 2x2', NULL, '2025-10-11 00:02:45'),
(410, 'email_sent', 36, 'Rejection email sent to vinceobinario08@gmaio.com via Firebase. Reason: 2x2', NULL, '2025-10-11 00:02:50'),
(411, 'admin_logout', 7, 'Admin logged out successfully', '::1', '2025-10-11 00:03:01');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile_number` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','registrar','department') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `mobile_number`, `password`, `role`, `created_at`) VALUES
(7, 'jerico', 'jericogutierrezsison12@gmail.com', '09513472168', '$2y$10$h81ZG0xk0f8MXSdJAswRuuu1tF8wdMKraq6kQH9s.Y8RXs07Ff3zu', 'admin', '2025-09-26 08:45:08');

-- --------------------------------------------------------

--
-- Table structure for table `admin_login_attempts`
--

CREATE TABLE `admin_login_attempts` (
  `id` int(11) UNSIGNED NOT NULL,
  `device_id` varchar(255) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_success` tinyint(1) NOT NULL DEFAULT 0,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `block_expires` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admin_login_attempts`
--

INSERT INTO `admin_login_attempts` (`id`, `device_id`, `attempt_time`, `is_success`, `is_blocked`, `block_expires`, `ip_address`, `user_agent`) VALUES
(25, 'dbb2d4d02ee7cd4fba1ef73d737fb421', '2025-09-26 08:45:31', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),
(26, '95d569aedc1744289469d48c841599f9', '2025-10-06 08:57:43', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0'),
(27, '95d569aedc1744289469d48c841599f9', '2025-10-06 09:46:57', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0'),
(28, '95d569aedc1744289469d48c841599f9', '2025-10-08 07:56:42', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0'),
(29, '95d569aedc1744289469d48c841599f9', '2025-10-09 06:15:13', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0'),
(30, '95d569aedc1744289469d48c841599f9', '2025-10-09 07:13:29', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0'),
(31, '95d569aedc1744289469d48c841599f9', '2025-10-09 10:11:22', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0'),
(32, 'dbb2d4d02ee7cd4fba1ef73d737fb421', '2025-10-09 10:38:56', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'),
(33, '17c9f17a19a1ab41a9d46a4e0286da42', '2025-10-10 03:06:25', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36'),
(34, '17c9f17a19a1ab41a9d46a4e0286da42', '2025-10-10 03:13:51', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36'),
(35, 'a208dbded40c684d4dd5f6ab22ae53e4', '2025-10-10 06:57:05', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0'),
(36, 'a208dbded40c684d4dd5f6ab22ae53e4', '2025-10-10 08:04:03', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0'),
(37, 'a208dbded40c684d4dd5f6ab22ae53e4', '2025-10-10 11:06:33', 0, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0'),
(38, 'a208dbded40c684d4dd5f6ab22ae53e4', '2025-10-10 11:06:40', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0'),
(39, 'a208dbded40c684d4dd5f6ab22ae53e4', '2025-10-11 00:02:37', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pdf_file` varchar(255) NOT NULL,
  `document_file_path` varchar(255) DEFAULT NULL,
  `document_file_size` int(11) DEFAULT NULL,
  `document_upload_date` datetime DEFAULT NULL,
  `image_2x2_path` varchar(255) DEFAULT NULL,
  `image_2x2_name` varchar(255) DEFAULT NULL,
  `image_2x2_size` int(11) DEFAULT NULL,
  `image_2x2_type` varchar(100) DEFAULT NULL,
  `pdf_validated` tinyint(1) DEFAULT 0,
  `validation_message` text DEFAULT NULL,
  `status` enum('Submitted','Verified','Rejected','Exam Scheduled','Score Posted','Course Assigned','Enrollment Scheduled','Enrolled') DEFAULT 'Submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `previous_school` varchar(255) DEFAULT NULL,
  `school_year` varchar(50) DEFAULT NULL,
  `strand` varchar(100) DEFAULT NULL,
  `gpa` decimal(5,2) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `user_id`, `pdf_file`, `document_file_path`, `document_file_size`, `document_upload_date`, `image_2x2_path`, `image_2x2_name`, `image_2x2_size`, `image_2x2_type`, `pdf_validated`, `validation_message`, `status`, `created_at`, `updated_at`, `previous_school`, `school_year`, `strand`, `gpa`, `rejection_reason`, `address`, `age`, `verified_at`) VALUES
(116, 34, 'PSAU589407_1760068078.pdf', 'uploads/PSAU589407_1760068078.pdf', 862503, '2025-10-10 11:48:03', 'images/PSAU589407_1760068078.png', 'PSAU589407_1760068078.png', 887943, 'png', 1, 'PDF validated successfully. Contains both grading periods, all grades are 75+, and document quality is good.', '', '2025-10-10 03:48:03', '2025-10-10 04:09:41', 'qwq', '2021-2022', 'ABM', '99.00', NULL, 'wew', 22, '2025-10-10 03:50:12'),
(117, 36, 'PSAU950806_1760093341.pdf', 'uploads/PSAU950806_1760093341.pdf', 224597, '2025-10-10 18:49:20', 'images/PSAU950806_1760093341.png', 'PSAU950806_1760093341.png', 237857, 'png', 1, 'PDF validated successfully. Contains both grading periods, all grades are 75+, and document quality is good.', 'Rejected', '2025-10-10 10:49:20', '2025-10-11 00:02:45', 'Tinajero National High School Annex', '2019-2021', 'STEM', '89.00', '2x2', 'San isidro', 17, NULL);

--
-- Triggers `applications`
--
DELIMITER $$
CREATE TRIGGER `trg_set_verified_at` BEFORE UPDATE ON `applications` FOR EACH ROW BEGIN
    IF NEW.status = 'Verified' AND OLD.status != 'Verified' THEN
        SET NEW.verified_at = CURRENT_TIMESTAMP;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `application_attempts`
--

CREATE TABLE `application_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `attempt_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `was_successful` tinyint(1) NOT NULL DEFAULT 0,
  `pdf_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `application_attempts`
--

INSERT INTO `application_attempts` (`id`, `user_id`, `attempt_date`, `was_successful`, `pdf_message`) VALUES
(152, 34, '2025-10-10 03:48:03', 1, 'PDF validated successfully. Contains both grading periods, all grades are 75+, and document quality is good.'),
(153, 36, '2025-10-10 10:49:20', 1, 'PDF validated successfully. Contains both grading periods, all grades are 75+, and document quality is good.');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `total_capacity` int(11) NOT NULL DEFAULT 50,
  `enrolled_students` int(11) NOT NULL DEFAULT 0,
  `slots` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `course_name`, `description`, `total_capacity`, `enrolled_students`, `slots`, `created_at`) VALUES
(1, 'BSCS', 'Bachelor of Science in Computer Science', 'A four-year program that focuses on computer theories and software development.', 50, 0, 50, '2025-05-15 00:50:55'),
(3, 'BSA', 'Bachelor of Science in Agriculture', 'A four-year program that focuses on agricultural science and technology.', 50, 0, 30, '2025-05-15 00:50:55'),
(4, 'BSED', 'Bachelor of Secondary Education', 'A four-year program that prepares students to become secondary education teachers.', 50, 1, 49, '2025-05-15 00:50:55'),
(5, 'BSBA', 'Bachelor of Science in Business Administration', 'A four-year program that focuses on business management and administration.', 50, 0, 50, '2025-05-19 06:30:07'),
(6, 'BSIT', 'IT', 'HSHS', 15, 3, 11, '2025-05-19 06:30:07');

-- --------------------------------------------------------

--
-- Table structure for table `course_assignments`
--

CREATE TABLE `course_assignments` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assignment_notes` text DEFAULT NULL,
  `preference_matched` tinyint(1) DEFAULT 0 COMMENT '1 if assigned course matched user preference',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `course_assignments`
--

INSERT INTO `course_assignments` (`id`, `application_id`, `user_id`, `course_id`, `assigned_by`, `assignment_notes`, `preference_matched`, `created_at`) VALUES
(21, 116, 34, 6, 7, '', 1, '2025-10-10 04:06:55');

-- --------------------------------------------------------

--
-- Table structure for table `course_selections`
--

CREATE TABLE `course_selections` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `preference_order` int(11) NOT NULL COMMENT '1 = first choice, 2 = second choice, 3 = third choice',
  `selection_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `course_selections`
--

INSERT INTO `course_selections` (`id`, `user_id`, `course_id`, `preference_order`, `selection_date`) VALUES
(58, 34, 6, 1, '2025-10-10 04:06:39'),
(59, 34, 4, 2, '2025-10-10 04:06:39'),
(60, 34, 3, 3, '2025-10-10 04:06:39');

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_assignments`
--

CREATE TABLE `enrollment_assignments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `is_auto_assigned` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `enrollment_assignments`
--

INSERT INTO `enrollment_assignments` (`id`, `student_id`, `schedule_id`, `assigned_by`, `is_auto_assigned`, `status`, `created_at`, `updated_at`) VALUES
(18, 34, 39, 7, 1, 'cancelled', '2025-10-10 04:07:23', '2025-10-10 04:09:41');

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_instructions`
--

CREATE TABLE `enrollment_instructions` (
  `id` int(11) NOT NULL,
  `instruction_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `enrollment_instructions`
--

INSERT INTO `enrollment_instructions` (`id`, `instruction_text`, `created_at`, `updated_at`) VALUES
(3, 'wew', '2025-10-09 08:06:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_schedules`
--

CREATE TABLE `enrollment_schedules` (
  `id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `enrollment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue` varchar(100) NOT NULL,
  `venue_id` int(11) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 30,
  `current_count` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_auto_assign` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `instructions` text DEFAULT NULL,
  `requirements` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `enrollment_schedules`
--

INSERT INTO `enrollment_schedules` (`id`, `course_id`, `enrollment_date`, `start_time`, `end_time`, `venue`, `venue_id`, `capacity`, `current_count`, `is_active`, `is_auto_assign`, `created_by`, `created_at`, `updated_at`, `instructions`, `requirements`) VALUES
(39, 6, '2025-10-25', '13:08:00', '14:08:00', 'Main Hall', 3, 2, 1, 1, 1, 7, '2025-10-10 04:07:23', '2025-10-10 04:07:24', '1. wew', '1. wawaw\r\n   - waawawwaawaww');

-- --------------------------------------------------------

--
-- Table structure for table `entrance_exam_scores`
--

CREATE TABLE `entrance_exam_scores` (
  `id` int(11) NOT NULL,
  `control_number` varchar(20) NOT NULL,
  `stanine_score` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `upload_method` enum('manual','bulk') NOT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `entrance_exam_scores`
--

INSERT INTO `entrance_exam_scores` (`id`, `control_number`, `stanine_score`, `uploaded_by`, `upload_date`, `upload_method`, `remarks`) VALUES
(21, 'PSAU589407', 7, 7, '2025-10-10 04:05:09', 'bulk', 'Sample entry');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `exam_schedule_id` int(11) DEFAULT NULL,
  `exam_date` date NOT NULL,
  `exam_time` time NOT NULL,
  `exam_time_end` time NOT NULL,
  `venue` varchar(100) NOT NULL,
  `venue_id` int(11) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `application_id`, `exam_schedule_id`, `exam_date`, `exam_time`, `exam_time_end`, `venue`, `venue_id`, `score`, `created_at`, `updated_at`) VALUES
(35, 114, 60, '2025-10-17', '05:31:00', '06:31:00', 'Main Hall', 3, NULL, '2025-10-09 07:30:20', '2025-10-09 07:30:20'),
(36, 115, 60, '2025-10-17', '05:31:00', '06:31:00', 'Main Hall', 3, NULL, '2025-10-10 03:14:12', '2025-10-10 03:14:12'),
(37, 116, 61, '2025-10-24', '10:50:00', '11:50:00', 'Main Hall', 3, NULL, '2025-10-10 03:51:03', '2025-10-10 03:51:03');

-- --------------------------------------------------------

--
-- Table structure for table `exam_instructions`
--

CREATE TABLE `exam_instructions` (
  `id` int(11) NOT NULL,
  `instruction_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `exam_required_documents`
--

CREATE TABLE `exam_required_documents` (
  `id` int(11) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `exam_schedules`
--

CREATE TABLE `exam_schedules` (
  `id` int(11) NOT NULL,
  `exam_date` date NOT NULL,
  `exam_time` time NOT NULL,
  `exam_time_end` time NOT NULL,
  `venue` varchar(100) NOT NULL,
  `venue_id` int(11) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 30,
  `current_count` int(11) NOT NULL DEFAULT 0,
  `instructions` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `exam_schedules`
--

INSERT INTO `exam_schedules` (`id`, `exam_date`, `exam_time`, `exam_time_end`, `venue`, `venue_id`, `capacity`, `current_count`, `instructions`, `requirements`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(61, '2025-10-24', '10:50:00', '11:50:00', 'Main Hall', 3, 10, 1, 'aw', 'aw', 1, 7, '2025-10-10 03:51:03', '2025-10-10 03:51:06');

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `question`, `answer`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'What are the admission requirements?', 'The basic requirements include:\n1. High School Report Card (Form 138)\n2. Certificate of Good Moral Character\n3. Birth Certificate\n4. 2x2 ID Pictures\n5. Completed Application Form', 1, 1, '2025-05-29 03:05:22', '2025-10-09 10:11:55'),
(2, 'How do I apply for admission?', 'To apply for admission:\n1. Create an account on our online portal\n2. Fill out the application form\n3. Upload required documents\n4. Wait for application verification\n5. Take the entrance exam\n6. Check course assignment results', 2, 1, '2025-05-29 03:05:22', '2025-05-29 03:05:22'),
(3, 'When is the entrance examination?', 'Entrance examinations are scheduled after your application is verified. You will receive an email notification with your exam schedule details.', 3, 1, '2025-05-29 03:05:22', '2025-05-29 03:05:22'),
(4, 'How long does the application process take?', 'The entire process typically takes 2-3 weeks from application submission to course assignment, depending on document verification and entrance exam scheduling.', 3, 1, '2025-05-29 03:05:22', '2025-10-10 06:58:34'),
(5, 'What courses are available?', 'PSAU offers various undergraduate programs including:\n- BS Information Technology\n- BS Computer Science\n- BS Agriculture\n- BS Education\n- BS Business Administration\nCheck our course listings for complete details.', 4, 1, '2025-05-29 03:05:22', '2025-10-10 06:58:34'),
(9, 'Where is the Admission Office', 'Located at the front of COED Building', 5, 1, '2025-10-10 08:06:29', '2025-10-10 08:06:29');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `device_id` varchar(255) NOT NULL,
  `attempt_time` datetime NOT NULL DEFAULT current_timestamp(),
  `is_success` tinyint(1) DEFAULT 0,
  `is_blocked` tinyint(1) DEFAULT 0,
  `block_expires` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `device_id`, `attempt_time`, `is_success`, `is_blocked`, `block_expires`, `ip_address`, `user_agent`) VALUES
(151, '95d569aedc1744289469d48c841599f9', '2025-10-06 16:57:19', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0'),
(152, '95d569aedc1744289469d48c841599f9', '2025-10-06 17:32:23', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0'),
(153, '95d569aedc1744289469d48c841599f9', '2025-10-06 18:45:15', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0'),
(154, '95d569aedc1744289469d48c841599f9', '2025-10-06 19:16:55', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0'),
(155, '95d569aedc1744289469d48c841599f9', '2025-10-08 16:44:17', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0'),
(156, '95d569aedc1744289469d48c841599f9', '2025-10-09 15:16:13', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0'),
(157, '95d569aedc1744289469d48c841599f9', '2025-10-09 17:30:59', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0'),
(158, '95d569aedc1744289469d48c841599f9', '2025-10-09 17:41:42', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0'),
(159, '95d569aedc1744289469d48c841599f9', '2025-10-09 18:10:50', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0'),
(160, '17c9f17a19a1ab41a9d46a4e0286da42', '2025-10-10 11:10:32', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36'),
(161, '17c9f17a19a1ab41a9d46a4e0286da42', '2025-10-10 11:12:31', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36'),
(162, 'a208dbded40c684d4dd5f6ab22ae53e4', '2025-10-10 15:21:53', 0, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0'),
(163, 'a208dbded40c684d4dd5f6ab22ae53e4', '2025-10-10 16:06:47', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0'),
(164, 'a208dbded40c684d4dd5f6ab22ae53e4', '2025-10-10 19:18:31', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0'),
(165, 'a208dbded40c684d4dd5f6ab22ae53e4', '2025-10-11 08:02:16', 0, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0'),
(166, 'a208dbded40c684d4dd5f6ab22ae53e4', '2025-10-11 08:02:20', 0, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0'),
(167, 'a208dbded40c684d4dd5f6ab22ae53e4', '2025-10-11 08:03:08', 1, 0, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0');

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `selector` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `reminder_logs`
--

CREATE TABLE `reminder_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reminder_type` varchar(50) NOT NULL,
  `sent_by` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `reminder_logs`
--

INSERT INTO `reminder_logs` (`id`, `user_id`, `reminder_type`, `sent_by`, `status`, `created_at`) VALUES
(21, 34, 'application_submission', 7, 'sent', '2025-10-10 04:16:20'),
(22, 35, 'application_submission', 7, 'sent', '2025-10-10 04:16:49');

-- --------------------------------------------------------

--
-- Table structure for table `required_documents`
--

CREATE TABLE `required_documents` (
  `id` int(11) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `required_documents`
--

INSERT INTO `required_documents` (`id`, `document_name`, `description`, `created_at`, `updated_at`) VALUES
(3, 'wawaw', 'waawawwaawaww', '2025-10-09 08:06:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `status_history`
--

CREATE TABLE `status_history` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `performed_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `status_history`
--

INSERT INTO `status_history` (`id`, `application_id`, `status`, `description`, `performed_by`, `created_at`) VALUES
(156, 107, 'Submitted', 'Application submitted with PDF upload', 'Jerico Sison', '2025-09-26 08:48:02'),
(157, 107, 'Submitted', 'Application submitted with PDF upload', 'Jerico Sison', '2025-10-06 09:32:51'),
(158, 108, 'Submitted', 'Application submitted with PDF upload', 'wew wew', '2025-10-06 10:11:12'),
(159, 109, 'Submitted', 'Application submitted with PDF upload', 'wew wew', '2025-10-06 10:21:03'),
(160, 110, 'Submitted', 'Application submitted with PDF upload', 'Jerico Sison', '2025-10-06 10:46:43'),
(161, 111, 'Submitted', 'Application submitted with PDF upload', 'Jerico Sison', '2025-10-06 11:27:51'),
(162, 112, 'Submitted', 'Application submitted with PDF upload', 'Jerico Sison', '2025-10-06 11:28:53'),
(163, 113, 'Submitted', 'Application submitted with PDF upload', 'Jerico Sison', '2025-10-06 11:31:45'),
(164, 114, 'Submitted', 'Application submitted with PDF upload', 'wew wew', '2025-10-09 07:18:18'),
(165, 114, 'Score Posted', 'Entrance exam score has been posted', 'jerico', '2025-10-09 07:44:18'),
(166, 114, 'Course Assigned', 'Course assigned by admin', 'jerico', '2025-10-09 07:47:34'),
(167, 114, 'Enrolled', 'Student accessed enrollment eligibility page', 'wew wew', '2025-10-09 07:49:25'),
(168, 114, 'Enrolled', 'Enrollment marked completed by admin', 'jerico', '2025-10-09 07:52:36'),
(169, 114, 'Enrollment Cancelled', 'Enrollment cancelled by admin', 'jerico', '2025-10-10 03:11:36'),
(170, 115, 'Submitted', 'Application submitted with PDF upload', 'Jerico Sison', '2025-10-10 03:13:25'),
(171, 115, 'Score Posted', 'Entrance exam score has been posted', 'jerico', '2025-10-10 03:14:40'),
(172, 115, 'Course Assigned', 'Course assigned by admin', 'jerico', '2025-10-10 03:15:41'),
(173, 115, 'Enrollment Cancelled', 'Enrollment cancelled by admin', 'jerico', '2025-10-10 03:16:33'),
(174, 116, 'Submitted', 'Application submitted with PDF upload', 'Jerico Sison', '2025-10-10 03:48:03'),
(175, 116, 'Score Posted', 'Entrance exam score has been posted', 'jerico', '2025-10-10 04:05:09'),
(176, 116, 'Course Assigned', 'Course assigned by admin', 'jerico', '2025-10-10 04:06:55'),
(177, 116, 'Enrollment Cancelled', 'Enrollment cancelled by admin', 'jerico', '2025-10-10 04:09:41'),
(178, 117, 'Submitted', 'Application submitted with PDF upload', 'Mark Obinario', '2025-10-10 10:49:20');

-- --------------------------------------------------------

--
-- Table structure for table `student_feedback_counts`
--

CREATE TABLE `student_feedback_counts` (
  `id` int(11) NOT NULL,
  `course` varchar(50) NOT NULL,
  `stanine` int(11) NOT NULL,
  `gwa` decimal(5,2) NOT NULL,
  `strand` varchar(100) NOT NULL,
  `rating` varchar(20) NOT NULL,
  `hobbies` text DEFAULT NULL,
  `count` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `student_feedback_counts`
--

INSERT INTO `student_feedback_counts` (`id`, `course`, `stanine`, `gwa`, `strand`, `rating`, `hobbies`, `count`, `created_at`, `updated_at`) VALUES
(1, 'BSCS', 7, '89.00', 'TVL', 'like', 'reading', 1, '2025-10-10 08:56:57', '2025-10-10 08:56:57'),
(2, 'BSA', 7, '89.00', 'TVL', 'neutral', 'reading', 1, '2025-10-10 08:56:57', '2025-10-10 08:56:57'),
(3, 'BSBA', 8, '89.00', 'ABM', 'like', 'reading', 1, '2025-10-10 08:58:50', '2025-10-10 08:58:50'),
(4, 'BSCS', 7, '78.00', 'ABM', 'like', 'proramming', 1, '2025-10-10 08:59:55', '2025-10-10 08:59:55'),
(5, 'BSA', 7, '78.00', 'ABM', 'neutral', 'proramming', 1, '2025-10-10 08:59:55', '2025-10-10 08:59:55'),
(6, 'BSBA', 7, '78.00', 'ABM', 'dislike', 'proramming', 1, '2025-10-10 08:59:55', '2025-10-10 08:59:55');

-- --------------------------------------------------------

--
-- Table structure for table `unanswered_questions`
--

CREATE TABLE `unanswered_questions` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `unanswered_questions`
--

INSERT INTO `unanswered_questions` (`id`, `question`, `created_at`) VALUES
(1, 'When is the entrance examination?', '2025-10-10 07:56:30'),
(3, 'Where is the Admission Office', '2025-10-10 08:07:29'),
(4, 'who to look for in admission office?', '2025-10-10 08:39:20'),
(5, 'where', '2025-10-11 00:12:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `control_number` varchar(10) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `is_flagged` tinyint(1) NOT NULL DEFAULT 0,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `block_reason` varchar(255) DEFAULT NULL,
  `mobile_number` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `gender` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `control_number`, `first_name`, `last_name`, `email`, `is_flagged`, `is_blocked`, `block_reason`, `mobile_number`, `password`, `is_verified`, `created_at`, `updated_at`, `gender`, `birth_date`, `address`) VALUES
(34, 'PSAU589407', 'Jerico', 'Sison', 'jericosison22@gmail.com', 0, 0, NULL, '09513472167', '$2y$10$5ew84js6vMAkErDPluyfYu7bkpBwvkYfuc5O2arkj4YbiRjTf6J6u', 1, '2025-09-26 08:46:42', '2025-10-10 03:48:03', 'Male', '2000-10-16', 'wew'),
(35, 'PSAU225334', 'wew', 'wew', 'jericosisonpogi@gmail.com', 0, 0, NULL, '9513472168', '$2y$10$LAdUlWPlD9w1jp6mcLhfMupf/g9QTf62ol9ZWxP8iEvvrIm882NoO', 1, '2025-10-06 10:09:31', '2025-10-09 07:18:18', NULL, NULL, 'wew'),
(36, 'PSAU950806', 'Mark', 'Obinario', 'vinceobinario08@gmaio.com', 0, 0, NULL, '9123456789', '$2y$10$IBSZSBA003ohpxq25cCTWebsoLjF7lkEoWlLi6FetuDGuWRVnQWWa', 1, '2025-10-10 07:23:55', '2025-10-10 10:49:20', NULL, NULL, 'San isidro');

-- --------------------------------------------------------

--
-- Table structure for table `venues`
--

CREATE TABLE `venues` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 30,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `venues`
--

INSERT INTO `venues` (`id`, `name`, `capacity`, `description`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Computer Lab A', 40, 'Main computer laboratory with 40 workstations', 1, 1, '2025-05-14 16:50:55', '2025-05-19 03:19:57'),
(2, 'Computer Lab B', 35, 'Secondary computer laboratory with 35 workstations', 1, 1, '2025-05-14 16:50:55', '2025-05-19 03:19:57'),
(3, 'Main Hall', 100, 'wwewewewew', 1, 1, '2025-05-14 16:50:55', '2025-05-20 04:18:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_login_attempts`
--
ALTER TABLE `admin_login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_device_id` (`device_id`),
  ADD KEY `idx_attempt_time` (`attempt_time`),
  ADD KEY `idx_is_blocked` (`is_blocked`),
  ADD KEY `idx_block_expires` (`block_expires`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_applications_verified_at` (`verified_at`);

--
-- Indexes for table `application_attempts`
--
ALTER TABLE `application_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `idx_total_capacity` (`total_capacity`),
  ADD KEY `idx_enrolled_students` (`enrolled_students`);

--
-- Indexes for table `course_assignments`
--
ALTER TABLE `course_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_id` (`application_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `course_selections`
--
ALTER TABLE `course_selections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_course_unique` (`user_id`,`course_id`),
  ADD UNIQUE KEY `user_preference_unique` (`user_id`,`preference_order`),
  ADD KEY `course_id_fk` (`course_id`);

--
-- Indexes for table `enrollment_assignments`
--
ALTER TABLE `enrollment_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `enrollment_instructions`
--
ALTER TABLE `enrollment_instructions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `enrollment_schedules`
--
ALTER TABLE `enrollment_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venue_id` (`venue_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `fk_enrollment_schedules_course` (`course_id`);

--
-- Indexes for table `entrance_exam_scores`
--
ALTER TABLE `entrance_exam_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `control_number` (`control_number`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `exam_schedule_id` (`exam_schedule_id`),
  ADD KEY `fk_exams_venue_id` (`venue_id`);

--
-- Indexes for table `exam_instructions`
--
ALTER TABLE `exam_instructions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exam_required_documents`
--
ALTER TABLE `exam_required_documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `fk_exam_schedules_venue_id` (`venue_id`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `attempt_time` (`attempt_time`),
  ADD KEY `is_blocked` (`is_blocked`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `selector` (`selector`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reminder_logs`
--
ALTER TABLE `reminder_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `sent_by` (`sent_by`);

--
-- Indexes for table `required_documents`
--
ALTER TABLE `required_documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `status_history`
--
ALTER TABLE `status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `student_feedback_counts`
--
ALTER TABLE `student_feedback_counts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_course_profile` (`course`,`stanine`,`gwa`,`strand`,`rating`),
  ADD KEY `idx_feedback_course` (`course`),
  ADD KEY `idx_feedback_profile` (`stanine`,`gwa`,`strand`);

--
-- Indexes for table `unanswered_questions`
--
ALTER TABLE `unanswered_questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `control_number` (`control_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `mobile_number` (`mobile_number`);

--
-- Indexes for table `venues`
--
ALTER TABLE `venues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=412;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `admin_login_attempts`
--
ALTER TABLE `admin_login_attempts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `application_attempts`
--
ALTER TABLE `application_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `course_assignments`
--
ALTER TABLE `course_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `course_selections`
--
ALTER TABLE `course_selections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `enrollment_assignments`
--
ALTER TABLE `enrollment_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `enrollment_instructions`
--
ALTER TABLE `enrollment_instructions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `enrollment_schedules`
--
ALTER TABLE `enrollment_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `entrance_exam_scores`
--
ALTER TABLE `entrance_exam_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `exam_instructions`
--
ALTER TABLE `exam_instructions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `exam_required_documents`
--
ALTER TABLE `exam_required_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reminder_logs`
--
ALTER TABLE `reminder_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `required_documents`
--
ALTER TABLE `required_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `status_history`
--
ALTER TABLE `status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT for table `student_feedback_counts`
--
ALTER TABLE `student_feedback_counts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `unanswered_questions`
--
ALTER TABLE `unanswered_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `venues`
--
ALTER TABLE `venues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_admin_fk` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `application_attempts`
--
ALTER TABLE `application_attempts`
  ADD CONSTRAINT `application_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollment_assignments`
--
ALTER TABLE `enrollment_assignments`
  ADD CONSTRAINT `enrollment_assignments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `enrollment_assignments_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `enrollment_schedules` (`id`),
  ADD CONSTRAINT `enrollment_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `admins` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
