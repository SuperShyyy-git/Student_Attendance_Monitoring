<?php
include __DIR__ . "/../config/db_connect.php";
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');

    if (empty($id) || empty($name)) {
        echo json_encode(["success" => false, "message" => "ID and Name are required"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE advisers SET name = ?, contact = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $contact, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Adviser updated"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
    }
    $stmt->close();
}
?>