-- ============================================================
-- TokenFlow Pro — Complete Database Schema
-- Intelligent Digital Queue & Service Optimization Platform
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+05:30";

CREATE DATABASE IF NOT EXISTS `tokenflow_pro` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tokenflow_pro`;

-- ============================================================
-- 1. ORGANIZATIONS
-- ============================================================
CREATE TABLE `organizations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `logo_path` VARCHAR(500) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `website` VARCHAR(255) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `accent_color` VARCHAR(7) DEFAULT '#38bdf8',
    `timezone` VARCHAR(50) DEFAULT 'Asia/Kolkata',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 2. BRANCHES
-- ============================================================
CREATE TABLE `branches` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `org_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(10) NOT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `latitude` DECIMAL(10,8) DEFAULT NULL,
    `longitude` DECIMAL(11,8) DEFAULT NULL,
    `opening_time` TIME DEFAULT '08:00:00',
    `closing_time` TIME DEFAULT '18:00:00',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`org_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_branch_code` (`org_id`, `code`)
) ENGINE=InnoDB;

-- ============================================================
-- 3. DEPARTMENTS
-- ============================================================
CREATE TABLE `departments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(10) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `icon` VARCHAR(50) DEFAULT 'bi-building',
    `color` VARCHAR(7) DEFAULT '#38bdf8',
    `token_prefix` VARCHAR(5) NOT NULL DEFAULT 'A',
    `daily_token_limit` INT UNSIGNED DEFAULT 500,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT UNSIGNED DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_dept_code` (`branch_id`, `code`)
) ENGINE=InnoDB;

-- ============================================================
-- 4. SERVICES
-- ============================================================
CREATE TABLE `services` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `department_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `icon` VARCHAR(50) DEFAULT 'bi-clipboard-check',
    `avg_service_time` INT UNSIGNED DEFAULT 10 COMMENT 'Average service time in minutes',
    `max_service_time` INT UNSIGNED DEFAULT 30 COMMENT 'Max allowed time in minutes',
    `is_appointment_allowed` TINYINT(1) DEFAULT 1,
    `is_priority_allowed` TINYINT(1) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT UNSIGNED DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 5. USERS (all roles: customer, staff, admin, super_admin)
-- ============================================================
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `org_id` INT UNSIGNED DEFAULT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('customer','staff','admin','super_admin') NOT NULL DEFAULT 'customer',
    `avatar_path` VARCHAR(500) DEFAULT NULL,
    `preferred_language` VARCHAR(5) DEFAULT 'en',
    `is_active` TINYINT(1) DEFAULT 1,
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `last_login_at` TIMESTAMP NULL DEFAULT NULL,
    `login_attempts` INT UNSIGNED DEFAULT 0,
    `locked_until` TIMESTAMP NULL DEFAULT NULL,
    `password_reset_token` VARCHAR(255) DEFAULT NULL,
    `password_reset_expires` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`org_id`) REFERENCES `organizations`(`id`) ON DELETE SET NULL,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB;

-- ============================================================
-- 6. COUNTERS
-- ============================================================
CREATE TABLE `counters` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `number` INT UNSIGNED NOT NULL,
    `status` ENUM('open','closed','break','maintenance') DEFAULT 'closed',
    `current_token_id` INT UNSIGNED DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_counter_number` (`branch_id`, `number`)
) ENGINE=InnoDB;

-- ============================================================
-- 7. STAFF ASSIGNMENTS
-- ============================================================
CREATE TABLE `staff_assignments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `department_id` INT UNSIGNED DEFAULT NULL,
    `counter_id` INT UNSIGNED DEFAULT NULL,
    `is_online` TINYINT(1) DEFAULT 0,
    `shift_start` TIME DEFAULT NULL,
    `shift_end` TIME DEFAULT NULL,
    `assigned_date` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`counter_id`) REFERENCES `counters`(`id`) ON DELETE SET NULL,
    INDEX `idx_staff_user` (`user_id`),
    INDEX `idx_staff_counter` (`counter_id`)
) ENGINE=InnoDB;

