<?php
include __DIR__ . "/../config/db_connect.php";
header("Content-Type: application/json");

$id = intval($_POST['id'] ?? 0);
$section = trim($_POST['section'] ?? '');
$year = trim($_POST['year_level'] ?? '');

if ($id <= 0 || $section === '' || $year === '') {
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit;
}

$stmt = $conn->prepare("UPDATE section_yrlevel SET section=?, year_level=? WHERE id=?");
$stmt->bind_param("ssi", $section, $year, $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => $conn->error]);
}
?>
