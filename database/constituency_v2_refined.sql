-- Refined Constituency Development Database Schema
-- Version 2.0 (Tailored to specific requirements)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- DOMAIN 1: CLASSIFICATION & LOOKUPS
-- ============================================================

-- Table structure for table `categories`
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Table structure for table `sectors`
DROP TABLE IF EXISTS `sectors`;
CREATE TABLE `sectors` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `sub_sectors`
DROP TABLE IF EXISTS `sub_sectors`;
CREATE TABLE `sub_sectors` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sector_id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `sector_id` (`sector_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `locations`
DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('community','suburb') DEFAULT 'community',
  `parent_id` int(11) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DOMAIN 2: USERS & ROLE PROFILES
-- ============================================================

-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','web_admin','officer','agent','task_force') NOT NULL,
  `status` enum('active','suspended','pending') NOT NULL DEFAULT 'pending',
  `remember_token` varchar(100) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profile structure template (Applied to all roles)
-- Table structure for table `web_admin_profiles`
DROP TABLE IF EXISTS `web_admin_profiles`;
CREATE TABLE `web_admin_profiles` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `officer_profiles`
DROP TABLE IF EXISTS `officer_profiles`;
CREATE TABLE `officer_profiles` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `agent_profiles`
DROP TABLE IF EXISTS `agent_profiles`;
CREATE TABLE `agent_profiles` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `task_force_profiles`
DROP TABLE IF EXISTS `task_force_profiles`;
CREATE TABLE `task_force_profiles` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `admin_profiles`
DROP TABLE IF EXISTS `admin_profiles`;
CREATE TABLE `admin_profiles` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DOMAIN 3: ISSUE TRACKING & RESOLUTION
-- ============================================================

-- Table structure for table `constituents`
DROP TABLE IF EXISTS `constituents`;
CREATE TABLE `constituents` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `phone_number` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `issues`
DROP TABLE IF EXISTS `issues`;
CREATE TABLE `issues` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category_id` int(11) UNSIGNED NOT NULL,
  `sector_id` int(11) UNSIGNED NOT NULL,
  `sub_sector_id` int(11) UNSIGNED NOT NULL,
  `community_id` int(11) UNSIGNED NOT NULL,
  `suburb_id` int(11) UNSIGNED DEFAULT NULL,
  `specific_location` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `status` enum('submitted','assessment_in_progress','assessment_submitted','resolution_in_progress','resolved','needs_revision') DEFAULT 'submitted',
  `constituent_id` int(11) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `sector_id` (`sector_id`),
  KEY `sub_sector_id` (`sub_sector_id`),
  KEY `community_id` (`community_id`),
  KEY `constituent_id` (`constituent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `issue_assessments`
DROP TABLE IF EXISTS `issue_assessments`;
CREATE TABLE `issue_assessments` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `issues_id` int(11) UNSIGNED NOT NULL,
  `recommendations` text DEFAULT NULL,
  `estimated_costs` decimal(15,2) DEFAULT NULL,
  `estimated_duration` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `issue_confirmed` tinyint(1) NOT NULL DEFAULT 1,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array of file paths' CHECK (json_valid(`attachments`)),
  `status` enum('pending_approval','approved','needs_revision') DEFAULT 'pending_approval',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `issues_id` (`issues_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `issue_resolutions`
DROP TABLE IF EXISTS `issue_resolutions`;
CREATE TABLE `issue_resolutions` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `issues_id` int(11) UNSIGNED NOT NULL,
  `summary` text NOT NULL,
  `status` enum('draft','submitted','completed') DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `issues_id` (`issues_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DOMAIN 4: IDEAS & CONTENT
-- ============================================================

-- Table structure for table `community_ideas`
DROP TABLE IF EXISTS `community_ideas`;
CREATE TABLE `community_ideas` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `blog_posts`
DROP TABLE IF EXISTS `blog_posts`;
CREATE TABLE `blog_posts` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL COMMENT 'Comma-separated tags or JSON array',
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DOMAIN 5: PROJECTS & EVENTS
-- ============================================================

-- Table structure for table `projects`
DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `status` enum('planning','ongoing','completed','on_hold','cancelled') NOT NULL DEFAULT 'planning',
  `budget` decimal(15,2) DEFAULT NULL,
  `progress_percent` int(11) NOT NULL DEFAULT 0,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array of file paths' CHECK (json_valid(`images`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `constituency_events`
DROP TABLE IF EXISTS `constituency_events`;
CREATE TABLE `constituency_events` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `status` enum('upcoming','ongoing','completed','cancelled','postponed') NOT NULL DEFAULT 'upcoming',
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array of file paths' CHECK (json_valid(`images`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DOMAIN 6: EMPLOYMENT & JOBS
-- ============================================================

-- Table structure for table `employment_jobs`
DROP TABLE IF EXISTS `employment_jobs`;
CREATE TABLE `employment_jobs` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `job_information` text DEFAULT NULL,
  `status` enum('open','closed','archived') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `job_applicants`
DROP TABLE IF EXISTS `job_applicants`;
CREATE TABLE `job_applicants` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id` int(11) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(50) NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `cv_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','shortlisted','interviewed','hired','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FINAL: CONSTRAINTS
-- ============================================================

-- Classification Hierarchy
ALTER TABLE `sectors` ADD CONSTRAINT `fk_sectors_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
ALTER TABLE `sub_sectors` ADD CONSTRAINT `fk_sub_sectors_sector` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`) ON DELETE CASCADE;
ALTER TABLE `locations` ADD CONSTRAINT `fk_locations_parent` FOREIGN KEY (`parent_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

-- User Profiles
ALTER TABLE `web_admin_profiles` ADD CONSTRAINT `fk_web_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `officer_profiles` ADD CONSTRAINT `fk_officer_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `agent_profiles` ADD CONSTRAINT `fk_agent_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `task_force_profiles` ADD CONSTRAINT `fk_task_force_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `admin_profiles` ADD CONSTRAINT `fk_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Issue Tracking
ALTER TABLE `issues` 
  ADD CONSTRAINT `fk_issue_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_issue_sec` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`),
  ADD CONSTRAINT `fk_issue_sub` FOREIGN KEY (`sub_sector_id`) REFERENCES `sub_sectors` (`id`),
  ADD CONSTRAINT `fk_issue_loc` FOREIGN KEY (`community_id`) REFERENCES `locations` (`id`),
  ADD CONSTRAINT `fk_issue_const` FOREIGN KEY (`constituent_id`) REFERENCES `constituents` (`id`);

ALTER TABLE `issue_assessments` ADD CONSTRAINT `fk_assessment_report` FOREIGN KEY (`issues_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE;
ALTER TABLE `issue_resolutions` ADD CONSTRAINT `fk_resolution_report` FOREIGN KEY (`issues_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE;

-- Jobs
ALTER TABLE `job_applicants` ADD CONSTRAINT `fk_applicant_job` FOREIGN KEY (`job_id`) REFERENCES `employment_jobs` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
