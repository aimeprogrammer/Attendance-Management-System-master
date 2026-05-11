-- ============================================================
-- EXTENDED DATABASE SCHEMA FOR COMPREHENSIVE ADMIN SYSTEM
-- ============================================================

-- NEW: Payments + Finance (continued from previous)
CREATE TABLE IF NOT EXISTS `student_payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `st_id` varchar(20) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `payment_amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` ENUM('cash', 'cheque', 'transfer', 'credit_card') NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `status` ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
  `recorded_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `st_id` (`st_id`),
  KEY `program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `income_entries` (
  `income_id` int(11) NOT NULL AUTO_INCREMENT,
  `income_type` varchar(100) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `income_date` date NOT NULL,
  `source` varchar(100) DEFAULT NULL,
  `recorded_by` varchar(20) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`income_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `expense_entries` (
  `expense_id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_category` varchar(100) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `description` text,
  `recorded_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`expense_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- NEW: Communication System
-- ============================================================

CREATE TABLE IF NOT EXISTS `announcements` (
  `announcement_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `announcement_type` ENUM('notice', 'announcement', 'alert') NOT NULL DEFAULT 'announcement',
  `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
  `visibility` ENUM('admin', 'teachers', 'students', 'all') NOT NULL DEFAULT 'all',
  `published_by` varchar(20) NOT NULL,
  `published_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `expiry_date` date DEFAULT NULL,
  `program_id` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`announcement_id`),
  KEY `idx_announcements_program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sms_messages` (
  `sms_id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient` varchar(20) NOT NULL,
  `recipient_phone` varchar(20) NOT NULL,
  `message_content` text NOT NULL,
  `message_type` ENUM('result', 'attendance', 'payment', 'notice', 'custom') NOT NULL,
  `status` ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
  `sent_by` varchar(20) DEFAULT NULL,
  `sent_date` timestamp NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sms_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- NEW: User Management + Settings
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` varchar(20) NOT NULL,
  `user_type` ENUM('admin', 'teacher', 'staff', 'student') NOT NULL,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `phone` varchar(20) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `login_count` int(11) NOT NULL DEFAULT 0,
  `last_login` timestamp NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `role` varchar(50) NOT NULL,
  `permissions` text,
  PRIMARY KEY (`role_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_login_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `login_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `logout_date` timestamp NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `browser_info` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- NEW: System Settings & Configuration
-- ============================================================

CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` longtext NOT NULL,
  `setting_type` ENUM('string', 'number', 'boolean', 'json') NOT NULL DEFAULT 'string',
  `description` text,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sms_gateway_config` (
  `config_id` int(11) NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `api_secret` varchar(255) DEFAULT NULL,
  `sender_id` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`config_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text,
  `old_value` longtext,
  `new_value` longtext,
  `status` ENUM('success', 'failure') NOT NULL DEFAULT 'success',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- NEW: Certificates & Documents
-- ============================================================

CREATE TABLE IF NOT EXISTS `student_certificates` (
  `cert_id` int(11) NOT NULL AUTO_INCREMENT,
  `st_id` varchar(20) NOT NULL,
  `cert_type` ENUM('admission', 'completion', 'transcript', 'conduct', 'custom') NOT NULL,
  `issued_date` date NOT NULL,
  `certificate_number` varchar(100) UNIQUE,
  `status` ENUM('draft', 'issued', 'archived') NOT NULL DEFAULT 'draft',
  `file_path` varchar(255) DEFAULT NULL,
  `issued_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`cert_id`),
  KEY `st_id` (`st_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Indexes for better query performance
-- ============================================================

ALTER TABLE `student_enrollments` ADD INDEX `idx_admission_date` (`admission_date`);
ALTER TABLE `student_payments` ADD INDEX `idx_payment_date` (`payment_date`);
ALTER TABLE `exams` ADD INDEX `idx_exam_date` (`exam_date`);
ALTER TABLE `announcements` ADD INDEX `idx_published_date` (`published_date`);
ALTER TABLE `activity_logs` ADD INDEX `idx_user_action` (`user_id`, `action`);

-- ============================================================
-- NEW: Course Materials (student academic resources)
-- ============================================================

-- MVP: one row per uploaded material tied to a subject (and optionally a batch).
-- Students see materials for subjects associated to their enrolled batches.
CREATE TABLE IF NOT EXISTS `course_materials` (
  `material_id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` varchar(30) NOT NULL,
  `batch_id` varchar(30) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` ENUM('pdf','doc','docx','ppt','pptx','xls','xlsx','image','link','other') NOT NULL DEFAULT 'pdf',
  `external_url` varchar(500) DEFAULT NULL,
  `uploaded_by` varchar(20) DEFAULT NULL,
  `uploaded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`material_id`),
  KEY `subject_id` (`subject_id`),
  KEY `batch_id` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional mapping table if later you want one material for multiple subjects.
CREATE TABLE IF NOT EXISTS `course_material_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `subject_id` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_material_subject` (`material_id`, `subject_id`),
  KEY `material_id` (`material_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