-- ============================================================
-- 8. TOKENS (Core Queue Table)
-- ============================================================
CREATE TABLE `tokens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `token_number` VARCHAR(20) NOT NULL,
    `display_number` VARCHAR(20) NOT NULL COMMENT 'Human-readable token like A-001',
    `user_id` INT UNSIGNED DEFAULT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `department_id` INT UNSIGNED NOT NULL,
    `service_id` INT UNSIGNED NOT NULL,
    `counter_id` INT UNSIGNED DEFAULT NULL,
    `served_by` INT UNSIGNED DEFAULT NULL COMMENT 'Staff user_id',
    `type` ENUM('normal','priority','vip','appointment') DEFAULT 'normal',
    `status` ENUM('waiting','serving','completed','skipped','cancelled','no_show','transferred') DEFAULT 'waiting',
    `priority_score` INT UNSIGNED DEFAULT 0 COMMENT 'Higher = served sooner',
    `position_in_queue` INT UNSIGNED DEFAULT NULL,
    `estimated_wait_minutes` INT UNSIGNED DEFAULT NULL,
    `actual_wait_minutes` INT UNSIGNED DEFAULT NULL,
    `service_start_time` TIMESTAMP NULL DEFAULT NULL,
    `service_end_time` TIMESTAMP NULL DEFAULT NULL,
    `actual_service_minutes` INT UNSIGNED DEFAULT NULL,
    `customer_name` VARCHAR(255) DEFAULT NULL COMMENT 'For walk-in customers without account',
    `customer_phone` VARCHAR(20) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `qr_code` VARCHAR(255) DEFAULT NULL,
    `is_virtual` TINYINT(1) DEFAULT 0 COMMENT 'Virtual queue (not physically present)',
    `called_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `date` DATE NOT NULL COMMENT 'Queue date for daily reset',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`counter_id`) REFERENCES `counters`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`served_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_tokens_status` (`status`),
    INDEX `idx_tokens_date` (`date`),
    INDEX `idx_tokens_branch_date` (`branch_id`, `date`),
    INDEX `idx_tokens_dept_status` (`department_id`, `status`),
    INDEX `idx_tokens_user` (`user_id`),
    INDEX `idx_tokens_display` (`display_number`),
    UNIQUE KEY `uk_token_number_date` (`token_number`, `date`, `branch_id`)
) ENGINE=InnoDB;

-- ============================================================
-- 9. TOKEN HISTORY (Status Change Audit)
-- ============================================================
CREATE TABLE `token_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `token_id` INT UNSIGNED NOT NULL,
    `from_status` VARCHAR(20) DEFAULT NULL,
    `to_status` VARCHAR(20) NOT NULL,
    `changed_by` INT UNSIGNED DEFAULT NULL,
    `counter_id` INT UNSIGNED DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`token_id`) REFERENCES `tokens`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`counter_id`) REFERENCES `counters`(`id`) ON DELETE SET NULL,
    INDEX `idx_th_token` (`token_id`)
) ENGINE=InnoDB;

-- ============================================================
-- 10. TOKEN TRANSFERS
-- ============================================================
CREATE TABLE `token_transfers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `token_id` INT UNSIGNED NOT NULL,
    `from_counter_id` INT UNSIGNED NOT NULL,
    `to_counter_id` INT UNSIGNED NOT NULL,
    `from_department_id` INT UNSIGNED DEFAULT NULL,
    `to_department_id` INT UNSIGNED DEFAULT NULL,
    `transferred_by` INT UNSIGNED NOT NULL,
    `reason` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`token_id`) REFERENCES `tokens`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`from_counter_id`) REFERENCES `counters`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`to_counter_id`) REFERENCES `counters`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`transferred_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 11. APPOINTMENTS
-- ============================================================
CREATE TABLE `appointments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `department_id` INT UNSIGNED NOT NULL,
    `service_id` INT UNSIGNED NOT NULL,
    `token_id` INT UNSIGNED DEFAULT NULL COMMENT 'Linked token when checked in',
    `appointment_date` DATE NOT NULL,
    `appointment_time` TIME NOT NULL,
    `status` ENUM('scheduled','confirmed','checked_in','completed','cancelled','no_show') DEFAULT 'scheduled',
    `notes` TEXT DEFAULT NULL,
    `reminder_sent` TINYINT(1) DEFAULT 0,
    `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
    `cancel_reason` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`token_id`) REFERENCES `tokens`(`id`) ON DELETE SET NULL,
    INDEX `idx_appt_date` (`appointment_date`),
    INDEX `idx_appt_user` (`user_id`)
) ENGINE=InnoDB;

-- ============================================================
-- 12. QUEUE STATUS (Real-time Snapshots)
-- ============================================================
CREATE TABLE `queue_status` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT UNSIGNED NOT NULL,
    `department_id` INT UNSIGNED NOT NULL,
    `total_waiting` INT UNSIGNED DEFAULT 0,
    `total_serving` INT UNSIGNED DEFAULT 0,
    `total_completed` INT UNSIGNED DEFAULT 0,
    `total_skipped` INT UNSIGNED DEFAULT 0,
    `avg_wait_time` DECIMAL(5,1) DEFAULT 0,
    `avg_service_time` DECIMAL(5,1) DEFAULT 0,
    `queue_health` ENUM('low','moderate','high','critical') DEFAULT 'low',
    `active_counters` INT UNSIGNED DEFAULT 0,
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_qs_branch_dept` (`branch_id`, `department_id`)
) ENGINE=InnoDB;

