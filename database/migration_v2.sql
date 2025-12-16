-- ============================================================================
-- Migration V2: Database Schema Revisions (Panelist Feedback)
-- Date: 2025-12-16
-- ============================================================================
-- Changes:
-- 1. Remove rfid_code column from students table
-- 2. Add address column to students table
-- 3. Create advisers table
-- 4. Add adviser_id to section_yrlevel table
-- 5. Rename year_level → grade_level in all tables
-- ============================================================================

-- Start transaction
START TRANSACTION;

-- ============================================================================
-- 1. CREATE ADVISERS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `advisers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL,
    `contact` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample adviser
INSERT INTO `advisers` (`name`, `contact`) VALUES ('Default Adviser', '09000000000');

-- ============================================================================
-- 2. MODIFY section_yrlevel TABLE
-- ============================================================================
-- Rename year_level to grade_level
ALTER TABLE `section_yrlevel` 
    CHANGE COLUMN `year_level` `grade_level` VARCHAR(50) NOT NULL;

-- Add adviser_id column
ALTER TABLE `section_yrlevel` 
    ADD COLUMN `adviser_id` INT(11) NULL AFTER `grade_level`,
    ADD CONSTRAINT `fk_section_adviser` 
        FOREIGN KEY (`adviser_id`) REFERENCES `advisers`(`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE;

-- ============================================================================
-- 3. MODIFY students TABLE
-- ============================================================================
-- Remove rfid_code column
ALTER TABLE `students` 
    DROP COLUMN `rfid_code`;

-- Add address column
ALTER TABLE `students` 
    ADD COLUMN `address` VARCHAR(255) NULL AFTER `lastname`;

-- Rename year_level to grade_level
ALTER TABLE `students` 
    CHANGE COLUMN `year_level` `grade_level` VARCHAR(50) NOT NULL;

-- ============================================================================
-- 4. MODIFY student_attendance TABLE
-- ============================================================================
-- Rename year_level to grade_level
ALTER TABLE `student_attendance` 
    CHANGE COLUMN `year_level` `grade_level` VARCHAR(50) NULL;

-- ============================================================================
-- 5. UPDATE EXISTING DATA (convert Grade 9, 10, 11 labels if needed)
-- ============================================================================
-- No data transformation needed, labels remain the same

-- Commit transaction
COMMIT;

-- ============================================================================
-- VERIFICATION QUERIES (run after migration)
-- ============================================================================
-- DESCRIBE students;
-- DESCRIBE section_yrlevel;
-- DESCRIBE student_attendance;
-- DESCRIBE advisers;
-- SELECT * FROM advisers;
