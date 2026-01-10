-- Add student_id column to student_attendance table if it doesn't exist
ALTER TABLE student_attendance 
ADD COLUMN student_id VARCHAR(50) AFTER id;

-- Create index for faster lookups
CREATE INDEX idx_student_id ON student_attendance(student_id);
CREATE INDEX idx_date_status ON student_attendance(attendance_date, status);
