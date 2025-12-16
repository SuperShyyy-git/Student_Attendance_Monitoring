<?php
include "../config/db_connect.php";
header("Content-Type: application/json");

$section = $_POST['section'] ?? '';
$grade_level = $_POST['grade_level'] ?? '';

$stmt = $conn->prepare("INSERT INTO section_yrlevel (section, grade_level) VALUES (?, ?)");
$stmt->bind_param("ss", $section, $grade_level);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => $conn->error]);
}
?>