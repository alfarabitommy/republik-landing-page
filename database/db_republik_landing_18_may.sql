-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 18, 2026 at 02:31 PM
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
-- Database: `db_republik_landing`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_leads`
--

CREATE TABLE `tb_leads` (
  `id_lead` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `organization` varchar(150) NOT NULL,
  `position` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `messages` text NOT NULL,
  `status` enum('new','reviewed','contacted','spam','closed') DEFAULT 'new',
  `is_deleted` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_leads`
--

INSERT INTO `tb_leads` (`id_lead`, `first_name`, `last_name`, `email`, `organization`, `position`, `country`, `messages`, `status`, `is_deleted`, `ip_address`, `created_at`, `updated_at`) VALUES
(1, 'Tommy', 'Alfarabi', 'tom@gmail.com', 'Web Developer', 'Developer', 'Indonesia', 'Hi, i would like to ask something', 'new', 0, '::1', '2026-05-11 07:32:34', NULL),
(2, 'Tom', 'Testing', 'tom@testing.com', 'Independent', 'CEO', 'indonesia', 'Halo, i would like to ask something', 'new', 0, '::1', '2026-05-18 06:21:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_lead_status_logs`
--

CREATE TABLE `tb_lead_status_logs` (
  `id_log` bigint(20) UNSIGNED NOT NULL,
  `lead_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `old_status` varchar(50) NOT NULL,
  `new_status` varchar(50) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `changed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_roles`
--

CREATE TABLE `tb_roles` (
  `id_role` int(11) UNSIGNED NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_roles`
--

INSERT INTO `tb_roles` (`id_role`, `role_name`, `description`) VALUES
(1, 'Super Admin', 'Akses penuh sistem');

-- --------------------------------------------------------

--
-- Table structure for table `tb_system_settings`
--

CREATE TABLE `tb_system_settings` (
  `id_setting` int(11) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_system_settings`
--

INSERT INTO `tb_system_settings` (`id_setting`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'headline_main', 'Your brand doesn\'t need more content. It needs a sharper creative system.', 'Headline Utama di Hero Section', '2026-05-14 13:22:09'),
(2, 'video_1', 'https://www.youtube.com/watch?v=UxqOCjGTx-Y', NULL, '2026-05-18 19:26:52'),
(3, 'video_2', 'https://www.youtube.com/watch?v=bDz6fdpadtU', NULL, '2026-05-18 19:26:52'),
(4, 'video_3', 'https://www.youtube.com/watch?v=xyFsjhSPiog', NULL, '2026-05-18 19:26:52'),
(5, 'video_4', 'https://www.youtube.com/watch?v=hUigMH7Pv6Y', NULL, '2026-05-18 19:30:18'),
(6, 'video_5', 'https://www.youtube.com/watch?v=4fA3E2pQ_rQ', NULL, '2026-05-18 19:30:18'),
(7, 'video_title_1', 'New Honda Vario 125 - Irit', NULL, '2026-05-18 19:26:52'),
(8, 'video_title_2', 'New Honda Vario 125 - Perfect Fit', NULL, '2026-05-18 19:26:52'),
(9, 'video_title_3', 'New Honda Vario 125 - Built-In USB Charger', NULL, '2026-05-18 19:26:52'),
(10, 'video_title_4', 'New Honda Vario 125', NULL, '2026-05-18 19:30:18'),
(11, 'video_title_5', 'JERGENS Enriching Shea Butter', NULL, '2026-05-18 19:30:18'),
(12, 'video_thumb_1', 'assets/img/5a4b7e232d98c1ac081d06bea432f42f.png', NULL, '2026-05-18 19:26:52'),
(13, 'video_thumb_2', 'assets/img/0213922cf8c512b4ccb1ce172fea1192.png', NULL, '2026-05-18 19:26:52'),
(14, 'video_thumb_3', 'assets/img/7a9acab8e0c44718a05e4bbafaa66e33.png', NULL, '2026-05-18 19:26:52'),
(15, 'video_thumb_4', 'assets/img/9b275c1c871e2c24d8a15dc98a19f8da.png', NULL, '2026-05-18 19:30:18'),
(16, 'video_thumb_5', 'assets/img/42eafe33a78710413d38221307b82980.png', NULL, '2026-05-18 19:30:18');

-- --------------------------------------------------------

--
-- Table structure for table `tb_users`
--

CREATE TABLE `tb_users` (
  `id_user` int(11) UNSIGNED NOT NULL,
  `role_id` int(11) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_users`
--

INSERT INTO `tb_users` (`id_user`, `role_id`, `username`, `password`, `fullname`, `is_active`, `last_login`, `created_at`) VALUES
(1, 1, 'admin', '$2a$12$3favMKoD7.i8QJQVRcQofuTV7pYYaQCJHo6d5BKMWDcnNnZzUjQhy', 'Administrator REPUBLIK', 1, NULL, '2026-05-14 12:37:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_leads`
--
ALTER TABLE `tb_leads`
  ADD PRIMARY KEY (`id_lead`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `tb_lead_status_logs`
--
ALTER TABLE `tb_lead_status_logs`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `fk_log_lead` (`lead_id`),
  ADD KEY `fk_log_user` (`user_id`);

--
-- Indexes for table `tb_roles`
--
ALTER TABLE `tb_roles`
  ADD PRIMARY KEY (`id_role`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `tb_system_settings`
--
ALTER TABLE `tb_system_settings`
  ADD PRIMARY KEY (`id_setting`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `tb_users`
--
ALTER TABLE `tb_users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_user_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_leads`
--
ALTER TABLE `tb_leads`
  MODIFY `id_lead` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_lead_status_logs`
--
ALTER TABLE `tb_lead_status_logs`
  MODIFY `id_log` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_roles`
--
ALTER TABLE `tb_roles`
  MODIFY `id_role` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_system_settings`
--
ALTER TABLE `tb_system_settings`
  MODIFY `id_setting` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tb_users`
--
ALTER TABLE `tb_users`
  MODIFY `id_user` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_lead_status_logs`
--
ALTER TABLE `tb_lead_status_logs`
  ADD CONSTRAINT `fk_log_lead` FOREIGN KEY (`lead_id`) REFERENCES `tb_leads` (`id_lead`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `tb_users` (`id_user`) ON DELETE SET NULL;

--
-- Constraints for table `tb_users`
--
ALTER TABLE `tb_users`
  ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `tb_roles` (`id_role`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
