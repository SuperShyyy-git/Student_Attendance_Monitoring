<?php
session_start();
include __DIR__ . "/../config/db_connect.php";
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $userId = $_SESSION['user_id'] ?? 0;

    if (empty($id)) {
        echo json_encode(["success" => false, "message" => "ID is required"]);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Create archived_advisers table if it doesn't exist
        $createTableSQL = "CREATE TABLE IF NOT EXISTS archived_advisers (
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
        )";
        $conn->query($createTableSQL);

        // Get the adviser record to archive
        $selectStmt = $conn->prepare("SELECT id, adviser_id, name, contact FROM advisers WHERE id = ?");
        $selectStmt->bind_param('i', $id);
        $selectStmt->execute();
        $result = $selectStmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception('Adviser not found');
        }

        $adviser = $result->fetch_assoc();
        $selectStmt->close();

        // Generate adviser_id if not exists
        if (empty($adviser['adviser_id'])) {
            $adviser['adviser_id'] = 'ADV-' . str_pad($adviser['id'], 4, '0', STR_PAD_LEFT);
        }

        // Insert into archive
        $archiveStmt = $conn->prepare("INSERT INTO archived_advisers (original_id, adviser_id, name, contact, archived_by) VALUES (?, ?, ?, ?, ?)");
        $archiveStmt->bind_param(
            'isssi',
            $adviser['id'],
            $adviser['adviser_id'],
            $adviser['name'],
            $adviser['contact'],
            $userId
        );

        if (!$archiveStmt->execute()) {
            throw new Exception('Failed to insert into archive');
        }
        $archiveStmt->close();

        // Delete from original table
        $deleteStmt = $conn->prepare("DELETE FROM advisers WHERE id = ?");
        $deleteStmt->bind_param('i', $id);

        if (!$deleteStmt->execute()) {
            throw new Exception('Failed to delete original record');
        }
        $deleteStmt->close();

        // Commit transaction
        $conn->commit();

        echo json_encode(["success" => true, "message" => "Adviser archived successfully"]);

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
}
?>