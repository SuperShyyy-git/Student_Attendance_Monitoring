<?php
session_start();
include __DIR__ . "/../config/db_connect.php";

echo "<h2>Table Structure Check</h2>";

// Check student_attendance table structure
echo "<h3>student_attendance table columns:</h3>";
$cols = $conn->query("SHOW COLUMNS FROM student_attendance");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Key</th></tr>";
while ($col = $cols->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>" . ($col['Key'] ?? '') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check recent records
echo "<h3>Recent Records (all columns):</h3>";
$today = date('Y-m-d');
$records = $conn->query("SELECT * FROM student_attendance WHERE attendance_date = '$today' LIMIT 10");
if ($records && $records->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    $first = true;
    while ($r = $records->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($r) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($r as $value) {
            echo "<td>" . ($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No records found for today ($today)</p>";
}
?>