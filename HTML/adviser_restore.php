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

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get archived adviser data
        $selectStmt = $conn->prepare("SELECT original_id, adviser_id, name, contact FROM archived_advisers WHERE id = ?");
        $selectStmt->bind_param('i', $archiveId);
        $selectStmt->execute();
        $result = $selectStmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception('Archived adviser not found');
        }

        $adviser = $result->fetch_assoc();
        $selectStmt->close();

        // Restore to advisers table
        $restoreStmt = $conn->prepare("INSERT INTO advisers (adviser_id, name, contact) VALUES (?, ?, ?)");
        $restoreStmt->bind_param(
            'sss',
            $adviser['adviser_id'],
            $adviser['name'],
            $adviser['contact']
        );

        if (!$restoreStmt->execute()) {
            throw new Exception('Failed to restore adviser');
        }
        $restoreStmt->close();

        // Delete from archive
        $deleteStmt = $conn->prepare("DELETE FROM archived_advisers WHERE id = ?");
        $deleteStmt->bind_param('i', $archiveId);

        if (!$deleteStmt->execute()) {
            throw new Exception('Failed to remove from archive');
        }
        $deleteStmt->close();

        // Commit transaction
        $conn->commit();

        echo json_encode(["success" => true, "message" => "Adviser restored successfully"]);

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
}
?>