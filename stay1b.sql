-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 23, 2025 at 07:25 AM
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
  `created_at` datetime DEFAULT current_timestamp(),
  `expense_id` int(11) NOT NULL,
  `house_id` int(11) NOT NULL,
  `expense_type` varchar(255) NOT NULL,
  `expense_amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `expense_status` enum('pending','successful','failed') DEFAULT 'pending',
  `receipt_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `housemate_application`
--

CREATE TABLE `housemate_application` (
  `application_id` int(11) NOT NULL,
  `booked_house_id` int(11) NOT NULL,
  `housemate_id` int(11) NOT NULL,
  `application_status` enum('pending','approved','rejected') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(36, 44, 'leader'),
(37, 45, 'member'),
(38, 46, 'leader'),
(39, 47, 'member'),
(42, 50, 'member'),
(44, 52, 'member'),
(46, 54, 'member'),
(47, 55, 'member'),
(48, 56, 'member'),
(49, 57, 'member');

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
-- Triggers `house_booking`
--
DELIMITER $$
CREATE TRIGGER `update_availability_on_delete` AFTER DELETE ON `house_booking` FOR EACH ROW BEGIN
  UPDATE landlord_house
  SET availability_status = 'Available'
  WHERE house_id = OLD.house_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_availability_status_after_delete` AFTER DELETE ON `house_booking` FOR EACH ROW BEGIN
    
    UPDATE landlord_house
    SET availability_status = 'Available'
    WHERE house_id = OLD.house_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `house_group`
--

CREATE TABLE `house_group` (
  `house_group_id` int(11) NOT NULL,
  `booked_house_id` int(11) NOT NULL,
  `housemate_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `house_preferences`
--

CREATE TABLE `house_preferences` (
  `preference_id` int(11) NOT NULL,
  `gender_preference` enum('Any','Male','Female') NOT NULL,
  `study_year_preference` enum('Any','1st Year','2nd Year','3rd Year','4th Year') NOT NULL,
  `pet_policy_preference` enum('Pets Allowed','No Pets') NOT NULL,
  `booked_house_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `house_reviews`
--

CREATE TABLE `house_reviews` (
  `review_id` int(11) NOT NULL,
  `house_id` int(11) NOT NULL,
  `reviewed_by` int(11) NOT NULL,
  `review_content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rating` decimal(2,1) NOT NULL DEFAULT 0.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(7, 'Brian Ho', 'brian85', '$2y$10$y0KrQABdiGEZEGxolOa2OeyzkZP.lciL4Sug9qSW2o3GJONNaK9Yy', 'brian85@gmail.com', '601131718439', 'assets/profile-pictures/678bcbc83a9ca_brian.jpg');

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
  `deposit` decimal(10,2) NOT NULL,
  `availability_status` enum('Available','Sold Out') NOT NULL DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landlord_payment_details`
--

CREATE TABLE `landlord_payment_details` (
  `landlord_id` int(11) NOT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leader_payment_details`
--

CREATE TABLE `leader_payment_details` (
  `housemate_id` int(11) NOT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `maintenance_id` int(11) NOT NULL,
  `house_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `scheduled_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_expenses`
--

CREATE TABLE `member_expenses` (
  `member_expense_id` int(11) NOT NULL,
  `expense_id` int(11) NOT NULL,
  `housemate_id` int(11) NOT NULL,
  `divided_amount` decimal(10,2) NOT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `member_payment_status` enum('pending','successful','failed') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `notice_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0
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
  `tenancy_period` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `deposit` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(44, 'Nursyazawanie', 'syaza02', '$2y$10$CB08rWaLrv7Q5pDHm00X6ut9B1w07s3dWld9XpiydLV0WiJ.gE3RS', 'syaza02@gmail.com', '01126231977', 0x6173736574732f70726f66696c652d70696374757265732f363738626335666537346433315f7379617a612e6a7067),
(45, 'Norliza', 'liza53', '$2y$10$sm7spBL/Yjx1khF408C0bene0oiuklpKk8UAGC/1bcc7Agtkd9Hf2', 'liza53@gmail.com', '0179516375', 0x6173736574732f70726f66696c652d70696374757265732f363739316139663232636632325f706963732e706e67),
(46, 'Ariana', 'ana99', '$2y$10$w31hhntUcBFxkTqh1zBJleI5rXb8/leeeLJHEsckE4/QgauW/m9US', 'aina99@gmail.com', '0189754236', 0x6173736574732f70726f66696c652d70696374757265732f363739316331663936663832365f417269616e612e6a7067),
(47, 'Alveonna ', 'alvy05', '$2y$10$zlqGcYcCfoOXjrn/JpUQCutmwFSo9KdCLUkWU2O3RH9veWJA6PFZG', 'alvy05@gmail.com', '0164127316', 0x6173736574732f70726f66696c652d70696374757265732f363739316331643862663165375f706963732e706e67),
(50, 'Noralyaa', 'alya15', '$2y$10$qC21Mp/RPJjxQqnEeXQZOuBezTsuX7f4b5BNdyBmD7drLzkpI7Jfa', 'alya15@gmail.com', '0178901234', 0x6173736574732f70726f66696c652d70696374757265732f64656661756c742d70726f66696c652e706e67),
(52, 'Nur Izzah', 'izzah03', '$2y$10$MrRFTZGpJkhV8BZxHvgbx.J3eHRNRQzj3HMZ/7/.v673.1qWwIQ9O', 'izzah03@gmail.com', '0101234567', 0x6173736574732f70726f66696c652d70696374757265732f64656661756c742d70726f66696c652e706e67),
(54, 'Nur Fatin', 'fatin87', '$2y$10$9Wz/ttmmRsOvL96MP40MiucLwDA1qth4tw02N/nHt5Wq3S5efzi0q', 'fatin87@gmail.com', '0198763456', 0x6173736574732f70726f66696c652d70696374757265732f363739306563393634366136385f666174696e2e6a7067),
(55, 'Alfinah', 'finah33', '$2y$10$9OJZuJjb7MbdVOdkh.6qWupJi5bT6OqeQ.t/EWuoQbJg8JH8umdh.', 'finah33@gmail.com', '0179628367', 0x6173736574732f70726f66696c652d70696374757265732f64656661756c742d70726f66696c652e706e67),
(56, 'Nur Atirah', 'tirah04', '$2y$10$oxGuZBjJwnwR7gykhu4tDuG36EmQpqo/s6x4X4uM9oqIB87MRUQpO', 'tirah04@gmail.com', '0198276358', 0x6173736574732f70726f66696c652d70696374757265732f64656661756c742d70726f66696c652e706e67),
(57, 'Nur Ameerah ', 'mai03', '$2y$10$ARupVASlc2n1GkAcIUdOm.eMUZ4EGf2FHO1Wumq1TZNDkjfED/T8e', 'mai03@gmail.com', '01126231977', 0x6173736574732f70726f66696c652d70696374757265732f363739316362393461383730315f6c697a612e6a7067);

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
-- Indexes for table `housemate_application`
--
ALTER TABLE `housemate_application`
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
  ADD KEY `fk_booked_house_id` (`booked_house_id`);

--
-- Indexes for table `house_reviews`
--
ALTER TABLE `house_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `house_id` (`house_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

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
-- Indexes for table `landlord_payment_details`
--
ALTER TABLE `landlord_payment_details`
  ADD PRIMARY KEY (`landlord_id`);

--
-- Indexes for table `leader_payment_details`
--
ALTER TABLE `leader_payment_details`
  ADD PRIMARY KEY (`housemate_id`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `house_id` (`house_id`);

--
-- Indexes for table `member_expenses`
--
ALTER TABLE `member_expenses`
  ADD PRIMARY KEY (`member_expense_id`),
  ADD KEY `expense_id` (`expense_id`),
  ADD KEY `housemate_id` (`housemate_id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`notice_id`),
  ADD KEY `fk_admin_id` (`admin_id`);

--
-- Indexes for table `tenancy_agreements`
--
ALTER TABLE `tenancy_agreements`
  ADD PRIMARY KEY (`agreement_id`),
  ADD KEY `booked_house_id` (`booked_house_id`),
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
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `housemate_application`
--
ALTER TABLE `housemate_application`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `housemate_role`
--
ALTER TABLE `housemate_role`
  MODIFY `housemate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `house_booking`
--
ALTER TABLE `house_booking`
  MODIFY `booked_house_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `house_group`
--
ALTER TABLE `house_group`
  MODIFY `house_group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `house_preferences`
--
ALTER TABLE `house_preferences`
  MODIFY `preference_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `house_reviews`
--
ALTER TABLE `house_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `landlord`
--
ALTER TABLE `landlord`
  MODIFY `landlord_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `landlord_house`
--
ALTER TABLE `landlord_house`
  MODIFY `house_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `member_expenses`
--
ALTER TABLE `member_expenses`
  MODIFY `member_expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `notice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tenancy_agreements`
--
ALTER TABLE `tenancy_agreements`
  MODIFY `agreement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tenant`
--
ALTER TABLE `tenant`
  MODIFY `tenant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `expense`
--
ALTER TABLE `expense`
  ADD CONSTRAINT `expense_ibfk_1` FOREIGN KEY (`house_id`) REFERENCES `landlord_house` (`house_id`) ON DELETE CASCADE;

--
-- Constraints for table `housemate_application`
--
ALTER TABLE `housemate_application`
  ADD CONSTRAINT `housemate_application_ibfk_1` FOREIGN KEY (`booked_house_id`) REFERENCES `house_booking` (`booked_house_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `housemate_application_ibfk_2` FOREIGN KEY (`housemate_id`) REFERENCES `housemate_role` (`housemate_id`) ON DELETE CASCADE;

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
-- Constraints for table `house_preferences`
--
ALTER TABLE `house_preferences`
  ADD CONSTRAINT `fk_booked_house_id` FOREIGN KEY (`booked_house_id`) REFERENCES `house_booking` (`booked_house_id`) ON DELETE CASCADE;

--
-- Constraints for table `house_reviews`
--
ALTER TABLE `house_reviews`
  ADD CONSTRAINT `house_reviews_ibfk_1` FOREIGN KEY (`house_id`) REFERENCES `landlord_house` (`house_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `house_reviews_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `housemate_role` (`housemate_id`) ON DELETE CASCADE;

--
-- Constraints for table `landlord_house`
--
ALTER TABLE `landlord_house`
  ADD CONSTRAINT `landlord_house_ibfk_1` FOREIGN KEY (`landlord_id`) REFERENCES `landlord` (`landlord_id`) ON DELETE CASCADE;

--
-- Constraints for table `landlord_payment_details`
--
ALTER TABLE `landlord_payment_details`
  ADD CONSTRAINT `landlord_payment_details_ibfk_1` FOREIGN KEY (`landlord_id`) REFERENCES `landlord` (`landlord_id`) ON DELETE CASCADE;

--
-- Constraints for table `leader_payment_details`
--
ALTER TABLE `leader_payment_details`
  ADD CONSTRAINT `leader_payment_details_ibfk_1` FOREIGN KEY (`housemate_id`) REFERENCES `housemate_role` (`housemate_id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`house_id`) REFERENCES `landlord_house` (`house_id`) ON DELETE CASCADE;

--
-- Constraints for table `member_expenses`
--
ALTER TABLE `member_expenses`
  ADD CONSTRAINT `member_expenses_ibfk_2` FOREIGN KEY (`housemate_id`) REFERENCES `housemate_role` (`housemate_id`);

--
-- Constraints for table `notices`
--
ALTER TABLE `notices`
  ADD CONSTRAINT `fk_admin_id` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
