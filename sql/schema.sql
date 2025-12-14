-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mysql:3306
-- Generation Time: Dec 14, 2025 at 01:34 PM
-- Server version: 8.0.44
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `knaa_membership`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `log_id` int NOT NULL,
  `admin_id` int DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL COMMENT 'updated_member, deleted_event, etc',
  `target_table` varchar(50) DEFAULT NULL,
  `target_id` int DEFAULT NULL,
  `old_value` text,
  `new_value` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT (now())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `admin_id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL COMMENT 'super_admin, admin, moderator',
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT (now())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `log_id` int NOT NULL,
  `member_id` varchar(20) DEFAULT NULL,
  `email_address` varchar(150) NOT NULL,
  `email_type` varchar(50) NOT NULL COMMENT 'welcome, expiry_reminder, event_confirmation, payment_receipt, etc',
  `email_subject` varchar(200) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL COMMENT 'sent, failed, pending',
  `error_message` text COMMENT 'If failed, reason for failure',
  `sent_date` timestamp NULL DEFAULT (now()),
  `event_id` int DEFAULT NULL COMMENT 'If email relates to an event',
  `payment_id` int DEFAULT NULL COMMENT 'If email relates to a payment'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`log_id`, `member_id`, `email_address`, `email_type`, `email_subject`, `status`, `error_message`, `sent_date`, `event_id`, `payment_id`) VALUES
(1, NULL, 'test@example.com', 'test', 'Test Email - KNAA', 'failed', 'SMTP Error: Could not authenticate. SMTP server error: QUIT command failed', '2025-12-14 10:05:14', NULL, NULL),
(2, NULL, 'test@example.com', 'test', 'Test Email - KNAA', 'failed', 'SMTP Error: Could not authenticate. SMTP server error: QUIT command failed', '2025-12-14 10:06:45', NULL, NULL),
(3, NULL, 'test@example.com', 'test', 'Test Email - KNAA', 'failed', 'SMTP Error: Could not authenticate. SMTP server error: QUIT command failed', '2025-12-14 10:18:21', NULL, NULL),
(4, NULL, 'test@example.com', 'test', 'Test Email - KNAA', 'failed', 'SMTP Error: Could not authenticate.', '2025-12-14 10:26:08', NULL, NULL),
(5, NULL, 'test@example.com', 'test', 'Test Email - KNAA', 'sent', 'Email disabled - logged only', '2025-12-14 10:29:04', NULL, NULL),
(6, NULL, 'test@example.com', 'test', 'Test Email - KNAA', 'sent', 'Email disabled - logged only', '2025-12-14 10:37:56', NULL, NULL),
(7, 'KNAA-2025-00003', 'test@email.com', 'event_confirmation', 'Registration Confirmed: KNAA Community Health Fair', 'sent', 'Email disabled - logged only', '2025-12-14 11:19:30', 23, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int NOT NULL,
  `event_title` varchar(200) NOT NULL,
  `event_description` text,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `street_address` varchar(200) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(2) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `venue_name` varchar(150) DEFAULT NULL,
  `early_bird_fee` decimal(10,2) DEFAULT NULL,
  `standard_fee` decimal(10,2) DEFAULT NULL,
  `member_discount_fee` decimal(10,2) DEFAULT NULL,
  `early_bird_deadline` date DEFAULT NULL,
  `max_attendees` int DEFAULT NULL,
  `current_attendees` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT (now()),
  `updated_at` timestamp NULL DEFAULT (now())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_title`, `event_description`, `event_date`, `event_time`, `street_address`, `city`, `state`, `zip_code`, `venue_name`, `early_bird_fee`, `standard_fee`, `member_discount_fee`, `early_bird_deadline`, `max_attendees`, `current_attendees`, `is_active`, `created_at`, `updated_at`) VALUES
