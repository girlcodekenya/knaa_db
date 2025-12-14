CREATE TABLE `membership_types` (
  `type_id` int PRIMARY KEY AUTO_INCREMENT,
  `type_name` varchar(50) NOT NULL COMMENT 'Full Member, Student Member, Premium, etc',
  `description` text,
  `annual_fee` decimal(10,2),
  `is_active` boolean DEFAULT true,
  `created_at` timestamp DEFAULT (now())
);

CREATE TABLE `members` (
  `member_id` int PRIMARY KEY AUTO_INCREMENT,
  `membership_type_id` int,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) UNIQUE NOT NULL,
  `phone` varchar(20),
  `street_address` varchar(200),
  `city` varchar(100),
  `state` varchar(2) COMMENT 'Two-letter state code',
  `zip_code` varchar(10),
  `password_hash` varchar(255) NOT NULL COMMENT 'Hashed password',
  `member_since` date NOT NULL,
  `membership_expiration_date` date,
  `email_notifications_enabled` boolean DEFAULT true,
  `reminder_days_before_expiry` int DEFAULT 30 COMMENT 'Days before expiry to send reminder',
  `is_active` boolean DEFAULT true,
  `created_at` timestamp DEFAULT (now()),
  `updated_at` timestamp DEFAULT (now())
);

CREATE TABLE `full_membership_details` (
  `detail_id` int PRIMARY KEY AUTO_INCREMENT,
  `member_id` int COMMENT 'One-to-one relationship',
  `highest_education` varchar(100),
  `license_type` varchar(50) COMMENT 'LPN/LVN, RN, Nurse Practitioner',
  `licensure_state` varchar(2),
  `license_number` varchar(50),
  `created_at` timestamp DEFAULT (now()),
  `updated_at` timestamp DEFAULT (now())
);

CREATE TABLE `student_membership_details` (
  `detail_id` int PRIMARY KEY AUTO_INCREMENT,
  `member_id` int COMMENT 'One-to-one relationship',
  `highest_education` varchar(100),
  `current_college_university` varchar(200),
  `anticipated_completion_date` date,
  `student_id_number` varchar(50),
  `created_at` timestamp DEFAULT (now()),
  `updated_at` timestamp DEFAULT (now())
);

CREATE TABLE `events` (
  `event_id` int PRIMARY KEY AUTO_INCREMENT,
  `event_title` varchar(200) NOT NULL,
  `event_description` text,
  `event_date` date NOT NULL,
  `event_time` time,
  `street_address` varchar(200),
  `city` varchar(100),
  `state` varchar(2),
  `zip_code` varchar(10),
  `venue_name` varchar(150),
  `early_bird_fee` decimal(10,2),
  `standard_fee` decimal(10,2),
  `member_discount_fee` decimal(10,2),
  `early_bird_deadline` date,
  `max_attendees` int,
  `current_attendees` int DEFAULT 0,
  `is_active` boolean DEFAULT true,
  `created_at` timestamp DEFAULT (now()),
  `updated_at` timestamp DEFAULT (now())
);

CREATE TABLE `event_registrations` (
  `registration_id` int PRIMARY KEY AUTO_INCREMENT,
  `event_id` int,
  `member_id` int COMMENT 'NULL if non-member',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20),
  `street_address` varchar(200),
  `city` varchar(100),
  `state` varchar(2),
  `zip_code` varchar(10),
  `registration_fee` decimal(10,2) NOT NULL,
  `payment_status` varchar(20) COMMENT 'pending, completed, refunded',
  `payment_method` varchar(50) COMMENT 'credit_card, ach, stripe_id, etc',
  `payment_date` timestamp,
  `confirmation_email_sent` boolean DEFAULT false,
  `confirmation_sent_date` timestamp,
  `registration_date` timestamp DEFAULT (now())
);

CREATE TABLE `membership_payments` (
  `payment_id` int PRIMARY KEY AUTO_INCREMENT,
  `member_id` int,
  `membership_type_id` int,
  `amount` decimal(10,2) NOT NULL,
  `payment_type` varchar(50) COMMENT 'new, renewal, upgrade',
  `payment_status` varchar(20) COMMENT 'pending, completed, failed, refunded',
  `payment_method` varchar(50) COMMENT 'credit_card, ach',
  `payment_provider` varchar(50) COMMENT 'every_org, stripe',
  `provider_customer_id` varchar(100) COMMENT 'Customer ID from payment provider',
  `provider_payment_id` varchar(100) COMMENT 'Transaction ID from provider',
  `saved_payment_method_id` varchar(100) COMMENT 'Saved payment method token',
  `period_start_date` date,
  `period_end_date` date,
  `payment_date` timestamp,
  `created_at` timestamp DEFAULT (now())
);

