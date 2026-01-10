<?php
session_start();
include __DIR__ . "/../config/db_connect.php";
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $archiveId = intval($_POST['archive_id'] ?? 0);

    if (empty($archiveId)) {
        echo json_encode(["success" => false, "message" => "Archive ID is required"]);
        exit;
    }

    // Permanently delete archived adviser
    $stmt = $conn->prepare("DELETE FROM archived_advisers WHERE id = ?");
    $stmt->bind_param('i', $archiveId);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Archived adviser deleted permanently"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
    }

    $stmt->close();
}
?>