-- Add adviser_id column to advisers table
-- This should run after existing data is in place
ALTER TABLE advisers ADD COLUMN IF NOT EXISTS adviser_id VARCHAR(20) UNIQUE;

-- Generate unique adviser IDs for existing records
-- Format: ADV-XXXX (ADV- prefix with 4-digit number)
UPDATE advisers 
SET adviser_id = CONCAT('ADV-', LPAD(id, 4, '0'))
WHERE adviser_id IS NULL;
