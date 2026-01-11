<?php
session_start();
include __DIR__ . "/../config/db_connect.php";

echo "<h2>Debug: Why Manual Override Records Don't Show</h2>";
$today = '2026-01-11';

echo "<h3>1. Attendance records for Jan 11, 2026</h3>";
$att = $conn->query("SELECT attendance_id, student_id, student_name, section, status, attendance_time FROM student_attendance WHERE attendance_date = '$today'");
echo "<table border='1'><tr><th>attendance_id</th><th>student_id</th><th>student_name</th><th>section</th><th>status</th><th>time</th></tr>";
while ($a = $att->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$a['attendance_id']}</td>";
    echo "<td>" . ($a['student_id'] ?? 'NULL') . " (" . gettype($a['student_id']) . ")</td>";
    echo "<td>{$a['student_name']}</td>";
    echo "<td>{$a['section']}</td>";
    echo "<td>{$a['status']}</td>";
    echo "<td>{$a['attendance_time']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>2. Students table (showing id and student_id)</h3>";
$stu = $conn->query("SELECT id, student_id, firstname, lastname, section FROM students ORDER BY id DESC LIMIT 5");
echo "<table border='1'><tr><th>id (database ID)</th><th>student_id</th><th>name</th><th>section</th></tr>";
while ($s = $stu->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$s['id']} (" . gettype($s['id']) . ")</td>";
    echo "<td>" . ($s['student_id'] ?? 'NULL') . " (" . gettype($s['student_id']) . ")</td>";
    echo "<td>{$s['firstname']} {$s['lastname']}</td>";
    echo "<td>{$s['section']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>3. Testing the JOIN query (what student-attendance.php sees)</h3>";
$join = $conn->query("
    SELECT DISTINCT
        s.id as student_db_id,
        s.student_id,
        CONCAT(s.firstname, ' ', COALESCE(s.middlename, ''), ' ', s.lastname) AS student_name,
        s.section,
        s.grade_level
    FROM students s
    LEFT JOIN student_attendance sa_in ON sa_in.student_id = s.id 
        AND sa_in.attendance_date = '$today' 
    ORDER BY s.lastname, s.firstname
    LIMIT 10
");
echo "<table border='1'><tr><th>student_db_id (s.id)</th><th>student_id (s.student_id)</th><th>name</th><th>section</th></tr>";
while ($j = $join->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$j['student_db_id']}</td>";
    echo "<td>" . ($j['student_id'] ?? 'NULL') . "</td>";
    echo "<td>{$j['student_name']}</td>";
    echo "<td>{$j['section']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>4. For each student, check if we can find their records</h3>";
$students = $conn->query("SELECT id, student_id, firstname, lastname FROM students ORDER BY id DESC LIMIT 3");
echo "<table border='1'><tr><th>Student</th><th>Database ID</th><th>Query</th><th>Record Count</th></tr>";
while ($s = $students->fetch_assoc()) {
    $studentDbId = $s['id'];
    $query = "SELECT COUNT(*) as cnt FROM student_attendance WHERE student_id = {$studentDbId} AND attendance_date = '$today'";
    $result = $conn->query($query);
    $count = $result->fetch_assoc()['cnt'];

    echo "<tr>";
    echo "<td>{$s['firstname']} {$s['lastname']}</td>";
    echo "<td>{$studentDbId}</td>";
    echo "<td><code>" . htmlspecialchars($query) . "</code></td>";
    echo "<td><strong>{$count}</strong></td>";
    echo "</tr>";
}
echo "</table>";
?>