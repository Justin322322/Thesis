-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2024 at 05:31 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `acadmeter`
--

-- --------------------------------------------------------

--
-- Table structure for table `action_logs`
--

CREATE TABLE `action_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `action_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `action_logs`
--

INSERT INTO `action_logs` (`log_id`, `user_id`, `action_type`, `description`, `action_timestamp`) VALUES
(4, 44, 'Account Approval', 'Approved action taken for User ID 44.', '2024-11-24 01:40:06');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('Login','Logout') NOT NULL,
  `activity_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `activity_type`, `activity_timestamp`) VALUES
(1, 44, '', '2024-11-22 11:59:19');

-- --------------------------------------------------------

--
-- Table structure for table `activity_types`
--

CREATE TABLE `activity_types` (
  `activity_type_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_number` varchar(20) NOT NULL,
  `position` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `user_id`, `employee_number`, `position`) VALUES
(1, 29, 'ADMIN001', 'System Administrator');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL,
  `excuse_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_rankings`
--

CREATE TABLE `class_rankings` (
  `ranking_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `quarter` tinyint(4) NOT NULL,
  `ranking_position` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_users_history`
--

CREATE TABLE `deleted_users_history` (
  `history_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `user_type` enum('Admin','Instructor','Student') NOT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `feedback_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `student_id`, `instructor_id`, `feedback_text`, `created_at`, `is_read`) VALUES
(5, 4, 1, 'adasdasdasdadsdas', '2024-11-24 16:24:56', 0),
(6, 11, 1, 'Test Feedback 3232323', '2024-11-24 16:29:58', 0);

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `grade_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `quarter` tinyint(4) NOT NULL,
  `component_id` int(11) NOT NULL,
  `grade` decimal(5,2) NOT NULL DEFAULT 0.00,
  `subcategories` longtext DEFAULT NULL CHECK (json_valid(`subcategories`)),
  `remarks` varchar(10) DEFAULT NULL,
  `academic_year` varchar(9) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `locked` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_components`
--

CREATE TABLE `grade_components` (
  `component_id` int(11) NOT NULL,
  `component_name` varchar(50) NOT NULL,
  `weight` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade_components`
--

INSERT INTO `grade_components` (`component_id`, `component_name`, `weight`) VALUES
(1, 'Written Works', 30.00),
(2, 'Performance Tasks', 50.00),
(3, 'Quarterly Assessment', 20.00);

-- --------------------------------------------------------

--
-- Table structure for table `grade_subcategories`
--

