<?php
/**
 * Diagnostic Script - Check Attendance Data
 */
include __DIR__ . "/../config/db_connect.php";

echo "<h1>🔍 Attendance Diagnostic Report</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    h1 { color: #2c3e50; }
    h2 { color: #27ae60; margin-top: 30px; }
    table { border-collapse: collapse; width: 100%; background: white; margin: 15px 0; }
    th { background: #34495e; color: white; padding: 12px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #ddd; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; }
    .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; }
    .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; }
    .null { color: red; font-weight: bold; }
</style>";

// 1. CHECK TABLE STRUCTURE
echo "<h2>1. Table Structure</h2>";
$structure = $conn->query("DESCRIBE student_attendance");
echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($col = $structure->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>{$col['Field']}</strong></td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
    echo "<td>{$col['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// 2. CHECK TOTAL RECORDS
echo "<h2>2. Records Count</h2>";
$total = $conn->query("SELECT COUNT(*) as total FROM student_attendance")->fetch_assoc();
echo "<div class='success'>Total attendance records: <strong>{$total['total']}</strong></div>";

// 3. CHECK TODAY'S RECORDS
$today = date('Y-m-d');
$todayCount = $conn->query("SELECT COUNT(*) as total FROM student_attendance WHERE attendance_date = '$today'")->fetch_assoc();
echo "<div class='success'>Today's records ($today): <strong>{$todayCount['total']}</strong></div>";

// 4. CHECK RECENT RECORDS (Last 10)
echo "<h2>3. Recent Attendance Records (Last 10)</h2>";
$recent = $conn->query("SELECT * FROM student_attendance ORDER BY attendance_id DESC LIMIT 10");

if ($recent && $recent->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Student Name</th><th>Section</th><th>Grade</th><th>Date</th><th>Time</th><th>Status</th></tr>";

    while ($row = $recent->fetch_assoc()) {
        $studentIdClass = empty($row['student_id']) ? 'class="null"' : '';
        $studentIdDisplay = empty($row['student_id']) ? 'NULL ❌' : $row['student_id'];

        echo "<tr>";
        echo "<td>{$row['attendance_id']}</td>";
        echo "<td $studentIdClass>$studentIdDisplay</td>";
        echo "<td>{$row['student_name']}</td>";
        echo "<td>" . ($row['section'] ?? '-') . "</td>";
        echo "<td>" . ($row['grade_level'] ?? '-') . "</td>";
        echo "<td>{$row['attendance_date']}</td>";
        echo "<td>{$row['attendance_time']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>No records found!</div>";
}

// 5. CHECK FOR NULL student_id
echo "<h2>4. Records Without student_id</h2>";
$nullCount = $conn->query("SELECT COUNT(*) as total FROM student_attendance WHERE student_id IS NULL OR student_id = ''")->fetch_assoc();
if ($nullCount['total'] > 0) {
    echo "<div class='warning'>⚠️ Found <strong>{$nullCount['total']}</strong> records without student_id</div>";

    $nullRecords = $conn->query("SELECT attendance_id, student_name, attendance_date, attendance_time, status FROM student_attendance WHERE student_id IS NULL OR student_id = '' ORDER BY attendance_id DESC LIMIT 15");
    echo "<table>";
    echo "<tr><th>ID</th><th>Student Name</th><th>Date</th><th>Time</th><th>Status</th></tr>";
    while ($row = $nullRecords->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['attendance_id']}</td>";
        echo "<td>{$row['student_name']}</td>";
        echo "<td>{$row['attendance_date']}</td>";
        echo "<td>{$row['attendance_time']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='success'>✅ All records have student_id!</div>";
}

// 6. CHECK student-attendance.php QUERY SIMULATION
echo "<h2>5. Testing student-attendance.php Query</h2>";
$testQuery = "
    SELECT 
        s.student_id,
        CONCAT(s.firstname, ' ', COALESCE(s.middlename, ''), ' ', s.lastname) AS student_name,
        s.section,
        s.grade_level,
        (SELECT attendance_time FROM student_attendance sa 
         WHERE (sa.student_id = s.student_id OR 
                TRIM(sa.student_name) = TRIM(CONCAT(s.firstname, ' ', COALESCE(CONCAT(s.middlename, ' '), ''), s.lastname)))
         AND sa.attendance_date = ? AND UPPER(sa.status) = 'TIME IN' 
         ORDER BY sa.attendance_time ASC LIMIT 1) AS time_in,
        (SELECT attendance_time FROM student_attendance sa 
         WHERE (sa.student_id = s.student_id OR 
                TRIM(sa.student_name) = TRIM(CONCAT(s.firstname, ' ', COALESCE(CONCAT(s.middlename, ' '), ''), s.lastname)))
         AND sa.attendance_date = ? AND UPPER(sa.status) = 'TIME OUT' 
         ORDER BY sa.attendance_time DESC LIMIT 1) AS time_out
    FROM students s
    ORDER BY s.lastname, s.firstname
    LIMIT 10
";

$stmt = $conn->prepare($testQuery);
$stmt->bind_param('ss', $today, $today);
$stmt->execute();
$result = $stmt->get_result();

echo "<div class='success'>Query executed successfully. Showing first 10 students:</div>";
echo "<table>";
echo "<tr><th>Student ID</th><th>Student Name</th><th>Section</th><th>Grade</th><th>Time In</th><th>Time Out</th><th>Status</th></tr>";

while ($row = $result->fetch_assoc()) {
    $status = $row['time_in'] ? 'Present ✅' : 'No Record ❌';
    $timeInDisplay = $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '--:-- --';
    $timeOutDisplay = $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '--:-- --';

    echo "<tr>";
    echo "<td>{$row['student_id']}</td>";
    echo "<td>{$row['student_name']}</td>";
    echo "<td>" . ($row['section'] ?? '-') . "</td>";
    echo "<td>" . ($row['grade_level'] ?? '-') . "</td>";
    echo "<td>$timeInDisplay</td>";
    echo "<td>$timeOutDisplay</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr><p style='color: #7f8c8d; font-size: 12px;'>Generated at: " . date('Y-m-d H:i:s') . "</p>";
?>