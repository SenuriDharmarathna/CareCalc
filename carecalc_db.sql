-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 09, 2026 at 09:47 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `carecalc_db`
--
CREATE DATABASE IF NOT EXISTS `carecalc_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `carecalc_db`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `created_at`, `is_read`) VALUES
(1, 'senu', 'senu@gmail.com', 'asdafnj', 'dfg', '2025-10-18 11:47:38', 0),
(2, 'Customer2', 'customer2@gmail.com', 'subject', 'qwerrt', '2025-11-21 01:25:53', 0),
(3, 'customer3', 'customer3@gmail.com', 'fgh', 'fgh', '2025-11-21 04:02:31', 0);

-- --------------------------------------------------------

--
-- Table structure for table `insurance_plans`
--

DROP TABLE IF EXISTS `insurance_plans`;
CREATE TABLE IF NOT EXISTS `insurance_plans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `tagline` varchar(255) DEFAULT NULL,
  `color_hex` varchar(10) DEFAULT '#2563ff',
  `annual_premium_min` int DEFAULT '0',
  `annual_premium_max` int DEFAULT '0',
  `inpatient_limit` int DEFAULT '0',
  `outpatient_limit` int DEFAULT '0',
  `surgery_limit` int DEFAULT '0',
  `icu_limit` int DEFAULT '0',
  `dental_covered` tinyint DEFAULT '0',
  `optical_covered` tinyint DEFAULT '0',
  `maternity_covered` tinyint DEFAULT '0',
  `emergency_covered` tinyint DEFAULT '1',
  `pre_existing_covered` tinyint DEFAULT '0',
  `waiting_period_months` int DEFAULT '0',
  `max_age_limit` int DEFAULT '70',
  `features` text,
  `exclusions` text,
  `is_active` tinyint DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `insurance_plans`
--

