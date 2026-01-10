-- Create archived_advisers table
CREATE TABLE IF NOT EXISTS archived_advisers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT NOT NULL,
    adviser_id VARCHAR(20),
    name VARCHAR(255) NOT NULL,
    contact VARCHAR(50),
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by INT,
    INDEX idx_adviser_id (adviser_id),
    INDEX idx_name (name),
    INDEX idx_archived_at (archived_at)
);
