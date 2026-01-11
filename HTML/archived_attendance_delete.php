<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include __DIR__ . "/../config/db_connect.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

header('Content-Type: application/json');

if (isset($_POST['delete_archived_id'])) {
    $archiveId = (int) $_POST['delete_archived_id'];

    // Delete from archived_attendance table
    $stmt = $conn->prepare("DELETE FROM archived_attendance WHERE id = ?");
    $stmt->bind_param('i', $archiveId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Archived record deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete archived record: ' . $conn->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>