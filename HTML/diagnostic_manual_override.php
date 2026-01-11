<?php
session_start();
include __DIR__ . "/../config/db_connect.php";

echo "<h2>Manual Override Diagnostic</h2>";
echo "<p>Checking why manual override records don't show on Student Attendance page...</p>";

$today = date('Y-m-d');

echo "<h3>1. Recent Attendance Records (from student_attendance table)</h3>";
$records = $conn->query("SELECT id, student_id, student_name, attendance_date, attendance_time, status FROM student_attendance WHERE attendance_date = '$today' ORDER BY id DESC LIMIT 10");
if (!$records) {
    echo "<p style='color: red;'>ERROR: " . $conn->error . "</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>student_id</th><th>student_name</th><th>Date</th><th>Time</th><th>Status</th></tr>";
    while ($r = $records->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$r['id']}</td>";
        echo "<td>" . ($r['student_id'] ?? 'NULL') . "</td>";
        echo "<td>{$r['student_name']}</td>";
        echo "<td>{$r['attendance_date']}</td>";
        echo "<td>{$r['attendance_time']}</td>";
        echo "<td>{$r['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>2. Students with their student_id (from students table)</h3>";
$students = $conn->query("SELECT id, student_id, firstname, lastname FROM students ORDER BY id DESC LIMIT 10");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>DB ID</th><th>student_id</th><th>Name</th></tr>";
while ($s = $students->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$s['id']}</td>";
    echo "<td>" . ($s['student_id'] ?? 'NULL') . "</td>";
    echo "<td>{$s['firstname']} {$s['lastname']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>3. Testing JOIN (what Student Attendance page sees)</h3>";
$sql = "
    SELECT DISTINCT
        s.student_id,
        CONCAT(s.firstname, ' ', COALESCE(s.middlename, ''), ' ', s.lastname) AS student_name,
        s.section,
        s.grade_level,
        sa_in.attendance_time as time_in,
        sa_out.attendance_time as time_out
    FROM students s
    LEFT JOIN student_attendance sa_in ON sa_in.student_id = s.student_id 
        AND sa_in.attendance_date = '$today' 
        AND UPPER(sa_in.status) LIKE '%TIME IN%'
    LEFT JOIN student_attendance sa_out ON sa_out.student_id = s.student_id 
        AND sa_out.attendance_date = '$today' 
        AND UPPER(sa_out.status) LIKE '%TIME OUT%'
    ORDER BY s.lastname, s.firstname
";
$join = $conn->query($sql);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>student_id</th><th>Name</th><th>Section</th><th>Grade</th><th>time_in</th><th>time_out</th></tr>";
while ($j = $join->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . ($j['student_id'] ?? 'NULL') . "</td>";
    echo "<td>{$j['student_name']}</td>";
    echo "<td>" . ($j['section'] ?? 'NULL') . "</td>";
    echo "<td>" . ($j['grade_level'] ?? 'NULL') . "</td>";
    echo "<td>" . ($j['time_in'] ?? 'NULL') . "</td>";
    echo "<td>" . ($j['time_out'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>4. Checking for student_id mismatches</h3>";
$mismatch = $conn->query("
    SELECT sa.id, sa.student_id as att_student_id, sa.student_name, 
           s.student_id as students_student_id, s.firstname, s.lastname
    FROM student_attendance sa
    LEFT JOIN students s ON sa.student_id = s.student_id
    WHERE sa.attendance_date = '$today'
    ORDER BY sa.id DESC
    LIMIT 10
");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Att ID</th><th>ATT student_id</th><th>ATT Name</th><th>STUDENTS student_id</th><th>STUDENTS Name</th><th>Match?</th></tr>";
while ($m = $mismatch->fetch_assoc()) {
    $match = ($m['att_student_id'] === $m['students_student_id']) ? '✅ YES' : '❌ NO';
    echo "<tr>";
    echo "<td>{$m['id']}</td>";
    echo "<td>" . ($m['att_student_id'] ?? 'NULL') . "</td>";
    echo "<td>{$m['student_name']}</td>";
    echo "<td>" . ($m['students_student_id'] ?? 'NULL') . "</td>";
    echo "<td>" . ($m['firstname'] ? $m['firstname'] . ' ' . $m['lastname'] : 'N/A') . "</td>";
    echo "<td><strong>{$match}</strong></td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>5. Direct query - students with attendance today</h3>";
$direct = $conn->query("
    SELECT s.student_id, CONCAT(s.firstname, ' ', s.lastname) as name, COUNT(sa.id) as record_count
    FROM students s
    INNER JOIN student_attendance sa ON s.student_id = sa.student_id
    WHERE sa.attendance_date = '$today'
    GROUP BY s.student_id
    ORDER BY s.lastname
");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>student_id</th><th>Name</th><th>Record Count</th></tr>";
while ($d = $direct->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$d['student_id']}</td>";
    echo "<td>{$d['name']}</td>";
    echo "<td>{$d['record_count']}</td>";
    echo "</tr>";
}
echo "</table>";

?>