-- Add is_archived column to students table for soft delete
ALTER TABLE students ADD COLUMN IF NOT EXISTS is_archived TINYINT(1) DEFAULT 0;
ALTER TABLE students ADD COLUMN IF NOT EXISTS archived_at DATETIME DEFAULT NULL;

-- Create index for faster queries
ALTER TABLE students ADD INDEX idx_is_archived (is_archived);
