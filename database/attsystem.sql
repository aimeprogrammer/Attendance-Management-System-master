-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 15, 2018 at 12:35 AM
-- Server version: 10.1.25-MariaDB
-- PHP Version: 5.6.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET FOREIGN_KEY_CHECKS=0;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `attsystem`
--
-- Note:
-- This file was extended to support the full Admin/Teacher/Student dashboards.
-- Existing tables are kept for backward compatibility with the current PHP pages.

-- --------------------------------------------------------

--
-- Table structure for table `admininfo`
--

CREATE TABLE `admininfo` (
  `username` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(30) NOT NULL,
  `fname` varchar(20) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `type` varchar(20) NOT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Backward-compat: minimal attendance tables stay compatible
CREATE TABLE `attendance` (
  `stat_id` varchar(20) NOT NULL,
  `course` varchar(20) NOT NULL,
  `st_status` varchar(10) NOT NULL,
  `stat_date` date NOT NULL,
  `check_in_time` TIME DEFAULT NULL,
  `check_out_time` TIME DEFAULT NULL,
  `status_type` ENUM('present', 'absent', 'late', 'half-day') DEFAULT 'present',
  `remarks` text DEFAULT NULL,
  KEY `stat_id` (`stat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE `reports` (
  `st_id` varchar(30) NOT NULL,
  `course` varchar(30) NOT NULL,
  `st_status` varchar(30) NOT NULL,
  `st_name` varchar(30) NOT NULL,
  `st_dept` varchar(30) NOT NULL,
  `st_batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE `students` (
  `st_id` varchar(20) NOT NULL,
  `st_name` varchar(20) NOT NULL,
  `st_dept` varchar(20) NOT NULL,
  `st_batch` int(4) NOT NULL,
  `st_sem` int(11) NOT NULL,
  `st_email` varchar(30) NOT NULL,
  `leave_balance` int(11) NOT NULL DEFAULT '15',
  PRIMARY KEY (`st_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE `teachers` (
  `tc_id` varchar(20) NOT NULL,
  `tc_name` varchar(20) NOT NULL,
  `tc_dept` varchar(20) NOT NULL,
  `tc_email` varchar(30) NOT NULL,
  `tc_course` varchar(20) NOT NULL,
  PRIMARY KEY (`tc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE `courses` (
  `course_id` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  PRIMARY KEY (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE `class_sections` (
  `section_id` int(11) NOT NULL AUTO_INCREMENT,
  `section_name` varchar(20) NOT NULL,
  PRIMARY KEY (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE `leave_requests` (
  `leave_id` int(11) NOT NULL AUTO_INCREMENT,
  `st_id` varchar(20) NOT NULL,
  `leave_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  PRIMARY KEY (`leave_id`),
  KEY `st_id` (`st_id`),
  CONSTRAINT `leave_ibfk_1` FOREIGN KEY (`st_id`) REFERENCES `students` (`st_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE `notifications` (
  `notif_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notif_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `log_time` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ============================================================
-- NEW: Core academic structure
-- ============================================================

CREATE TABLE `programs` (
  `program_id` varchar(30) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  PRIMARY KEY (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `program_fees` (
  `fee_id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` varchar(30) NOT NULL,
  `fee_type` ENUM('one-time', 'monthly') NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  PRIMARY KEY (`fee_id`),
  KEY `program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `batches` (
  `batch_id` varchar(30) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `batch_name` varchar(50) NOT NULL,
  `start_year` int(11) DEFAULT NULL,
  `end_year` int(11) DEFAULT NULL,
  PRIMARY KEY (`batch_id`),
  KEY `program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `subjects` (
  `subject_id` varchar(30) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  PRIMARY KEY (`subject_id`),
  UNIQUE KEY `uq_subject_code` (`subject_code`),
  KEY `program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- assignment of subject to batch (optional but helpful)
CREATE TABLE `batch_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(30) NOT NULL,
  `subject_id` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_batch_subject` (`batch_id`, `subject_id`),
  KEY `batch_id` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- NEW: Student enrollment + admission history
-- ============================================================

CREATE TABLE `student_enrollments` (
  `enrollment_id` int(11) NOT NULL AUTO_INCREMENT,
  `st_id` varchar(20) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `batch_id` varchar(30) NOT NULL,
  `semester` int(11) NOT NULL,
  `admission_date` date NOT NULL,
  `status` ENUM('active','deactivated') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`enrollment_id`),
  KEY `st_id` (`st_id`),
  KEY `program_id` (`program_id`),
  KEY `batch_id` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- NEW: Exams + Results
-- ============================================================

CREATE TABLE `exam_categories` (
  `exam_category_id` varchar(30) NOT NULL,
  `exam_category_name` varchar(100) NOT NULL,
  PRIMARY KEY (`exam_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `exams` (
  `exam_id` varchar(30) NOT NULL,
  `exam_category_id` varchar(30) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `batch_id` varchar(30) NOT NULL,
  `exam_name` varchar(100) NOT NULL,
  `exam_date` date NOT NULL,
  `mcq_marks` decimal(10,2) NOT NULL DEFAULT 0,
  `written_marks` decimal(10,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`exam_id`),
  KEY `exam_category_id` (`exam_category_id`),
  KEY `program_id` (`program_id`),
  KEY `batch_id` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `exam_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` varchar(30) NOT NULL,
  `subject_id` varchar(30) NOT NULL,
  `mcq_allocation` decimal(10,2) NOT NULL DEFAULT 0,
  `written_allocation` decimal(10,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_exam_subject` (`exam_id`, `subject_id`),
  KEY `exam_id` (`exam_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `marks_entries` (
  `entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` varchar(30) NOT NULL,
  `subject_id` varchar(30) NOT NULL,
  `st_id` varchar(20) NOT NULL,
  `mcq_obtained` decimal(10,2) NOT NULL DEFAULT 0,
  `written_obtained` decimal(10,2) NOT NULL DEFAULT 0,
  `total_obtained` decimal(10,2) NOT NULL DEFAULT 0,
  `grading` varchar(20) DEFAULT NULL,
  `entered_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`entry_id`),
  UNIQUE KEY `uq_entry` (`exam_id`, `subject_id`, `st_id`),
  KEY `exam_id` (`exam_id`),
  KEY `subject_id` (`subject_id`),
  KEY `st_id` (`st_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `results_publish` (
  `publish_id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` varchar(30) NOT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `published_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`publish_id`),
  UNIQUE KEY `uq_exam_publish` (`exam_id`),
  KEY `exam_id` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- NEW: Payments + Finance
-- ============================================================

CREATE TABLE `payment_schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` varchar(30) NOT NULL,
  `fee_month` int(11) DEFAULT NULL,
  `fee_year` int(11) NOT NULL,
  `one_time_amount` decimal(10,2) DEFAULT 0,
  `monthly_amount` decimal(10,2) DEFAULT 0,
  PRIMARY KEY (`schedule_id`),
  KEY `program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `student_fee_balances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `st_id` varchar(20) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `batch_id` varchar(30) NOT NULL,
  `total_due` decimal(12,2) NOT NULL DEFAULT 0,
  `total_paid` decimal(12,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_balance` (`st_id`, `program_id`, `batch_id`),
  KEY `st_id` (`st_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `st_id` varchar(20) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `batch_id` varchar(30) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `method` ENUM('manual','cash','card','bank_transfer') NOT NULL DEFAULT 'manual',
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` ENUM('pending','paid') NOT NULL DEFAULT 'paid',
  PRIMARY KEY (`payment_id`),
  KEY `st_id` (`st_id`),
  KEY `program_id` (`program_id`),
  KEY `batch_id` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `income_entries` (
  `income_id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_type` varchar(50) NOT NULL, -- fees, donation, etc.
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `entry_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`income_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `expense_entries` (
  `expense_id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL, -- salaries, utilities, etc.
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `entry_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`expense_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- NEW: SMS (gateway settings + send history)
-- ============================================================

CREATE TABLE `sms_gateway_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) NOT NULL DEFAULT 'custom',
  `api_key` varchar(255) DEFAULT NULL,
  `sender_id` varchar(30) DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'inactive',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sms_outbox` (
  `sms_id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_user_id` varchar(20) DEFAULT NULL,
  `recipient_phone` varchar(30) DEFAULT NULL,
  `message` text NOT NULL,
  `status` ENUM('queued','sent','failed','pending') NOT NULL DEFAULT 'queued',
  `provider_message_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`sms_id`),
  KEY `recipient_user_id` (`recipient_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- NEW: System preferences
-- ============================================================

CREATE TABLE `system_settings` (
  `key_name` varchar(100) NOT NULL,
  `value_text` text DEFAULT NULL,
  `value_int` int(11) DEFAULT NULL,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- NEW: Login/security extensions (activation/theme) - optional
-- ============================================================

ALTER TABLE `admininfo`
  ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1;

-- ============================================================
-- Seed values (keep current seeds if you want, but include minimal defaults)
-- ============================================================

-- Keep original inserts if they exist in your environment; this dump focuses on schema.
-- You can re-seed after applying this updated file.

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