-- ============================================================
-- 13. NOTIFICATIONS
-- ============================================================
CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('token','appointment','system','emergency','feedback') DEFAULT 'system',
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'bi-bell',
    `link` VARCHAR(500) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_notif_user` (`user_id`, `is_read`),
    INDEX `idx_notif_created` (`created_at`)
) ENGINE=InnoDB;

-- ============================================================
-- 14. FEEDBACK
-- ============================================================
CREATE TABLE `feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `token_id` INT UNSIGNED DEFAULT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `department_id` INT UNSIGNED DEFAULT NULL,
    `service_id` INT UNSIGNED DEFAULT NULL,
    `staff_id` INT UNSIGNED DEFAULT NULL,
    `rating` TINYINT UNSIGNED NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `comment` TEXT DEFAULT NULL,
    `category` ENUM('service','staff','facility','wait_time','overall') DEFAULT 'overall',
    `is_resolved` TINYINT(1) DEFAULT 0,
    `admin_response` TEXT DEFAULT NULL,
    `responded_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`token_id`) REFERENCES `tokens`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`staff_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_feedback_branch` (`branch_id`),
    INDEX `idx_feedback_rating` (`rating`)
) ENGINE=InnoDB;

-- ============================================================
-- 15. COMPLAINTS
-- ============================================================
CREATE TABLE `complaints` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `department_id` INT UNSIGNED DEFAULT NULL,
    `token_id` INT UNSIGNED DEFAULT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `category` ENUM('service','staff','wait_time','facility','system','other') DEFAULT 'other',
    `priority` ENUM('low','medium','high','critical') DEFAULT 'medium',
    `status` ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
    `assigned_to` INT UNSIGNED DEFAULT NULL,
    `resolution` TEXT DEFAULT NULL,
    `resolved_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_complaint_status` (`status`)
) ENGINE=InnoDB;

-- ============================================================
-- 16. SLA RULES
-- ============================================================
CREATE TABLE `sla_rules` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `service_id` INT UNSIGNED NOT NULL,
    `max_wait_minutes` INT UNSIGNED NOT NULL DEFAULT 30,
    `max_service_minutes` INT UNSIGNED NOT NULL DEFAULT 20,
    `target_compliance_pct` DECIMAL(5,2) DEFAULT 90.00,
    `escalation_after_minutes` INT UNSIGNED DEFAULT 45,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_sla_service` (`service_id`)
) ENGINE=InnoDB;

-- ============================================================
-- 17. SERVICE RECORDS
-- ============================================================
CREATE TABLE `service_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `token_id` INT UNSIGNED NOT NULL,
    `staff_id` INT UNSIGNED NOT NULL,
    `counter_id` INT UNSIGNED NOT NULL,
    `service_id` INT UNSIGNED NOT NULL,
    `start_time` TIMESTAMP NOT NULL,
    `end_time` TIMESTAMP NULL DEFAULT NULL,
    `duration_minutes` INT UNSIGNED DEFAULT NULL,
    `sla_met` TINYINT(1) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`token_id`) REFERENCES `tokens`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`staff_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`counter_id`) REFERENCES `counters`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE,
    INDEX `idx_sr_staff` (`staff_id`),
    INDEX `idx_sr_date` (`start_time`)
) ENGINE=InnoDB;

-- ============================================================
-- 18. STAFF PERFORMANCE (Daily Aggregates)
-- ============================================================
CREATE TABLE `staff_performance` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `staff_id` INT UNSIGNED NOT NULL,
    `branch_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `tokens_served` INT UNSIGNED DEFAULT 0,
    `avg_service_time` DECIMAL(5,1) DEFAULT 0,
    `total_service_time` INT UNSIGNED DEFAULT 0 COMMENT 'In minutes',
    `idle_time` INT UNSIGNED DEFAULT 0 COMMENT 'In minutes',
    `sla_compliance_pct` DECIMAL(5,2) DEFAULT 0,
    `customer_rating_avg` DECIMAL(3,2) DEFAULT 0,
    `skipped_tokens` INT UNSIGNED DEFAULT 0,
    `transferred_tokens` INT UNSIGNED DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`staff_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_perf_staff_date` (`staff_id`, `date`),
    INDEX `idx_perf_date` (`date`)
) ENGINE=InnoDB;

-- ============================================================
-- 19. AUDIT LOGS
-- ============================================================
CREATE TABLE `audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(50) DEFAULT NULL,
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `old_values` JSON DEFAULT NULL,
    `new_values` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_created` (`created_at`),
    INDEX `idx_audit_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB;

-- ============================================================
-- 20. EMERGENCY ANNOUNCEMENTS
-- ============================================================
CREATE TABLE `emergency_announcements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `branch_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = all branches',
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `severity` ENUM('info','warning','critical') DEFAULT 'info',
    `is_active` TINYINT(1) DEFAULT 1,
    `starts_at` TIMESTAMP NULL DEFAULT NULL,
    `ends_at` TIMESTAMP NULL DEFAULT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 21. SYSTEM SETTINGS
-- ============================================================
CREATE TABLE `system_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('string','number','boolean','json') DEFAULT 'string',
    `category` VARCHAR(50) DEFAULT 'general',
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_by` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Add the FK from counters to tokens (deferred because tokens didn't exist yet)
ALTER TABLE `counters` ADD FOREIGN KEY (`current_token_id`) REFERENCES `tokens`(`id`) ON DELETE SET NULL;

COMMIT;
