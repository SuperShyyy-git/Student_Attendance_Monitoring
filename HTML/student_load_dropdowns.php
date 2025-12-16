<?php
include "../config/db_connect.php";
header("Content-Type: application/json");

$data = [];

// Load year levels
$years = $conn->query("SELECT DISTINCT year_level FROM section_yrlevel ORDER BY year_level");
$year_list = [];

while ($y = $years->fetch_assoc()) {
    $year_list[] = $y['year_level'];
}

// Load sections
$sections = $conn->query("SELECT DISTINCT section FROM section_yrlevel ORDER BY section");
$section_list = [];

while ($s = $sections->fetch_assoc()) {
    $section_list[] = $s['section'];
}

echo json_encode([
    "success" => true,
    "year_levels" => $year_list,
    "sections" => $section_list
]);
?>