INSERT INTO `insurance_plans` (`id`, `name`, `slug`, `tagline`, `color_hex`, `annual_premium_min`, `annual_premium_max`, `inpatient_limit`, `outpatient_limit`, `surgery_limit`, `icu_limit`, `dental_covered`, `optical_covered`, `maternity_covered`, `emergency_covered`, `pre_existing_covered`, `waiting_period_months`, `max_age_limit`, `features`, `exclusions`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Basic', 'basic', 'Essential coverage for everyday health needs', '#2563ff', 30000, 120000, 300000, 50000, 150000, 200000, 0, 0, 0, 1, 0, 3, 65, 'Inpatient hospitalisation;Emergency ambulance;24/7 helpline;Basic diagnostics', 'Dental & optical;Cosmetic procedures;Pre-existing conditions;Maternity', 1, 1, '2026-03-25 06:13:52', '2026-03-25 06:13:52'),
(2, 'Standard', 'standard', 'Balanced protection for individuals & families', '#00d4aa', 120000, 400000, 750000, 150000, 400000, 500000, 1, 1, 0, 1, 0, 6, 70, 'All Basic benefits;Outpatient consultations;Dental & optical;Specialist referrals;Prescription drugs', 'Cosmetic procedures;Pre-existing conditions (first year);Maternity', 1, 2, '2026-03-25 06:13:52', '2026-03-25 06:13:52'),
(3, 'Premium', 'premium', 'Comprehensive cover with zero compromise', '#f97316', 400000, 1200000, 2000000, 400000, 1000000, 1500000, 1, 1, 1, 1, 1, 0, 75, 'All Standard benefits;Maternity & newborn;Pre-existing conditions;International emergency;Annual health check;Mental health support;No waiting period', 'Experimental treatments;Self-inflicted injuries', 1, 3, '2026-03-25 06:13:52', '2026-03-25 06:13:52');

-- --------------------------------------------------------

--
-- Table structure for table `predictions`
--

DROP TABLE IF EXISTS `predictions`;
CREATE TABLE IF NOT EXISTS `predictions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `predicted_premium` decimal(12,2) NOT NULL,
  `recommended_plan` varchar(20) NOT NULL,
  `gender` tinyint(1) NOT NULL DEFAULT '0',
  `age` int NOT NULL,
  `bmi` decimal(5,1) NOT NULL,
  `smoker` tinyint(1) NOT NULL DEFAULT '0',
  `alcohol_use` tinyint(1) NOT NULL DEFAULT '0',
  `coverage_plan` varchar(20) NOT NULL,
  `district` varchar(50) NOT NULL,
  `heart_disease` tinyint(1) NOT NULL DEFAULT '0',
  `diabetes` tinyint(1) NOT NULL DEFAULT '0',
  `hypertension` tinyint(1) NOT NULL DEFAULT '0',
  `asthma` tinyint(1) NOT NULL DEFAULT '0',
  `marital_status` varchar(20) NOT NULL,
  `number_of_children` int NOT NULL DEFAULT '0',
  `annual_income` bigint NOT NULL,
  `hospitalization_last_5yrs` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `predictions`
--

INSERT INTO `predictions` (`id`, `user_id`, `predicted_premium`, `recommended_plan`, `gender`, `age`, `bmi`, `smoker`, `alcohol_use`, `coverage_plan`, `district`, `heart_disease`, `diabetes`, `hypertension`, `asthma`, `marital_status`, `number_of_children`, `annual_income`, `hospitalization_last_5yrs`, `created_at`) VALUES
(1, 3, 87000.00, 'Basic', 0, 45, 22.0, 0, 1, 'Basic', 'Colombo', 0, 0, 0, 0, 'Married', 2, 2000000, 1, '2026-03-24 19:43:39'),
(2, 3, 147000.00, 'Standard', 1, 40, 22.0, 1, 1, 'Basic', 'Kandy', 1, 1, 0, 0, 'Married', 2, 2300000, 2, '2026-03-24 19:44:37'),
(3, 3, 38000.00, 'Basic', 0, 35, 22.0, 0, 0, 'Basic', 'Colombo', 0, 0, 0, 0, 'Married', 1, 0, 1, '2026-03-24 19:46:32'),
(4, 3, 129000.00, 'Standard', 0, 35, 22.0, 0, 0, 'Basic', 'Colombo', 0, 0, 0, 0, 'Single', 2, 12000000, 1, '2026-03-29 23:00:32'),
(5, 3, 129000.00, 'Standard', 0, 35, 22.0, 0, 0, 'Basic', 'Colombo', 0, 0, 0, 0, 'Single', 2, 12000000, 1, '2026-03-29 23:03:21'),
(6, 3, 129000.00, 'Standard', 0, 35, 22.0, 0, 0, 'Basic', 'Colombo', 0, 0, 0, 0, 'Single', 2, 12000000, 1, '2026-03-29 23:10:45'),
(7, 3, 76500.00, 'Basic', 0, 35, 22.0, 0, 1, 'Basic', 'Colombo', 1, 0, 0, 0, 'Married', 1, 1300000, 0, '2026-03-30 14:06:35'),
(8, 3, 54500.00, 'Basic', 0, 35, 22.0, 0, 1, 'Basic', 'Gampaha', 0, 0, 0, 0, 'Married', 1, 1000000, 1, '2026-03-30 14:43:27'),
(9, 3, 58500.00, 'Basic', 0, 35, 22.0, 0, 0, 'Basic', 'Colombo', 0, 0, 0, 0, 'Married', 1, 1300000, 1, '2026-03-30 16:01:33'),
(10, 3, 83500.00, 'Basic', 0, 35, 22.0, 0, 1, 'Basic', 'Colombo', 0, 1, 0, 0, 'Married', 1, 1300000, 1, '2026-03-30 16:29:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `city`, `contact`, `role`, `created_at`, `is_blocked`) VALUES
(1, 'senu', 'senu@gmail.com', '$2y$10$vO0SJB7MUwqU1t5k2h4.qOwJuhy/5O1/ufEA436MOTkeuJc/xwriO', 'gampaha', '0332288759', 'user', '2025-10-18 11:37:25', 0),
(2, 'admin1', 'admin1@gmail.com', '$2y$10$sP21e9hBHt5W46VNuadCXuXrwJ/GeOBBTzSfCK1l9s7UuKfP4DQ9G', '', '', 'admin', '2025-10-18 11:40:25', 0),
(3, 'customer1', 'customer1@gmail.com', '$2y$10$25u/eLI.bn2zUPKdxVgliOcdWgg3vKfi2bn2PNM34AXrrXxKjxaRa', 'colombo', '', 'user', '2025-10-18 12:58:26', 0),
(4, 'ustomer2', 'customer2@gmail.com', '$2y$10$6pWaGDbJN5K1M7AlLmFyyuERPD5biX6iKEui4RWrakCYphElJU/Pu', 'Colombo', '0774567780', 'user', '2025-11-21 01:29:53', 0),
(5, 'admin2', 'admin2@gmail.com', '$2y$10$fIXjf64t6KnuLgav7zgnRuZwNgGSblDek.uop84VlSvG/s6qf/G1y', '', '', 'admin', '2025-11-21 01:35:23', 0),
(6, 'customer3', 'customer3@gmail.com', '$2y$10$t8PmRwPlxnuvcL16k8QA5e4IiJAi9.Q5iBoMpOfEOYpLYAeLn5LLi', 'gampaha', '077564783', 'user', '2025-11-21 04:03:48', 0);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
