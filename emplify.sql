-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 10, 2025 at 04:05 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
(1, 2, '2025-04-30', '17:14:43', NULL, 'Present'),
(2, 2, '2025-05-03', '06:07:06', NULL, 'Present'),
(3, 4, '2025-05-04', '04:56:13', NULL, 'Present'),
(4, 2, '2025-05-10', '07:20:03', NULL, 'Present'),
(5, 6, '0000-00-00', '09:28:58', '10:12:58', 'Present'),
(6, 6, '0000-00-00', '09:32:26', '09:33:49', 'Present'),
(7, 6, '0000-00-00', '09:34:41', '09:34:43', 'Present'),
(8, 6, '0000-00-00', '09:46:36', '10:12:11', 'Present'),
(9, 6, '0000-00-00', '09:47:28', '09:47:30', 'Present'),
(10, 6, '0000-00-00', '10:18:04', '10:21:04', 'Present'),
(11, 6, '0000-00-00', '10:19:33', '10:20:39', 'Present'),
(12, 6, '0000-00-00', '10:21:06', '10:21:10', 'Present'),
(13, 6, '0000-00-00', '10:23:14', '10:23:16', 'Present'),
(14, 6, '0000-00-00', '10:32:36', '20:53:58', 'Present'),
(15, 6, '0000-00-00', '10:33:01', '07:17:04', 'Present'),
(16, 6, '0000-00-00', '10:43:25', '07:16:47', 'Present'),
(17, 6, '0000-00-00', '14:02:31', '14:02:33', 'Present'),
(18, 6, '2025-06-03', '17:37:26', '17:37:34', 'Present'),
(19, 6, '2025-06-04', '07:16:22', '21:37:53', 'Present'),
(20, 6, '2025-06-04', '07:16:30', '21:31:58', 'Present'),
(21, 6, '2025-06-04', '08:18:06', '21:31:31', 'Present'),
(22, 6, '2025-06-04', '08:18:50', '20:54:02', 'Present'),
(23, 6, '2025-06-09', '21:38:00', '21:38:08', 'Present'),
(24, 6, '2025-06-09', '21:38:13', '21:38:19', 'Present'),
(25, 6, '2025-06-09', '21:38:47', '21:38:54', 'Present'),
(26, 7, '2025-06-09', '21:39:58', '21:40:01', 'Present'),
(27, 6, '2025-06-09', '21:42:41', '21:42:43', 'Present'),
(28, 7, '2025-06-09', '21:47:26', NULL, 'Present'),
(29, 6, '2025-06-10', '10:58:19', '11:01:24', 'Present'),
(30, 6, '2025-06-10', '19:21:49', '19:22:11', 'Present');

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
  `desg_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`emp_id`, `name`, `email`, `phone`, `gender`, `dob`, `address`, `dep_id`, `desg_id`) VALUES
(2, 'Rana Garai', 'ranagarai820@gmail.com', '9883169583', 'Male', '0000-00-00', 'Melatala,po: Shyam Nabagram;ps-Monteswar', 4, 5),
(3, 'Pratap Garai', 'pratap@123gmail.com', '6235452036', 'Male', '2005-04-18', 'Melatala,po: Shyam Nabagram;ps-Monteswar', 4, 5),
(4, 'Md Shakil', 'shakil6294@gmail.com', '06294030194', 'Male', '2005-03-28', 'Ghatshila', 1, 1),
(5, 'Bikas Sen', 'bikas1234@gmail.com', '5698754525', 'Male', '0000-00-00', 'xyz', 2, 1),
(6, 'Arka Dutta', 'arkadutta333@gmail.com', '12345678900', 'Male', '2003-05-18', 'Bankura More', 9, 2),
(7, 'Soumyadip Hazra', 'soumyadip@123gmail.com', '123456789', 'Male', '2003-02-06', 'xyz', 9, 2);

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
(5, 6, 'Casual', '2025-06-11', '2025-06-12', 'casul', 'Approved');

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
(4, 6, 'project1 - Report', '                            Task Description: ddddddddddddddddddddddddddaaaaaaaaaaaaa                        ', 0, '2025-05-31 11:57:12', 25),
(5, 6, 'project2 - Report', '                           \\r\\n                        kkkkkkkkkkkkkkkk', 0, '2025-05-31 12:18:19', 26);

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
(4, 4, 'uploads/reports/683aee980f714_Untitled1.png', '2025-05-31 11:57:12'),
(5, 5, 'uploads/reports/683af38b51f0c_arya.png', '2025-05-31 12:18:19');

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
(25, 6, 'project1', 'dddddddddgSDgSdg', '2025-05-31', '2025-05-31', 'Completed', NULL, NULL, '2025-06-04 11:49:21', 1, 'katwa', 'good'),
(26, 6, 'project2', 'gfhshsfh', '2025-05-31', '2025-05-31', 'Completed', NULL, NULL, '2025-05-31 05:17:46', 1, 'burdwan', 'fsegf');

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
  MODIFY `att_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

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
  MODIFY `emp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `leave_management`
--
ALTER TABLE `leave_management`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `login_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `report_images`
--
ALTER TABLE `report_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `site_visit_tracking`
--
ALTER TABLE `site_visit_tracking`
  MODIFY `visit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_assigned`
--
ALTER TABLE `task_assigned`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

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
