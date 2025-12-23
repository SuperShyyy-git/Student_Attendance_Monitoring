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

    $stmt = $conn->prepare("INSERT INTO advisers (name, contact) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $contact);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Adviser added"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
    }
    $stmt->close();
}
?>