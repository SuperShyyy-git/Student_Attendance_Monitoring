<?php
include __DIR__ . "/../config/db_connect.php";
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');

    if (empty($name)) {
        echo json_encode(["success" => false, "message" => "Name is required"]);
        exit;
    }

    // First, insert to get the ID
    $stmt = $conn->prepare("INSERT INTO advisers (name, contact) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $contact);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;

        // Generate unique adviser_id
        $adviserId = 'ADV-' . str_pad($newId, 4, '0', STR_PAD_LEFT);

        // Update with adviser_id
        $updateStmt = $conn->prepare("UPDATE advisers SET adviser_id = ? WHERE id = ?");
        $updateStmt->bind_param("si", $adviserId, $newId);
        $updateStmt->execute();
        $updateStmt->close();

        echo json_encode(["success" => true, "message" => "Adviser added successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
    }
    $stmt->close();
}
?>