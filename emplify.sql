-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 29, 2026 at 05:38 AM
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
-- Database: `emplify`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `desg_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `name`, `email`, `phone`, `desg_id`) VALUES
(1, 'Bikas Sen', 'bikas@123gmail.com', '6236562310', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `att_id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `check_in` time NOT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('Present','Absent','Leave') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`att_id`, `emp_id`, `date`, `check_in`, `check_out`, `status`) VALUES
(39, 6, '2026-03-26', '10:46:50', '10:46:52', 'Present'),
(40, 6, '2026-03-26', '10:46:54', '10:46:57', 'Present'),
(41, 6, '2026-03-26', '10:47:07', '10:47:09', 'Present'),
(42, 6, '2026-03-27', '09:06:59', '09:07:00', 'Present'),
(43, 6, '2026-03-27', '09:07:02', '09:07:04', 'Present'),
(44, 6, '2026-03-27', '09:07:05', '09:07:07', 'Present'),
(45, 7, '2026-03-27', '12:20:23', '12:20:37', 'Present'),
(46, 6, '2026-03-28', '09:39:09', '10:41:19', 'Present'),
(47, 6, '2026-03-28', '19:54:05', '19:58:38', 'Present'),
(48, 7, '2026-03-28', '20:01:01', '20:01:03', 'Present'),
(49, 7, '2026-03-28', '20:01:04', '20:01:05', 'Present'),
(50, 7, '2026-03-28', '20:01:07', '20:01:09', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `dep_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`dep_id`, `name`, `description`) VALUES
(1, 'Sales', 'Drives revenue by selling products or services and building customer relationships.\r\n'),
(2, 'Accounts', 'Handles financial transactions, budgeting, and ensures accurate financial reporting.\r\n'),
(3, 'HR', 'Manages recruitment, employee relations, and organizational development.\r\n'),
(4, 'IT', 'Maintains technological infrastructure and supports digital systems and security.\r\n'),
(5, 'Operations', 'Oversees day-to-day processes to ensure efficient production and service delivery.\r\n'),
(9, 'Marketing', 'test');

-- --------------------------------------------------------

--
-- Table structure for table `designation`
--

CREATE TABLE `designation` (
  `desg_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `designation`
--

INSERT INTO `designation` (`desg_id`, `name`, `description`) VALUES
(1, 'Manager', 'Oversees team operations, planning, and resource management to achieve organizational goals.\r\n'),
(2, 'Executive', 'Handles strategic tasks, communication, and high-level administrative responsibilities.'),
(3, 'Team Lead', 'Guides and coordinates a team, ensuring successful project execution and collaboration.\r\n'),
(4, 'Intern', 'A trainee gaining practical experience while assisting with basic tasks and learning processes.\r\n'),
(5, 'Senior Developer', 'Designs, develops, and maintains complex software solutions, mentoring junior developers.\r\n');

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `emp_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `dep_id` int(11) DEFAULT NULL,
  `desg_id` int(11) DEFAULT NULL,
  `image` text NOT NULL,
  `leave_balance` int(11) NOT NULL,
  `last_leave_reset` varchar(7) NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`emp_id`, `name`, `email`, `phone`, `gender`, `dob`, `address`, `dep_id`, `desg_id`, `image`, `leave_balance`, `last_leave_reset`, `status`) VALUES
(2, 'Rana Garai', 'ranagarai820@gmail.com', '9883169583', 'Male', '0000-00-00', 'Melatala,po: Shyam Nabagram;ps-Monteswar', 4, 5, '', 0, '', 1),
(5, 'Bikas Sen', 'bikas1234@gmail.com', '5698754525', 'Male', '0000-00-00', 'xyz11', 2, 1, '', 0, '', 1),
(6, 'Arka Dutta', 'arkadutta333@gmail.com', '12345678900', 'Male', '2003-05-18', 'Bankura More', 9, 2, '1774596100_IMG_20231105_103916 copy.jpg', 0, '2026-03', 1),
(7, 'Soumyadip Hazra', 'soumyadip@123gmail.com', '123456789', 'Male', '2003-02-06', 'Katwa', 9, 2, '', 5, '2026-03', 1);

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `submitted_on` datetime DEFAULT current_timestamp(),
  `status` enum('unread','read') DEFAULT 'unread',
  `admin_comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `emp_id`, `subject`, `message`, `submitted_on`, `status`, `admin_comment`) VALUES
