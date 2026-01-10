<?php
session_start();
date_default_timezone_set('Asia/Manila');
include __DIR__ . "/../config/db_connect.php";

$today = date('Y-m-d');

echo "<h1>SIMPLE TEST - What's happening?</h1>";
echo "<p>Today's date: <strong>$today</strong></p>";
echo "<hr>";

// Test 1: Direct query for adrian's attendance
echo "<h2>Test 1: Adrian's Attendance Records for $today</h2>";
$test1 = $conn->query("
    SELECT id, student_id, student_name, attendance_date, attendance_time, status 
    FROM student_attendance 
    WHERE student_id = 'STU-2026-0003' AND attendance_date = '$today'
");

if ($test1 && $test1->num_rows > 0) {
    echo "<p style='background:#d4edda;padding:10px;'>✅ Found " . $test1->num_rows . " records</p>";
    while ($r = $test1->fetch_assoc()) {
        echo "<p>- {$r['status']}: {$r['attendance_time']}</p>";
    }
} else {
    echo "<p style='background:#f8d7da;padding:10px;'>❌ NO RECORDS FOUND for STU-2026-0003 on $today</p>";
}

// Test 2: The exact subquery for TIME IN
echo "<h2>Test 2: Subquery for TIME IN (adrian)</h2>";
$test2 = $conn->query("
    SELECT attendance_time 
    FROM student_attendance 
    WHERE student_id = 'STU-2026-0003' 
    AND attendance_date = '$today' 
    AND UPPER(status) = 'TIME IN' 
    ORDER BY attendance_time ASC 
    LIMIT 1
");

if ($test2 && $test2->num_rows > 0) {
    $time = $test2->fetch_assoc()['attendance_time'];
    echo "<p style='background:#d4edda;padding:10px;'>✅ TIME IN: $time</p>";
} else {
    echo "<p style='background:#f8d7da;padding:10px;'>❌ NO TIME IN FOUND</p>";
}

// Test 3: Full query like student-attendance.php
echo "<h2>Test 3: Full Query (like student-attendance.php)</h2>";
$sql = "
    SELECT 
        s.student_id,
        CONCAT(s.firstname, ' ', COALESCE(s.middlename, ''), ' ', s.lastname) AS student_name,
        (SELECT attendance_time FROM student_attendance sa 
         WHERE sa.student_id = s.student_id
         AND sa.attendance_date = ? AND UPPER(sa.status) = 'TIME IN' 
         LIMIT 1) AS time_in
    FROM students s
    WHERE s.student_id = 'STU-2026-0003'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $today);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<p>Student: {$row['student_name']}</p>";
    echo "<p>TIME IN: " . ($row['time_in'] ?? 'NULL') . "</p>";

    if ($row['time_in']) {
        echo "<p style='background:#d4edda;padding:10px;'>✅ SUCCESS! Time in is: {$row['time_in']}</p>";
    } else {
        echo "<p style='background:#f8d7da;padding:10px;'>❌ time_in is NULL - subquery returned nothing!</p>";
    }
} else {
    echo "<p style='background:#f8d7da;padding:10px;'>❌ Student not found!</p>";
}

echo "<hr>";
echo "<h2>Conclusion:</h2>";
echo "<p>If Test 1 shows records but Test 3 shows NULL, there's a problem with the prepared statement subquery.</p>";
echo "<p>If all tests show records, then student-attendance.php is not using the updated code.</p>";
?>