(14, 'End Year KNAA Gala', 'Join us for an elegant evening as we celebrate the achievements of 2025 and connect with fellow KNAA members. This gala will feature dinner, entertainment, awards recognition, and networking opportunities.', '2025-12-06', '19:00:00', '800 Rahway Ave', 'Woodbridge', 'NJ', '07095', 'The Grand Sapphire', NULL, 150.00, 130.00, NULL, 200, 0, 1, '2025-12-11 05:59:59', '2025-12-11 05:59:59'),
(15, '2025 KNAA Conference', 'Conference focus: Continuing Education Units (CEUs), Networking Opportunities, Men\'s Mental Health Roundtable, Bipolar Disorder & Depression, Schizophrenia, Hormonal Imbalance, New HIV Treatments, Impact of HIV on Black Communities, Oral Health & Cardiovascular Implications, Weight Management & GLP-1 Therapies, Nurse Entrepreneurship, Relationship Health & Stress, Suicide Prevention, Patient Data Privacy & Confidentiality.', '2025-09-18', '08:00:00', '4099 Valley View Ln', 'Dallas', 'TX', '75244', 'Double Tree by Hilton', 300.00, 450.00, 350.00, '2025-08-18', 500, 0, 1, '2025-12-11 05:59:59', '2025-12-11 05:59:59'),
(16, '2025 KNAA Conference - Day 2', 'Day 2 of the annual conference continuing with CEUs, workshops, and networking sessions.', '2025-09-19', '08:00:00', '4099 Valley View Ln', 'Dallas', 'TX', '75244', 'Double Tree by Hilton', NULL, 450.00, 350.00, NULL, 500, 0, 1, '2025-12-11 05:59:59', '2025-12-11 05:59:59'),
(17, '2025 KNAA Conference - Day 3', 'Final day of the conference with closing sessions and awards ceremony.', '2025-09-20', '08:00:00', '4099 Valley View Ln', 'Dallas', 'TX', '75244', 'Double Tree by Hilton', NULL, 450.00, 350.00, NULL, 500, 0, 1, '2025-12-11 05:59:59', '2025-12-11 05:59:59'),
(22, 'Professional Development Workshop: Advanced Patient Care', 'This comprehensive workshop focuses on advanced patient care techniques and the latest evidence-based practices in nursing. Topics include critical thinking in clinical settings, effective communication with interdisciplinary teams, and innovative approaches to patient advocacy. CEU credits available. All attendees will receive a certificate of completion and workshop materials.', '2026-01-18', '09:00:00', '5678 Medical Plaza', 'Houston', 'TX', '77002', 'KNAA Training Center', 25.00, 40.00, 20.00, '2026-01-10', 75, 0, 1, '2025-12-11 06:32:52', '2025-12-11 06:32:52'),
(23, 'KNAA Community Health Fair', 'Free community health screening event organized by KNAA members. We will provide blood pressure checks, glucose screening, health education, and wellness consultations. Volunteer nurses are needed to help serve the community. This is a great opportunity to give back and make a meaningful impact. Lunch will be provided for all volunteers.', '2026-01-12', '10:00:00', '910 Community Way', 'Miami', 'FL', '33101', 'Community Center Hall', 0.00, 0.00, 0.00, NULL, 200, 2, 1, '2025-12-11 06:32:52', '2025-12-11 06:32:52');

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `registration_id` int NOT NULL,
  `event_id` int DEFAULT NULL,
  `member_id` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `street_address` varchar(200) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(2) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `registration_fee` decimal(10,2) NOT NULL,
  `payment_status` varchar(20) DEFAULT NULL COMMENT 'pending, completed, refunded',
  `payment_method` varchar(50) DEFAULT NULL COMMENT 'credit_card, ach, stripe_id, etc',
  `payment_date` timestamp NULL DEFAULT NULL,
  `confirmation_email_sent` tinyint(1) DEFAULT '0',
  `confirmation_sent_date` timestamp NULL DEFAULT NULL,
  `registration_date` timestamp NULL DEFAULT (now())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `event_registrations`
--

