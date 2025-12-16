<?php
include "../config/db_connect.php";
header("Content-Type: application/json");

$section = $_POST['section'] ?? '';
$grade_level = $_POST['grade_level'] ?? '';
$adviser_id = !empty($_POST['adviser_id']) ? intval($_POST['adviser_id']) : null;

$stmt = $conn->prepare("INSERT INTO section_yrlevel (section, grade_level, adviser_id) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $section, $grade_level, $adviser_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => $conn->error]);
}
?>