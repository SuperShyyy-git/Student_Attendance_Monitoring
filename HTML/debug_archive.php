<?php
// Debug script to test archive functionality
include "../config/db_connect.php";

echo "<h2>Archive Debug Test</h2>";

// Check if columns exist
$result = $conn->query("SHOW COLUMNS FROM students LIKE 'is_archived'");
if ($result->num_rows > 0) {
    echo "<p style='color:green'>✅ Column 'is_archived' exists</p>";
} else {
    echo "<p style='color:red'>❌ Column 'is_archived' does NOT exist</p>";
}

$result = $conn->query("SHOW COLUMNS FROM students LIKE 'archived_at'");
if ($result->num_rows > 0) {
    echo "<p style='color:green'>✅ Column 'archived_at' exists</p>";
} else {
    echo "<p style='color:red'>❌ Column 'archived_at' does NOT exist</p>";
}

// Show current student data
echo "<h3>Current Students:</h3>";
$result = $conn->query("SELECT id, student_id, firstname, lastname, is_archived, archived_at FROM students LIMIT 10");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Student ID</th><th>Name</th><th>is_archived</th><th>archived_at</th></tr>";
while ($row = $result->fetch_assoc()) {
    $archived = $row['is_archived'] ?? 'NULL';
    $archivedAt = $row['archived_at'] ?? 'NULL';
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['student_id']}</td>";
    echo "<td>{$row['firstname']} {$row['lastname']}</td>";
    echo "<td>{$archived}</td>";
    echo "<td>{$archivedAt}</td>";
    echo "</tr>";
}
echo "</table>";

// Test archive one student
if (isset($_GET['test_archive'])) {
    $id = intval($_GET['test_archive']);
    $sql = "UPDATE students SET is_archived = 1, archived_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<p style='color:green'>✅ Successfully archived student ID: $id (affected rows: {$stmt->affected_rows})</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to archive: " . $stmt->error . "</p>";
    }
}

echo "<h3>Test Archive:</h3>";
echo "<p>Add ?test_archive=ID to URL to test archiving a specific student</p>";
echo "<p>Example: <a href='?test_archive=1'>?test_archive=1</a></p>";

$conn->close();
?>