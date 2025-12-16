<?php
include "../config/db_connect.php";
header("Content-Type: application/json");

$section = $_POST['section'] ?? '';
$year_level = $_POST['year_level'] ?? '';

$stmt = $conn->prepare("INSERT INTO section_yrlevel (section, year_level) VALUES (?, ?)");
$stmt->bind_param("ss", $section, $year_level);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => $conn->error]);
}
?>