CREATE TABLE `grade_subcategories` (
  `subcategory_id` int(11) NOT NULL,
  `grade_id` int(11) NOT NULL,
  `subcategory_name` varchar(255) NOT NULL,
  `score` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grading_formulas`
--

CREATE TABLE `grading_formulas` (
  `formula_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `formula_name` varchar(255) NOT NULL,
  `quiz_weight` decimal(5,2) NOT NULL,
  `midterm_weight` decimal(5,2) NOT NULL,
  `final_weight` decimal(5,2) NOT NULL,
  `extracurricular_weight` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instructors`
--

CREATE TABLE `instructors` (
  `instructor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_number` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `instructors`
--

INSERT INTO `instructors` (`instructor_id`, `user_id`, `employee_number`) VALUES
(1, 40, 'EMP-0040');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `notification_type`, `is_read`, `created_at`) VALUES
(4, 44, 'Your account has been approved.', 'account_approval', 0, '2024-11-24 01:40:06');

-- --------------------------------------------------------

--
-- Table structure for table `participation`
--

CREATE TABLE `participation` (
  `participation_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `quarter` int(11) NOT NULL,
  `participation_score` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_predictions`
--

CREATE TABLE `performance_predictions` (
  `prediction_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `predicted_grade` decimal(5,2) NOT NULL,
  `prediction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `failing_probability` decimal(5,2) DEFAULT NULL COMMENT 'Probability of failing based on ML model',
  `status` enum('Pending','Notified','Resolved') DEFAULT 'Pending' COMMENT 'Status of intervention'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quarterly_performance`
--

CREATE TABLE `quarterly_performance` (
  `performance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `quarter` tinyint(4) NOT NULL COMMENT 'Quarter of the school year (1-4)',
  `quiz_score` decimal(5,2) DEFAULT NULL,
  `assignment_score` decimal(5,2) DEFAULT NULL,
  `extracurricular_score` decimal(5,2) DEFAULT NULL,
  `exam_score` decimal(5,2) DEFAULT NULL,
  `total_score` decimal(5,2) GENERATED ALWAYS AS (`quiz_score` * 0.3 + `assignment_score` * 0.2 + `exam_score` * 0.5) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `report_type` varchar(255) NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(255) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `school_year` varchar(9) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`section_id`, `section_name`, `subject_id`, `instructor_id`, `school_year`) VALUES
(30, 'Apple', NULL, 40, '2024-2025');

-- --------------------------------------------------------

--
-- Table structure for table `section_students`
--

CREATE TABLE `section_students` (
  `section_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `section_students`
--

INSERT INTO `section_students` (`section_id`, `student_id`) VALUES
(15, 3),
(15, 4),
(30, 4),
(30, 11);

-- --------------------------------------------------------

--
-- Table structure for table `section_subjects`
--

CREATE TABLE `section_subjects` (
  `section_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `first_name`, `last_name`) VALUES
(3, 41, 'Naruto', 'Uzumaki'),
(4, 42, 'Monkey D.', 'Luffy'),
(11, 44, 'Justin', 'Sibonga');

-- --------------------------------------------------------

--
-- Table structure for table `student_risk`
--

CREATE TABLE `student_risk` (
  `risk_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `quarter` tinyint(4) NOT NULL COMMENT 'School year quarter (1-4)',
  `risk_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `predicted_grade` decimal(5,2) NOT NULL DEFAULT 0.00,
  `intervention_needed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `passing_grade` decimal(5,2) DEFAULT 75.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`, `instructor_id`, `section_id`, `passing_grade`) VALUES
(21, 'Filipino', 0, NULL, 75.00),
(22, 'Science', 0, NULL, 75.00);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_evaluations`
--

CREATE TABLE `teacher_evaluations` (
  `evaluation_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `evaluation` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `user_type` enum('Admin','Instructor','Student') NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verification_code` varchar(32) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `sex` enum('Male','Female','Rather not say') NOT NULL,
  `verification_timestamp` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `first_name`, `last_name`, `password`, `email`, `user_type`, `status`, `created_at`, `verification_code`, `verified`, `reset_token`, `reset_token_expiry`, `dob`, `sex`, `verification_timestamp`) VALUES
(29, 'admin', 'Admin', 'User', '$2y$10$IaGhcvGNJYoX0NjaatqsaOvoN0pXGBwb4pr5IXSa3871730PbZDmi', 'admin@example.com', 'Admin', 'approved', '2024-11-21 06:31:35', NULL, 1, NULL, NULL, NULL, 'Male', NULL),
(40, 'Justin', 'Justin', 'Sibonga', '$2y$10$QaSfNlXj/1UG5Er9FNgvv.tMsrorDmKYyDvu9Ih5rpWwfi08esMXO', 'justinmarlosibonga@gmail.com', 'Instructor', 'approved', '2024-11-21 06:31:35', NULL, 1, '03d0317c10a3b6fcd844608a97df0c44', '2024-11-24 17:24:59', NULL, 'Male', NULL),
(41, 'naruto_uzumaki', 'Naruto', 'Uzumaki', '$2y$10$dummyhashedpassword1', 'naruto.uzumaki@example.com', 'Student', 'approved', '2024-11-21 10:06:36', NULL, 0, NULL, NULL, NULL, 'Male', NULL),
(42, 'monkey_luffy', 'Monkey D.', 'Luffy', '$2y$10$dummyhashedpassword2', 'monkey.luffy@example.com', 'Student', 'approved', '2024-11-21 10:06:36', NULL, 0, NULL, NULL, NULL, 'Male', NULL),
(44, 'justin', 'Justin', 'Sibonga', '$2y$10$RfXDQjnNMuAFGuKB2fVKveK3gx5/mLRPQvWmAxCQ7VGkKRZwvEGIe', 'pakalucamel@gmail.com', 'Student', 'approved', '2024-11-22 11:58:57', '4f8d16d6253b536e49dbe23666e33f95', 1, NULL, NULL, '1992-05-05', 'Male', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_approval_logs`
--

CREATE TABLE `user_approval_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` enum('approved','rejected') NOT NULL,
  `actioned_by` int(11) NOT NULL,
  `action_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_complete_student_performance`
-- (See below for the actual view)
--
CREATE TABLE `vw_complete_student_performance` (
`student_id` int(11)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`section_id` int(11)
,`section_name` varchar(255)
,`subject_id` int(11)
,`subject_name` varchar(100)
,`quarter` tinyint(4)
,`component_name` varchar(50)
,`weight` decimal(5,2)
,`grade` decimal(5,2)
,`subcategories` longtext
,`remarks` varchar(10)
,`academic_year` varchar(9)
,`attendance_percentage` decimal(29,2)
,`ranking_position` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_student_attendance`
-- (See below for the actual view)
--
CREATE TABLE `vw_student_attendance` (
`student_id` int(11)
,`quarter` int(1)
,`total_days` bigint(21)
,`attended_days` decimal(25,2)
,`attendance_percentage` decimal(29,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_student_grades`
-- (See below for the actual view)
--
CREATE TABLE `vw_student_grades` (
`grade_id` int(11)
,`student_id` int(11)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`section_id` int(11)
,`section_name` varchar(255)
,`subject_id` int(11)
,`subject_name` varchar(100)
,`quarter` tinyint(4)
,`component_name` varchar(50)
,`weight` decimal(5,2)
,`grade` decimal(5,2)
,`subcategories` longtext
,`remarks` varchar(10)
,`academic_year` varchar(9)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_complete_student_performance`
--
DROP TABLE IF EXISTS `vw_complete_student_performance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_complete_student_performance`  AS SELECT `g`.`student_id` AS `student_id`, `s`.`first_name` AS `first_name`, `s`.`last_name` AS `last_name`, `g`.`section_id` AS `section_id`, `sec`.`section_name` AS `section_name`, `g`.`subject_id` AS `subject_id`, `sub`.`subject_name` AS `subject_name`, `g`.`quarter` AS `quarter`, `gc`.`component_name` AS `component_name`, `gc`.`weight` AS `weight`, `g`.`grade` AS `grade`, `g`.`subcategories` AS `subcategories`, `g`.`remarks` AS `remarks`, `g`.`academic_year` AS `academic_year`, coalesce(`a`.`attendance_percentage`,0) AS `attendance_percentage`, `cr`.`ranking_position` AS `ranking_position` FROM ((((((`grades` `g` join `students` `s` on(`g`.`student_id` = `s`.`student_id`)) join `sections` `sec` on(`g`.`section_id` = `sec`.`section_id`)) join `subjects` `sub` on(`g`.`subject_id` = `sub`.`subject_id`)) join `grade_components` `gc` on(`g`.`component_id` = `gc`.`component_id`)) left join `vw_student_attendance` `a` on(`g`.`student_id` = `a`.`student_id` and `g`.`quarter` = `a`.`quarter`)) left join `class_rankings` `cr` on(`g`.`student_id` = `cr`.`student_id` and `g`.`quarter` = `cr`.`quarter`)) WHERE `cr`.`created_at` = (select max(`cr2`.`created_at`) from `class_rankings` `cr2` where `cr2`.`student_id` = `g`.`student_id` AND `cr2`.`quarter` = `g`.`quarter`) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_student_attendance`
--
DROP TABLE IF EXISTS `vw_student_attendance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_student_attendance`  AS SELECT `a`.`student_id` AS `student_id`, quarter(`a`.`date`) AS `quarter`, count(0) AS `total_days`, sum(case when `a`.`status` = 'Present' then 1 when `a`.`status` = 'Absent' then 0 when `a`.`status` = 'Late' then 0.5 when `a`.`status` = 'Excused' then 0.75 end) AS `attended_days`, round(sum(case when `a`.`status` = 'Present' then 1 when `a`.`status` = 'Absent' then 0 when `a`.`status` = 'Late' then 0.5 when `a`.`status` = 'Excused' then 0.75 end) / count(0) * 100,2) AS `attendance_percentage` FROM `attendance` AS `a` GROUP BY `a`.`student_id`, quarter(`a`.`date`) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_student_grades`
--
DROP TABLE IF EXISTS `vw_student_grades`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_student_grades`  AS SELECT `g`.`grade_id` AS `grade_id`, `g`.`student_id` AS `student_id`, `s`.`first_name` AS `first_name`, `s`.`last_name` AS `last_name`, `g`.`section_id` AS `section_id`, `sec`.`section_name` AS `section_name`, `g`.`subject_id` AS `subject_id`, `sub`.`subject_name` AS `subject_name`, `g`.`quarter` AS `quarter`, `gc`.`component_name` AS `component_name`, `gc`.`weight` AS `weight`, `g`.`grade` AS `grade`, `g`.`subcategories` AS `subcategories`, `g`.`remarks` AS `remarks`, `g`.`academic_year` AS `academic_year` FROM ((((`grades` `g` join `students` `s` on(`g`.`student_id` = `s`.`student_id`)) join `sections` `sec` on(`g`.`section_id` = `sec`.`section_id`)) join `subjects` `sub` on(`g`.`subject_id` = `sub`.`subject_id`)) join `grade_components` `gc` on(`g`.`component_id` = `gc`.`component_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `action_logs`
--
ALTER TABLE `action_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `activity_types`
--
ALTER TABLE `activity_types`
  ADD PRIMARY KEY (`activity_type_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `class_rankings`
--
ALTER TABLE `class_rankings`
  ADD PRIMARY KEY (`ranking_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `instructor_id` (`instructor_id`),
  ADD KEY `idx_student_quarter` (`student_id`,`quarter`);

--
-- Indexes for table `deleted_users_history`
--
ALTER TABLE `deleted_users_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD UNIQUE KEY `unique_grade` (`student_id`,`section_id`,`subject_id`,`quarter`,`component_id`,`academic_year`),
  ADD KEY `fk_grades_section` (`section_id`),
  ADD KEY `fk_grades_subject` (`subject_id`),
  ADD KEY `fk_grades_component` (`component_id`);

--
-- Indexes for table `grade_components`
--
ALTER TABLE `grade_components`
  ADD PRIMARY KEY (`component_id`),
  ADD UNIQUE KEY `unique_component` (`component_name`);

--
-- Indexes for table `grade_subcategories`
--
ALTER TABLE `grade_subcategories`
  ADD PRIMARY KEY (`subcategory_id`),
  ADD KEY `grade_id` (`grade_id`);

--
-- Indexes for table `grading_formulas`
--
ALTER TABLE `grading_formulas`
  ADD PRIMARY KEY (`formula_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `instructors`
--
ALTER TABLE `instructors`
  ADD PRIMARY KEY (`instructor_id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `participation`
--
ALTER TABLE `participation`
  ADD PRIMARY KEY (`participation_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `performance_predictions`
--
ALTER TABLE `performance_predictions`
  ADD PRIMARY KEY (`prediction_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `quarterly_performance`
--
ALTER TABLE `quarterly_performance`
  ADD PRIMARY KEY (`performance_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `section_name` (`section_name`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `section_students`
--
ALTER TABLE `section_students`
  ADD PRIMARY KEY (`section_id`,`student_id`),
  ADD KEY `fk_section_students_student` (`student_id`);

--
-- Indexes for table `section_subjects`
--
ALTER TABLE `section_subjects`
  ADD PRIMARY KEY (`section_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `unique_user_id` (`user_id`);

--
-- Indexes for table `student_risk`
--
ALTER TABLE `student_risk`
  ADD PRIMARY KEY (`risk_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `instructor_id` (`instructor_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_name` (`subject_name`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `teacher_evaluations`
--
ALTER TABLE `teacher_evaluations`
  ADD PRIMARY KEY (`evaluation_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_approval_logs`
--
ALTER TABLE `user_approval_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `actioned_by` (`actioned_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `action_logs`
--
ALTER TABLE `action_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `activity_types`
--
ALTER TABLE `activity_types`
  MODIFY `activity_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_rankings`
--
ALTER TABLE `class_rankings`
  MODIFY `ranking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deleted_users_history`
--
ALTER TABLE `deleted_users_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=254;

--
-- AUTO_INCREMENT for table `grade_components`
--
ALTER TABLE `grade_components`
  MODIFY `component_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `grade_subcategories`
--
ALTER TABLE `grade_subcategories`
  MODIFY `subcategory_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grading_formulas`
--
ALTER TABLE `grading_formulas`
  MODIFY `formula_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `instructors`
--
ALTER TABLE `instructors`
  MODIFY `instructor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `participation`
--
ALTER TABLE `participation`
  MODIFY `participation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `performance_predictions`
--
ALTER TABLE `performance_predictions`
  MODIFY `prediction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quarterly_performance`
--
ALTER TABLE `quarterly_performance`
  MODIFY `performance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `student_risk`
--
ALTER TABLE `student_risk`
  MODIFY `risk_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `teacher_evaluations`
--
ALTER TABLE `teacher_evaluations`
  MODIFY `evaluation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `user_approval_logs`
--
ALTER TABLE `user_approval_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `action_logs`
--
ALTER TABLE `action_logs`
  ADD CONSTRAINT `action_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `class_rankings`
--
ALTER TABLE `class_rankings`
  ADD CONSTRAINT `class_rankings_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_rankings_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`instructor_id`) ON DELETE CASCADE;

--
-- Constraints for table `deleted_users_history`
--
ALTER TABLE `deleted_users_history`
  ADD CONSTRAINT `deleted_users_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`instructor_id`);

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `fk_grades_component` FOREIGN KEY (`component_id`) REFERENCES `grade_components` (`component_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_grades_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_grades_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_grades_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `grade_subcategories`
--
ALTER TABLE `grade_subcategories`
  ADD CONSTRAINT `grade_subcategories_ibfk_1` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`grade_id`) ON DELETE CASCADE;

--
-- Constraints for table `grading_formulas`
--
ALTER TABLE `grading_formulas`
  ADD CONSTRAINT `grading_formulas_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`instructor_id`) ON DELETE CASCADE;

--
-- Constraints for table `instructors`
--
ALTER TABLE `instructors`
  ADD CONSTRAINT `instructors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `participation`
--
ALTER TABLE `participation`
  ADD CONSTRAINT `participation_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participation_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participation_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `performance_predictions`
--
ALTER TABLE `performance_predictions`
  ADD CONSTRAINT `fk_performance_predictions_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`instructor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_performance_predictions_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `quarterly_performance`
--
ALTER TABLE `quarterly_performance`
  ADD CONSTRAINT `quarterly_performance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quarterly_performance_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quarterly_performance_ibfk_3` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`instructor_id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;