-- Create archived_attendance table to store deleted records
CREATE TABLE IF NOT EXISTS archived_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT NOT NULL,
    student_id VARCHAR(50),
    student_name VARCHAR(255) NOT NULL,
    section VARCHAR(100),
    grade_level VARCHAR(50),
    attendance_date DATE NOT NULL,
    attendance_time TIME NOT NULL,
    status VARCHAR(50) NOT NULL,
    image_path VARCHAR(255),
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by INT,
    INDEX idx_student_id (student_id),
    INDEX idx_student (student_name),
    INDEX idx_date (attendance_date),
    INDEX idx_archived_at (archived_at)
);