CREATE TABLE `email_logs` (
  `log_id` int PRIMARY KEY AUTO_INCREMENT,
  `member_id` int COMMENT 'NULL for non-member emails',
  `email_address` varchar(150) NOT NULL,
  `email_type` varchar(50) NOT NULL COMMENT 'welcome, expiry_reminder, event_confirmation, payment_receipt, etc',
  `email_subject` varchar(200),
  `status` varchar(20) COMMENT 'sent, failed, pending',
  `error_message` text COMMENT 'If failed, reason for failure',
  `sent_date` timestamp DEFAULT (now()),
  `event_id` int COMMENT 'If email relates to an event',
  `payment_id` int COMMENT 'If email relates to a payment'
);

CREATE TABLE `admin_users` (
  `admin_id` int PRIMARY KEY AUTO_INCREMENT,
  `username` varchar(50) UNIQUE NOT NULL,
  `email` varchar(150) UNIQUE NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100),
  `role` varchar(50) COMMENT 'super_admin, admin, moderator',
  `is_active` boolean DEFAULT true,
  `last_login` timestamp,
  `created_at` timestamp DEFAULT (now())
);

CREATE TABLE `admin_logs` (
  `log_id` int PRIMARY KEY AUTO_INCREMENT,
  `admin_id` int,
  `action` varchar(100) COMMENT 'updated_member, deleted_event, etc',
  `target_table` varchar(50),
  `target_id` int,
  `old_value` text,
  `new_value` text,
  `ip_address` varchar(45),
  `created_at` timestamp DEFAULT (now())
);

CREATE UNIQUE INDEX `members_index_0` ON `members` (`email`);

CREATE INDEX `members_index_1` ON `members` (`last_name`, `first_name`);

CREATE INDEX `members_index_2` ON `members` (`membership_expiration_date`);

CREATE INDEX `events_index_3` ON `events` (`event_date`);

CREATE INDEX `events_index_4` ON `events` (`is_active`);

CREATE INDEX `event_registrations_index_5` ON `event_registrations` (`event_id`);

CREATE INDEX `event_registrations_index_6` ON `event_registrations` (`member_id`);

CREATE INDEX `event_registrations_index_7` ON `event_registrations` (`email`);

CREATE INDEX `event_registrations_index_8` ON `event_registrations` (`payment_status`);

CREATE INDEX `membership_payments_index_9` ON `membership_payments` (`member_id`);

CREATE INDEX `membership_payments_index_10` ON `membership_payments` (`payment_status`);

CREATE INDEX `membership_payments_index_11` ON `membership_payments` (`payment_date`);

CREATE INDEX `email_logs_index_12` ON `email_logs` (`member_id`);

CREATE INDEX `email_logs_index_13` ON `email_logs` (`email_type`);

CREATE INDEX `email_logs_index_14` ON `email_logs` (`sent_date`);

CREATE INDEX `email_logs_index_15` ON `email_logs` (`status`);

CREATE INDEX `admin_logs_index_16` ON `admin_logs` (`admin_id`);

CREATE INDEX `admin_logs_index_17` ON `admin_logs` (`created_at`);

CREATE INDEX `admin_logs_index_18` ON `admin_logs` (`target_table`);

ALTER TABLE `members` ADD FOREIGN KEY (`membership_type_id`) REFERENCES `membership_types` (`type_id`);

ALTER TABLE `full_membership_details` ADD FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`);

ALTER TABLE `student_membership_details` ADD FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`);

ALTER TABLE `event_registrations` ADD FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`);

ALTER TABLE `event_registrations` ADD FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`);

ALTER TABLE `membership_payments` ADD FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`);

ALTER TABLE `membership_payments` ADD FOREIGN KEY (`membership_type_id`) REFERENCES `membership_types` (`type_id`);

ALTER TABLE `email_logs` ADD FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`);

ALTER TABLE `email_logs` ADD FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`);

ALTER TABLE `email_logs` ADD FOREIGN KEY (`payment_id`) REFERENCES `membership_payments` (`payment_id`);

ALTER TABLE `admin_logs` ADD FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`admin_id`);
