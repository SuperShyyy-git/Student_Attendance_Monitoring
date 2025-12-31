<?php
include "../config/db_connect.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = $_POST['id'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

// Get student info
$checkSql = "SELECT student_id, firstname, lastname FROM students WHERE id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

$student = $result->fetch_assoc();
$checkStmt->close();

// Permanently delete the student
$sql = "DELETE FROM students WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => "Student {$student['firstname']} {$student['lastname']} has been permanently deleted"
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete student: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>