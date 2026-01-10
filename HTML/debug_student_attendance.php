<?php
/**
 * Debug Student Attendance Query
 * Check why records don't show on student-attendance.php
 */

date_default_timezone_set('Asia/Manila');
include __DIR__ . "/../config/db_connect.php";

$today = date('Y-m-d');

echo "<h1>🔍 Student Attendance Query Debug</h1>";
echo "<p><strong>Today's Date:</strong> $today</p>";

echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    h2 { color: #2c3e50; margin-top: 30px; }
    table { border-collapse: collapse; width: 100%; background: white; margin: 15px 0; }
    th { background: #34495e; color: white; padding: 12px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #ddd; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; }
    .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; }
    .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; }
    .code { background: #f8f9fa; padding: 10px; border-left: 3px solid #007bff; margin: 10px 0; font-family: monospace; }
</style>";

// 1. Check students
echo "<h2>1. Students in Database</h2>";
$students = $conn->query("SELECT student_id, firstname, middlename, lastname FROM students ORDER BY lastname LIMIT 5");
echo "<table><tr><th>student_id</th><th>First</th><th>Middle</th><th>Last</th><th>Full Name (as built)</th></tr>";
while ($s = $students->fetch_assoc()) {
    $fullName = $s['firstname'] . ' ' . ($s['middlename'] ?? '') . ' ' . $s['lastname'];
    $fullNameAlt = $s['firstname'] . ' ' . ($s['middlename'] ? $s['middlename'] . ' ' : '') . $s['lastname'];
    echo "<tr>";
    echo "<td><strong>{$s['student_id']}</strong></td>";
    echo "<td>{$s['firstname']}</td>";
    echo "<td>" . ($s['middlename'] ?? 'NULL') . "</td>";
    echo "<td>{$s['lastname']}</td>";
    echo "<td>{$fullName}<br><small style='color:#666;'>{$fullNameAlt}</small></td>";
    echo "</tr>";
}
echo "</table>";

// 2. Check attendance records for today
echo "<h2>2. Attendance Records for Today ($today)</h2>";
$attendance = $conn->query("SELECT id, student_id, student_name, attendance_date, attendance_time, status FROM student_attendance WHERE attendance_date = '$today' ORDER BY id");

if ($attendance && $attendance->num_rows > 0) {
    echo "<div class='success'>Found {$attendance->num_rows} records for today</div>";
    echo "<table><tr><th>ID</th><th>student_id</th><th>student_name</th><th>Date</th><th>Time</th><th>Status</th></tr>";
    while ($a = $attendance->fetch_assoc()) {
        $highlight = empty($a['student_id']) ? "style='background:#f8d7da;'" : "";
        echo "<tr $highlight>";
        echo "<td>{$a['id']}</td>";
        echo "<td>" . ($a['student_id'] ?? '<span style="color:red;">NULL</span>') . "</td>";
        echo "<td>{$a['student_name']}</td>";
        echo "<td>{$a['attendance_date']}</td>";
        echo "<td>{$a['attendance_time']}</td>";
        echo "<td>{$a['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ NO RECORDS FOR TODAY!</div>";
}

// 3. Run the EXACT query from student-attendance.php
echo "<h2>3. Exact Query from student-attendance.php</h2>";
echo "<div class='code'>This is the EXACT query the page uses to fetch attendance</div>";

$sql = "
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
    LIMIT 5
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $today, $today);
$stmt->execute();
$result = $stmt->get_result();

echo "<table><tr><th>student_id</th><th>Name</th><th>Section</th><th>Grade</th><th>TIME IN</th><th>TIME OUT</th><th>Status</th></tr>";
while ($row = $result->fetch_assoc()) {
    $status = $row['time_in'] ? 'Present' : 'No Record';
    $statusColor = $row['time_in'] ? '#d4edda' : '#f8d7da';

    echo "<tr style='background:$statusColor;'>";
    echo "<td>{$row['student_id']}</td>";
    echo "<td>{$row['student_name']}</td>";
    echo "<td>" . ($row['section'] ?? '-') . "</td>";
    echo "<td>" . ($row['grade_level'] ?? '-') . "</td>";
    echo "<td>" . ($row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '--:-- --') . "</td>";
    echo "<td>" . ($row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '--:-- --') . "</td>";
    echo "<td><strong>$status</strong></td>";
    echo "</tr>";
}
echo "</table>";

// 4. Detailed matching check for adrian balagat avorque
echo "<h2>4. Detailed Matching for adrian balagat avorque</h2>";

echo "<h3>A. From students table:</h3>";
$studentCheck = $conn->query("SELECT student_id, firstname, middlename, lastname, 
    CONCAT(firstname, ' ', COALESCE(middlename, ''), ' ', lastname) as name1,
    CONCAT(firstname, ' ', COALESCE(CONCAT(middlename, ' '), ''), lastname) as name2
    FROM students WHERE firstname = 'adrian' AND lastname = 'avorque'");

if ($studentCheck && $studentCheck->num_rows > 0) {
    $st = $studentCheck->fetch_assoc();
    echo "<table>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>student_id</td><td><strong>{$st['student_id']}</strong></td></tr>";
    echo "<tr><td>firstname</td><td>{$st['firstname']}</td></tr>";
    echo "<tr><td>middlename</td><td>" . ($st['middlename'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td>lastname</td><td>{$st['lastname']}</td></tr>";
    echo "<tr><td>Built name (method 1)</td><td>{$st['name1']}</td></tr>";
    echo "<tr><td>Built name (method 2)</td><td>{$st['name2']}</td></tr>";
    echo "</table>";

    echo "<h3>B. From attendance table:</h3>";
    $attCheck = $conn->query("SELECT id, student_id, student_name, attendance_date, status 
        FROM student_attendance 
        WHERE student_name LIKE '%adrian%avorque%' AND attendance_date = '$today'");

    if ($attCheck && $attCheck->num_rows > 0) {
        echo "<table><tr><th>ID</th><th>student_id</th><th>student_name</th><th>Date</th><th>Status</th></tr>";
        while ($att = $attCheck->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$att['id']}</td>";
            echo "<td>" . ($att['student_id'] ?? '<span style="color:red;">NULL</span>') . "</td>";
            echo "<td>{$att['student_name']}</td>";
            echo "<td>{$att['attendance_date']}</td>";
            echo "<td>{$att['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Check if names match
        echo "<h3>C. Name Matching Test:</h3>";
        echo "<div class='code'>";
        echo "Students table name: <strong>{$st['name1']}</strong><br>";
        echo "Students table name (alt): <strong>{$st['name2']}</strong><br>";

        $attCheck2 = $conn->query("SELECT student_name FROM student_attendance WHERE student_name LIKE '%adrian%avorque%' AND attendance_date = '$today' LIMIT 1");
        if ($attCheck2 && $attCheck2->num_rows > 0) {
            $attName = $attCheck2->fetch_assoc()['student_name'];
            echo "Attendance table name: <strong>$attName</strong><br>";
            echo "<br>";

            if (trim($attName) == trim($st['name1'])) {
                echo "<div class='success'>✅ Names MATCH (method 1)</div>";
            } elseif (trim($attName) == trim($st['name2'])) {
                echo "<div class='success'>✅ Names MATCH (method 2)</div>";
            } else {
                echo "<div class='error'>❌ Names DO NOT MATCH!<br>";
                echo "Length difference: " . (strlen($attName) - strlen($st['name1'])) . " characters</div>";
            }
        }
        echo "</div>";
    } else {
        echo "<div class='error'>No attendance records found for adrian</div>";
    }
}

echo "<hr><p style='color: #7f8c8d; font-size: 12px;'>Generated at: " . date('Y-m-d H:i:s') . "</p>";
?>