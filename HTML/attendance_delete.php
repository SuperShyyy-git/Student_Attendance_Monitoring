<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include __DIR__ . "/../config/db_connect.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = intval($_POST['delete_id']);
    $userId = $_SESSION['user_id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // First, check if archived_attendance table exists, if not create it
        $createTableSQL = "CREATE TABLE IF NOT EXISTS archived_attendance (
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
        )";
        $conn->query($createTableSQL);

        // Get the record to archive
        $selectStmt = $conn->prepare("SELECT id, student_id, student_name, section, grade_level, attendance_date, attendance_time, status, image_path FROM student_attendance WHERE id = ?");
        $selectStmt->bind_param('i', $deleteId);
        $selectStmt->execute();
        $result = $selectStmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception('Record not found');
        }

        $record = $result->fetch_assoc();
        $selectStmt->close();

        // Insert into archive
        $archiveStmt = $conn->prepare("INSERT INTO archived_attendance (original_id, student_id, student_name, section, grade_level, attendance_date, attendance_time, status, image_path, archived_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $archiveStmt->bind_param(
            'issssssssi',
            $record['id'],
            $record['student_id'],
            $record['student_name'],
            $record['section'],
            $record['grade_level'],
            $record['attendance_date'],
            $record['attendance_time'],
            $record['status'],
            $record['image_path'],
            $userId
        );

        if (!$archiveStmt->execute()) {
            throw new Exception('Failed to insert into archive');
        }
        $archiveStmt->close();

        // Delete from original table
        $deleteStmt = $conn->prepare("DELETE FROM student_attendance WHERE id = ?");
        $deleteStmt->bind_param('i', $deleteId);

        if (!$deleteStmt->execute()) {
            throw new Exception('Failed to delete original record');
        }
        $deleteStmt->close();

        // Commit transaction
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Attendance record archived successfully']);

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to archive record: ' . $e->getMessage()]);
    }

    $conn->close();
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>