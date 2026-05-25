-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 20, 2026 at 05:56 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `attsystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `old_value` longtext DEFAULT NULL,
  `new_value` longtext DEFAULT NULL,
  `status` enum('success','failure') NOT NULL DEFAULT 'success',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `entity_type`, `entity_id`, `ip_address`, `user_agent`, `old_value`, `new_value`, `status`, `created_at`) VALUES
(1, 'admin', 'Logged into system', 'auth', NULL, NULL, NULL, NULL, NULL, 'success', '2024-11-06 06:00:00'),
(2, 'admin', 'Added new student', 'student', 'UR2024004', NULL, NULL, NULL, NULL, 'success', '2024-11-05 12:30:00'),
(3, 'registrar', 'Generated attendance report', 'report', 'attendance_oct2024', NULL, NULL, NULL, NULL, 'success', '2024-11-04 08:15:00');

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
  `type` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admininfo`
--

INSERT INTO `admininfo` (`username`, `password`, `email`, `fname`, `phone`, `type`) VALUES
('Aimeprogrammer', '$2y$10$oBGov54VN.NvRhYcnqAkjOODL2eyMyax2TomgQ6PyHkv0QNeGqAV.', 'aimeprogrammer@gmail.com', 'Aimeprogrammer', '0795567748', 'student'),
('Gloire', 'goxprogrammer@gmail.com', 'goxprogrammer@gmail.com', 'Mugisha Gloire', '0791486601', 'student'),
('oasis', '$2y$10$Cgx66WCvpTJVjAJpGgEM8OQ11/kp7oqYH7T8YaW3.9yxpFRRbxI5W', 'admin@system.com', 'System Admin', '1234567890', 'admin'),
('programmer', 'Aime2007@', 'aime@gmail.com', 'Aimeprogrammer', '0784555868', 'student');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `announcement_type` enum('notice','announcement','alert') NOT NULL DEFAULT 'announcement',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `visibility` enum('admin','teachers','students','all') NOT NULL DEFAULT 'all',
  `published_by` varchar(20) NOT NULL,
  `published_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `program_id` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `announcement_type`, `priority`, `visibility`, `published_by`, `published_date`, `expiry_date`, `is_active`, `program_id`) VALUES
(1, 'Mid-Semester Exams Start November 15th', 'All students are reminded that mid-semester exams will begin on November 15th. Please check your exam schedules.', 'announcement', 'high', 'all', 'admin', '2026-05-10 16:55:59', '2024-11-20', 1, NULL),
(2, 'Attendance Policy Update', 'Minimum 75% attendance required for exam eligibility. Current attendance can be checked in your dashboard.', 'notice', 'medium', 'students', 'registrar', '2026-05-10 16:55:59', '2024-12-31', 1, NULL),
(3, 'Faculty Meeting', 'All faculty members are required to attend the meeting on Friday at 2PM.', 'announcement', 'medium', 'teachers', 'dean_cst', '2026-05-10 16:55:59', '2024-11-10', 1, NULL),
(4, 'Welcome to the Term', 'Please ensure attendance is marked daily.', 'announcement', 'high', 'all', 'ADMIN', '2026-05-10 18:06:09', NULL, 1, NULL),
(5, 'Exam Schedule Update', 'Exams start next week. Check exam dates in Exams section.', 'notice', 'medium', 'teachers', 'ADMIN', '2026-05-10 18:06:09', NULL, 1, NULL),
(6, 'Seed Announcement', 'This is seeded content so student dashboard pages are not empty.', 'announcement', 'medium', 'all', 'admin', '2026-05-09 22:00:00', NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `stat_id` varchar(20) NOT NULL,
  `course` varchar(20) NOT NULL,
  `st_status` varchar(10) NOT NULL,
  `stat_date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `status_type` enum('present','absent','late','half-day') DEFAULT 'present',
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`stat_id`, `course`, `st_status`, `stat_date`, `check_in_time`, `check_out_time`, `status_type`, `remarks`) VALUES
('UR2024001', 'CSC101', 'Present', '2024-10-15', '08:15:00', '16:30:00', 'present', 'Good performance'),
('UR2024001', 'CSC101', 'Present', '2024-10-16', '08:10:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Present', '2024-10-17', '08:20:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Present', '2024-10-18', '08:05:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Absent', '2024-10-19', NULL, NULL, 'absent', 'Sick leave'),
('UR2024001', 'CSC101', 'Present', '2024-10-22', '08:10:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Late', '2024-10-23', '09:15:00', '16:30:00', 'late', 'Traffic jam'),
('UR2024001', 'CSC101', 'Present', '2024-10-24', '08:00:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Present', '2024-10-25', '08:20:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Present', '2024-10-28', '08:10:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Present', '2024-10-29', '08:15:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Present', '2024-10-30', '08:05:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Present', '2024-10-31', '08:10:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Present', '2024-11-01', '08:00:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Present', '2024-11-04', '08:10:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Present', '2024-11-05', '08:15:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Present', '2024-11-06', '08:05:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Present', '2024-11-07', '08:10:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Present', '2024-11-08', '08:20:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Absent', '2024-11-09', NULL, NULL, 'absent', 'Family event'),
('UR2024001', 'CSC101', 'Present', '2024-11-10', '08:10:00', '16:30:00', 'present', ''),
('UR2024002', 'CSC101', 'Present', '2024-10-15', '08:20:00', '16:30:00', 'present', ''),
('UR2024002', 'CSC101', 'Absent', '2024-10-16', NULL, NULL, 'absent', 'Medical'),
('UR2024002', 'CSC101', 'Present', '2024-10-17', '08:15:00', '16:30:00', 'present', ''),
('UR2024002', 'CSC101', 'Late', '2024-10-18', '09:30:00', '16:30:00', 'late', 'Late bus'),
('UR2024002', 'CSC101', 'Present', '2024-11-01', '08:10:00', '16:30:00', 'present', ''),
('UR2024002', 'CSC101', 'Present', '2024-11-04', '08:05:00', '16:30:00', 'present', ''),
('UR2024002', 'CSC101', 'Absent', '2024-11-05', NULL, NULL, 'absent', 'Not feeling well'),
('UR2024003', 'CSE201', 'Present', '2024-10-15', '08:00:00', '16:30:00', 'present', ''),
('UR2024003', 'CSE201', 'Present', '2024-10-16', '08:15:00', '16:30:00', 'present', ''),
('UR2024003', 'CSE201', 'Present', '2024-11-01', '08:20:00', '16:30:00', 'present', ''),
('UR2024003', 'CSE201', 'Present', '2024-11-04', '08:10:00', '16:30:00', 'present', ''),
('UR2023001', 'CSC201', 'Present', '2024-10-15', '08:10:00', '16:30:00', 'present', ''),
('UR2023001', 'CSC201', 'Present', '2024-10-16', '08:05:00', '16:30:00', 'present', ''),
('UR2023001', 'CSC201', 'Absent', '2024-10-17', NULL, NULL, 'absent', ''),
('UR2023001', 'CSC201', 'Present', '2024-11-01', '08:00:00', '16:30:00', 'present', ''),
('UR2023001', 'CSC201', 'Present', '2024-11-04', '08:15:00', '16:30:00', 'present', ''),
('UR2023001', 'CSC201', 'Late', '2024-11-05', '09:10:00', '16:30:00', 'late', ''),
('UR2023001', 'CSC201', 'Present', '2024-11-06', '08:20:00', '16:30:00', 'present', ''),
('UR2023001', 'CSC201', 'Present', '2024-11-07', '08:05:00', '16:30:00', 'present', ''),
('UR2023001', 'CSC201', 'Present', '2024-11-08', '08:10:00', '16:30:00', 'present', ''),
('UR2023001', 'CSC201', 'Present', '2024-11-09', '08:15:00', '16:30:00', 'present', ''),
('UR2023001', 'CSC201', 'Present', '2024-11-10', '08:00:00', '16:30:00', 'present', ''),
('UR2024001', 'CSC101', 'Present', '2026-04-12', '08:10:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-13', '08:15:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-14', '08:05:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Absent', '2026-04-15', NULL, NULL, 'absent', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-16', '08:20:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-17', '08:10:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Late', '2026-04-18', '09:15:00', '16:30:00', 'late', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-19', '08:00:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-20', '08:10:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-21', '08:15:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-22', '08:05:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-23', '08:10:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Absent', '2026-04-24', NULL, NULL, 'absent', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-25', '08:00:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-26', '08:10:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-27', '08:20:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-28', '08:05:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-29', '08:10:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Late', '2026-04-30', '09:00:00', '16:30:00', 'late', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-01', '08:15:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-02', '08:10:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-03', '08:05:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-04', '08:20:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Absent', '2026-05-05', NULL, NULL, 'absent', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-06', '08:10:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-07', '08:00:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-08', '08:15:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-09', '08:10:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-10', '08:05:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-11', '08:20:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-12', '08:20:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Absent', '2026-04-13', NULL, NULL, 'absent', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-14', '08:15:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Absent', '2026-04-15', NULL, NULL, 'absent', NULL),
('UR2024002', 'CSC101', 'Late', '2026-04-16', '09:30:00', '16:30:00', 'late', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-17', '08:10:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Absent', '2026-04-18', NULL, NULL, 'absent', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-19', '08:05:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-20', '08:20:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Absent', '2026-04-21', NULL, NULL, 'absent', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-22', '08:10:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Absent', '2026-04-23', NULL, NULL, 'absent', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-24', '08:15:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-25', '08:00:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Absent', '2026-04-26', NULL, NULL, 'absent', NULL),
('UR2024002', 'CSC101', 'Late', '2026-04-27', '09:15:00', '16:30:00', 'late', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-28', '08:10:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Absent', '2026-04-29', NULL, NULL, 'absent', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-30', '08:20:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-05-01', '08:05:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Absent', '2026-05-02', NULL, NULL, 'absent', NULL),
('UR2024002', 'CSC101', 'Present', '2026-05-03', '08:10:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Late', '2026-05-04', '09:00:00', '16:30:00', 'late', NULL),
('UR2024002', 'CSC101', 'Present', '2026-05-05', '08:15:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Absent', '2026-05-06', NULL, NULL, 'absent', NULL),
('UR2024002', 'CSC101', 'Present', '2026-05-07', '08:00:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-05-08', '08:10:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Absent', '2026-05-09', NULL, NULL, 'absent', NULL),
('UR2024002', 'CSC101', 'Present', '2026-05-10', '08:20:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-05-11', '08:05:00', '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-05-11', NULL, '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-05-10', '08:17:00', '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-05-09', NULL, '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-05-08', '08:57:00', '16:30:00', 'late', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-05-07', NULL, '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-05-06', NULL, '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-05-05', '08:56:00', NULL, 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-05-04', '08:43:00', '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-05-03', '08:46:00', '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-05-02', '08:16:00', '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-05-01', '08:25:00', '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-30', '08:22:00', NULL, 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-29', '08:28:00', NULL, 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-28', '08:27:00', NULL, 'present', NULL),
('ALU2024001', 'CSC101', 'Late', '2026-04-27', '08:56:00', '16:30:00', 'late', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-26', '08:45:00', '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-25', '08:53:00', '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-24', '08:17:00', '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-23', '08:13:00', '16:30:00', 'late', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-22', '08:22:00', '16:30:00', 'late', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-21', '08:20:00', '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-20', '08:35:00', '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-19', '08:53:00', NULL, 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-18', NULL, '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-17', '08:40:00', '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Late', '2026-04-16', '08:16:00', '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-15', '08:55:00', '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-14', '08:29:00', '16:30:00', 'present', NULL),
('ALU2024001', 'CSC101', 'Present', '2026-04-13', '08:11:00', NULL, 'late', NULL),
('ALU2024001', 'CSC101', 'Late', '2026-04-12', '08:42:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-05-11', '08:30:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Late', '2026-05-10', '08:19:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-05-09', '08:37:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-05-08', '08:36:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-05-07', '08:11:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-05-06', '08:33:00', '16:30:00', 'late', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-05-05', '08:29:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-05-04', '08:44:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Late', '2026-05-03', '08:59:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-05-02', '08:53:00', NULL, 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-05-01', NULL, '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-30', '08:15:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-29', '08:13:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-28', '08:37:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-27', '08:52:00', '16:30:00', 'absent', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-26', '08:30:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-25', '08:12:00', '16:30:00', 'late', NULL),
('ALU2024002', 'CSC101', 'Late', '2026-04-24', '08:32:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Late', '2026-04-23', '08:16:00', '16:30:00', 'late', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-22', '08:39:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-21', '08:49:00', '16:30:00', 'late', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-20', '08:18:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-19', '08:33:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-18', '08:36:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-17', '08:44:00', '16:30:00', 'late', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-16', '08:39:00', '16:30:00', 'late', NULL),
('ALU2024002', 'CSC101', 'Late', '2026-04-15', '08:38:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-14', '08:24:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-13', '08:59:00', '16:30:00', 'present', NULL),
('ALU2024002', 'CSC101', 'Present', '2026-04-12', '08:21:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-05-11', '08:40:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-05-10', NULL, '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-05-09', '08:29:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-05-08', '08:49:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-05-07', '08:53:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-05-06', '08:33:00', '16:30:00', 'late', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-05-05', NULL, '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-05-04', '08:21:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-05-03', '08:54:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-05-02', '08:54:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-05-01', NULL, '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-30', '08:56:00', '16:30:00', 'late', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-29', '08:23:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-28', '08:15:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-27', '08:38:00', NULL, 'present', NULL),
('BATCH24_001', 'CSC101', 'Late', '2026-04-26', '08:11:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-25', NULL, '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-24', '08:44:00', '16:30:00', 'late', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-23', '08:19:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-22', '08:51:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-21', '08:43:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-20', '08:47:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-19', NULL, NULL, 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-18', '08:32:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-17', '08:22:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-16', '08:57:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-15', NULL, '16:30:00', 'late', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-14', '08:46:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Present', '2026-04-13', '08:39:00', '16:30:00', 'present', NULL),
('BATCH24_001', 'CSC101', 'Late', '2026-04-12', '08:14:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Absent', '2026-05-11', '08:48:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-05-10', '08:48:00', NULL, 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-05-09', '08:55:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-05-08', '08:31:00', '16:30:00', 'late', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-05-07', '08:46:00', '16:30:00', 'late', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-05-06', '08:42:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-05-05', '08:13:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Late', '2026-05-04', '08:34:00', NULL, 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-05-03', '08:48:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Late', '2026-05-02', '08:55:00', NULL, 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-05-01', '08:52:00', NULL, 'present', NULL),
('BATCH24_002', 'CSC101', 'Late', '2026-04-30', '08:34:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-29', '08:46:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-28', '08:29:00', NULL, 'present', NULL),
('BATCH24_002', 'CSC101', 'Late', '2026-04-27', '08:44:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-26', '08:36:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-25', '08:45:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-24', '08:35:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-23', '08:11:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-22', '08:30:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-21', '08:15:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-20', '08:20:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-19', '08:11:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-18', '08:10:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-17', '08:51:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-16', '08:41:00', NULL, 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-15', NULL, '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-14', '08:23:00', '16:30:00', 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-13', '08:46:00', NULL, 'present', NULL),
('BATCH24_002', 'CSC101', 'Present', '2026-04-12', '08:51:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-05-11', '08:48:00', '16:30:00', 'late', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-05-10', '08:49:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-05-09', '08:59:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-05-08', '08:53:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-05-07', '08:14:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-05-06', '08:55:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-05-05', NULL, '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-05-04', '08:59:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-05-03', '08:20:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-05-02', '08:33:00', NULL, 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-05-01', '08:32:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-04-30', '08:50:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-04-29', '08:53:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-04-28', '08:39:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-04-27', '08:23:00', '16:30:00', 'late', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-04-26', '08:25:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-04-25', '08:44:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-04-24', '08:35:00', '16:30:00', 'late', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-04-23', '08:52:00', NULL, 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-04-22', '08:22:00', NULL, 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-04-21', '08:24:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-04-20', '08:47:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Late', '2026-04-19', '08:28:00', '16:30:00', 'late', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-04-18', NULL, NULL, 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-04-17', NULL, '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-04-16', '08:33:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Absent', '2026-04-15', '08:46:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Late', '2026-04-14', '08:27:00', '16:30:00', 'present', NULL),
('BATCH24_003', 'CSC101', 'Present', '2026-04-13', '08:21:00', '16:30:00', 'late', NULL),
('BATCH24_003', 'CSC101', 'Late', '2026-04-12', '08:53:00', NULL, 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-05-11', '08:44:00', '16:30:00', 'absent', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-05-10', '08:28:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-05-09', '08:43:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-05-08', '08:25:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Late', '2026-05-07', '08:26:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-05-06', '08:52:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-05-05', '08:34:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-05-04', '08:25:00', NULL, 'late', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-05-03', NULL, '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-05-02', NULL, '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-05-01', '08:54:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-30', NULL, '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Late', '2026-04-29', NULL, '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-28', '08:35:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-27', '08:27:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Late', '2026-04-26', '08:56:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-25', NULL, '16:30:00', 'late', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-24', '08:46:00', NULL, 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-23', NULL, '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-22', '08:54:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-21', '08:10:00', '16:30:00', 'late', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-20', '08:30:00', '16:30:00', 'late', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-19', '08:58:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-18', '08:24:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-17', '08:34:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Late', '2026-04-16', '08:54:00', '16:30:00', 'late', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-15', '08:11:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-14', '08:48:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-13', '08:27:00', '16:30:00', 'present', NULL),
('BATCH24_004', 'CSC101', 'Present', '2026-04-12', '08:26:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-05-11', NULL, '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-05-10', '08:22:00', '16:30:00', 'late', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-05-09', '08:13:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-05-08', '08:53:00', '16:30:00', 'late', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-05-07', '08:50:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-05-06', '08:50:00', '16:30:00', 'late', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-05-05', '08:41:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-05-04', '08:49:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-05-03', NULL, '16:30:00', 'late', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-05-02', '08:19:00', NULL, 'late', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-05-01', '08:25:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-04-30', '08:28:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-04-29', '08:34:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-04-28', '08:45:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-04-27', '08:59:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-04-26', '08:56:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Late', '2026-04-25', '08:25:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-04-24', '08:19:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-04-23', '08:38:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Late', '2026-04-22', '08:27:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-04-21', '08:20:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-04-20', '08:35:00', NULL, 'present', NULL),
('BATCH24_005', 'CSC101', 'Late', '2026-04-19', '08:22:00', '16:30:00', 'late', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-04-18', '08:22:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-04-17', '08:51:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-04-16', '08:12:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-04-15', NULL, '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-04-14', '08:23:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Present', '2026-04-13', '08:43:00', '16:30:00', 'present', NULL),
('BATCH24_005', 'CSC101', 'Late', '2026-04-12', NULL, '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-05-11', '08:23:00', '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-05-10', '08:49:00', '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-05-09', '08:56:00', '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-05-08', '08:21:00', '16:30:00', 'late', NULL),
('RP2024001', 'CSC101', 'Present', '2026-05-07', '08:34:00', NULL, 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-05-06', '08:46:00', '16:30:00', 'late', NULL),
('RP2024001', 'CSC101', 'Present', '2026-05-05', '08:10:00', '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-05-04', '08:22:00', '16:30:00', 'late', NULL),
('RP2024001', 'CSC101', 'Present', '2026-05-03', NULL, '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-05-02', '08:14:00', '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-05-01', '08:26:00', '16:30:00', 'late', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-30', '08:43:00', '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-29', '08:24:00', NULL, 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-28', '08:12:00', '16:30:00', 'late', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-27', '08:47:00', NULL, 'present', NULL),
('RP2024001', 'CSC101', 'Late', '2026-04-26', '08:35:00', NULL, 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-25', '08:29:00', '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-24', '08:58:00', '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-23', NULL, NULL, 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-22', '08:44:00', '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-21', '08:46:00', '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-20', NULL, '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-19', '08:47:00', '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-18', '08:45:00', '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-17', NULL, '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-16', '08:57:00', NULL, 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-15', '08:22:00', '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-14', NULL, '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-13', NULL, '16:30:00', 'present', NULL),
('RP2024001', 'CSC101', 'Present', '2026-04-12', '08:53:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Late', '2026-05-11', NULL, '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-05-10', NULL, '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Late', '2026-05-09', '08:27:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-05-08', '08:49:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-05-07', '08:32:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-05-06', '08:34:00', '16:30:00', 'late', NULL),
('RP2024002', 'CSC101', 'Present', '2026-05-05', '08:44:00', NULL, 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-05-04', '08:28:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-05-03', NULL, '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-05-02', '08:17:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-05-01', '08:19:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Late', '2026-04-30', '08:41:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-29', '08:40:00', NULL, 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-28', '08:41:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-27', '08:58:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-26', '08:21:00', NULL, 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-25', NULL, '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-24', NULL, '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-23', '08:51:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-22', '08:31:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-21', '08:20:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-20', '08:50:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-19', '08:52:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Late', '2026-04-18', '08:34:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-17', '08:40:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-16', '08:39:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-15', '08:35:00', '16:30:00', 'late', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-14', '08:22:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-13', '08:16:00', '16:30:00', 'present', NULL),
('RP2024002', 'CSC101', 'Present', '2026-04-12', '08:40:00', '16:30:00', 'late', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-11', '08:21:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-10', '08:45:00', '16:30:00', 'late', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-09', '08:20:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-08', '08:31:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Late', '2026-05-07', NULL, '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-06', '08:18:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-05', NULL, '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-04', '08:26:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-03', '08:58:00', '16:30:00', 'late', NULL),
('UR2024001', 'CSC101', 'Late', '2026-05-02', '08:50:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-05-01', NULL, NULL, 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-30', '08:31:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-29', '08:27:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-28', '08:36:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-27', '08:31:00', NULL, 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-26', '08:44:00', NULL, 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-25', '08:58:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Late', '2026-04-24', NULL, '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-23', '08:39:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-22', NULL, '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-21', '08:26:00', '16:30:00', 'late', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-20', '08:19:00', '16:30:00', 'late', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-19', '08:52:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-18', '08:20:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Late', '2026-04-17', '08:26:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-16', '08:11:00', NULL, 'present', NULL),
('UR2024001', 'CSC101', 'Late', '2026-04-15', '08:52:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-14', '08:26:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Present', '2026-04-13', '08:59:00', '16:30:00', 'present', NULL),
('UR2024001', 'CSC101', 'Late', '2026-04-12', NULL, '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-05-11', '08:55:00', NULL, 'present', NULL),
('UR2024002', 'CSC101', 'Late', '2026-05-10', '08:22:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Late', '2026-05-09', NULL, '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-05-08', NULL, '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Late', '2026-05-07', '08:43:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-05-06', '08:17:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-05-05', '08:44:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-05-04', '08:47:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-05-03', NULL, '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-05-02', '08:15:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-05-01', '08:39:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-30', '08:37:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-29', '08:27:00', NULL, 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-28', '08:19:00', '16:30:00', 'late', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-27', '08:16:00', NULL, 'late', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-26', '08:21:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-25', '08:45:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Late', '2026-04-24', NULL, '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-23', '08:40:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Late', '2026-04-22', '08:53:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Late', '2026-04-21', '08:57:00', '16:30:00', 'late', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-20', NULL, '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-19', '08:39:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-18', '08:42:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-17', '08:25:00', '16:30:00', 'late', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-16', NULL, '16:30:00', 'late', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-15', '08:12:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-14', NULL, '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-13', '08:19:00', '16:30:00', 'present', NULL),
('UR2024002', 'CSC101', 'Present', '2026-04-12', '08:45:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Present', '2026-05-11', NULL, '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Present', '2026-05-10', '08:16:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Present', '2026-05-09', NULL, '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Late', '2026-05-08', '08:11:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Late', '2026-05-07', '08:27:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Late', '2026-05-06', '08:11:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Late', '2026-05-05', '08:46:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Present', '2026-05-04', '08:35:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Late', '2026-05-03', '08:57:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Present', '2026-05-02', NULL, '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Present', '2026-05-01', '08:44:00', NULL, 'present', NULL),
('UR2024003', 'CSC101', 'Late', '2026-04-30', '08:21:00', NULL, 'present', NULL),
('UR2024003', 'CSC101', 'Present', '2026-04-29', '08:39:00', NULL, 'present', NULL),
('UR2024003', 'CSC101', 'Late', '2026-04-28', '08:40:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Present', '2026-04-27', '08:28:00', NULL, 'present', NULL),
('UR2024003', 'CSC101', 'Late', '2026-04-26', NULL, '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Late', '2026-04-25', '08:32:00', NULL, 'present', NULL),
('UR2024003', 'CSC101', 'Late', '2026-04-24', '08:53:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Late', '2026-04-23', '08:40:00', '16:30:00', 'late', NULL),
('UR2024003', 'CSC101', 'Present', '2026-04-22', '08:25:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Late', '2026-04-21', NULL, '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Present', '2026-04-20', '08:43:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Present', '2026-04-19', '08:34:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Present', '2026-04-18', '08:55:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Present', '2026-04-17', '08:55:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Present', '2026-04-16', NULL, NULL, 'present', NULL),
('UR2024003', 'CSC101', 'Late', '2026-04-15', NULL, '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Late', '2026-04-14', '08:14:00', '16:30:00', 'late', NULL),
('UR2024003', 'CSC101', 'Present', '2026-04-13', '08:44:00', '16:30:00', 'present', NULL),
('UR2024003', 'CSC101', 'Present', '2026-04-12', '08:23:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Late', '2026-05-11', '08:31:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-05-10', '08:17:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-05-09', '08:33:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Late', '2026-05-08', NULL, '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Late', '2026-05-07', NULL, '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-05-06', '08:53:00', NULL, 'late', NULL),
('UR2024004', 'CSC101', 'Present', '2026-05-05', '08:41:00', NULL, 'late', NULL),
('UR2024004', 'CSC101', 'Present', '2026-05-04', '08:21:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-05-03', '08:23:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Late', '2026-05-02', '08:52:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-05-01', NULL, '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-04-30', '08:25:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-04-29', NULL, '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-04-28', '08:44:00', NULL, 'present', NULL),
('UR2024004', 'CSC101', 'Late', '2026-04-27', '08:26:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-04-26', NULL, '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Late', '2026-04-25', '08:24:00', '16:30:00', 'late', NULL),
('UR2024004', 'CSC101', 'Present', '2026-04-24', '08:12:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Late', '2026-04-23', '08:28:00', '16:30:00', 'late', NULL),
('UR2024004', 'CSC101', 'Present', '2026-04-22', '08:30:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Late', '2026-04-21', '08:46:00', NULL, 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-04-20', '08:39:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-04-19', '08:17:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-04-18', '08:51:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Late', '2026-04-17', '08:16:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-04-16', '08:18:00', NULL, 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-04-15', '08:13:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-04-14', '08:16:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-04-13', '08:44:00', '16:30:00', 'present', NULL),
('UR2024004', 'CSC101', 'Present', '2026-04-12', '08:11:00', '16:30:00', 'late', NULL),
('UR2024005', 'CSC101', 'Present', '2026-05-11', '08:41:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-05-10', '08:15:00', '16:30:00', 'late', NULL),
('UR2024005', 'CSC101', 'Late', '2026-05-09', '08:10:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-05-08', '08:47:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-05-07', '08:50:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Late', '2026-05-06', '08:16:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-05-05', '08:31:00', '16:30:00', 'late', NULL),
('UR2024005', 'CSC101', 'Present', '2026-05-04', '08:15:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-05-03', '08:49:00', '16:30:00', 'late', NULL),
('UR2024005', 'CSC101', 'Present', '2026-05-02', '08:38:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-05-01', '08:53:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Late', '2026-04-30', '08:27:00', '16:30:00', 'late', NULL),
('UR2024005', 'CSC101', 'Present', '2026-04-29', '08:12:00', NULL, 'present', NULL),
('UR2024005', 'CSC101', 'Late', '2026-04-28', '08:33:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Late', '2026-04-27', '08:22:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-04-26', '08:42:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-04-25', '08:19:00', NULL, 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-04-24', '08:45:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Late', '2026-04-23', '08:35:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-04-22', '08:16:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-04-21', '08:14:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-04-20', '08:54:00', NULL, 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-04-19', '08:30:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-04-18', '08:44:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-04-17', '08:23:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-04-16', '08:52:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-04-15', NULL, '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-04-14', NULL, '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-04-13', '08:24:00', '16:30:00', 'present', NULL),
('UR2024005', 'CSC101', 'Present', '2026-04-12', NULL, '16:30:00', 'present', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_summary`
--

CREATE TABLE `attendance_summary` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `total_present` int(11) DEFAULT 0,
  `total_absent` int(11) DEFAULT 0,
  `total_late` int(11) DEFAULT 0,
  `percentage` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `attendance_summary`
--

INSERT INTO `attendance_summary` (`id`, `student_id`, `month`, `year`, `total_present`, `total_absent`, `total_late`, `percentage`) VALUES
(1, 'UR2024001', 4, 2026, 15, 2, 2, 78.95),
(2, 'UR2024001', 5, 2026, 10, 1, 0, 90.91),
(3, 'UR2024002', 4, 2026, 10, 7, 2, 52.63),
(4, 'UR2024002', 5, 2026, 7, 3, 1, 63.64);

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `batch_id` varchar(30) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `batch_name` varchar(50) NOT NULL,
  `start_year` int(11) DEFAULT NULL,
  `end_year` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`batch_id`, `program_id`, `batch_name`, `start_year`, `end_year`) VALUES
('', '', 'Seed Batch', 2026, 2027),
('BATCH-CS-2023', 'PG-CS', 'Computer Science 2023', 2023, 2027),
('BATCH-CS-2024', 'PG-CS', 'Computer Science 2024', 2024, 2028),
('BATCH-IT-2024', 'PG-IT', 'Information Technology 2024', 2024, 2028),
('BATCH-SE-2023', 'PG-SE', 'Software Engineering 2023', 2023, 2027),
('BATCH-SE-2024', 'PG-SE', 'Software Engineering 2024', 2024, 2028),
('CS-2023', 'PG-CS', 'CS Batch 2023-2027', 2023, 2027),
('CS-2024', 'PG-CS', 'CS Batch 2024-2028', 2024, 2028),
('IT-2024', 'PG-IT', 'IT Batch 2024-2028', 2024, 2028),
('SE-2024', 'PG-SE', 'SE Batch 2024-2028', 2024, 2028),
('SEED-BATCH', 'SEED-PROG', 'Seed Batch', 2026, 2027);

-- --------------------------------------------------------

--
-- Stand-in structure for view `batch_attendance_analytics`
-- (See below for the actual view)
--
CREATE TABLE `batch_attendance_analytics` (
`st_batch` int(4)
,`academic_year` bigint(12)
,`enrolled_students` bigint(21)
,`attendance_records` bigint(21)
,`total_present` decimal(22,0)
,`total_absent` decimal(22,0)
,`total_late` decimal(22,0)
,`avg_attendance_score` decimal(5,2)
,`attendance_rate` decimal(28,2)
,`monthly_trend` decimal(29,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `batch_attendance_stats`
-- (See below for the actual view)
--
CREATE TABLE `batch_attendance_stats` (
`st_batch` int(4)
,`total_students` bigint(21)
,`total_attendance_records` bigint(21)
,`total_present` decimal(22,0)
,`total_absent` decimal(22,0)
,`total_late` decimal(22,0)
,`avg_attendance_score` decimal(5,2)
,`attendance_percentage` decimal(28,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `batch_subjects`
--

CREATE TABLE `batch_subjects` (
  `id` int(11) NOT NULL,
  `batch_id` varchar(30) NOT NULL,
  `subject_id` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `batch_subjects`
--

INSERT INTO `batch_subjects` (`id`, `batch_id`, `subject_id`) VALUES
(1, 'CS-2024', 'SUBJ001'),
(2, 'CS-2024', 'SUBJ002'),
(4, 'IT-2024', 'SUBJ004'),
(3, 'SE-2024', 'SUBJ003');

-- --------------------------------------------------------

--
-- Table structure for table `class_sections`
--

CREATE TABLE `class_sections` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_materials`
--

CREATE TABLE `course_materials` (
  `material_id` int(11) NOT NULL,
  `subject_id` varchar(30) NOT NULL,
  `batch_id` varchar(30) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` enum('pdf','doc','docx','ppt','pptx','xls','xlsx','image','link','other') NOT NULL DEFAULT 'pdf',
  `external_url` varchar(500) DEFAULT NULL,
  `uploaded_by` varchar(20) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_material_subjects`
--

CREATE TABLE `course_material_subjects` (
  `id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `subject_id` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `exam_id` varchar(30) NOT NULL,
  `exam_category_id` varchar(30) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `batch_id` varchar(30) NOT NULL,
  `exam_name` varchar(100) NOT NULL,
  `exam_date` date NOT NULL,
  `mcq_marks` decimal(10,2) NOT NULL DEFAULT 0.00,
  `written_marks` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`exam_id`, `exam_category_id`, `program_id`, `batch_id`, `exam_name`, `exam_date`, `mcq_marks`, `written_marks`) VALUES
('EX-2024-CS101-FIN', 'CAT-FINAL', 'PG-CS', 'CS-2024', 'Final Exam - Programming', '2024-12-15', 40.00, 60.00),
('EX-2024-CS101-MID', 'CAT-MID', 'PG-CS', 'CS-2024', 'Mid Semester Exam - Programming', '2024-11-15', 30.00, 70.00),
('EX-2024-CS102-MID', 'CAT-MID', 'PG-CS', 'CS-2024', 'Mid Semester Exam - Database', '2024-11-18', 30.00, 70.00),
('EX-2024-CS103-MID', 'CAT-MID', 'PG-CS', 'CS-2024', 'Mid Exam - Web Development', '2024-11-22', 25.00, 75.00);

-- --------------------------------------------------------

--
-- Table structure for table `exam_categories`
--

CREATE TABLE `exam_categories` (
  `exam_category_id` varchar(30) NOT NULL,
  `exam_category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `exam_categories`
--

INSERT INTO `exam_categories` (`exam_category_id`, `exam_category_name`) VALUES
('', 'Mid Term'),
('CAT-ASSIGN', 'Assignment/Project'),
('CAT-FINAL', 'Final Semester Examination'),
('CAT-MID', 'Mid Semester Examination'),
('CAT-QUIZ', 'Quiz/Continuous Assessment');

-- --------------------------------------------------------

--
-- Table structure for table `exam_subjects`
--

CREATE TABLE `exam_subjects` (
  `id` int(11) NOT NULL,
  `exam_id` varchar(30) NOT NULL,
  `subject_id` varchar(30) NOT NULL,
  `mcq_allocation` decimal(10,2) NOT NULL DEFAULT 0.00,
  `written_allocation` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_subjects`
--

INSERT INTO `exam_subjects` (`id`, `exam_id`, `subject_id`, `mcq_allocation`, `written_allocation`) VALUES
(1, 'EX-2024-CS101-MID', 'SUBJ001', 30.00, 70.00),
(2, 'EX-2024-CS101-MID', 'SUBJ002', 30.00, 70.00),
(3, 'EX-2024-CS102-MID', 'SUBJ001', 30.00, 70.00),
(4, 'EX-2024-CS102-MID', 'SUBJ002', 30.00, 70.00),
(5, 'EX-2024-CS101-FIN', 'SUBJ001', 40.00, 60.00),
(6, 'EX-2024-CS103-MID', 'SUBJ003', 25.00, 75.00);

-- --------------------------------------------------------

--
-- Table structure for table `expense_entries`
--

CREATE TABLE `expense_entries` (
  `id` int(11) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_entries`
--

INSERT INTO `expense_entries` (`id`, `category`, `amount`, `currency`, `entry_date`, `notes`) VALUES
(1, 'Salaries', 5000000.00, 'RWF', '2024-10-30', 'October Staff Salaries'),
(2, 'Utilities', 500000.00, 'RWF', '2024-10-25', 'Electricity and Internet'),
(3, 'Equipment', 1200000.00, 'RWF', '2024-10-15', 'Computer Lab Maintenance');

-- --------------------------------------------------------

--
-- Table structure for table `income_entries`
--

CREATE TABLE `income_entries` (
  `id` int(11) NOT NULL,
  `entry_type` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `income_entries`
--

INSERT INTO `income_entries` (`id`, `entry_type`, `amount`, `currency`, `entry_date`, `notes`) VALUES
(1, 'Tuition Fees', 1500000.00, 'RWF', '2024-10-01', 'September 2024 Fees Collection'),
(2, 'Registration Fees', 500000.00, 'RWF', '2024-10-05', 'New Student Registration'),
(3, 'Tuition Fees', 1500000.00, 'RWF', '2024-10-01', 'September 2024 Fees Collection'),
(4, 'Registration Fees', 500000.00, 'RWF', '2024-10-05', 'New Student Registration');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `leave_id` int(11) NOT NULL,
  `st_id` varchar(20) NOT NULL,
  `leave_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marks_entries`
--

CREATE TABLE `marks_entries` (
  `entry_id` int(11) NOT NULL,
  `exam_id` varchar(30) NOT NULL,
  `subject_id` varchar(30) NOT NULL,
  `st_id` varchar(20) NOT NULL,
  `mcq_obtained` decimal(10,2) NOT NULL DEFAULT 0.00,
  `written_obtained` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_obtained` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grading` varchar(20) DEFAULT NULL,
  `entered_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marks_entries`
--

INSERT INTO `marks_entries` (`entry_id`, `exam_id`, `subject_id`, `st_id`, `mcq_obtained`, `written_obtained`, `total_obtained`, `grading`, `entered_by`, `created_at`, `updated_at`) VALUES
(1, 'EX-2024-CS101-MID', 'SUBJ001', 'UR2024001', 25.00, 60.00, 85.00, 'A', 'admin', '2026-05-10 18:50:00', NULL),
(2, 'EX-2024-CS101-MID', 'SUBJ002', 'UR2024001', 28.00, 65.00, 93.00, 'A+', 'admin', '2026-05-10 18:50:00', NULL),
(3, 'EX-2024-CS101-MID', 'SUBJ001', 'UR2024002', 20.00, 55.00, 75.00, 'B+', 'admin', '2026-05-10 18:50:00', NULL),
(4, 'EX-2024-CS101-MID', 'SUBJ002', 'UR2024002', 22.00, 58.00, 80.00, 'A-', 'admin', '2026-05-10 18:50:00', NULL),
(5, 'EX-2024-CS102-MID', 'SUBJ001', 'UR2024001', 27.00, 62.00, 89.00, 'A', 'admin', '2026-05-10 18:50:00', NULL),
(6, 'EX-2024-CS102-MID', 'SUBJ002', 'UR2024001', 26.00, 68.00, 94.00, 'A+', 'admin', '2026-05-10 18:50:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notif_id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `st_id` varchar(20) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `batch_id` varchar(30) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `method` enum('manual','cash','card','bank_transfer') NOT NULL DEFAULT 'manual',
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('pending','paid') NOT NULL DEFAULT 'paid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `st_id`, `program_id`, `batch_id`, `schedule_id`, `payment_date`, `amount`, `currency`, `method`, `reference_no`, `remarks`, `status`) VALUES
(1, 'UR2024001', 'PG-CS', 'CS-2024', NULL, '2024-09-20', 250000.00, 'RWF', 'bank_transfer', NULL, NULL, 'paid'),
(2, 'UR2024001', 'PG-CS', 'CS-2024', NULL, '2024-10-15', 250000.00, 'RWF', 'bank_transfer', NULL, NULL, 'paid'),
(3, 'UR2024002', 'PG-CS', 'CS-2024', NULL, '2024-09-18', 500000.00, 'RWF', 'cash', NULL, NULL, 'paid'),
(4, 'UR2024003', 'PG-CS', 'CS-2024', NULL, '2024-09-25', 250000.00, 'RWF', 'card', NULL, NULL, 'paid');

-- --------------------------------------------------------

--
-- Table structure for table `payment_schedules`
--

CREATE TABLE `payment_schedules` (
  `schedule_id` int(11) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `fee_month` int(11) DEFAULT NULL,
  `fee_year` int(11) NOT NULL,
  `one_time_amount` decimal(10,2) DEFAULT 0.00,
  `monthly_amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_schedules`
--

INSERT INTO `payment_schedules` (`schedule_id`, `program_id`, `fee_month`, `fee_year`, `one_time_amount`, `monthly_amount`) VALUES
(1, 'PG-CS', 9, 2024, 0.00, 250000.00),
(2, 'PG-CS', 10, 2024, 0.00, 250000.00),
(3, 'PG-CS', 11, 2024, 0.00, 250000.00),
(4, 'PG-CS', NULL, 2024, 1500000.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `program_id` varchar(30) NOT NULL,
  `program_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `program_name`) VALUES
('', 'Seed Program'),
('PG-CS', 'Bachelor of Science in Computer Science'),
('PG-CYBER', 'Bachelor of Science in Cyber Security'),
('PG-DS', 'Bachelor of Science in Data Science'),
('PG-IT', 'Bachelor of Science in Information Technology'),
('PG-SE', 'Bachelor of Science in Software Engineering'),
('SEED-PROG', 'Seed Program');

-- --------------------------------------------------------

--
-- Table structure for table `program_fees`
--

CREATE TABLE `program_fees` (
  `fee_id` int(11) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `fee_type` enum('one-time','monthly') NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `program_fees`
--

INSERT INTO `program_fees` (`fee_id`, `program_id`, `fee_type`, `fee_amount`, `currency`) VALUES
(1, 'PG-CS', 'one-time', 1500000.00, 'RWF'),
(2, 'PG-CS', 'monthly', 250000.00, 'RWF'),
(3, 'PG-SE', 'one-time', 1600000.00, 'RWF'),
(4, 'PG-IT', 'one-time', 1400000.00, 'RWF'),
(5, 'PG-DS', 'one-time', 1800000.00, 'RWF');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `st_id` varchar(30) NOT NULL,
  `course` varchar(30) NOT NULL,
  `st_status` varchar(30) NOT NULL,
  `st_name` varchar(30) NOT NULL,
  `st_dept` varchar(30) NOT NULL,
  `st_batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `results_publish`
--

CREATE TABLE `results_publish` (
  `publish_id` int(11) NOT NULL,
  `exam_id` varchar(30) NOT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `published_by` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `results_publish`
--

INSERT INTO `results_publish` (`publish_id`, `exam_id`, `published_at`, `published`, `published_by`) VALUES
(1, 'EX-2024-CS101-MID', '2024-11-20 08:00:00', 1, 'admin'),
(2, 'EX-2024-CS102-MID', '2024-11-21 08:00:00', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sms_gateway_settings`
--

CREATE TABLE `sms_gateway_settings` (
  `id` int(11) NOT NULL,
  `provider` varchar(50) NOT NULL DEFAULT 'custom',
  `api_key` varchar(255) DEFAULT NULL,
  `sender_id` varchar(30) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'inactive'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_gateway_settings`
--

INSERT INTO `sms_gateway_settings` (`id`, `provider`, `api_key`, `sender_id`, `status`) VALUES
(1, 'AfricaTalking', 'test_api_key_here', 'RwandaEdu', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `sms_outbox`
--

CREATE TABLE `sms_outbox` (
  `sms_id` int(11) NOT NULL,
  `recipient_user_id` varchar(20) DEFAULT NULL,
  `recipient_phone` varchar(30) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('queued','sent','failed','pending') NOT NULL DEFAULT 'queued',
  `provider_message_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_outbox`
--

INSERT INTO `sms_outbox` (`sms_id`, `recipient_user_id`, `recipient_phone`, `message`, `status`, `provider_message_id`, `created_at`, `sent_at`) VALUES
(1, 'UR2024001', '0788888001', 'Your attendance is 90%. Keep it up!', 'sent', NULL, '2024-11-01 06:00:00', NULL),
(2, 'UR2024002', '0788888002', 'Alert: Your attendance is below 75%', 'sent', NULL, '2024-11-01 06:05:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `st_id` varchar(20) NOT NULL,
  `st_name` varchar(20) NOT NULL,
  `st_dept` varchar(20) NOT NULL,
  `st_batch` int(4) NOT NULL,
  `st_sem` int(11) NOT NULL,
  `st_email` varchar(30) NOT NULL,
  `leave_balance` int(11) NOT NULL DEFAULT 15,
  `password` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`st_id`, `st_name`, `st_dept`, `st_batch`, `st_sem`, `st_email`, `leave_balance`, `password`) VALUES
('', '', '', 0, 0, '', 15, 'student123'),
('ALU2024001', 'Umwari Gloria', 'Software Development', 2024, 1, 'g.umwari@alueducation.com', 15, ''),
('ALU2024002', 'Mukeshimana Solange', 'Cloud Computing', 2024, 1, 'solange@ines.ac.rw', 14, ''),
('B22_001', 'David Iradukunda', 'SE', 2022, 5, 'david@ur.ac.rw', 8, ''),
('B22_002', 'Gloria Umwari', 'CS', 2022, 5, 'gloria@ur.ac.rw', 7, ''),
('B22_003', 'Solange Mukeshimana', 'IT', 2022, 5, 'solange@ur.ac.rw', 9, ''),
('B22_004', 'Jean Bosco Niyibizi', 'Cyber Security', 2022, 5, 'jeanb@ur.ac.rw', 6, ''),
('B22_005', 'Augustin Ndagijimana', 'Data Science', 2022, 5, 'augustin@ur.ac.rw', 8, ''),
('B23_001', 'Emmanuel Uwitonze', 'SE', 2023, 3, 'emmanuel@ur.ac.rw', 12, ''),
('B23_002', 'Samuel Niyonkuru', 'CS', 2023, 3, 'samuel@ur.ac.rw', 11, ''),
('B23_003', 'Alice Uwamariya', 'IT', 2023, 3, 'alice@ur.ac.rw', 13, ''),
('B23_004', 'Fabrice Nsanzimana', 'Data Science', 2023, 3, 'fabrice@ur.ac.rw', 12, ''),
('B23_005', 'Benjamin Muhire', 'Cyber Security', 2023, 3, 'benjamin@ur.ac.rw', 14, ''),
('B23_006', 'Josephine Uwimbabazi', 'CS', 2023, 3, 'josephine@ur.ac.rw', 11, ''),
('B24_001', 'Aline Uwase', 'Computer Science', 2024, 1, 'aline@ur.ac.rw', 15, ''),
('B24_002', 'Jean Paul Ishimwe', 'Software Engineering', 2024, 1, 'jean@ur.ac.rw', 15, ''),
('B24_003', 'Grace Mukamana', 'IT', 2024, 1, 'grace@ur.ac.rw', 15, ''),
('B24_004', 'Olivier Hakizimana', 'CS', 2024, 1, 'olivier@ur.ac.rw', 15, ''),
('B24_005', 'Diane Nyiransabimana', 'Data Science', 2024, 1, 'diane@ur.ac.rw', 15, ''),
('B24_006', 'Eric Niyomugabo', 'Cyber Security', 2024, 1, 'eric@ur.ac.rw', 15, ''),
('B24_007', 'Chantal Ingabire', 'CS', 2024, 1, 'chantal@ur.ac.rw', 15, ''),
('B24_008', 'Theogene Habumugisha', 'IT', 2024, 1, 'theogene@ur.ac.rw', 15, ''),
('BATCH22_001', 'Niyonkuru Samuel', 'Computer Science', 2022, 5, 'samuel@example.com', 10, ''),
('BATCH22_002', 'Uwamariya Alice', 'Software Engineering', 2022, 5, 'alice@example.com', 9, ''),
('BATCH22_003', 'Nsanzimana Fabrice', 'Data Science', 2022, 5, 'fabrice@example.com', 11, ''),
('BATCH22_004', 'Umwari Gloria', 'Software Development', 2022, 5, 'gloria@example.com', 10, ''),
('BATCH23_001', 'Niyomugabo Eric', 'Computer Science', 2023, 3, 'eric@example.com', 12, ''),
('BATCH23_002', 'Uwitonze Emmanuel', 'Software Engineering', 2023, 3, 'emmanuel@example.com', 11, ''),
('BATCH23_003', 'Ingabire Chantal', 'Cyber Security', 2023, 3, 'chantal@example.com', 14, ''),
('BATCH23_004', 'Habumugisha Theogene', 'Information Technolo', 2023, 3, 'theogene@example.com', 13, ''),
('BATCH23_005', 'Muhire Benjamin', 'ICT', 2023, 3, 'benjamin@example.com', 13, ''),
('BATCH24_001', 'Uwase Aline', 'Computer Science', 2024, 1, 'aline@example.com', 15, ''),
('BATCH24_002', 'Ishimwe Jean', 'Software Engineering', 2024, 1, 'jean@example.com', 15, ''),
('BATCH24_003', 'Mukamana Grace', 'Information Technolo', 2024, 1, 'grace@example.com', 15, ''),
('BATCH24_004', 'Hakizimana Olivier', 'Computer Science', 2024, 1, 'olivier@example.com', 15, ''),
('BATCH24_005', 'Nyiransabimana Diane', 'Data Science', 2024, 1, 'diane@example.com', 15, ''),
('RP2023001', 'Iradukunda David', 'Network Security', 2023, 3, 'd.iradukunda@alueducation.com', 13, ''),
('RP2024001', 'Muhire Benjamin', 'ICT', 2024, 1, 'benjamin.muhire@rp.ac.rw', 15, ''),
('RP2024002', 'Uwimbabazi Josephine', 'Web Development', 2024, 1, 'josephine@rp.ac.rw', 15, ''),
('UR2022001', 'Niyonkuru Samuel', 'Computer Science', 2022, 5, 'samuel.niyonkuru@ulk.ac.rw', 10, ''),
('UR2022002', 'Uwamariya Alice', 'Software Engineering', 2022, 5, 'alice.uwamariya@ulk.ac.rw', 9, ''),
('UR2022003', 'Nsanzimana Fabrice', 'Data Science', 2022, 5, 'fabrice@rp.ac.rw', 11, ''),
('UR2023001', 'Niyomugabo Eric', 'Computer Science', 2023, 3, 'eric.niyo@ur.ac.rw', 12, ''),
('UR2023002', 'Uwitonze Emmanuel', 'Software Engineering', 2023, 3, 'emmanuel.uwitonze@ur.ac.rw', 11, ''),
('UR2023004', 'Habumugisha Theogene', 'Information Technolo', 2023, 3, 'theogene@ines.ac.rw', 13, ''),
('UR2024001', 'Uwase Aline', 'Computer Science', 2024, 1, 'aline.uwase@ur.ac.rw', 15, ''),
('UR2024002', 'Ishimwe Jean Paul', 'Computer Science', 2024, 1, 'jp.ishimwe@ur.ac.rw', 14, ''),
('UR2024003', 'Mukamana Grace', 'Software Engineering', 2024, 1, 'grace.mukamana@ur.ac.rw', 15, ''),
('UR2024004', 'Hakizimana Olivier', 'Information Technolo', 2024, 1, 'olivier.hakizimana@ur.ac.rw', 15, ''),
('UR2024005', 'Nyiransabimana Diane', 'Data Science', 2024, 1, 'diane.nyira@ur.ac.rw', 13, '');

-- --------------------------------------------------------

--
-- Table structure for table `student_certificates`
--

CREATE TABLE `student_certificates` (
  `id` int(11) NOT NULL,
  `st_id` varchar(20) DEFAULT NULL,
  `cert_type` varchar(50) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `certificate_number` varchar(50) DEFAULT NULL,
  `status` enum('issued','pending','cancelled') DEFAULT NULL,
  `issued_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_certificates`
--

INSERT INTO `student_certificates` (`id`, `st_id`, `cert_type`, `issued_date`, `certificate_number`, `status`, `issued_by`) VALUES
(1, 'UR2024001', 'admission', '2024-09-10', 'UR-ADM-2024-001', 'issued', 'registrar'),
(2, 'UR2024002', 'admission', '2024-09-10', 'UR-ADM-2024-002', 'issued', 'registrar');

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollments`
--

CREATE TABLE `student_enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `st_id` varchar(20) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `batch_id` varchar(30) NOT NULL,
  `semester` int(11) NOT NULL,
  `admission_date` date NOT NULL,
  `status` enum('active','deactivated') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_enrollments`
--

INSERT INTO `student_enrollments` (`enrollment_id`, `st_id`, `program_id`, `batch_id`, `semester`, `admission_date`, `status`) VALUES
(1, 'UR2024001', 'PG-CS', 'CS-2024', 1, '2024-09-15', 'active'),
(2, 'UR2024002', 'PG-CS', 'CS-2024', 1, '2024-09-15', 'active'),
(3, 'UR2024003', 'PG-CS', 'CS-2024', 1, '2024-09-15', 'active'),
(4, 'UR2023001', 'PG-CS', 'CS-2023', 3, '2023-09-10', 'active'),
(5, 'UR2023002', 'PG-CS', 'CS-2023', 3, '2023-09-10', 'active'),
(6, 'INES2024001', 'PG-IT', 'IT-2024', 1, '2024-09-20', 'active'),
(7, 'STU001', 'SEED-PROG', 'SEED-BATCH', 1, '2026-05-10', 'active'),
(8, 'ALU2024001', 'PG-CS', 'BATCH-CS-2024', 1, '2024-09-01', 'active'),
(9, 'ALU2024002', 'PG-CS', 'BATCH-CS-2024', 1, '2024-09-01', 'active'),
(10, 'B24_001', 'PG-CS', 'BATCH-CS-2024', 1, '2024-09-01', 'active'),
(11, 'B24_002', 'PG-CS', 'BATCH-CS-2024', 1, '2024-09-01', 'active'),
(12, 'B24_003', 'PG-CS', 'BATCH-CS-2024', 1, '2024-09-01', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_balances`
--

CREATE TABLE `student_fee_balances` (
  `id` int(11) NOT NULL,
  `st_id` varchar(20) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `batch_id` varchar(30) NOT NULL,
  `total_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_paid` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_fee_balances`
--

INSERT INTO `student_fee_balances` (`id`, `st_id`, `program_id`, `batch_id`, `total_due`, `total_paid`) VALUES
(1, 'UR2024001', 'PG-CS', 'CS-2024', 1500000.00, 500000.00),
(2, 'UR2024002', 'PG-CS', 'CS-2024', 1500000.00, 750000.00),
(3, 'UR2024003', 'PG-CS', 'CS-2024', 1500000.00, 250000.00),
(4, 'STU001', 'SEED-PROG', 'SEED-BATCH', 1000.00, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `student_payments`
--

CREATE TABLE `student_payments` (
  `payment_id` int(11) NOT NULL,
  `st_id` varchar(20) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `payment_amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','cheque','transfer','credit_card') NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `recorded_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_payments`
--

INSERT INTO `student_payments` (`payment_id`, `st_id`, `program_id`, `payment_amount`, `payment_date`, `payment_method`, `reference_number`, `status`, `recorded_by`, `created_at`) VALUES
(1, 'B23_005', '', 200000.00, '2026-05-19', 'cash', '11345654321', 'completed', 'oasis', '2026-05-19 15:48:57');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` varchar(30) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `program_id`, `subject_code`, `subject_name`) VALUES
('', '', 'SUB101', 'Seed Subject'),
('SUBJ001', 'PG-CS', 'CSC101', 'Programming Fundamentals'),
('SUBJ002', 'PG-CS', 'CSC102', 'Database Systems'),
('SUBJ003', 'PG-SE', 'SE101', 'Software Engineering Principles'),
('SUBJ004', 'PG-IT', 'IT101', 'Network Fundamentals');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`log_id`, `user_id`, `action`, `log_time`) VALUES
(1, 'admin', 'System initialized for 2024-2025 academic year', '2024-11-01 06:00:00'),
(2, 'admin', 'Added 15 new students to database', '2024-11-01 07:30:00'),
(3, 'T001', 'Marked attendance for CS Batch 2024', '2024-11-01 06:15:00'),
(4, 'T002', 'Updated exam results for Programming', '2024-11-02 12:20:00'),
(5, 'admin', 'Generated monthly attendance report', '2024-11-03 08:00:00'),
(6, 'T003', 'Approved leave requests for 3 students', '2024-11-04 09:30:00'),
(7, 'admin', 'System backup completed', '2024-11-05 00:00:00'),
(8, 'T001', 'Marked attendance for 45 students', '2024-11-05 06:10:00'),
(9, 'T002', 'Entered marks for Database exam', '2024-11-06 13:45:00'),
(10, 'admin', 'Published exam results for Mid-Semester', '2024-11-07 07:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `key_name` varchar(100) NOT NULL,
  `value_text` text DEFAULT NULL,
  `value_int` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`key_name`, `value_text`, `value_int`, `updated_at`) VALUES
('academic_year', NULL, 2024, '2026-05-10 16:51:53'),
('attendance_threshold', NULL, 75, '2026-05-10 16:51:53'),
('semester', NULL, 1, '2026-05-10 16:51:53'),
('sms_enabled', 'true', NULL, '2026-05-10 16:51:53'),
('system_name', 'Rwanda Education Attendance System', NULL, '2026-05-10 16:51:53');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `tc_id` varchar(20) NOT NULL,
  `tc_name` varchar(20) NOT NULL,
  `tc_dept` varchar(20) NOT NULL,
  `tc_email` varchar(30) NOT NULL,
  `tc_course` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`tc_id`, `tc_name`, `tc_dept`, `tc_email`, `tc_course`, `password`) VALUES
('T001', 'Dr. Mugisha Jean Cla', 'Computer Science', 'jc.mugisha@ur.ac.rw', 'Programming', ''),
('T002', 'Prof. Uwimana Marie', 'Information Technolo', 'm.uwimana@ur.ac.rw', 'Database', ''),
('T003', 'Dr. Nzabanita Daniel', 'Cyber Security', 'd.nzabanita@ur.ac.rw', 'Security', ''),
('T004', 'Eng. Habimana Emmanu', 'Software Engineering', 'e.habimana@ur.ac.rw', 'Web Dev', ''),
('T005', 'Dr. Mukandutiye Vest', 'Data Science', 'v.mukandutiye@ur.ac.rw', 'Machine Learning', ''),
('T006', 'Prof. Rwanda Theophi', 'Computer Science', 't.rwanda@ines.ac.rw', 'Algorithms', ''),
('T007', 'Dr. Ndagijimana Augu', 'Information Systems', 'a.ndagijimana@ulk.ac.rw', 'System Analysis', ''),
('T008', 'Eng. Niyibizi Jean B', 'ICT', 'jb.niyibizi@rp.ac.rw', 'Hardware', '');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_batches`
--

CREATE TABLE `teacher_batches` (
  `id` int(11) NOT NULL,
  `teacher_id` varchar(20) NOT NULL,
  `batch_id` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_batches`
--

INSERT INTO `teacher_batches` (`id`, `teacher_id`, `batch_id`) VALUES
(1, 'oasis', 'CS-2024'),
(2, 'oasis', 'CS-2023');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(20) NOT NULL,
  `user_type` enum('admin','teacher','student') NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `user_type`, `username`, `password`, `email`, `phone`, `first_name`, `last_name`, `is_active`) VALUES
('ADMIN001', 'admin', 'admin_user', 'admin123', 'admin@rwandaedu.rw', '0788888000', 'System', 'Administrator', 1),
('TCH001', 'teacher', 'prof_mugisha', 'teacher123', 'mugisha@ur.ac.rw', '0788888100', 'Jean', 'Mugisha', 1);

-- --------------------------------------------------------

--
-- Structure for view `batch_attendance_analytics`
--
DROP TABLE IF EXISTS `batch_attendance_analytics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `batch_attendance_analytics`  AS SELECT `s`.`st_batch` AS `st_batch`, year(curdate()) - `s`.`st_batch` AS `academic_year`, count(distinct `s`.`st_id`) AS `enrolled_students`, count(distinct `a`.`stat_id`) AS `attendance_records`, sum(case when `a`.`st_status` = 'Present' then 1 else 0 end) AS `total_present`, sum(case when `a`.`st_status` = 'Absent' then 1 else 0 end) AS `total_absent`, sum(case when `a`.`st_status` = 'Late' then 1 else 0 end) AS `total_late`, round(avg(case when `a`.`st_status` = 'Present' then 100 when `a`.`st_status` = 'Late' then 50 else 0 end),2) AS `avg_attendance_score`, round(sum(case when `a`.`st_status` = 'Present' then 1 else 0 end) * 100.0 / nullif(count(`a`.`stat_id`),0),2) AS `attendance_rate`, round(sum(case when `a`.`st_status` = 'Present' then 1 else 0 end) * 100.0 / nullif(count(`a`.`stat_id`),0) - (select avg(case when `attendance`.`st_status` = 'Present' then 100 else 0 end) from `attendance` where `attendance`.`stat_date` between curdate() - interval 60 day and curdate() - interval 30 day),2) AS `monthly_trend` FROM (`students` `s` left join `attendance` `a` on(`s`.`st_id` = `a`.`stat_id` and `a`.`stat_date` >= curdate() - interval 30 day)) WHERE `s`.`st_batch` is not null GROUP BY `s`.`st_batch` ORDER BY `s`.`st_batch` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `batch_attendance_stats`
--
DROP TABLE IF EXISTS `batch_attendance_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `batch_attendance_stats`  AS SELECT `s`.`st_batch` AS `st_batch`, count(distinct `s`.`st_id`) AS `total_students`, count(`a`.`stat_id`) AS `total_attendance_records`, sum(case when `a`.`st_status` = 'Present' then 1 else 0 end) AS `total_present`, sum(case when `a`.`st_status` = 'Absent' then 1 else 0 end) AS `total_absent`, sum(case when `a`.`st_status` = 'Late' then 1 else 0 end) AS `total_late`, round(avg(case when `a`.`st_status` = 'Present' then 100 when `a`.`st_status` = 'Late' then 50 else 0 end),2) AS `avg_attendance_score`, round(sum(case when `a`.`st_status` = 'Present' then 1 else 0 end) * 100.0 / nullif(count(`a`.`stat_id`),0),2) AS `attendance_percentage` FROM (`students` `s` left join `attendance` `a` on(`s`.`st_id` = `a`.`stat_id`)) WHERE `a`.`stat_date` >= curdate() - interval 30 day GROUP BY `s`.`st_batch` ORDER BY `s`.`st_batch` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `admininfo`
--
ALTER TABLE `admininfo`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `idx_announcements_program_id` (`program_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD KEY `stat_id` (`stat_id`);

--
-- Indexes for table `attendance_summary`
--
ALTER TABLE `attendance_summary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`batch_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `batch_subjects`
--
ALTER TABLE `batch_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_batch_subject` (`batch_id`,`subject_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `class_sections`
--
ALTER TABLE `class_sections`
  ADD PRIMARY KEY (`section_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD PRIMARY KEY (`material_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `course_material_subjects`
--
ALTER TABLE `course_material_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_material_subject` (`material_id`,`subject_id`),
  ADD KEY `material_id` (`material_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `exam_category_id` (`exam_category_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `exam_categories`
--
ALTER TABLE `exam_categories`
  ADD PRIMARY KEY (`exam_category_id`);

--
-- Indexes for table `exam_subjects`
--
ALTER TABLE `exam_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exam_subject` (`exam_id`,`subject_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `expense_entries`
--
ALTER TABLE `expense_entries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `income_entries`
--
ALTER TABLE `income_entries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `st_id` (`st_id`);

--
-- Indexes for table `marks_entries`
--
ALTER TABLE `marks_entries`
  ADD PRIMARY KEY (`entry_id`),
  ADD UNIQUE KEY `uq_entry` (`exam_id`,`subject_id`,`st_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `st_id` (`st_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notif_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `st_id` (`st_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `payment_schedules`
--
ALTER TABLE `payment_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`);

--
-- Indexes for table `program_fees`
--
ALTER TABLE `program_fees`
  ADD PRIMARY KEY (`fee_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `results_publish`
--
ALTER TABLE `results_publish`
  ADD PRIMARY KEY (`publish_id`),
  ADD UNIQUE KEY `uq_exam_publish` (`exam_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `sms_gateway_settings`
--
ALTER TABLE `sms_gateway_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sms_outbox`
--
ALTER TABLE `sms_outbox`
  ADD PRIMARY KEY (`sms_id`),
  ADD KEY `recipient_user_id` (`recipient_user_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`st_id`);

--
-- Indexes for table `student_certificates`
--
ALTER TABLE `student_certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`);

--
-- Indexes for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `st_id` (`st_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `student_fee_balances`
--
ALTER TABLE `student_fee_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_balance` (`st_id`,`program_id`,`batch_id`),
  ADD KEY `st_id` (`st_id`);

--
-- Indexes for table `student_payments`
--
ALTER TABLE `student_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `st_id` (`st_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `uq_subject_code` (`subject_code`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`key_name`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`tc_id`);

--
-- Indexes for table `teacher_batches`
--
ALTER TABLE `teacher_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `attendance_summary`
--
ALTER TABLE `attendance_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `batch_subjects`
--
ALTER TABLE `batch_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `class_sections`
--
ALTER TABLE `class_sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_materials`
--
ALTER TABLE `course_materials`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_material_subjects`
--
ALTER TABLE `course_material_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_subjects`
--
ALTER TABLE `exam_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `expense_entries`
--
ALTER TABLE `expense_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `income_entries`
--
ALTER TABLE `income_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marks_entries`
--
ALTER TABLE `marks_entries`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notif_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment_schedules`
--
ALTER TABLE `payment_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `program_fees`
--
ALTER TABLE `program_fees`
  MODIFY `fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `results_publish`
--
ALTER TABLE `results_publish`
  MODIFY `publish_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sms_gateway_settings`
--
ALTER TABLE `sms_gateway_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sms_outbox`
--
ALTER TABLE `sms_outbox`
  MODIFY `sms_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_certificates`
--
ALTER TABLE `student_certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `student_fee_balances`
--
ALTER TABLE `student_fee_balances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_payments`
--
ALTER TABLE `student_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `teacher_batches`
--
ALTER TABLE `teacher_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_summary`
--
ALTER TABLE `attendance_summary`
  ADD CONSTRAINT `attendance_summary_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`st_id`);

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_ibfk_1` FOREIGN KEY (`st_id`) REFERENCES `students` (`st_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
