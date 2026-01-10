<?php
// Test to see what's actually in the database vs what the query returns
include __DIR__ . "/../config/db_connect.php";
$today = date('Y-m-d');

echo "<h2>Student Attendance Query Debug</h2>";
echo "<p>Today's date: {$today}</p>";

// Show what's actually in student_attendance for today
echo "<h3>Raw data in student_attendance table (today):</h3>";
$raw = $conn->query("SELECT * FROM student_attendance WHERE attendance_date = '{$today}' ORDER BY attendance_time DESC");
if ($raw && $raw->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Student Name</th><th>Section</th><th>Grade</th><th>Date</th><th>Time</th><th>Status</th></tr>";
    while ($row = $raw->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>'{$row['student_name']}'</td>";
        echo "<td>{$row['section']}</td>";
        echo "<td>{$row['grade_level']}</td>";
        echo "<td>{$row['attendance_date']}</td>";
        echo "<td>{$row['attendance_time']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ No records found for today!</p>";
}

// Show what's in students table
echo "<h3>Students table names:</h3>";
$students = $conn->query("SELECT student_id, firstname, middlename, lastname FROM students ORDER BY lastname LIMIT 10");
if ($students && $students->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Student ID</th><th>Firstname</th><th>Middlename</th><th>Lastname</th><th>Concatenated (with COALESCE)</th></tr>";
    while ($row = $students->fetch_assoc()) {
        $concat = $row['firstname'] . ' ' . ($row['middlename'] ?? '') . ' ' . $row['lastname'];
        $concat2 = trim($row['firstname'] . ' ' . ($row['middlename'] ? $row['middlename'] . ' ' : '') . $row['lastname']);
        echo "<tr>";
        echo "<td>{$row['student_id']}</td>";
        echo "<td>{$row['firstname']}</td>";
        echo "<td>" . ($row['middlename'] ?? 'NULL') . "</td>";
        echo "<td>{$row['lastname']}</td>";
        echo "<td>'{$concat}' | Clean: '{$concat2}'</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Now test the actual query from student-attendance.php
echo "<h3>Testing the JOIN query from student-attendance.php:</h3>";
$sql = "
    SELECT 
        s.student_id,
        CONCAT(s.firstname, ' ', COALESCE(s.middlename, ''), ' ', s.lastname) AS student_name,
        s.section,
        s.grade_level,
        (SELECT attendance_time FROM student_attendance sa 
         WHERE sa.student_name = CONCAT(s.firstname, ' ', COALESCE(s.middlename, ''), ' ', s.lastname) 
         AND sa.attendance_date = ? AND UPPER(sa.status) = 'TIME IN' 
         ORDER BY sa.attendance_time ASC LIMIT 1) AS time_in,
        (SELECT attendance_time FROM student_attendance sa 
         WHERE sa.student_name = CONCAT(s.firstname, ' ', COALESCE(s.middlename, ''), ' ', s.lastname) 
         AND sa.attendance_date = ? AND UPPER(sa.status) = 'TIME OUT' 
         ORDER BY sa.attendance_time DESC LIMIT 1) AS time_out
    FROM students s
    ORDER BY s.lastname, s.firstname
    LIMIT 10
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $today, $today);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Student ID</th><th>Name from Query</th><th>Section</th><th>Time In</th><th>Time Out</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['student_id']}</td>";
        echo "<td>'{$row['student_name']}'</td>";
        echo "<td>{$row['section']}</td>";
        echo "<td>" . ($row['time_in'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['time_out'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Query returned no results!</p>";
}

// Check for name mismatches
echo "<h3>Checking for name mismatches:</h3>";
$mismatch = $conn->query("
    SELECT DISTINCT sa.student_name as attendance_name
    FROM student_attendance sa
    WHERE sa.attendance_date = '{$today}'
    AND sa.student_name NOT IN (
        SELECT CONCAT(s.firstname, ' ', COALESCE(s.middlename, ''), ' ', s.lastname)
        FROM students s
    )
");

if ($mismatch && $mismatch->num_rows > 0) {
    echo "<p style='color: red;'>⚠️ Found names in attendance that don't match students table:</p>";
    echo "<ul>";
    while ($row = $mismatch->fetch_assoc()) {
        echo "<li>'{$row['attendance_name']}'</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: green;'>✅ All attendance names match the students table format</p>";
}

$conn->close();
?>