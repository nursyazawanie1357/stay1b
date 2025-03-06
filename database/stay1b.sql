-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 06, 2025 at 11:49 PM
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
-- Database: `stay1b`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `admin_username` varchar(255) NOT NULL,
  `admin_password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `admin_username`, `admin_password`) VALUES
(1, 'admin1', 'Admin@111');

-- --------------------------------------------------------

--
-- Table structure for table `expense`
--

CREATE TABLE `expense` (
  `expense_id` int(11) NOT NULL,
  `house_id` int(11) NOT NULL,
  `expense_type` varchar(255) NOT NULL,
  `expense_amount` decimal(10,2) NOT NULL,
  `expense_status` enum('pending','successful','failed') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense`
--

INSERT INTO `expense` (`expense_id`, `house_id`, `expense_type`, `expense_amount`, `expense_status`) VALUES
(5, 23, 'Water Bill', 50.00, 'pending'),
(6, 27, 'Water Bill', 50.00, 'successful');

-- --------------------------------------------------------

--
-- Table structure for table `housemate_finder`
--

CREATE TABLE `housemate_finder` (
  `application_id` int(11) NOT NULL,
  `booked_house_id` int(11) NOT NULL,
  `housemate_id` int(11) NOT NULL,
  `application_status` enum('pending','approved','rejected') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `housemate_finder`
--

INSERT INTO `housemate_finder` (`application_id`, `booked_house_id`, `housemate_id`, `application_status`) VALUES
(13, 22, 6, 'approved'),
(14, 22, 14, 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `housemate_role`
--

CREATE TABLE `housemate_role` (
  `housemate_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `house_role` enum('leader','member') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `housemate_role`
--

INSERT INTO `housemate_role` (`housemate_id`, `tenant_id`, `house_role`) VALUES
(3, 3, 'leader'),
(6, 6, 'member'),
(9, 17, 'member'),
(10, 18, 'member'),
(14, 22, 'member');

-- --------------------------------------------------------

--
-- Table structure for table `house_booking`
--

CREATE TABLE `house_booking` (
  `booked_house_id` int(11) NOT NULL,
  `house_id` int(11) NOT NULL,
  `housemate_id` int(11) NOT NULL,
  `booking_status` enum('pending','approved','rejected') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `house_booking`
--

INSERT INTO `house_booking` (`booked_house_id`, `house_id`, `housemate_id`, `booking_status`, `created_at`) VALUES
(22, 26, 3, 'approved', '2025-01-06 19:26:10');

-- --------------------------------------------------------

--
-- Table structure for table `house_group`
--

CREATE TABLE `house_group` (
  `house_group_id` int(11) NOT NULL,
  `booked_house_id` int(11) NOT NULL,
  `housemate_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `house_group`
--

INSERT INTO `house_group` (`house_group_id`, `booked_house_id`, `housemate_id`) VALUES
(9, 22, 3),
(10, 22, 6),
(11, 22, 14);

-- --------------------------------------------------------

--
-- Table structure for table `house_preferences`
--

CREATE TABLE `house_preferences` (
  `preference_id` int(11) NOT NULL,
  `house_group_id` int(11) NOT NULL,
  `gender_preference` enum('Any','Male','Female') NOT NULL,
  `study_year_preference` enum('Any','1st Year','2nd Year','3rd Year','4th Year') NOT NULL,
  `pet_policy_preference` enum('Pets Allowed','No Pets') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `house_preferences`
--

INSERT INTO `house_preferences` (`preference_id`, `house_group_id`, `gender_preference`, `study_year_preference`, `pet_policy_preference`) VALUES
(8, 9, 'Female', '3rd Year', 'No Pets');

-- --------------------------------------------------------

--
-- Table structure for table `landlord`
--

CREATE TABLE `landlord` (
  `landlord_id` int(11) NOT NULL,
  `landlord_full_name` varchar(255) NOT NULL,
  `landlord_username` varchar(255) NOT NULL,
  `landlord_password` varchar(255) NOT NULL,
  `landlord_email` varchar(255) NOT NULL,
  `landlord_whatsapp` varchar(15) DEFAULT NULL,
  `landlord_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landlord`
--

INSERT INTO `landlord` (`landlord_id`, `landlord_full_name`, `landlord_username`, `landlord_password`, `landlord_email`, `landlord_whatsapp`, `landlord_picture`) VALUES
(4, 'Brian Ho', 'brian1', '$2y$10$d2RJa6dlXVgq6WTxOFxNT.D2jhpYHsGdvtjs9fPjengzgN34LM9TC', 'brian1@gmail.com', '01126231977', 'assets/profile-pictures/674410a280b0e_brian.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `landlord_house`
--

CREATE TABLE `landlord_house` (
  `house_id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `house_number` varchar(100) NOT NULL,
  `max_occupancy` int(11) NOT NULL,
  `furnishing_condition` enum('Fully Furnished','Partially Furnished','Not Furnished') NOT NULL,
  `wifi_status` enum('Available','Not Available') NOT NULL DEFAULT 'Not Available',
  `house_picture` varchar(255) DEFAULT NULL,
  `viewing_date` date DEFAULT NULL,
  `monthly_rental` decimal(10,2) NOT NULL,
  `deposit` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landlord_house`
--

INSERT INTO `landlord_house` (`house_id`, `landlord_id`, `house_number`, `max_occupancy`, `furnishing_condition`, `wifi_status`, `house_picture`, `viewing_date`, `monthly_rental`, `deposit`) VALUES
(23, 4, 'A-09-13', 7, 'Fully Furnished', 'Not Available', 'assets/house-pictures/6744075828acd_HOUSE12.jpg', '2024-11-30', 1800.00, 3600.00),
(26, 4, 'A-25-06', 7, 'Fully Furnished', 'Not Available', 'assets/house-pictures/67440f956e7b8_HOUSE11.jpg', '2024-11-28', 1700.00, 3400.00),
(27, 4, 'A-16-03', 6, 'Partially Furnished', 'Available', 'assets/house-pictures/677ba11b0a90f_house2.jpg', '2024-11-29', 1500.00, 3000.00),
(28, 4, 'A-02-05', 6, 'Fully Furnished', 'Not Available', 'assets/house-pictures/677bdbc91ad31_house4.jpg', '2025-01-23', 1700.00, 3400.00);

-- --------------------------------------------------------

--
-- Table structure for table `leader_payment`
--

CREATE TABLE `leader_payment` (
  `payment_id` int(11) NOT NULL,
  `house_group_id` int(11) NOT NULL,
  `expense_type` enum('maintenance','utility','other') NOT NULL,
  `expense_amount` decimal(10,2) NOT NULL,
  `expense_receipt` blob DEFAULT NULL,
  `payment_status` enum('pending','completed') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tenancy_agreements`
--

CREATE TABLE `tenancy_agreements` (
  `agreement_id` int(11) NOT NULL,
  `booked_house_id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `tenancy_period` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `deposit` decimal(10,2) NOT NULL,
  `term_description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenancy_agreements`
--

INSERT INTO `tenancy_agreements` (`agreement_id`, `booked_house_id`, `landlord_id`, `tenant_id`, `tenancy_period`, `start_date`, `monthly_rent`, `deposit`, `term_description`, `updated_at`) VALUES
(20, 22, 4, 3, '1 year', '2025-01-18', 1700.00, 3400.00, '', '2025-01-06 19:27:49');

-- --------------------------------------------------------

--
-- Table structure for table `tenant`
--

CREATE TABLE `tenant` (
  `tenant_id` int(11) NOT NULL,
  `tenant_full_name` varchar(255) NOT NULL,
  `tenant_username` varchar(255) NOT NULL,
  `tenant_password` varchar(255) NOT NULL,
  `tenant_email` varchar(255) NOT NULL,
  `tenant_whatsapp` varchar(15) DEFAULT NULL,
  `tenant_picture` blob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenant`
--

INSERT INTO `tenant` (`tenant_id`, `tenant_full_name`, `tenant_username`, `tenant_password`, `tenant_email`, `tenant_whatsapp`, `tenant_picture`) VALUES
(3, 'Nursyazawanie', 'syaza3', '$2y$10$QSgUyApGXfHNj4VIFU8areYXlcjz5VD2A0uzBoYIwYJCEMYPt5hju', 'syaza3@gmail.com', '01126231977', 0x6173736574732f70726f66696c652d70696374757265732f363737623038333633626538375f706963312e6a7067),
(6, 'Ariana', 'ariana02', '$2y$10$AuTQfD9ZwQaJaJao89tje.buKZNu0rC7IWgl5iE0xJnxgfBLiYlA2', 'ariana@gmail.com', '0123456789', 0x6173736574732f70726f66696c652d70696374757265732f363737633331393731346266655f706963332e6a666966),
(17, 'Nell', '', '', 'nell@gmail.com', '0198654372', NULL),
(18, 'Zen', '', '', 'zen@gmail.com', '018653874', NULL),
(22, 'Tasha', 'tasha23', '$2y$10$Gf5S3kxCT9llGgel1lY5Eeh9eYE96wiK8ir0OAE4AT9XeTCWOeF4y', 'tasha23@gmail.com', '0198653725', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `expense`
--
ALTER TABLE `expense`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `house_id` (`house_id`);

--
-- Indexes for table `housemate_finder`
--
ALTER TABLE `housemate_finder`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `booked_house_id` (`booked_house_id`),
  ADD KEY `housemate_id` (`housemate_id`);

--
-- Indexes for table `housemate_role`
--
ALTER TABLE `housemate_role`
  ADD PRIMARY KEY (`housemate_id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `house_booking`
--
ALTER TABLE `house_booking`
  ADD PRIMARY KEY (`booked_house_id`),
  ADD KEY `house_id` (`house_id`),
  ADD KEY `housemate_id` (`housemate_id`);

--
-- Indexes for table `house_group`
--
ALTER TABLE `house_group`
  ADD PRIMARY KEY (`house_group_id`),
  ADD KEY `booked_house_id` (`booked_house_id`),
  ADD KEY `housemate_id` (`housemate_id`);

--
-- Indexes for table `house_preferences`
--
ALTER TABLE `house_preferences`
  ADD PRIMARY KEY (`preference_id`),
  ADD KEY `house_group_id` (`house_group_id`);

--
-- Indexes for table `landlord`
--
ALTER TABLE `landlord`
  ADD PRIMARY KEY (`landlord_id`);

--
-- Indexes for table `landlord_house`
--
ALTER TABLE `landlord_house`
  ADD PRIMARY KEY (`house_id`),
  ADD KEY `landlord_id` (`landlord_id`);

--
-- Indexes for table `leader_payment`
--
ALTER TABLE `leader_payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `house_group_id` (`house_group_id`);

--
-- Indexes for table `tenancy_agreements`
--
ALTER TABLE `tenancy_agreements`
  ADD PRIMARY KEY (`agreement_id`),
  ADD UNIQUE KEY `unique_booked_house` (`booked_house_id`),
  ADD KEY `landlord_id` (`landlord_id`),
  ADD KEY `tenant_id` (`tenant_id`);

--
-- Indexes for table `tenant`
--
ALTER TABLE `tenant`
  ADD PRIMARY KEY (`tenant_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expense`
--
ALTER TABLE `expense`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `housemate_finder`
--
ALTER TABLE `housemate_finder`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `housemate_role`
--
ALTER TABLE `housemate_role`
  MODIFY `housemate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `house_booking`
--
ALTER TABLE `house_booking`
  MODIFY `booked_house_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `house_group`
--
ALTER TABLE `house_group`
  MODIFY `house_group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `house_preferences`
--
ALTER TABLE `house_preferences`
  MODIFY `preference_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `landlord`
--
ALTER TABLE `landlord`
  MODIFY `landlord_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `landlord_house`
--
ALTER TABLE `landlord_house`
  MODIFY `house_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `leader_payment`
--
ALTER TABLE `leader_payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tenancy_agreements`
--
ALTER TABLE `tenancy_agreements`
  MODIFY `agreement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `tenant`
--
ALTER TABLE `tenant`
  MODIFY `tenant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `expense`
--
ALTER TABLE `expense`
  ADD CONSTRAINT `expense_ibfk_1` FOREIGN KEY (`house_id`) REFERENCES `landlord_house` (`house_id`) ON DELETE CASCADE;

--
-- Constraints for table `housemate_finder`
--
ALTER TABLE `housemate_finder`
  ADD CONSTRAINT `housemate_finder_ibfk_1` FOREIGN KEY (`booked_house_id`) REFERENCES `house_booking` (`booked_house_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `housemate_finder_ibfk_2` FOREIGN KEY (`housemate_id`) REFERENCES `housemate_role` (`housemate_id`) ON DELETE CASCADE;

--
-- Constraints for table `housemate_role`
--
ALTER TABLE `housemate_role`
  ADD CONSTRAINT `housemate_role_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenant` (`tenant_id`) ON DELETE CASCADE;

--
-- Constraints for table `house_booking`
--
ALTER TABLE `house_booking`
  ADD CONSTRAINT `house_booking_ibfk_1` FOREIGN KEY (`house_id`) REFERENCES `landlord_house` (`house_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `house_booking_ibfk_2` FOREIGN KEY (`housemate_id`) REFERENCES `housemate_role` (`housemate_id`) ON DELETE CASCADE;

--
-- Constraints for table `house_group`
--
ALTER TABLE `house_group`
  ADD CONSTRAINT `house_group_ibfk_1` FOREIGN KEY (`booked_house_id`) REFERENCES `house_booking` (`booked_house_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `house_group_ibfk_2` FOREIGN KEY (`housemate_id`) REFERENCES `housemate_role` (`housemate_id`) ON DELETE CASCADE;

--
-- Constraints for table `landlord_house`
--
ALTER TABLE `landlord_house`
  ADD CONSTRAINT `landlord_house_ibfk_1` FOREIGN KEY (`landlord_id`) REFERENCES `landlord` (`landlord_id`) ON DELETE CASCADE;

--
-- Constraints for table `leader_payment`
--
ALTER TABLE `leader_payment`
  ADD CONSTRAINT `leader_payment_ibfk_1` FOREIGN KEY (`house_group_id`) REFERENCES `house_group` (`house_group_id`) ON DELETE CASCADE;

--
-- Constraints for table `tenancy_agreements`
--
ALTER TABLE `tenancy_agreements`
  ADD CONSTRAINT `tenancy_agreements_ibfk_1` FOREIGN KEY (`booked_house_id`) REFERENCES `house_booking` (`booked_house_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenancy_agreements_ibfk_2` FOREIGN KEY (`landlord_id`) REFERENCES `landlord` (`landlord_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenancy_agreements_ibfk_3` FOREIGN KEY (`tenant_id`) REFERENCES `tenant` (`tenant_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
