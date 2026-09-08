-- ============================================================
-- TokenFlow Pro — Seed Data
-- Realistic demo data for immediate demonstration
-- ============================================================

USE `tokenflow_pro`;

-- ============================================================
-- 1. ORGANIZATION
-- ============================================================
INSERT INTO `organizations` (`id`, `name`, `slug`, `email`, `phone`, `website`, `address`) VALUES
(1, 'MetroCity General Services', 'metrocity', 'admin@metrocity.gov', '+91-44-2345-6789', 'https://metrocity.gov', '123 Civic Center Road, Chennai, TN 600001');

-- ============================================================
-- 2. BRANCHES
-- ============================================================
INSERT INTO `branches` (`id`, `org_id`, `name`, `code`, `address`, `city`, `state`, `phone`, `opening_time`, `closing_time`) VALUES
(1, 1, 'Main Branch - Anna Nagar', 'MN', '15 Anna Nagar Main Road, Chennai', 'Chennai', 'Tamil Nadu', '+91-44-2345-0001', '08:00:00', '18:00:00'),
(2, 1, 'T. Nagar Branch', 'TN', '42 Usman Road, T. Nagar, Chennai', 'Chennai', 'Tamil Nadu', '+91-44-2345-0002', '09:00:00', '17:00:00');

-- ============================================================
-- 3. DEPARTMENTS
-- ============================================================
INSERT INTO `departments` (`id`, `branch_id`, `name`, `code`, `description`, `icon`, `color`, `token_prefix`, `sort_order`) VALUES
(1, 1, 'General Consultation', 'GC', 'General walk-in consultations', 'bi-chat-dots', '#38bdf8', 'A', 1),
(2, 1, 'Billing & Payments', 'BP', 'Billing, payments, and receipts', 'bi-receipt', '#a78bfa', 'B', 2),
(3, 1, 'Document Services', 'DS', 'Document processing and verification', 'bi-file-earmark-check', '#34d399', 'C', 3),
(4, 1, 'Customer Support', 'CS', 'Issue resolution and support', 'bi-headset', '#fbbf24', 'D', 4),
(5, 1, 'Registration', 'RG', 'New registrations and onboarding', 'bi-person-plus', '#f87171', 'E', 5),
(6, 2, 'General Consultation', 'GC', 'General walk-in consultations', 'bi-chat-dots', '#38bdf8', 'A', 1);

-- ============================================================
-- 4. SERVICES
-- ============================================================
INSERT INTO `services` (`id`, `department_id`, `name`, `description`, `icon`, `avg_service_time`, `max_service_time`, `sort_order`) VALUES
(1, 1, 'General Inquiry', 'General questions and information', 'bi-info-circle', 7, 15, 1),
(2, 1, 'Consultation', 'One-on-one consultation session', 'bi-chat-left-text', 12, 30, 2),
(3, 1, 'Follow-up Visit', 'Follow-up on previous consultation', 'bi-arrow-repeat', 8, 20, 3),
(4, 2, 'Bill Payment', 'Pay outstanding bills', 'bi-credit-card', 5, 10, 1),
(5, 2, 'Refund Request', 'Process refund requests', 'bi-arrow-counterclockwise', 10, 20, 2),
(6, 2, 'Account Statement', 'Generate account statement', 'bi-file-text', 8, 15, 3),
(7, 3, 'Certificate Issuance', 'Issue official certificates', 'bi-patch-check', 15, 30, 1),
(8, 3, 'Document Verification', 'Verify submitted documents', 'bi-shield-check', 10, 25, 2),
(9, 3, 'Application Processing', 'Process new applications', 'bi-clipboard-data', 20, 40, 3),
(10, 4, 'Complaint Resolution', 'Resolve customer complaints', 'bi-exclamation-triangle', 15, 30, 1),
(11, 4, 'Technical Support', 'Technical issue assistance', 'bi-tools', 12, 25, 2),
(12, 5, 'New Registration', 'Register as a new member', 'bi-person-plus-fill', 10, 20, 1),
(13, 5, 'ID Card Issuance', 'Issue new ID cards', 'bi-person-vcard', 8, 15, 2),
(14, 6, 'General Inquiry', 'General questions and information', 'bi-info-circle', 7, 15, 1);

