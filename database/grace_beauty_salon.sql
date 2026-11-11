-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 11, 2026 at 09:46 AM
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
-- Database: `grace_beauty_salon`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `salonist_id` int(11) DEFAULT NULL,
  `service_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `style_preferences` text DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `customer_id`, `salonist_id`, `service_id`, `appointment_date`, `appointment_time`, `style_preferences`, `additional_notes`, `status`, `created_at`) VALUES
(1, 1, NULL, 1, '2026-11-11', '23:30:00', 'i love salons', '', 'pending', '2026-11-10 19:21:53'),
(2, 1, NULL, 4, '2026-11-19', '13:37:00', 'i love style', '', 'pending', '2026-11-10 19:37:30'),
(3, 1, NULL, 1, '2026-11-26', '10:00:00', 'i need change', '', 'pending', '2026-11-10 19:58:41'),
(4, 1, NULL, 1, '2026-11-26', '10:00:00', 'i need change', '', 'pending', '2026-11-10 20:00:12'),
(5, 1, 3, 5, '2026-12-08', '11:07:00', 'ii09ugukfkutf', '', 'pending', '2026-11-10 20:07:35'),
(6, 1, NULL, 3, '2026-11-23', '10:11:00', 'fgrsthsrht', '', 'pending', '2026-11-11 06:11:15'),
(7, 1, NULL, 5, '2026-11-17', '12:18:00', 'bjgkgk', '', 'pending', '2026-11-11 06:18:36'),
(8, 1, NULL, 1, '2026-11-24', '09:23:00', 'ebartb', '', 'pending', '2026-11-11 06:20:56'),
(9, 1, 4, 7, '2026-11-17', '11:45:00', 'i want to get married', '', 'confirmed', '2026-11-11 07:45:31');

-- --------------------------------------------------------

--
-- Table structure for table `otp_verifications`
--

CREATE TABLE `otp_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `consumed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_verifications`
--

INSERT INTO `otp_verifications` (`id`, `user_id`, `otp_hash`, `expires_at`, `attempts`, `consumed_at`, `created_at`) VALUES
(1, 1, '$2y$10$WQUwC9ErmlmXPetGhmms5u/ipC5NvtpuE5I5NBecg8bjLiA85fdwK', '2026-11-10 20:00:32', 0, '2026-11-10 21:51:15', '2026-11-10 18:50:32');

-- --------------------------------------------------------

--
-- Table structure for table `salonist_services`
--

CREATE TABLE `salonist_services` (
  `salonist_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salonist_services`
--

INSERT INTO `salonist_services` (`salonist_id`, `service_id`) VALUES
(3, 1),
(3, 2),
(3, 3),
(3, 4),
(3, 5),
(3, 6),
(3, 7),
(4, 1),
(4, 2),
(4, 3),
(4, 4),
(4, 5),
(4, 6),
(4, 7),
(5, 8);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `service_name`, `description`, `price`, `duration_minutes`, `image_url`, `is_active`, `created_at`) VALUES
(1, 'Hair Styling', 'Professional hair styling and finishing.', 1500.00, 60, NULL, 1, '2026-11-10 19:01:16'),
(2, 'Hair Braiding', 'Professional braiding and protective hairstyles.', 2500.00, 120, NULL, 1, '2026-11-10 19:01:16'),
(3, 'Hair Treatment', 'Deep conditioning and hair nourishment.', 2000.00, 60, NULL, 1, '2026-11-10 19:01:16'),
(4, 'Manicure', 'Nail shaping, cleaning and polish.', 800.00, 45, NULL, 1, '2026-11-10 19:01:16'),
(5, 'Pedicure', 'Foot care, nail treatment and polish.', 1000.00, 60, NULL, 1, '2026-11-10 19:01:16'),
(6, 'Makeup', 'Professional makeup application.', 3000.00, 90, NULL, 1, '2026-11-10 19:01:16'),
(7, 'Bridal Makeup', 'Special occasion bridal makeup.', 5000.00, 120, NULL, 1, '2026-11-10 19:01:16'),
(8, 'SPA', 'its really good', 5000.00, 5, NULL, 1, '2026-11-11 07:09:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','salonist','admin') NOT NULL DEFAULT 'customer',
  `is_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `role`, `is_verified`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'bogan', 'abdimohamedamin04@gmail.com', '0727161815', '$2y$10$5mE5vv7F7htpUEWcVuOxDORWced7RWXRfw9Rxao9zkErg1.Rs/WSS', 'customer', 1, 1, '2026-11-10 18:50:32', '2026-11-10 18:51:15'),
(2, 'Aminkey', 'haterw45@gmail.com', '0755289898', '$2y$10$uDC2g/XBwToplKVN4BmO6Oi25AyY7Y6Q3VXMxPCZe1rc5uZwiam/O', 'admin', 1, 1, '2026-11-11 06:31:37', '2026-11-11 06:31:37'),
(3, 'yosh', 'yosh@gmail.com', '0723456789', '$2y$10$NX8NlIhbLIrSpxygCAxhRO/LiCOkr2ptUzQSVbKLC3CQ/1bIWS5hK', 'salonist', 1, 1, '2026-11-11 07:03:04', '2026-11-11 07:03:04'),
(4, 'Maureen', 'techwithmoha69@gmail.com', '0789124365', '$2y$10$bSCgIbt7OuC.sJpsz4HR9eqJmWm0zmTFsvnw/o6GQTEXL.I3AG8fS', 'salonist', 1, 1, '2026-11-11 07:07:00', '2026-11-11 07:07:15'),
(5, 'zamzam', 'zamzam@gmail.com', '0989123456', '$2y$10$hnCfow3TYAUoOdSQxGMLEefy.K4dadVhgwa8Jc2ft7SV0Z/U.Gawq', 'salonist', 1, 1, '2026-11-11 07:13:51', '2026-11-11 07:13:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `salonist_id` (`salonist_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `salonist_services`
--
ALTER TABLE `salonist_services`
  ADD PRIMARY KEY (`salonist_id`,`service_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`salonist_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);

--
-- Constraints for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD CONSTRAINT `otp_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `salonist_services`
--
ALTER TABLE `salonist_services`
  ADD CONSTRAINT `salonist_services_ibfk_1` FOREIGN KEY (`salonist_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `salonist_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