INSERT INTO `event_registrations` (`registration_id`, `event_id`, `member_id`, `first_name`, `last_name`, `email`, `phone`, `street_address`, `city`, `state`, `zip_code`, `registration_fee`, `payment_status`, `payment_method`, `payment_date`, `confirmation_email_sent`, `confirmation_sent_date`, `registration_date`) VALUES
(1, 23, NULL, 'tes', 't', 'test@email.com', '(555) 123-4567', NULL, NULL, NULL, NULL, 0.00, 'pending', 'zelle', '2025-12-14 08:37:34', 0, NULL, '2025-12-14 08:37:14'),
(2, 23, 'KNAA-2025-00003', 'tes', 't', 'test@email.com', '(555) 123-4567', '997 Church Road Summerville', 'South Carolina', 'SC', '29483', 0.00, 'pending', 'zelle', '2025-12-14 11:19:30', 0, NULL, '2025-12-14 11:19:26');

-- --------------------------------------------------------

--
-- Table structure for table `full_membership_details`
--

CREATE TABLE `full_membership_details` (
  `detail_id` int NOT NULL,
  `member_id` varchar(20) DEFAULT NULL,
  `highest_education` varchar(100) DEFAULT NULL,
  `license_type` varchar(50) DEFAULT NULL COMMENT 'LPN/LVN, RN, Nurse Practitioner',
  `licensure_state` varchar(2) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT (now()),
  `updated_at` timestamp NULL DEFAULT (now())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `full_membership_details`
--

INSERT INTO `full_membership_details` (`detail_id`, `member_id`, `highest_education`, `license_type`, `licensure_state`, `license_number`, `created_at`, `updated_at`) VALUES
(3, 'KNAA-2025-00003', 'Bachelor&#039;s Degree', 'LVN', 'IN', NULL, '2025-12-11 05:04:30', '2025-12-11 05:04:30');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `member_id` varchar(20) NOT NULL,
  `membership_type_id` int DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `street_address` varchar(200) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(2) DEFAULT NULL COMMENT 'Two-letter state code',
  `zip_code` varchar(10) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Hashed password',
  `member_since` date NOT NULL,
  `membership_expiration_date` date DEFAULT NULL,
  `email_notifications_enabled` tinyint(1) DEFAULT '1',
  `reminder_days_before_expiry` int DEFAULT '30' COMMENT 'Days before expiry to send reminder',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT (now()),
  `updated_at` timestamp NULL DEFAULT (now())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`member_id`, `membership_type_id`, `first_name`, `last_name`, `email`, `phone`, `street_address`, `city`, `state`, `zip_code`, `password_hash`, `member_since`, `membership_expiration_date`, `email_notifications_enabled`, `reminder_days_before_expiry`, `is_active`, `created_at`, `updated_at`) VALUES
('KNAA-2025-00003', 1, 'tes', 't', 'test@email.com', '(555) 123-4567', '997 Church Road Summerville', 'South Carolina', 'SC', '29483', '$2y$10$03tIceHsWypQySaxLmaS4uaqdVeIryu.UNFsbzyOfPXW3VRhBAzrK', '2025-12-11', '2026-12-11', 1, 30, 1, '2025-12-11 05:04:30', '2025-12-11 05:13:54');

-- --------------------------------------------------------

--
-- Table structure for table `membership_payments`
--

