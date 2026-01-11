-- FIX FOR MANUAL OVERRIDE - Change student_id column type to match students table
-- This fixes the issue where manual override records don't appear on Student Attendance page

-- Step 1: Change student_id from INT to VARCHAR to match students.student_id
ALTER TABLE student_attendance 
MODIFY COLUMN student_id VARCHAR(50);

-- Step 2: Verify the change
DESCRIBE student_attendance;

-- After running this SQL:
-- 1. Go to Manual Override
-- 2. Add a NEW record for TODAY (2026-01-11)
-- 3. Select any student
-- 4. Choose TIME IN
-- 5. Click Add
-- 6. Check Student Attendance page - it will now appear!
