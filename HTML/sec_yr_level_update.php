<?php
include __DIR__ . "/../config/db_connect.php";
header("Content-Type: application/json");

$id = intval($_POST['id'] ?? 0);
$section = trim($_POST['section'] ?? '');
$grade = trim($_POST['grade_level'] ?? '');
$adviser_id = !empty($_POST['adviser_id']) ? intval($_POST['adviser_id']) : null;

if ($section === '' || $grade === '' || $id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$stmt = $conn->prepare("UPDATE section_yrlevel SET section=?, grade_level=?, adviser_id=? WHERE id=?");
$stmt->bind_param("ssii", $section, $grade, $adviser_id, $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => $conn->error]);
}
?>