(1, 2, 'work area problem', 'work area and the consumer are not supportive', '2025-06-09 21:04:30', 'read', 'ygiyfgiyewgd'),
(2, 6, 'ugeygf8yeg8yfg', 'er2524trqe', '2025-06-09 21:22:19', 'read', 'best\\\\r\\\\njgg'),
(3, 6, 'igwiydgiywgdiy', 'adgfetrwert', '2025-06-10 19:20:07', 'read', 'iygd8tfq8wfy8gqwydb');

-- --------------------------------------------------------

--
-- Table structure for table `leave_management`
--

CREATE TABLE `leave_management` (
  `leave_id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `leave_type` enum('Sick','Casual','Emergency','Other') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_management`
--

INSERT INTO `leave_management` (`leave_id`, `emp_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`) VALUES
(13, 6, 'Casual', '2026-03-27', '2026-03-29', 'ds', 'Approved'),
(14, 6, 'Casual', '2026-03-27', '2026-03-28', 'ee', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `login_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `emp_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`login_id`, `username`, `password`, `emp_id`) VALUES
(2, 'bikas123', 'bikas@123', 5),
(3, 'arka123', '$2y$10$kTe/m6CtfGSl/JSUf5DBOe7C.pxVW9BinG5Dy.k5tPDcRuAhU1QEG', 6),
(4, 'soumya123', '$2y$10$nH5ph/obyjsBCkUzMFtIn.N7Wq/JT1FaaILF2PjsAoGBEUTAz1IJ6', 7);

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `is_site_visit` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `task_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `emp_id`, `title`, `description`, `is_site_visit`, `created_at`, `task_id`) VALUES
(6, 6, 'Hello World - Report', 'wwwwwwwwwwwwwwwwwwwwwwwwwwww', 0, '2026-03-27 03:50:45', 27),
(8, 6, 'Hello World - Report', 'aaaaaaaaaaaaaaaaaaaa', 0, '2026-03-27 03:59:29', 27),
(9, 6, 'hello Google  - Report', 'progress', 0, '2026-03-27 04:05:02', 28),
(10, 6, 'hello Google  - Report', 'Completed', 0, '2026-03-27 04:05:27', 28),
(11, 6, 'Hello World - Report', 'com', 0, '2026-03-27 07:22:08', 27);

-- --------------------------------------------------------

--
-- Table structure for table `report_images`
--

CREATE TABLE `report_images` (
  `image_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report_images`
--

INSERT INTO `report_images` (`image_id`, `report_id`, `image_path`, `uploaded_at`) VALUES
(6, 6, 'uploads/reports/69c5fe9579b1d_686a91063b5a1_my photo.jpg', '2026-03-27 03:50:45'),
(8, 8, 'uploads/reports/69c600a1a995d_685a3ef73e3c8_Screenshot (4).png', '2026-03-27 03:59:29'),
(9, 9, 'uploads/reports/69c601ee004b5_Screenshot (5).png', '2026-03-27 04:05:02'),
(10, 10, 'uploads/reports/69c60207aa0c6_Screenshot (1).png', '2026-03-27 04:05:27'),
(11, 11, 'uploads/reports/69c63020ea34e_IMG_20231105_103916 copy.jpg', '2026-03-27 07:22:08');

-- --------------------------------------------------------

--
-- Table structure for table `site_visit_tracking`
--

CREATE TABLE `site_visit_tracking` (
  `visit_id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `geo_location` varchar(100) DEFAULT NULL,
  `visit_time` datetime DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_assigned`
--

CREATE TABLE `task_assigned` (
  `task_id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `title` varchar(70) NOT NULL,
  `description` text DEFAULT NULL,
  `assign_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Assigned','In Progress','Completed','Pending Review') DEFAULT 'Assigned',
  `report` text DEFAULT NULL,
  `file_upload` varchar(255) DEFAULT NULL,
  `report_submitted_on` datetime DEFAULT NULL,
  `is_site_visit` tinyint(1) DEFAULT 0,
  `location` varchar(255) DEFAULT NULL,
  `feedback` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_assigned`
--

INSERT INTO `task_assigned` (`task_id`, `emp_id`, `title`, `description`, `assign_date`, `due_date`, `status`, `report`, `file_upload`, `report_submitted_on`, `is_site_visit`, `location`, `feedback`) VALUES
(27, 6, 'Hello World', 'fsdggggggggggggggg', '2026-03-27', '2026-03-27', 'Completed', NULL, NULL, '2026-03-27 09:29:12', 1, 'burdwan', 'ddddddd'),
(28, 6, 'hello Google ', 'hihstkhertkedshekgeisy ', '2026-03-27', '2026-03-27', 'Completed', NULL, NULL, '2026-03-27 09:35:17', 1, 'katwa', 'Completed');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `desg_id` (`desg_id`),
  ADD UNIQUE KEY `desg_id_2` (`desg_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`att_id`),
  ADD KEY `emp_id` (`emp_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`dep_id`);

--
-- Indexes for table `designation`
--
ALTER TABLE `designation`
  ADD PRIMARY KEY (`desg_id`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`emp_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `dep_id` (`dep_id`),
  ADD KEY `desg_id` (`desg_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `emp_id` (`emp_id`);

--
-- Indexes for table `leave_management`
--
ALTER TABLE `leave_management`
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `emp_id` (`emp_id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`login_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_login_emp` (`emp_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `emp_id` (`emp_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `report_images`
--
ALTER TABLE `report_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `report_id` (`report_id`);

--
-- Indexes for table `site_visit_tracking`
--
ALTER TABLE `site_visit_tracking`
  ADD PRIMARY KEY (`visit_id`),
  ADD KEY `emp_id` (`emp_id`);

--
-- Indexes for table `task_assigned`
--
ALTER TABLE `task_assigned`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `emp_id` (`emp_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `att_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `dep_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `designation`
--
ALTER TABLE `designation`
  MODIFY `desg_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `emp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `leave_management`
--
ALTER TABLE `leave_management`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `login_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `report_images`
--
ALTER TABLE `report_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `site_visit_tracking`
--
ALTER TABLE `site_visit_tracking`
  MODIFY `visit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_assigned`
--
ALTER TABLE `task_assigned`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `fk_admin_designation` FOREIGN KEY (`desg_id`) REFERENCES `designation` (`desg_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`);

--
-- Constraints for table `employee`
--
ALTER TABLE `employee`
  ADD CONSTRAINT `employee_ibfk_1` FOREIGN KEY (`dep_id`) REFERENCES `department` (`dep_id`),
  ADD CONSTRAINT `employee_ibfk_2` FOREIGN KEY (`desg_id`) REFERENCES `designation` (`desg_id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_management`
--
ALTER TABLE `leave_management`
  ADD CONSTRAINT `leave_management_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`);

--
-- Constraints for table `login`
--
ALTER TABLE `login`
  ADD CONSTRAINT `fk_login_emp` FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`),
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `task_assigned` (`task_id`);

--
-- Constraints for table `report_images`
--
ALTER TABLE `report_images`
  ADD CONSTRAINT `report_images_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`) ON DELETE CASCADE;

--
-- Constraints for table `site_visit_tracking`
--
ALTER TABLE `site_visit_tracking`
  ADD CONSTRAINT `site_visit_tracking_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