CREATE TABLE `membership_payments` (
  `payment_id` int NOT NULL,
  `member_id` varchar(20) DEFAULT NULL,
  `membership_type_id` int DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_type` varchar(50) DEFAULT NULL COMMENT 'new, renewal, upgrade',
  `payment_status` varchar(20) DEFAULT NULL COMMENT 'pending, completed, failed, refunded',
  `payment_method` varchar(50) DEFAULT NULL COMMENT 'credit_card, ach',
  `payment_provider` varchar(50) DEFAULT NULL COMMENT 'every_org, stripe',
  `provider_customer_id` varchar(100) DEFAULT NULL COMMENT 'Customer ID from payment provider',
  `provider_payment_id` varchar(100) DEFAULT NULL COMMENT 'Transaction ID from provider',
  `saved_payment_method_id` varchar(100) DEFAULT NULL COMMENT 'Saved payment method token',
  `period_start_date` date DEFAULT NULL,
  `period_end_date` date DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT (now())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `membership_types`
--

CREATE TABLE `membership_types` (
  `type_id` int NOT NULL,
  `type_name` varchar(50) NOT NULL COMMENT 'Full Member, Student Member, Premium, etc',
  `description` text,
  `annual_fee` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT (now())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `membership_types`
--

INSERT INTO `membership_types` (`type_id`, `type_name`, `description`, `annual_fee`, `is_active`, `created_at`) VALUES
(1, 'Full', NULL, 0.00, 1, '2025-11-24 05:38:49'),
(2, 'Student', NULL, 0.00, 1, '2025-11-24 05:38:49');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int NOT NULL,
  `member_id` varchar(20) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT (now())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_membership_details`
--

CREATE TABLE `student_membership_details` (
  `detail_id` int NOT NULL,
  `member_id` varchar(20) DEFAULT NULL,
  `highest_education` varchar(100) DEFAULT NULL,
  `current_college_university` varchar(200) DEFAULT NULL,
  `anticipated_completion_date` date DEFAULT NULL,
  `student_id_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT (now()),
  `updated_at` timestamp NULL DEFAULT (now())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_logs_index_16` (`admin_id`),
  ADD KEY `admin_logs_index_17` (`created_at`),
  ADD KEY `admin_logs_index_18` (`target_table`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `email_logs_index_12` (`member_id`),
  ADD KEY `email_logs_index_13` (`email_type`),
  ADD KEY `email_logs_index_14` (`sent_date`),
  ADD KEY `email_logs_index_15` (`status`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `events_index_3` (`event_date`),
  ADD KEY `events_index_4` (`is_active`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD KEY `event_registrations_index_5` (`event_id`),
  ADD KEY `event_registrations_index_6` (`member_id`),
  ADD KEY `event_registrations_index_7` (`email`),
  ADD KEY `event_registrations_index_8` (`payment_status`);

--
-- Indexes for table `full_membership_details`
--
ALTER TABLE `full_membership_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `members_index_0` (`email`),
  ADD KEY `members_index_1` (`last_name`,`first_name`),
  ADD KEY `members_index_2` (`membership_expiration_date`),
  ADD KEY `membership_type_id` (`membership_type_id`);

--
-- Indexes for table `membership_payments`
--
ALTER TABLE `membership_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `membership_payments_index_9` (`member_id`),
  ADD KEY `membership_payments_index_10` (`payment_status`),
  ADD KEY `membership_payments_index_11` (`payment_date`),
  ADD KEY `membership_type_id` (`membership_type_id`);

--
-- Indexes for table `membership_types`
--
ALTER TABLE `membership_types`
  ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD UNIQUE KEY `password_resets_index_0` (`token`),
  ADD KEY `password_resets_index_1` (`member_id`),
  ADD KEY `password_resets_index_2` (`expires_at`);

--
-- Indexes for table `student_membership_details`
--
ALTER TABLE `student_membership_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `member_id` (`member_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `log_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `admin_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `log_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `registration_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `full_membership_details`
--
ALTER TABLE `full_membership_details`
  MODIFY `detail_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `membership_payments`
--
ALTER TABLE `membership_payments`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `membership_types`
--
ALTER TABLE `membership_types`
  MODIFY `type_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_membership_details`
--
ALTER TABLE `student_membership_details`
  MODIFY `detail_id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`admin_id`);

--
-- Constraints for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD CONSTRAINT `email_logs_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`),
  ADD CONSTRAINT `email_logs_ibfk_3` FOREIGN KEY (`payment_id`) REFERENCES `membership_payments` (`payment_id`),
  ADD CONSTRAINT `email_logs_ibfk_4` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`);

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`),
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`);

--
-- Constraints for table `full_membership_details`
--
ALTER TABLE `full_membership_details`
  ADD CONSTRAINT `full_membership_details_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`);

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`membership_type_id`) REFERENCES `membership_types` (`type_id`);

--
-- Constraints for table `membership_payments`
--
ALTER TABLE `membership_payments`
  ADD CONSTRAINT `membership_payments_ibfk_2` FOREIGN KEY (`membership_type_id`) REFERENCES `membership_types` (`type_id`),
  ADD CONSTRAINT `membership_payments_ibfk_3` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`);

--
-- Constraints for table `student_membership_details`
--
ALTER TABLE `student_membership_details`
  ADD CONSTRAINT `student_membership_details_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
