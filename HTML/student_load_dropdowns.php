<?php
include "../config/db_connect.php";
header("Content-Type: application/json");

// Load grade levels with sections
$query = "SELECT grade_level, section FROM section_yrlevel ORDER BY grade_level, section";

$result = $conn->query($query);
$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'grade_level' => $row['grade_level'],
            'section' => $row['section']
        ];
    }
}

echo json_encode($data);
?>