-- ============================================================
-- 5. USERS (password: admin123 / staff123 / customer123)
-- ============================================================
INSERT INTO `users` (`id`, `org_id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `is_active`) VALUES
-- Admin
(1, 1, 'Akilesh Kumar', 'admin@tokenflow.com', '+91-98765-43210', '$2y$12$LJ3m/6jQ4Z8F.HgVQjvJxO8x5Dz.YgQqKj3nE6YwKfQHVxYhGJzSi', 'admin', 1),
-- Staff
(2, 1, 'Priya Sharma', 'staff@tokenflow.com', '+91-98765-43211', '$2y$12$1pZr5g4X3YZH8KvJk9Q7YeN5tY2w.FpQ6rM3dS9xL1kA4vR7BnGmC', 'staff', 1),
(3, 1, 'Rajesh Patel', 'rajesh.staff@tokenflow.com', '+91-98765-43212', '$2y$12$1pZr5g4X3YZH8KvJk9Q7YeN5tY2w.FpQ6rM3dS9xL1kA4vR7BnGmC', 'staff', 1),
(4, 1, 'Anitha Rajan', 'anitha.staff@tokenflow.com', '+91-98765-43213', '$2y$12$1pZr5g4X3YZH8KvJk9Q7YeN5tY2w.FpQ6rM3dS9xL1kA4vR7BnGmC', 'staff', 1),
(5, 1, 'Mohammed Ali', 'ali.staff@tokenflow.com', '+91-98765-43214', '$2y$12$1pZr5g4X3YZH8KvJk9Q7YeN5tY2w.FpQ6rM3dS9xL1kA4vR7BnGmC', 'staff', 1),
(6, 1, 'Lakshmi Devi', 'lakshmi.staff@tokenflow.com', '+91-98765-43215', '$2y$12$1pZr5g4X3YZH8KvJk9Q7YeN5tY2w.FpQ6rM3dS9xL1kA4vR7BnGmC', 'staff', 1),
-- Customers
(7, 1, 'Akilesh Ramanathan', 'customer@tokenflow.com', '+91-98765-43220', '$2y$12$wN8rA3pX2vQ6kG7jH5mF4eL9dI0sB1tC3uR5yO7wE6xS8nJ0fK2vA', 'customer', 1),
(8, 1, 'Deepa Suresh', 'deepa@example.com', '+91-98765-43221', '$2y$12$wN8rA3pX2vQ6kG7jH5mF4eL9dI0sB1tC3uR5yO7wE6xS8nJ0fK2vA', 'customer', 1),
(9, 1, 'Vikram Singh', 'vikram@example.com', '+91-98765-43222', '$2y$12$wN8rA3pX2vQ6kG7jH5mF4eL9dI0sB1tC3uR5yO7wE6xS8nJ0fK2vA', 'customer', 1),
(10, 1, 'Meera Krishnan', 'meera@example.com', '+91-98765-43223', '$2y$12$wN8rA3pX2vQ6kG7jH5mF4eL9dI0sB1tC3uR5yO7wE6xS8nJ0fK2vA', 'customer', 1),
(11, 1, 'Arjun Nair', 'arjun@example.com', '+91-98765-43224', '$2y$12$wN8rA3pX2vQ6kG7jH5mF4eL9dI0sB1tC3uR5yO7wE6xS8nJ0fK2vA', 'customer', 1),
(12, 1, 'Kavitha Murthy', 'kavitha@example.com', '+91-98765-43225', '$2y$12$wN8rA3pX2vQ6kG7jH5mF4eL9dI0sB1tC3uR5yO7wE6xS8nJ0fK2vA', 'customer', 1);

-- ============================================================
-- 6. COUNTERS
-- ============================================================
INSERT INTO `counters` (`id`, `branch_id`, `name`, `number`, `status`) VALUES
(1, 1, 'Counter 1', 1, 'open'),
(2, 1, 'Counter 2', 2, 'open'),
(3, 1, 'Counter 3', 3, 'open'),
(4, 1, 'Counter 4', 4, 'closed'),
(5, 1, 'Counter 5', 5, 'open'),
(6, 1, 'Counter 6', 6, 'closed'),
(7, 2, 'Counter 1', 1, 'open'),
(8, 2, 'Counter 2', 2, 'open');

-- ============================================================
-- 7. STAFF ASSIGNMENTS
-- ============================================================
INSERT INTO `staff_assignments` (`user_id`, `branch_id`, `department_id`, `counter_id`, `is_online`, `shift_start`, `shift_end`) VALUES
(2, 1, 1, 1, 1, '08:00:00', '16:00:00'),
(3, 1, 1, 2, 1, '08:00:00', '16:00:00'),
(4, 1, 2, 3, 1, '08:00:00', '16:00:00'),
(5, 1, 3, 5, 1, '08:00:00', '16:00:00'),
(6, 2, 6, 7, 1, '09:00:00', '17:00:00');

-- ============================================================
-- 8. SLA RULES
-- ============================================================
INSERT INTO `sla_rules` (`service_id`, `max_wait_minutes`, `max_service_minutes`, `target_compliance_pct`) VALUES
(1, 20, 15, 90.00),
(2, 30, 30, 85.00),
(3, 25, 20, 90.00),
(4, 15, 10, 95.00),
(5, 25, 20, 85.00),
(6, 20, 15, 90.00),
(7, 30, 30, 85.00),
(8, 25, 25, 85.00),
(9, 35, 40, 80.00),
(10, 20, 30, 85.00),
(11, 20, 25, 85.00),
(12, 20, 20, 90.00),
(13, 15, 15, 90.00),
(14, 20, 15, 90.00);

-- ============================================================
-- 9. TOKENS (Today's active queue + historical data)
-- ============================================================

-- Today's tokens
INSERT INTO `tokens` (`token_number`, `display_number`, `user_id`, `branch_id`, `department_id`, `service_id`, `counter_id`, `served_by`, `type`, `status`, `priority_score`, `estimated_wait_minutes`, `actual_wait_minutes`, `service_start_time`, `actual_service_minutes`, `date`, `created_at`) VALUES
-- Completed tokens (earlier today)
('A001', 'A-001', 8, 1, 1, 1, 1, 2, 'normal', 'completed', 0, 10, 8, DATE_FORMAT(NOW() - INTERVAL 4 HOUR, '%Y-%m-%d %H:%i:%s'), 6, CURDATE(), NOW() - INTERVAL 5 HOUR),
('A002', 'A-002', 9, 1, 1, 2, 2, 3, 'normal', 'completed', 0, 15, 12, DATE_FORMAT(NOW() - INTERVAL 3 HOUR, '%Y-%m-%d %H:%i:%s'), 9, CURDATE(), NOW() - INTERVAL 4 HOUR),
('A003', 'A-003', 10, 1, 1, 1, 1, 2, 'priority', 'completed', 100, 5, 4, DATE_FORMAT(NOW() - INTERVAL 2 HOUR, '%Y-%m-%d %H:%i:%s'), 7, CURDATE(), NOW() - INTERVAL 3 HOUR),
('B001', 'B-001', 11, 1, 2, 4, 3, 4, 'normal', 'completed', 0, 8, 6, DATE_FORMAT(NOW() - INTERVAL 3 HOUR, '%Y-%m-%d %H:%i:%s'), 4, CURDATE(), NOW() - INTERVAL 4 HOUR),
('B002', 'B-002', 12, 1, 2, 5, 3, 4, 'normal', 'completed', 0, 12, 10, DATE_FORMAT(NOW() - INTERVAL 2 HOUR, '%Y-%m-%d %H:%i:%s'), 8, CURDATE(), NOW() - INTERVAL 3 HOUR),
('C001', 'C-001', 8, 1, 3, 7, 5, 5, 'normal', 'completed', 0, 15, 14, DATE_FORMAT(NOW() - INTERVAL 2 HOUR, '%Y-%m-%d %H:%i:%s'), 12, CURDATE(), NOW() - INTERVAL 3 HOUR),

-- Currently serving
('A004', 'A-004', NULL, 1, 1, 2, 1, 2, 'normal', 'serving', 0, 20, 18, DATE_FORMAT(NOW() - INTERVAL 8 MINUTE, '%Y-%m-%d %H:%i:%s'), NULL, CURDATE(), NOW() - INTERVAL 30 MINUTE),
('B003', 'B-003', NULL, 1, 2, 4, 3, 4, 'normal', 'serving', 0, 10, 9, DATE_FORMAT(NOW() - INTERVAL 5 MINUTE, '%Y-%m-%d %H:%i:%s'), NULL, CURDATE(), NOW() - INTERVAL 20 MINUTE),

-- Waiting tokens
('A005', 'A-005', 7, 1, 1, 1, NULL, NULL, 'normal', 'waiting', 0, 14, NULL, NULL, NULL, CURDATE(), NOW() - INTERVAL 18 MINUTE),
('A006', 'A-006', NULL, 1, 1, 2, NULL, NULL, 'normal', 'waiting', 0, 21, NULL, NULL, NULL, CURDATE(), NOW() - INTERVAL 15 MINUTE),
('A007', 'A-007', NULL, 1, 1, 1, NULL, NULL, 'priority', 'waiting', 100, 7, NULL, NULL, NULL, CURDATE(), NOW() - INTERVAL 10 MINUTE),
('A008', 'A-008', 9, 1, 1, 3, NULL, NULL, 'normal', 'waiting', 0, 28, NULL, NULL, NULL, CURDATE(), NOW() - INTERVAL 8 MINUTE),
('A009', 'A-009', NULL, 1, 1, 1, NULL, NULL, 'normal', 'waiting', 0, 35, NULL, NULL, NULL, CURDATE(), NOW() - INTERVAL 5 MINUTE),
('B004', 'B-004', 10, 1, 2, 6, NULL, NULL, 'normal', 'waiting', 0, 12, NULL, NULL, NULL, CURDATE(), NOW() - INTERVAL 12 MINUTE),
('B005', 'B-005', NULL, 1, 2, 4, NULL, NULL, 'normal', 'waiting', 0, 18, NULL, NULL, NULL, CURDATE(), NOW() - INTERVAL 6 MINUTE),
('C002', 'C-002', 11, 1, 3, 8, NULL, NULL, 'normal', 'waiting', 0, 15, NULL, NULL, NULL, CURDATE(), NOW() - INTERVAL 10 MINUTE),
('C003', 'C-003', NULL, 1, 3, 9, NULL, NULL, 'normal', 'waiting', 0, 25, NULL, NULL, NULL, CURDATE(), NOW() - INTERVAL 4 MINUTE),
('D001', 'D-001', 12, 1, 4, 10, NULL, NULL, 'normal', 'waiting', 0, 20, NULL, NULL, NULL, CURDATE(), NOW() - INTERVAL 15 MINUTE),
('E001', 'E-001', NULL, 1, 5, 12, NULL, NULL, 'normal', 'waiting', 0, 10, NULL, NULL, NULL, CURDATE(), NOW() - INTERVAL 8 MINUTE);

-- Update counters with current serving tokens
UPDATE `counters` SET `current_token_id` = (SELECT id FROM tokens WHERE display_number = 'A-004' AND date = CURDATE()) WHERE `id` = 1;
UPDATE `counters` SET `current_token_id` = (SELECT id FROM tokens WHERE display_number = 'B-003' AND date = CURDATE()) WHERE `id` = 3;

-- ============================================================
-- 10. FEEDBACK (historical)
-- ============================================================
INSERT INTO `feedback` (`user_id`, `branch_id`, `department_id`, `rating`, `comment`, `category`, `created_at`) VALUES
(7, 1, 1, 5, 'Excellent service, very quick!', 'service', NOW() - INTERVAL 1 DAY),
(8, 1, 1, 4, 'Good experience, minimal wait.', 'overall', NOW() - INTERVAL 2 DAY),
(9, 1, 2, 5, 'Staff was very helpful and professional.', 'staff', NOW() - INTERVAL 1 DAY),
(10, 1, 3, 3, 'Wait time was a bit long.', 'wait_time', NOW() - INTERVAL 3 DAY),
(11, 1, 1, 4, 'Nice environment, smooth process.', 'facility', NOW() - INTERVAL 2 DAY),
(12, 1, 2, 5, 'Quick billing, no issues at all.', 'service', NOW() - INTERVAL 1 DAY),
(7, 1, 4, 4, 'Issue resolved satisfactorily.', 'service', NOW() - INTERVAL 4 DAY),
(8, 1, 1, 5, 'Best experience so far!', 'overall', NOW() - INTERVAL 5 DAY);

-- ============================================================
-- 11. NOTIFICATIONS (for demo user)
-- ============================================================
INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `icon`, `is_read`, `created_at`) VALUES
(7, 'token', 'Token Generated: A-005', 'Your token A-005 has been generated. Estimated wait: ~14 min.', 'bi-ticket-perforated', 0, NOW() - INTERVAL 18 MINUTE),
(7, 'system', 'Welcome to TokenFlow Pro!', 'Your account is ready. Generate your first token to get started.', 'bi-stars', 1, NOW() - INTERVAL 7 DAY),
(7, 'appointment', 'Appointment Reminder', 'Your appointment for Document Verification is tomorrow at 10:00 AM.', 'bi-calendar-check', 0, NOW() - INTERVAL 1 HOUR),
(1, 'system', 'System Health: All Operational', 'All counters and services are running normally.', 'bi-shield-check', 0, NOW() - INTERVAL 30 MINUTE),
(1, 'system', 'Peak Hour Alert', 'Expected high demand between 10 AM - 12 PM. Ensure adequate staffing.', 'bi-graph-up', 0, NOW() - INTERVAL 2 HOUR);

-- ============================================================
-- 12. SYSTEM SETTINGS
-- ============================================================
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `description`) VALUES
('org_name', 'MetroCity General Services', 'string', 'branding', 'Organization display name'),
('org_tagline', 'Serving Citizens with Excellence', 'string', 'branding', 'Organization tagline'),
('default_language', 'en', 'string', 'general', 'Default application language'),
('voice_announcements', 'true', 'boolean', 'queue', 'Enable voice announcements'),
('queue_poll_interval', '3000', 'number', 'queue', 'Queue polling interval in ms'),
('max_daily_tokens', '500', 'number', 'queue', 'Maximum tokens per department per day'),
('enable_appointments', 'true', 'boolean', 'features', 'Enable appointment booking'),
('enable_virtual_queue', 'true', 'boolean', 'features', 'Enable virtual queue joining'),
('enable_feedback', 'true', 'boolean', 'features', 'Enable customer feedback'),
('working_hours_start', '08:00', 'string', 'general', 'Working hours start'),
('working_hours_end', '18:00', 'string', 'general', 'Working hours end');

-- ============================================================
-- 13. APPOINTMENTS
-- ============================================================
INSERT INTO `appointments` (`user_id`, `branch_id`, `department_id`, `service_id`, `appointment_date`, `appointment_time`, `status`, `notes`) VALUES
(7, 1, 3, 8, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 'confirmed', 'Document verification for certificate'),
(8, 1, 1, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:30:00', 'scheduled', 'Follow-up consultation'),
(9, 1, 2, 5, CURDATE(), '15:00:00', 'confirmed', 'Refund processing');
