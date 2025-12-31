-- ============================================================================
-- COMPLETE DATABASE FIX SCRIPT
-- Run this in phpMyAdmin to fix all missing columns
-- ============================================================================

-- 1. Create advisers table if not exists
CREATE TABLE IF NOT EXISTS `advisers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL,
    `contact` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert a default adviser if table is empty
INSERT INTO `advisers` (`name`, `contact`) 
SELECT 'Default Adviser', '09000000000'
WHERE NOT EXISTS (SELECT 1 FROM `advisers` LIMIT 1);

-- 2. Fix section_yrlevel table
-- Add adviser_id if missing
ALTER TABLE `section_yrlevel` ADD COLUMN IF NOT EXISTS `adviser_id` INT(11) NULL;

-- Rename year_level to grade_level (ignore error if already renamed)
-- Note: Run this manually if it fails: ALTER TABLE section_yrlevel CHANGE COLUMN year_level grade_level VARCHAR(50) NOT NULL;

-- 3. Fix students table
-- Add address column if missing
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `address` VARCHAR(255) NULL;

-- Rename year_level to grade_level if needed
-- Note: Run this manually if it fails: ALTER TABLE students CHANGE COLUMN year_level grade_level VARCHAR(50) NOT NULL;

-- 4. Fix student_attendance table  
-- Rename year_level to grade_level if needed
-- Note: Run this manually if it fails: ALTER TABLE student_attendance CHANGE COLUMN year_level grade_level VARCHAR(50) NULL;

-- ============================================================================
-- IF YOU GET ERRORS, run these one at a time:
-- ============================================================================
-- ALTER TABLE `section_yrlevel` CHANGE COLUMN `year_level` `grade_level` VARCHAR(50) NOT NULL;
-- ALTER TABLE `students` CHANGE COLUMN `year_level` `grade_level` VARCHAR(50) NOT NULL;
-- ALTER TABLE `student_attendance` CHANGE COLUMN `year_level` `grade_level` VARCHAR(50) NULL;
-- ============================================================================
