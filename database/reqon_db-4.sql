-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 25, 2026 at 12:25 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `reqon_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `approval_history`
--

CREATE TABLE `approval_history` (
  `approval_id` int(11) NOT NULL,
  `requisition_id` int(11) NOT NULL,
  `approver_id` int(11) NOT NULL,
  `level_id` int(11) NOT NULL,
  `decision` enum('pending','approved','rejected') DEFAULT 'pending',
  `comments` text DEFAULT NULL,
  `decision_date` datetime DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `approver_role` varchar(50) DEFAULT NULL,
  `notification_sent` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_history`
--

INSERT INTO `approval_history` (`approval_id`, `requisition_id`, `approver_id`, `level_id`, `decision`, `comments`, `decision_date`, `timestamp`, `approver_role`, `notification_sent`) VALUES
(1, 9, 5, 1, 'approved', '', '2026-05-22 09:45:58', '2026-05-22 09:08:01', NULL, 0),
(2, 10, 6, 1, 'approved', '', '2026-05-22 09:50:59', '2026-05-22 09:35:34', NULL, 0),
(3, 6, 5, 1, 'approved', '', '2026-05-22 09:43:57', '2026-05-22 09:43:57', NULL, 0),
(4, 6, 7, 2, 'rejected', 'Not priority', '2026-06-10 09:33:23', '2026-05-22 09:43:57', NULL, 0),
(5, 9, 7, 2, 'approved', '', '2026-06-10 09:33:49', '2026-05-22 09:45:58', NULL, 0),
(6, 11, 7, 2, 'rejected', 'Not priority', '2026-06-10 09:33:44', '2026-05-22 09:47:46', NULL, 0),
(7, 10, 8, 2, 'approved', '', '2026-06-15 00:41:39', '2026-05-22 09:50:59', NULL, 0),
(8, 12, 7, 2, 'approved', '', '2026-06-10 09:25:40', '2026-05-22 09:53:42', NULL, 0),
(9, 13, 9, 3, 'rejected', 'not priority', '2026-06-10 09:34:55', '2026-05-22 09:57:30', NULL, 0),
(10, 14, 5, 1, 'approved', '', '2026-05-25 19:14:50', '2026-05-25 19:06:18', NULL, 0),
(11, 14, 8, 2, 'approved', '', '2026-05-25 19:18:45', '2026-05-25 19:14:50', NULL, 0),
(12, 14, 9, 3, 'approved', '', '2026-05-25 19:35:32', '2026-05-25 19:18:45', NULL, 0),
(13, 5, 9, 3, 'rejected', 'Not priority', '2026-05-25 19:35:05', '2026-05-25 19:35:05', NULL, 0),
(14, 14, 10, 4, 'approved', '', '2026-05-26 14:05:28', '2026-05-25 19:35:32', NULL, 0),
(15, 15, 5, 1, 'approved', '', '2026-05-26 13:54:42', '2026-05-26 13:53:35', NULL, 0),
(16, 15, 7, 2, 'approved', '', '2026-06-10 09:33:33', '2026-05-26 13:54:42', NULL, 0),
(17, 12, 9, 3, 'approved', '', '2026-06-10 09:35:06', '2026-06-10 09:25:40', NULL, 0),
(18, 16, 7, 2, 'approved', '', '2026-06-10 09:33:08', '2026-06-10 09:32:29', NULL, 0),
(19, 16, 9, 3, 'approved', '', '2026-06-10 09:34:17', '2026-06-10 09:33:08', NULL, 0),
(20, 15, 9, 3, 'approved', '', '2026-06-10 09:35:33', '2026-06-10 09:33:33', NULL, 0),
(21, 9, 9, 3, 'approved', '', '2026-06-10 09:34:26', '2026-06-10 09:33:49', NULL, 0),
(22, 16, 10, 4, 'approved', '', '2026-06-10 09:35:56', '2026-06-10 09:34:17', NULL, 0),
(23, 9, 10, 4, 'rejected', 'not needed', '2026-06-17 11:40:40', '2026-06-10 09:34:26', NULL, 0),
(24, 3, 9, 3, 'approved', '', '2026-06-10 09:34:36', '2026-06-10 09:34:36', NULL, 0),
(25, 3, 10, 4, 'rejected', 'N/A', '2026-06-15 00:43:08', '2026-06-10 09:34:36', NULL, 0),
(26, 12, 10, 4, 'pending', NULL, NULL, '2026-06-10 09:35:06', NULL, 0),
(27, 4, 9, 3, 'rejected', 'Not now', '2026-06-10 09:35:24', '2026-06-10 09:35:24', NULL, 0),
(28, 15, 10, 4, 'approved', '', '2026-06-10 09:36:05', '2026-06-10 09:35:33', NULL, 0),
(29, 17, 7, 2, 'approved', '', '2026-06-13 22:26:18', '2026-06-10 23:44:22', NULL, 0),
(30, 18, 7, 2, 'pending', NULL, NULL, '2026-06-13 22:25:37', NULL, 0),
(31, 17, 9, 3, 'approved', '', '2026-06-13 22:27:08', '2026-06-13 22:26:18', NULL, 0),
(32, 17, 10, 4, 'approved', '', '2026-06-13 22:28:15', '2026-06-13 22:27:08', NULL, 0),
(33, 19, 8, 2, 'approved', '', '2026-06-15 00:41:43', '2026-06-15 00:40:33', NULL, 0),
(34, 10, 9, 3, 'rejected', 'Hold for Q4', '2026-06-15 00:42:25', '2026-06-15 00:41:39', NULL, 0),
(35, 19, 9, 3, 'approved', '', '2026-06-15 00:42:30', '2026-06-15 00:41:43', NULL, 0),
(36, 19, 10, 4, 'approved', '', '2026-06-15 00:44:44', '2026-06-15 00:42:30', NULL, 0),
(37, 20, 5, 1, 'approved', '', '2026-06-15 21:36:19', '2026-06-15 21:35:38', NULL, 0),
(38, 20, 7, 2, 'approved', '', '2026-06-15 21:36:46', '2026-06-15 21:36:19', NULL, 0),
(39, 20, 9, 3, 'approved', '', '2026-06-15 21:37:27', '2026-06-15 21:36:46', NULL, 0),
(40, 20, 10, 4, 'approved', '', '2026-06-15 21:38:00', '2026-06-15 21:37:27', NULL, 0),
(41, 21, 7, 2, 'approved', '', '2026-06-17 11:39:36', '2026-06-17 11:39:08', NULL, 0),
(42, 21, 10, 4, 'approved', '', '2026-06-17 11:40:20', '2026-06-17 11:39:36', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `approval_levels`
--

CREATE TABLE `approval_levels` (
  `level_id` int(11) NOT NULL,
  `requisition_type` varchar(50) NOT NULL,
  `level_number` tinyint(4) NOT NULL,
  `role_id` int(11) NOT NULL,
  `approval_threshold` decimal(15,2) DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_levels`
--

INSERT INTO `approval_levels` (`level_id`, `requisition_type`, `level_number`, `role_id`, `approval_threshold`, `description`) VALUES
(1, 'it_asset', 1, 3, 0.00, 'IT Dept Head (Elizabeth — always dept 1)'),
(2, 'it_asset', 2, 3, 0.00, 'Procurement Head (Mary)'),
(3, 'it_asset', 3, 3, 0.00, 'Finance Director (David)'),
(4, 'it_asset', 4, 3, 0.00, 'Managing Director (James)'),
(5, 'merchandise', 1, 3, 0.00, 'Submitter Dept Head'),
(6, 'merchandise', 2, 3, 0.00, 'Procurement Head (Mary)'),
(7, 'merchandise', 3, 3, 0.00, 'Finance Director (David)'),
(8, 'merchandise', 4, 3, 0.00, 'Managing Director (James)'),
(9, 'personnel', 1, 3, 0.00, 'Submitter Dept Head'),
(10, 'personnel', 2, 2, 0.00, 'HR Director (Grace)'),
(11, 'personnel', 3, 3, 0.00, 'Finance Director (David)'),
(12, 'personnel', 4, 3, 0.00, 'Managing Director (James)'),
(13, 'procurement', 1, 3, 0.00, 'Submitter Dept Head'),
(14, 'procurement', 2, 3, 0.00, 'Procurement Head (Mary)'),
(15, 'procurement', 3, 3, 0.00, 'Finance Director (David)'),
(16, 'procurement', 4, 3, 0.00, 'Managing Director (James)');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` enum('CREATE','UPDATE','DELETE','LOGIN','APPROVE','REJECT','CANCEL') NOT NULL,
  `table_affected` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`log_id`, `user_id`, `action_type`, `table_affected`, `record_id`, `timestamp`, `ip_address`, `description`, `old_value`, `new_value`) VALUES
(1, 2, 'CREATE', 'requisitions', 1, '2026-04-29 08:11:34', '127.0.0.1', 'Submitted REQ-001 (IT Asset)', NULL, NULL),
(2, 5, 'APPROVE', 'requisitions', 1, '2026-04-29 08:11:34', '127.0.0.1', 'Approved REQ-001 at Level 1 (Dept Head)', NULL, NULL),
(3, 8, 'APPROVE', 'requisitions', 1, '2026-04-29 08:11:34', '127.0.0.1', 'Approved REQ-001 at Level 2 (HR Director)', NULL, NULL),
(4, 9, 'APPROVE', 'requisitions', 1, '2026-04-29 08:11:34', '127.0.0.1', 'Approved REQ-001 at Level 3 (Finance Director)', NULL, NULL),
(5, 10, 'APPROVE', 'requisitions', 1, '2026-04-29 08:11:34', '127.0.0.1', 'Approved REQ-001 at Level 4 (MD) — Final', NULL, NULL),
(6, 4, 'CREATE', 'requisitions', 2, '2026-04-29 08:11:34', '127.0.0.1', 'Submitted REQ-002 (Personnel)', NULL, NULL),
(7, 6, 'APPROVE', 'requisitions', 2, '2026-04-29 08:11:34', '127.0.0.1', 'Approved REQ-002 at Level 1', NULL, NULL),
(8, 8, 'REJECT', 'requisitions', 2, '2026-04-29 08:11:34', '127.0.0.1', 'Rejected REQ-002 at Level 2 — budget issue', NULL, NULL),
(9, 3, 'CREATE', 'requisitions', 3, '2026-04-29 08:11:34', '127.0.0.1', 'Submitted REQ-003 (Procurement)', NULL, NULL),
(10, 2, 'CREATE', 'requisitions', 4, '2026-04-29 08:11:34', '127.0.0.1', 'Submitted REQ-004 (IT Asset)', NULL, NULL),
(11, 5, 'APPROVE', 'requisitions', 4, '2026-04-29 08:11:34', '127.0.0.1', 'Approved REQ-004 at Level 1', NULL, NULL),
(12, 4, 'CREATE', 'requisitions', 5, '2026-04-29 08:11:34', '127.0.0.1', 'Submitted REQ-005 (Personnel — Internship)', NULL, NULL),
(13, 6, 'APPROVE', 'requisitions', 5, '2026-04-29 08:11:34', '127.0.0.1', 'Approved REQ-005 at Level 1', NULL, NULL),
(14, 8, 'APPROVE', 'requisitions', 5, '2026-04-29 08:11:34', '127.0.0.1', 'Approved REQ-005 at Level 2', NULL, NULL),
(15, 8, 'APPROVE', 'requisitions', 4, '2026-05-06 23:45:11', '::1', 'Approved at level 2, advanced to 3', NULL, NULL),
(16, 2, 'CREATE', 'requisitions', 6, '2026-05-07 00:58:41', '::1', 'Submitted REQ-006', NULL, NULL),
(17, 5, 'APPROVE', 'requisitions', 3, '2026-05-07 12:14:47', '::1', 'Approved at level 1, advanced to 2', NULL, NULL),
(18, 8, 'APPROVE', 'requisitions', 3, '2026-05-07 12:16:18', '::1', 'Approved at level 2, advanced to 3', NULL, NULL),
(19, 2, 'CREATE', 'requisitions', 7, '2026-05-07 12:20:01', '::1', 'Submitted REQ-007', NULL, NULL),
(20, 5, 'APPROVE', 'requisitions', 7, '2026-05-07 12:22:20', '::1', 'Approved at level 1, advanced to 2', NULL, NULL),
(21, 8, 'APPROVE', 'requisitions', 7, '2026-05-07 12:24:23', '::1', 'Approved at level 2, advanced to 3', NULL, NULL),
(22, 9, 'APPROVE', 'requisitions', 7, '2026-05-07 12:25:15', '::1', 'Approved at level 3, advanced to 4', NULL, NULL),
(23, 10, 'REJECT', 'requisitions', 7, '2026-05-07 12:26:22', '::1', 'Rejected at level 4', NULL, NULL),
(24, 5, 'CREATE', 'requisitions', 8, '2026-05-12 09:32:52', '::1', 'Submitted REQ-008', NULL, NULL),
(25, 5, 'APPROVE', 'requisitions', 8, '2026-05-12 09:33:15', '::1', 'Approved at level 1, advanced to 2', NULL, NULL),
(26, 5, 'CANCEL', 'requisitions', 8, '2026-05-12 09:33:22', '::1', 'Cancelled by requester', NULL, NULL),
(27, 2, 'CREATE', 'requisitions', 9, '2026-05-22 09:08:01', '::1', 'Submitted REQ-009', NULL, NULL),
(28, 4, 'CREATE', 'requisitions', 10, '2026-05-22 09:35:34', '::1', 'Submitted REQ-010', NULL, NULL),
(29, 5, 'APPROVE', 'requisitions', 6, '2026-05-22 09:43:57', '::1', 'Approved at level 1, advanced to 2', NULL, NULL),
(30, 5, 'APPROVE', 'requisitions', 9, '2026-05-22 09:45:58', '::1', 'Approved at level 1, advanced to 2', NULL, NULL),
(31, 5, 'CREATE', 'requisitions', 11, '2026-05-22 09:47:46', '::1', 'Submitted REQ-011', NULL, NULL),
(32, 6, 'APPROVE', 'requisitions', 10, '2026-05-22 09:50:59', '::1', 'Approved at level 1, advanced to 2', NULL, NULL),
(33, 6, 'CREATE', 'requisitions', 12, '2026-05-22 09:53:42', '::1', 'Submitted REQ-012', NULL, NULL),
(34, 7, 'CREATE', 'requisitions', 13, '2026-05-22 09:57:30', '::1', 'Submitted REQ-013', NULL, NULL),
(35, 2, 'CREATE', 'requisitions', 14, '2026-05-25 19:06:18', '::1', 'Submitted REQ-014', NULL, NULL),
(36, 5, 'APPROVE', 'requisitions', 14, '2026-05-25 19:14:50', '::1', 'Approved at level 1, advanced to 2', NULL, NULL),
(37, 8, 'APPROVE', 'requisitions', 14, '2026-05-25 19:18:45', '::1', 'Approved at level 2, advanced to 3', NULL, NULL),
(38, 9, 'REJECT', 'requisitions', 5, '2026-05-25 19:35:05', '::1', 'Rejected at level 3', NULL, NULL),
(39, 9, 'APPROVE', 'requisitions', 14, '2026-05-25 19:35:32', '::1', 'Approved at level 3, advanced to 4', NULL, NULL),
(40, 2, 'CREATE', 'requisitions', 15, '2026-05-26 13:53:35', '::1', 'Submitted REQ-015', NULL, NULL),
(41, 5, 'APPROVE', 'requisitions', 15, '2026-05-26 13:54:42', '::1', 'Approved at level 1, advanced to 2', NULL, NULL),
(42, 10, 'APPROVE', 'requisitions', 14, '2026-05-26 14:05:28', '::1', 'Final approval — fully approved', NULL, NULL),
(43, 7, 'APPROVE', 'requisitions', 12, '2026-06-10 09:25:40', '::1', 'Approved at level 2, advanced to 3', NULL, NULL),
(44, 2, 'CREATE', 'requisitions', 16, '2026-06-10 09:32:29', '::1', 'Submitted REQ-016', NULL, NULL),
(45, 7, 'APPROVE', 'requisitions', 16, '2026-06-10 09:33:08', '::1', 'Approved at level 2, advanced to 3', NULL, NULL),
(46, 7, 'REJECT', 'requisitions', 6, '2026-06-10 09:33:23', '::1', 'Rejected at level 2', NULL, NULL),
(47, 7, 'APPROVE', 'requisitions', 15, '2026-06-10 09:33:33', '::1', 'Approved at level 2, advanced to 3', NULL, NULL),
(48, 7, 'REJECT', 'requisitions', 11, '2026-06-10 09:33:44', '::1', 'Rejected at level 2', NULL, NULL),
(49, 7, 'APPROVE', 'requisitions', 9, '2026-06-10 09:33:49', '::1', 'Approved at level 2, advanced to 3', NULL, NULL),
(50, 9, 'APPROVE', 'requisitions', 16, '2026-06-10 09:34:17', '::1', 'Approved at level 3, advanced to 4', NULL, NULL),
(51, 9, 'APPROVE', 'requisitions', 9, '2026-06-10 09:34:26', '::1', 'Approved at level 3, advanced to 4', NULL, NULL),
(52, 9, 'APPROVE', 'requisitions', 3, '2026-06-10 09:34:36', '::1', 'Approved at level 3, advanced to 4', NULL, NULL),
(53, 9, 'REJECT', 'requisitions', 13, '2026-06-10 09:34:55', '::1', 'Rejected at level 3', NULL, NULL),
(54, 9, 'APPROVE', 'requisitions', 12, '2026-06-10 09:35:06', '::1', 'Approved at level 3, advanced to 4', NULL, NULL),
(55, 9, 'REJECT', 'requisitions', 4, '2026-06-10 09:35:24', '::1', 'Rejected at level 3', NULL, NULL),
(56, 9, 'APPROVE', 'requisitions', 15, '2026-06-10 09:35:33', '::1', 'Approved at level 3, advanced to 4', NULL, NULL),
(57, 10, 'APPROVE', 'requisitions', 16, '2026-06-10 09:35:56', '::1', 'Final approval — fully approved', NULL, NULL),
(58, 10, 'APPROVE', 'requisitions', 15, '2026-06-10 09:36:05', '::1', 'Final approval — fully approved', NULL, NULL),
(59, 7, 'CREATE', 'lpo_log', 15, '2026-06-10 10:30:53', '::1', 'Generated LPO-015', NULL, NULL),
(60, 2, 'CREATE', 'requisitions', 17, '2026-06-10 23:44:22', '::1', 'Submitted REQ-017', NULL, NULL),
(61, 1, 'CREATE', 'users', 11, '2026-06-13 12:31:56', '::1', 'Created user: Simon Njau', NULL, NULL),
(62, 7, 'CREATE', 'lpo_log', 1, '2026-06-13 12:34:05', '::1', 'Generated LPO-001', NULL, NULL),
(63, 2, 'CREATE', 'requisitions', 18, '2026-06-13 22:25:37', '::1', 'Submitted REQ-018', NULL, NULL),
(64, 7, 'APPROVE', 'requisitions', 17, '2026-06-13 22:26:18', '::1', 'Approved at level 2, advanced to 3', NULL, NULL),
(65, 9, 'APPROVE', 'requisitions', 17, '2026-06-13 22:27:08', '::1', 'Approved at level 3, advanced to 4', NULL, NULL),
(66, 10, 'APPROVE', 'requisitions', 17, '2026-06-13 22:28:15', '::1', 'Final approval — fully approved', NULL, NULL),
(67, 1, 'CREATE', 'users', 12, '2026-06-13 22:33:22', '::1', 'Created user: mark n', NULL, NULL),
(68, 6, 'CREATE', 'requisitions', 19, '2026-06-15 00:40:33', '::1', 'Submitted REQ-019', NULL, NULL),
(69, 8, 'APPROVE', 'requisitions', 10, '2026-06-15 00:41:39', '::1', 'Approved at level 2, advanced to 3', NULL, NULL),
(70, 8, 'APPROVE', 'requisitions', 19, '2026-06-15 00:41:43', '::1', 'Approved at level 2, advanced to 3', NULL, NULL),
(71, 9, 'REJECT', 'requisitions', 10, '2026-06-15 00:42:25', '::1', 'Rejected at level 3', NULL, NULL),
(72, 9, 'APPROVE', 'requisitions', 19, '2026-06-15 00:42:30', '::1', 'Approved at level 3, advanced to 4', NULL, NULL),
(73, 10, 'REJECT', 'requisitions', 3, '2026-06-15 00:43:08', '::1', 'Rejected at level 4', NULL, NULL),
(74, 10, 'APPROVE', 'requisitions', 19, '2026-06-15 00:44:44', '::1', 'Final approval — fully approved', NULL, NULL),
(75, 7, 'CREATE', 'lpo_log', 16, '2026-06-15 00:46:12', '::1', 'Generated LPO-016', NULL, NULL),
(76, 2, 'CREATE', 'requisitions', 20, '2026-06-15 21:35:38', '::1', 'Submitted REQ-020', NULL, NULL),
(77, 5, 'APPROVE', 'requisitions', 20, '2026-06-15 21:36:19', '::1', 'Approved at level 1, advanced to 2', NULL, NULL),
(78, 7, 'APPROVE', 'requisitions', 20, '2026-06-15 21:36:46', '::1', 'Approved at level 2, advanced to 3', NULL, NULL),
(79, 9, 'APPROVE', 'requisitions', 20, '2026-06-15 21:37:27', '::1', 'Approved at level 3, advanced to 4', NULL, NULL),
(80, 10, 'APPROVE', 'requisitions', 20, '2026-06-15 21:38:00', '::1', 'Final approval — fully approved', NULL, NULL),
(81, 7, 'CREATE', 'lpo_log', 20, '2026-06-15 21:38:24', '::1', 'Generated LPO-020', NULL, NULL),
(82, 9, 'CREATE', 'requisitions', 21, '2026-06-17 11:39:08', '::1', 'Submitted REQ-021', NULL, NULL),
(83, 7, 'APPROVE', 'requisitions', 21, '2026-06-17 11:39:36', '::1', 'Approved at level 2, advanced to 4', NULL, NULL),
(84, 10, 'APPROVE', 'requisitions', 21, '2026-06-17 11:40:20', '::1', 'Final approval — fully approved', NULL, NULL),
(85, 10, 'REJECT', 'requisitions', 9, '2026-06-17 11:40:40', '::1', 'Rejected at level 4', NULL, NULL),
(86, 7, 'CREATE', 'lpo_log', 17, '2026-06-17 11:46:54', '::1', 'Generated LPO-017', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `department_code` varchar(20) NOT NULL,
  `division` varchar(100) DEFAULT NULL,
  `budget_code` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `department_code`, `division`, `budget_code`) VALUES
(1, 'Information Technology', 'IT', 'Service', 'BDG-IT-2026'),
(2, 'Human Resources', 'HR', 'Administration', 'BDG-HR-2026'),
(3, 'Finance', 'FIN', 'Administration', 'BDG-FIN-2026'),
(4, 'Procurement', 'PROC', 'Operations', 'BDG-PROC-2026'),
(5, 'Operations', 'OPS', 'Operations', 'BDG-OPS-2026'),
(6, 'Sales', 'SLS', 'Sales', 'BDG-SLS-2026'),
(7, 'Management', 'MGM', 'Administration', 'BDG-MGM-2026');

-- --------------------------------------------------------

--
-- Table structure for table `item_catalog`
--

CREATE TABLE `item_catalog` (
  `catalog_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` enum('it_asset','procurement','merchandise','personnel') NOT NULL,
  `description` text DEFAULT NULL,
  `standard_unit_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `unit_label` varchar(30) DEFAULT 'unit',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_catalog`
--

INSERT INTO `item_catalog` (`catalog_id`, `item_name`, `category`, `description`, `standard_unit_cost`, `unit_label`, `is_active`, `created_at`) VALUES
(1, 'Dell Latitude 5540 Laptop', 'it_asset', '15.6\" FHD, Intel i7, 16GB RAM, 512GB SSD, Win 11 Pro', 100000.00, 'unit', 1, '2026-05-21 22:56:17'),
(2, 'HP EliteBook 840 G10 Laptop', 'it_asset', '14\" FHD, Intel i5, 8GB RAM, 256GB SSD', 85000.00, 'unit', 1, '2026-05-21 22:56:17'),
(3, 'Dell UltraSharp 27\" Monitor', 'it_asset', 'U2722D, 4K, USB-C, IPS panel', 45000.00, 'unit', 1, '2026-05-21 22:56:17'),
(4, 'Cisco Catalyst 2960-X 24-Port Switch', 'it_asset', '24x GigE, PoE+, LAN Base', 85000.00, 'unit', 1, '2026-05-21 22:56:17'),
(5, 'Uninterruptible Power Supply (UPS)', 'it_asset', 'APC Smart-UPS 1500VA, rack-mount', 35000.00, 'unit', 1, '2026-05-21 22:56:17'),
(6, 'HP LaserJet Pro M408dn Printer', 'it_asset', 'Mono laser, duplex, network-ready', 28000.00, 'unit', 1, '2026-05-21 22:56:17'),
(7, 'Logitech MX Keys Keyboard + Mouse', 'it_asset', 'Wireless combo, multi-device', 8500.00, 'unit', 1, '2026-05-21 22:56:17'),
(8, 'External Hard Drive 2TB', 'it_asset', 'Seagate Backup Plus, USB 3.0', 6500.00, 'unit', 1, '2026-05-21 22:56:17'),
(9, 'USB-C Docking Station', 'it_asset', 'Dell WD19S, 130W, dual display', 18000.00, 'unit', 1, '2026-05-21 22:56:17'),
(10, 'Webcam HD 1080p', 'it_asset', 'Logitech C920, with mic', 5500.00, 'unit', 1, '2026-05-21 22:56:17'),
(11, 'Network Patch Panel 24-Port', 'it_asset', 'Cat6, rack-mount, 1U', 4500.00, 'unit', 1, '2026-05-21 22:56:17'),
(12, 'Structured Cabling (per point)', 'it_asset', 'Cat6 data point installation including faceplate', 3500.00, 'point', 1, '2026-05-21 22:56:17'),
(13, 'A4 Printing Paper (500 sheets)', 'procurement', 'Double A brand, 80gsm', 800.00, 'ream', 1, '2026-05-21 22:56:17'),
(14, 'Ballpoint Pens (box of 50)', 'procurement', 'Blue ink, medium tip', 600.00, 'box', 1, '2026-05-21 22:56:17'),
(15, 'Stapler Heavy Duty', 'procurement', '26/6 staples, desktop', 1200.00, 'unit', 1, '2026-05-21 22:56:17'),
(16, 'Printer Ink Cartridge Set', 'procurement', 'Compatible with HP LaserJet M408', 3500.00, 'set', 1, '2026-05-21 22:56:17'),
(17, 'Whiteboard Markers (set of 4)', 'procurement', 'Assorted colours, dry-erase', 450.00, 'set', 1, '2026-05-21 22:56:17'),
(18, 'Lever Arch File A4', 'procurement', '70mm spine, assorted colours', 280.00, 'unit', 1, '2026-05-21 22:56:17'),
(19, 'Sticky Notes 76x76mm (pack of 12)', 'procurement', '3M Post-it, assorted neon', 650.00, 'pack', 1, '2026-05-21 22:56:17'),
(20, 'Toner Cartridge — HP 26A', 'procurement', 'Black, ~3100 pages yield', 4800.00, 'unit', 1, '2026-05-21 22:56:17'),
(21, 'Desk Organiser Set', 'procurement', '5-piece: pen holder, tray, file stand', 1800.00, 'set', 1, '2026-05-21 22:56:17'),
(22, 'Cleaning Supplies Monthly Pack', 'procurement', 'Disinfectant, wipes, hand sanitiser, bin liners', 3200.00, 'pack', 1, '2026-05-21 22:56:17'),
(23, 'Tea & Coffee Supplies (monthly)', 'procurement', 'Tea bags, coffee, sugar, creamer — office kitchen', 4500.00, 'month', 1, '2026-05-21 22:56:17'),
(24, 'Bottled Water (20L dispenser)', 'procurement', 'Keringet or equivalent, per bottle', 350.00, 'bottle', 1, '2026-05-21 22:56:17'),
(25, 'Branded Polo Shirt', 'merchandise', 'Isuzu EA logo embroidered, polyester-cotton blend', 1800.00, 'unit', 1, '2026-05-21 22:56:17'),
(26, 'Branded Cap', 'merchandise', 'Structured 6-panel, embroidered logo', 950.00, 'unit', 1, '2026-05-21 22:56:17'),
(27, 'Branded Notebook A5', 'merchandise', 'Hardcover, 200 pages, logo on cover', 650.00, 'unit', 1, '2026-05-21 22:56:17'),
(28, 'Branded Pen', 'merchandise', 'Metal ballpoint, laser-engraved logo', 350.00, 'unit', 1, '2026-05-21 22:56:17'),
(29, 'Branded Tote Bag', 'merchandise', 'Canvas, 38x42cm, screen-printed logo', 1200.00, 'unit', 1, '2026-05-21 22:56:17'),
(30, 'Branded Mug', 'merchandise', 'Ceramic 350ml, dishwasher-safe, logo print', 750.00, 'unit', 1, '2026-05-21 22:56:17'),
(31, 'Branded Lanyard', 'merchandise', 'Polyester, 20mm wide, safety clip, logo print', 400.00, 'unit', 1, '2026-05-21 22:56:17'),
(32, 'Branded USB Flash Drive 32GB', 'merchandise', 'Metal casing, USB 3.0, logo engraved', 1500.00, 'unit', 1, '2026-05-21 22:56:17'),
(33, 'Intern', 'personnel', 'Entry-level internship position (3-6 months)', 20000.00, 'month', 1, '2026-05-21 22:56:17'),
(34, 'Junior Associate', 'personnel', 'Graduate / entry-level permanent or contract role', 60000.00, 'month', 1, '2026-05-21 22:56:17'),
(35, 'Associate', 'personnel', 'Mid-level individual contributor', 150000.00, 'month', 1, '2026-05-21 22:56:17'),
(36, 'Senior Associate', 'personnel', 'Experienced individual contributor', 200000.00, 'month', 1, '2026-05-21 22:56:17'),
(37, 'Senior 1', 'personnel', 'Senior specialist / team lead', 280000.00, 'month', 1, '2026-05-21 22:56:17'),
(38, 'Senior 2', 'personnel', 'Senior specialist with broader scope', 350000.00, 'month', 1, '2026-05-21 22:56:17'),
(39, 'Senior 3', 'personnel', 'Principal specialist / department expert', 430000.00, 'month', 1, '2026-05-21 22:56:17'),
(40, 'Manager', 'personnel', 'People manager, single team', 500000.00, 'month', 1, '2026-05-21 22:56:17'),
(41, 'Senior Manager', 'personnel', 'Multi-team or cross-functional manager', 650000.00, 'month', 1, '2026-05-21 22:56:17'),
(42, 'Director', 'personnel', 'Department head / executive leadership', 900000.00, 'month', 1, '2026-05-21 22:56:17');

-- --------------------------------------------------------

--
-- Table structure for table `lpo_log`
--

CREATE TABLE `lpo_log` (
  `lpo_id` int(11) NOT NULL,
  `requisition_id` int(11) NOT NULL,
  `lpo_number` varchar(20) NOT NULL,
  `generated_by` int(11) NOT NULL,
  `generated_at` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lpo_log`
--

INSERT INTO `lpo_log` (`lpo_id`, `requisition_id`, `lpo_number`, `generated_by`, `generated_at`, `notes`) VALUES
(1, 15, 'LPO-015', 7, '2026-06-10 10:30:53', NULL),
(2, 1, 'LPO-001', 7, '2026-06-13 12:34:05', NULL),
(3, 16, 'LPO-016', 7, '2026-06-15 00:46:12', NULL),
(4, 20, 'LPO-020', 7, '2026-06-15 21:38:24', NULL),
(5, 17, 'LPO-017', 7, '2026-06-17 11:46:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `requisition_id` int(11) DEFAULT NULL,
  `notification_type` enum('submission','approval','rejection','reminder') DEFAULT 'submission',
  `message` text NOT NULL,
  `sent_date` datetime DEFAULT current_timestamp(),
  `read_status` enum('unread','read') DEFAULT 'unread',
  `email_sent` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `requisition_id`, `notification_type`, `message`, `sent_date`, `read_status`, `email_sent`) VALUES
(1, 2, 1, 'approval', 'Congratulations! Your requisition REQ-001 has been fully approved.', '2026-04-29 08:11:34', 'read', 1),
(2, 2, 4, 'approval', 'REQ-004 approved by Dept Head and forwarded to HR Director.', '2026-04-29 08:11:34', 'read', 1),
(3, 4, 2, 'rejection', 'Your requisition REQ-002 was rejected at HR Director. Reason: Budget not allocated for Q1.', '2026-04-29 08:11:34', 'read', 1),
(4, 4, 5, 'approval', 'REQ-005 approved by HR Director and forwarded to Finance Director.', '2026-04-29 08:11:34', 'unread', 1),
(5, 5, 3, 'submission', 'New requisition REQ-003 requires your approval (Level 1).', '2026-04-29 08:11:34', 'read', 1),
(6, 8, 4, 'approval', 'REQ-004 has been approved by Dept Head and now requires your approval (Level 2).', '2026-04-29 08:11:34', 'unread', 1),
(7, 9, 5, 'approval', 'REQ-005 has been approved by HR Director and now requires your approval (Level 3).', '2026-04-29 08:11:34', 'unread', 1),
(8, 9, 4, 'approval', 'REQ-004 approved by HR Director — requires your approval (Level 3).', '2026-05-06 23:45:11', 'unread', 0),
(9, 5, 6, 'submission', 'New requisition REQ-006 requires your approval.', '2026-05-07 00:58:41', 'read', 0),
(10, 8, 3, 'approval', 'REQ-003 approved by Dept Head — requires your approval (Level 2).', '2026-05-07 12:14:47', 'unread', 0),
(11, 9, 3, 'approval', 'REQ-003 approved by HR Director — requires your approval (Level 3).', '2026-05-07 12:16:18', 'unread', 0),
(12, 5, 7, 'submission', 'New requisition REQ-007 requires your approval.', '2026-05-07 12:20:01', 'read', 0),
(13, 8, 7, 'approval', 'REQ-007 approved by Dept Head — requires your approval (Level 2).', '2026-05-07 12:22:20', 'unread', 0),
(14, 9, 7, 'approval', 'REQ-007 approved by HR Director — requires your approval (Level 3).', '2026-05-07 12:24:23', 'unread', 0),
(15, 10, 7, 'approval', 'REQ-007 approved by Finance Director — requires your approval (Level 4).', '2026-05-07 12:25:15', 'read', 0),
(16, 2, 7, 'rejection', 'Your requisition REQ-007 was rejected at Managing Director.', '2026-05-07 12:26:22', 'read', 0),
(17, 5, 8, 'submission', 'New requisition REQ-008 requires your approval.', '2026-05-12 09:32:52', 'read', 0),
(18, 8, 8, 'approval', 'REQ-008 approved by Dept Head — requires your approval (Level 2).', '2026-05-12 09:33:15', 'unread', 0),
(19, 5, 9, 'submission', 'New requisition REQ-009 requires your approval.', '2026-05-22 09:08:01', 'read', 0),
(20, 6, 10, 'submission', 'New requisition REQ-010 requires your approval.', '2026-05-22 09:35:34', 'read', 0),
(21, 7, 6, 'approval', 'REQ-006 approved by Dept Head — requires your approval (Level 2).', '2026-05-22 09:43:57', 'read', 0),
(22, 7, 9, 'approval', 'REQ-009 approved by Dept Head — requires your approval (Level 2).', '2026-05-22 09:45:58', 'read', 0),
(23, 7, 11, 'submission', 'New requisition REQ-011 requires your approval.', '2026-05-22 09:47:46', 'read', 0),
(24, 8, 10, 'approval', 'REQ-010 approved by Dept Head — requires your approval (Level 2).', '2026-05-22 09:50:59', 'unread', 0),
(25, 7, 12, 'submission', 'New requisition REQ-012 requires your approval.', '2026-05-22 09:53:42', 'read', 0),
(26, 9, 13, 'submission', 'New requisition REQ-013 requires your approval.', '2026-05-22 09:57:30', 'unread', 0),
(27, 5, 14, 'submission', 'New requisition REQ-014 requires your approval.', '2026-05-25 19:06:18', 'read', 0),
(28, 8, 14, 'approval', 'REQ-014 approved by Dept Head — requires your approval (Level 2).', '2026-05-25 19:14:50', 'unread', 0),
(29, 9, 14, 'approval', 'REQ-014 approved by HR Director — requires your approval (Level 3).', '2026-05-25 19:18:45', 'unread', 0),
(30, 4, 5, 'rejection', 'Your requisition REQ-005 was rejected at Finance Director.', '2026-05-25 19:35:05', 'unread', 0),
(31, 10, 14, 'approval', 'REQ-014 approved by Finance Director — requires your approval (Level 4).', '2026-05-25 19:35:32', 'read', 0),
(32, 5, 15, 'submission', 'New requisition REQ-015 requires your approval.', '2026-05-26 13:53:35', 'unread', 0),
(33, 7, 15, 'approval', 'REQ-015 approved by IT Dept Head — requires your approval (Level 2).', '2026-05-26 13:54:42', 'read', 0),
(34, 2, 14, 'approval', 'Your requisition REQ-014 has been fully approved.', '2026-05-26 14:05:28', 'read', 0),
(35, 9, 12, 'approval', 'REQ-012 approved by Procurement Head — requires your approval (Level 3).', '2026-06-10 09:25:40', 'unread', 0),
(36, 7, 16, 'submission', 'New requisition REQ-016 requires your approval.', '2026-06-10 09:32:29', 'read', 0),
(37, 9, 16, 'approval', 'REQ-016 approved by Procurement Head — requires your approval (Level 3).', '2026-06-10 09:33:08', 'unread', 0),
(38, 2, 6, 'rejection', 'Your requisition REQ-006 was rejected at Procurement Head.', '2026-06-10 09:33:23', 'read', 0),
(39, 9, 15, 'approval', 'REQ-015 approved by Procurement Head — requires your approval (Level 3).', '2026-06-10 09:33:33', 'unread', 0),
(40, 5, 11, 'rejection', 'Your requisition REQ-011 was rejected at Procurement Head.', '2026-06-10 09:33:44', 'unread', 0),
(41, 9, 9, 'approval', 'REQ-009 approved by Procurement Head — requires your approval (Level 3).', '2026-06-10 09:33:49', 'read', 0),
(42, 10, 16, 'approval', 'REQ-016 approved by Finance Director — requires your approval (Level 4).', '2026-06-10 09:34:17', 'read', 0),
(43, 10, 9, 'approval', 'REQ-009 approved by Finance Director — requires your approval (Level 4).', '2026-06-10 09:34:26', 'read', 0),
(44, 10, 3, 'approval', 'REQ-003 approved by Finance Director — requires your approval (Level 4).', '2026-06-10 09:34:36', 'read', 0),
(45, 7, 13, 'rejection', 'Your requisition REQ-013 was rejected at Finance Director.', '2026-06-10 09:34:55', 'unread', 0),
(46, 10, 12, 'approval', 'REQ-012 approved by Finance Director — requires your approval (Level 4).', '2026-06-10 09:35:06', 'read', 0),
(47, 2, 4, 'rejection', 'Your requisition REQ-004 was rejected at Finance Director.', '2026-06-10 09:35:24', 'read', 0),
(48, 10, 15, 'approval', 'REQ-015 approved by Finance Director — requires your approval (Level 4).', '2026-06-10 09:35:33', 'read', 0),
(49, 2, 16, 'approval', 'Your requisition REQ-016 has been fully approved.', '2026-06-10 09:35:56', 'read', 0),
(50, 2, 15, 'approval', 'Your requisition REQ-015 has been fully approved.', '2026-06-10 09:36:05', 'read', 0),
(51, 2, 15, 'approval', 'LPO LPO-015 has been generated for your requisition REQ-015.', '2026-06-10 10:30:53', 'read', 0),
(52, 7, 17, 'submission', 'New requisition REQ-017 requires your approval.', '2026-06-10 23:44:22', 'unread', 0),
(53, 2, 1, 'approval', 'LPO LPO-001 has been generated for your requisition REQ-001.', '2026-06-13 12:34:05', 'read', 0),
(54, 7, 18, 'submission', 'New requisition REQ-018 requires your approval.', '2026-06-13 22:25:37', 'unread', 0),
(55, 9, 17, 'approval', 'REQ-017 approved by Procurement Head — requires your approval (Level 3).', '2026-06-13 22:26:18', 'unread', 0),
(56, 10, 17, 'approval', 'REQ-017 approved by Finance Director — requires your approval (Level 4).', '2026-06-13 22:27:08', 'read', 0),
(57, 2, 17, 'approval', 'Your requisition REQ-017 has been fully approved.', '2026-06-13 22:28:15', 'read', 0),
(58, 8, 19, 'submission', 'New requisition REQ-019 requires your approval.', '2026-06-15 00:40:33', 'unread', 0),
(59, 9, 10, 'approval', 'REQ-010 approved by HR Director — requires your approval (Level 3).', '2026-06-15 00:41:39', 'unread', 0),
(60, 9, 19, 'approval', 'REQ-019 approved by HR Director — requires your approval (Level 3).', '2026-06-15 00:41:43', 'unread', 0),
(61, 4, 10, 'rejection', 'Your requisition REQ-010 was rejected at Finance Director.', '2026-06-15 00:42:25', 'unread', 0),
(62, 10, 19, 'approval', 'REQ-019 approved by Finance Director — requires your approval (Level 4).', '2026-06-15 00:42:30', 'read', 0),
(63, 3, 3, 'rejection', 'Your requisition REQ-003 was rejected at Managing Director.', '2026-06-15 00:43:08', 'unread', 0),
(64, 6, 19, 'approval', 'Your requisition REQ-019 has been fully approved.', '2026-06-15 00:44:44', 'unread', 0),
(65, 2, 16, 'approval', 'LPO LPO-016 has been generated for your requisition REQ-016.', '2026-06-15 00:46:12', 'read', 0),
(66, 5, 20, 'submission', 'New requisition REQ-020 requires your approval.', '2026-06-15 21:35:38', 'unread', 0),
(67, 7, 20, 'approval', 'REQ-020 approved by IT Dept Head — requires your approval (Level 2).', '2026-06-15 21:36:19', 'unread', 0),
(68, 9, 20, 'approval', 'REQ-020 approved by Procurement Head — requires your approval (Level 3).', '2026-06-15 21:36:46', 'unread', 0),
(69, 10, 20, 'approval', 'REQ-020 approved by Finance Director — requires your approval (Level 4).', '2026-06-15 21:37:27', 'unread', 0),
(70, 2, 20, 'approval', 'Your requisition REQ-020 has been fully approved.', '2026-06-15 21:38:00', 'unread', 0),
(71, 2, 20, 'approval', 'LPO LPO-020 has been generated for your requisition REQ-020.', '2026-06-15 21:38:24', 'unread', 0),
(72, 7, 21, 'submission', 'New requisition REQ-021 requires your approval.', '2026-06-17 11:39:08', 'unread', 0),
(73, 10, 21, 'approval', 'REQ-021 approved by Procurement Head — requires your approval (Level 4).', '2026-06-17 11:39:36', 'unread', 0),
(74, 9, 21, 'approval', 'Your requisition REQ-021 has been fully approved.', '2026-06-17 11:40:20', 'read', 0),
(75, 2, 9, 'rejection', 'Your requisition REQ-009 was rejected at Managing Director.', '2026-06-17 11:40:40', 'unread', 0),
(76, 2, 17, 'approval', 'LPO LPO-017 has been generated for your requisition REQ-017.', '2026-06-17 11:46:54', 'unread', 0);

-- --------------------------------------------------------

--
-- Table structure for table `requisitions`
--

CREATE TABLE `requisitions` (
  `requisition_id` int(11) NOT NULL,
  `requisition_number` varchar(20) NOT NULL,
  `requisition_type` enum('personnel','procurement','it_asset','merchandise') NOT NULL,
  `requester_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `submission_date` datetime DEFAULT current_timestamp(),
  `current_status` enum('pending','in_review','approved','rejected','cancelled') DEFAULT 'pending',
  `priority` enum('high','medium','low') DEFAULT 'medium',
  `description` text DEFAULT NULL,
  `date_required` date DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `current_approval_level` tinyint(4) DEFAULT 1,
  `position_title` varchar(200) DEFAULT NULL,
  `employment_type` enum('permanent','contract','internship') DEFAULT NULL,
  `elements_of_job` text DEFAULT NULL,
  `special_qualifications` text DEFAULT NULL,
  `working_tools_required` text DEFAULT NULL,
  `final_approver_id` int(11) DEFAULT NULL,
  `final_decision_date` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `budget_code` varchar(30) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requisitions`
--

INSERT INTO `requisitions` (`requisition_id`, `requisition_number`, `requisition_type`, `requester_id`, `department_id`, `submission_date`, `current_status`, `priority`, `description`, `date_required`, `total_amount`, `current_approval_level`, `position_title`, `employment_type`, `elements_of_job`, `special_qualifications`, `working_tools_required`, `final_approver_id`, `final_decision_date`, `rejection_reason`, `cancelled_by`, `cancelled_at`, `budget_code`, `created_at`, `updated_at`) VALUES
(1, 'REQ-001', 'it_asset', 2, 1, '2025-12-20 09:00:00', 'approved', 'high', 'Procurement of 5 laptops for new IT support team members joining in January.', '2026-02-15', 500000.00, 4, NULL, NULL, NULL, NULL, NULL, 10, '2026-01-10 14:30:00', NULL, NULL, NULL, 'BDG-IT-2026', '2026-04-29 08:11:34', '2026-04-29 08:11:34'),
(2, 'REQ-002', 'personnel', 4, 2, '2025-12-25 10:00:00', 'rejected', 'medium', 'We need a Marketing Manager to lead our new brand campaign starting Q2 2026.', '2026-03-01', 0.00, 2, 'Marketing Manager', 'permanent', NULL, NULL, NULL, NULL, NULL, 'Budget for this position has not been allocated in Q1. Please resubmit in Q2.', NULL, NULL, 'BDG-HR-2026', '2026-04-29 08:11:34', '2026-04-29 08:11:34'),
(3, 'REQ-003', 'procurement', 3, 4, '2026-01-05 08:30:00', 'rejected', 'high', 'Office stationery and consumables for Procurement department Q1 2026.', '2026-02-01', 45000.00, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'N/A', NULL, NULL, 'BDG-PROC-2026', '2026-04-29 08:11:34', '2026-06-15 00:43:08'),
(4, 'REQ-004', 'it_asset', 2, 1, '2026-01-03 11:00:00', 'rejected', 'medium', 'Network switch replacement for server room. Current switch is 8 years old and causing packet loss.', '2026-03-01', 85000.00, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Not now', NULL, NULL, 'BDG-IT-2026', '2026-04-29 08:11:34', '2026-06-10 09:35:24'),
(5, 'REQ-005', 'personnel', 4, 2, '2025-12-28 14:00:00', 'rejected', 'low', 'HR requires an intern for records management and filing for 3 months.', '2026-04-01', 0.00, 3, 'Records Management Intern', 'internship', NULL, NULL, NULL, NULL, NULL, 'Not priority', NULL, NULL, 'BDG-HR-2026', '2026-04-29 08:11:34', '2026-05-25 19:35:05'),
(6, 'REQ-006', 'merchandise', 2, 1, '2026-05-07 00:58:41', 'rejected', 'medium', 'For company representation at the National AI Conference.', '2026-05-20', 8400.00, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Not priority', NULL, NULL, NULL, '2026-05-07 00:58:41', '2026-06-10 09:33:23'),
(7, 'REQ-007', 'merchandise', 2, 1, '2026-05-07 12:20:01', 'rejected', 'medium', 'AI conference', '2026-05-21', 8200.00, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Not a priority', NULL, NULL, NULL, '2026-05-07 12:20:01', '2026-05-07 12:26:22'),
(8, 'REQ-008', 'it_asset', 5, 1, '2026-05-12 09:32:52', 'cancelled', 'high', 'New network connection', '2026-05-27', 50000.00, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, '2026-05-12 09:33:22', NULL, '2026-05-12 09:32:52', '2026-05-12 09:33:22'),
(9, 'REQ-009', 'procurement', 2, 1, '2026-05-22 09:08:01', 'rejected', 'medium', 'Office A printers', '2026-06-01', 7000.00, 4, '', NULL, NULL, NULL, NULL, NULL, NULL, 'not needed', NULL, NULL, NULL, '2026-05-22 09:08:01', '2026-06-17 11:40:40'),
(10, 'REQ-010', 'personnel', 4, 2, '2026-05-22 09:35:34', 'rejected', 'medium', 'Help with internal hiring', '2026-06-15', 20000.00, 3, 'Talent Acquisition Intern', 'internship', NULL, NULL, NULL, NULL, NULL, 'Hold for Q4', NULL, NULL, NULL, '2026-05-22 09:35:34', '2026-06-15 00:42:25'),
(11, 'REQ-011', 'merchandise', 5, 1, '2026-05-22 09:47:46', 'rejected', 'medium', 'Personal Replacement', '2026-06-09', 1800.00, 2, '', NULL, NULL, NULL, NULL, NULL, NULL, 'Not priority', NULL, NULL, NULL, '2026-05-22 09:47:46', '2026-06-10 09:33:44'),
(12, 'REQ-012', 'procurement', 6, 2, '2026-05-22 09:53:42', 'pending', 'high', 'Required', '2026-05-29', 1600.00, 4, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-22 09:53:42', '2026-06-10 09:35:06'),
(13, 'REQ-013', 'procurement', 7, 5, '2026-05-22 09:57:30', 'rejected', 'medium', 'needed', '2026-05-29', 600.00, 3, '', NULL, NULL, NULL, NULL, NULL, NULL, 'not priority', NULL, NULL, NULL, '2026-05-22 09:57:30', '2026-06-10 09:34:55'),
(14, 'REQ-014', 'personnel', 2, 1, '2026-05-25 19:06:18', 'approved', 'medium', 'Offer support to users', '2026-08-03', 60000.00, 4, 'IT Intern', 'internship', NULL, NULL, NULL, 10, '2026-05-26 14:05:28', NULL, NULL, NULL, NULL, '2026-05-25 19:06:18', '2026-05-26 14:05:28'),
(15, 'REQ-015', 'it_asset', 2, 2, '2026-05-26 13:53:35', 'approved', 'medium', 'Needed for new IT team', '2026-06-03', 100000.00, 4, '', NULL, NULL, NULL, NULL, 10, '2026-06-10 09:36:05', NULL, NULL, NULL, NULL, '2026-05-26 13:53:35', '2026-06-10 09:36:05'),
(16, 'REQ-016', 'procurement', 2, 1, '2026-06-10 09:32:29', 'approved', 'medium', 'New team member', '2026-06-30', 280.00, 4, '', NULL, NULL, NULL, NULL, 10, '2026-06-10 09:35:56', NULL, NULL, NULL, NULL, '2026-06-10 09:32:29', '2026-06-10 09:35:56'),
(17, 'REQ-017', 'procurement', 2, 1, '2026-06-10 23:44:22', 'approved', 'medium', 'For office A', '2026-06-19', 3500.00, 4, '', NULL, NULL, NULL, NULL, 10, '2026-06-13 22:28:15', NULL, NULL, NULL, NULL, '2026-06-10 23:44:22', '2026-06-13 22:28:15'),
(18, 'REQ-018', 'procurement', 2, 1, '2026-06-13 22:25:37', 'pending', 'medium', 'Needed for office', '2026-06-15', 3500.00, 2, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-13 22:25:37', '2026-06-13 22:25:37'),
(19, 'REQ-019', 'personnel', 6, 5, '2026-06-15 00:40:33', 'approved', 'medium', '2 years contractor to backfill', '2026-07-01', 1440000.00, 4, 'Customer Support Officer', 'contract', NULL, NULL, NULL, 10, '2026-06-15 00:44:44', NULL, NULL, NULL, NULL, '2026-06-15 00:40:33', '2026-06-15 00:44:44'),
(20, 'REQ-020', 'it_asset', 2, 1, '2026-06-15 21:35:38', 'approved', 'low', 'Needed for office A', '2026-06-26', 28000.00, 4, '', NULL, NULL, NULL, NULL, 10, '2026-06-15 21:38:00', NULL, NULL, NULL, NULL, '2026-06-15 21:35:38', '2026-06-15 21:38:00'),
(21, 'REQ-021', 'merchandise', 9, 3, '2026-06-17 11:39:08', 'approved', 'medium', 'For office use', '2026-06-26', 650.00, 4, '', NULL, NULL, NULL, NULL, 10, '2026-06-17 11:40:20', NULL, NULL, NULL, NULL, '2026-06-17 11:39:08', '2026-06-17 11:40:20');

-- --------------------------------------------------------

--
-- Table structure for table `requisition_items`
--

CREATE TABLE `requisition_items` (
  `item_id` int(11) NOT NULL,
  `requisition_id` int(11) NOT NULL,
  `item_description` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(15,2) GENERATED ALWAYS AS (`quantity` * `unit_cost`) STORED,
  `specifications` text DEFAULT NULL,
  `catalog_id` int(11) DEFAULT NULL,
  `is_custom` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requisition_items`
--

INSERT INTO `requisition_items` (`item_id`, `requisition_id`, `item_description`, `quantity`, `unit_cost`, `specifications`, `catalog_id`, `is_custom`) VALUES
(1, 1, 'Dell Latitude 5540 Laptop', 5, 100000.00, '15.6\" FHD, Intel i7, 16GB RAM, 512GB SSD, Windows 11 Pro', NULL, 0),
(2, 3, 'A4 Printing Paper (500 sheets)', 20, 800.00, 'Double A brand, 80gsm', NULL, 0),
(3, 3, 'Ballpoint Pens (box of 50)', 10, 600.00, 'Blue ink, medium tip', NULL, 0),
(4, 3, 'Stapler', 5, 1200.00, 'Heavy duty, 26/6 staples', NULL, 0),
(5, 3, 'Printer Ink Cartridges (set)', 6, 3500.00, 'Compatible with HP LaserJet M408', NULL, 0),
(6, 4, 'Cisco Catalyst 2960-X 24-Port Switch', 1, 85000.00, '24x GigE, PoE+, LAN Base', NULL, 0),
(7, 6, 'Branded Polo-Shirts', 7, 1200.00, NULL, NULL, 0),
(8, 7, 'Branded Laptop Covers', 5, 1500.00, NULL, NULL, 0),
(9, 7, 'Branded Polo shirts', 1, 700.00, NULL, NULL, 0),
(10, 8, 'Cisco Switch', 1, 50000.00, NULL, NULL, 0),
(11, 9, 'Printer Ink Cartridge Set', 2, 3500.00, NULL, 16, 0),
(12, 10, 'Intern', 1, 20000.00, NULL, 33, 0),
(13, 11, 'Branded Polo Shirt', 1, 1800.00, NULL, 25, 0),
(14, 12, 'A4 Printing Paper (500 sheets)', 2, 800.00, NULL, 13, 0),
(15, 13, 'Ballpoint Pens (box of 50)', 1, 600.00, NULL, 14, 0),
(16, 14, 'Intern', 3, 20000.00, NULL, 33, 0),
(17, 15, 'Dell Latitude 5540 Laptop', 1, 100000.00, NULL, 1, 0),
(18, 16, 'Lever Arch File A4', 1, 280.00, NULL, 18, 0),
(19, 17, 'Printer Ink Cartridge Set', 1, 3500.00, NULL, 16, 0),
(20, 18, 'Printer Ink Cartridge Set', 1, 3500.00, NULL, 16, 0),
(21, 19, 'Junior Associate', 24, 60000.00, NULL, 34, 0),
(22, 20, 'HP LaserJet Pro M408dn Printer', 1, 28000.00, NULL, 6, 0),
(23, 21, 'Branded Notebook A5', 1, 650.00, NULL, 27, 0);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`, `permissions`) VALUES
(1, 'System Admin', 'Full system access', '{\"all\": true}'),
(2, 'HR Admin', 'Manages users and personnel requisitions', '{\"users\": true, \"reports\": true}'),
(3, 'Approver', 'Reviews and approves/rejects requisitions', '{\"approve\": true, \"view_all\": true}'),
(4, 'Requester', 'Creates and tracks own requisitions', '{\"create\": true, \"view_own\": true}');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(80) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(120) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `section` varchar(100) DEFAULT NULL,
  `created_date` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `email`, `full_name`, `department_id`, `role_id`, `phone_number`, `status`, `section`, `created_date`, `last_login`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@isuzu.co.ke', 'System Administrator', 1, 1, '+254700000001', 'active', NULL, '2026-04-29 08:11:34', '2026-06-17 11:44:02'),
(2, 'sharon.kitavi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sharon.kitavi@isuzu.co.ke', 'Sharon Kitavi', 1, 4, '+254700000002', 'active', 'Support', '2026-04-29 08:11:34', '2026-06-17 11:36:13'),
(3, 'john.muchai', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'john.muchai@isuzu.co.ke', 'John Muchai', 4, 4, '+254700000003', 'active', 'Supply Chain', '2026-04-29 08:11:34', '2026-06-09 12:01:36'),
(4, 'jane.smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jane.smith@isuzu.co.ke', 'Jane Smith', 2, 4, '+254700000004', 'active', 'Recruitment', '2026-04-29 08:11:34', '2026-05-22 09:33:48'),
(5, 'elizabeth.wanjiku', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'elizabeth.wanjiku@isuzu.co.ke', 'Elizabeth Wanjiku', 1, 3, '+254700000005', 'active', 'IT Dept Head', '2026-04-29 08:11:34', '2026-06-17 11:43:45'),
(6, 'peter.kamau', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'peter.kamau@isuzu.co.ke', 'Peter Kamau', 2, 3, '+254700000006', 'active', 'HR Dept Head', '2026-04-29 08:11:34', '2026-06-15 00:38:56'),
(7, 'mary.wambua', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mary.wambua@isuzu.co.ke', 'Mary Wambua', 4, 3, '+254700000007', 'active', 'Procurement Dept Head', '2026-04-29 08:11:34', '2026-06-17 11:46:03'),
(8, 'hr.director', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hr.director@isuzu.co.ke', 'Grace Odhiambo', 2, 2, '+254700000008', 'active', NULL, '2026-04-29 08:11:34', '2026-06-15 21:41:30'),
(9, 'finance.director', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'finance.director@isuzu.co.ke', 'David Kariuki', 3, 3, '+254700000009', 'active', NULL, '2026-04-29 08:11:34', '2026-06-17 11:40:52'),
(10, 'md', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'md@isuzu.co.ke', 'James Ngugi', 7, 3, '+254700000010', 'active', NULL, '2026-04-29 08:11:34', '2026-06-17 11:39:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `approval_history`
--
ALTER TABLE `approval_history`
  ADD PRIMARY KEY (`approval_id`),
  ADD KEY `idx_ah_requisition` (`requisition_id`),
  ADD KEY `idx_ah_approver` (`approver_id`),
  ADD KEY `approval_history_ibfk_3` (`level_id`);

--
-- Indexes for table `approval_levels`
--
ALTER TABLE `approval_levels`
  ADD PRIMARY KEY (`level_id`),
  ADD UNIQUE KEY `uq_type_level` (`requisition_type`,`level_number`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_timestamp` (`timestamp`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_code` (`department_code`);

--
-- Indexes for table `item_catalog`
--
ALTER TABLE `item_catalog`
  ADD PRIMARY KEY (`catalog_id`),
  ADD KEY `idx_catalog_category` (`category`);

--
-- Indexes for table `lpo_log`
--
ALTER TABLE `lpo_log`
  ADD PRIMARY KEY (`lpo_id`),
  ADD UNIQUE KEY `uq_lpo_requisition` (`requisition_id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `requisition_id` (`requisition_id`),
  ADD KEY `idx_notif_user_read` (`user_id`,`read_status`);

--
-- Indexes for table `requisitions`
--
ALTER TABLE `requisitions`
  ADD PRIMARY KEY (`requisition_id`),
  ADD UNIQUE KEY `requisition_number` (`requisition_number`),
  ADD KEY `final_approver_id` (`final_approver_id`),
  ADD KEY `cancelled_by` (`cancelled_by`),
  ADD KEY `idx_req_status` (`current_status`),
  ADD KEY `idx_req_type` (`requisition_type`),
  ADD KEY `idx_req_requester` (`requester_id`),
  ADD KEY `idx_req_department` (`department_id`),
  ADD KEY `idx_req_level` (`current_approval_level`);

--
-- Indexes for table `requisition_items`
--
ALTER TABLE `requisition_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `requisition_id` (`requisition_id`),
  ADD KEY `fk_ri_catalog` (`catalog_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approval_history`
--
ALTER TABLE `approval_history`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `approval_levels`
--
ALTER TABLE `approval_levels`
  MODIFY `level_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `item_catalog`
--
ALTER TABLE `item_catalog`
  MODIFY `catalog_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `lpo_log`
--
ALTER TABLE `lpo_log`
  MODIFY `lpo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `requisitions`
--
ALTER TABLE `requisitions`
  MODIFY `requisition_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `requisition_items`
--
ALTER TABLE `requisition_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `approval_history`
--
ALTER TABLE `approval_history`
  ADD CONSTRAINT `approval_history_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`requisition_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `approval_history_ibfk_2` FOREIGN KEY (`approver_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `approval_history_ibfk_3` FOREIGN KEY (`level_id`) REFERENCES `approval_levels` (`level_id`);

--
-- Constraints for table `approval_levels`
--
ALTER TABLE `approval_levels`
  ADD CONSTRAINT `approval_levels_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `lpo_log`
--
ALTER TABLE `lpo_log`
  ADD CONSTRAINT `lpo_log_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`requisition_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lpo_log_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`requisition_id`) ON DELETE SET NULL;

--
-- Constraints for table `requisitions`
--
ALTER TABLE `requisitions`
  ADD CONSTRAINT `requisitions_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `requisitions_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `requisitions_ibfk_3` FOREIGN KEY (`final_approver_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `requisitions_ibfk_4` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `requisition_items`
--
ALTER TABLE `requisition_items`
  ADD CONSTRAINT `fk_ri_catalog` FOREIGN KEY (`catalog_id`) REFERENCES `item_catalog` (`catalog_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `requisition_items_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`requisition_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
