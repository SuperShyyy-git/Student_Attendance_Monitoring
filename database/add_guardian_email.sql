-- ============================================================================
-- Migration: Add guardian_email column for Gmail notifications
-- Run this in phpMyAdmin to add the email column to students table
-- ============================================================================

ALTER TABLE `students` ADD COLUMN `guardian_email` VARCHAR(255) NULL AFTER `guardian_contact`;

-- Verify the column was added:
-- DESCRIBE students;
