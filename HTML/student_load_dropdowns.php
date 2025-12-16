<?php
include "../config/db_connect.php";
header("Content-Type: application/json");

$data = [];

// Load year levels
$grades = $conn->query("SELECT DISTINCT grade_level FROM section_yrlevel ORDER BY grade_level");
$grade_list = [];
if ($grades) {
    while ($g = $grades->fetch_assoc()) {
        $grade_list[] = $g['grade_level'];
    }
}

// Load sections
$sections = $conn->query("SELECT DISTINCT section FROM section_yrlevel ORDER BY section");
$section_list = [];

while ($s = $sections->fetch_assoc()) {
    $section_list[] = $s['section'];
}

echo json_encode([
    "success" => true,
    "grade_levels" => $grade_list,
    "sections" => $section_list
]);
?>