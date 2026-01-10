<?php
// Update existing attendance records with student_id
include __DIR__ . "/../config/db_connect.php";

echo "<h2>Updating Attendance Records with Student IDs</h2>";

// Get all attendance records without student_id
$query = "SELECT id, student_name FROM student_attendance WHERE student_id IS NULL OR student_id = ''";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<p>Found " . $result->num_rows . " records to update...</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Attendance ID</th><th>Student Name</th><th>Matched Student ID</th><th>Status</th></tr>";

    $updated = 0;
    $notFound = 0;

    while ($row = $result->fetch_assoc()) {
        $attendanceId = $row['id'];
        $studentName = $row['student_name'];

        // Try to find matching student by name
        // Try exact match first
        $findStudent = $conn->prepare("
            SELECT student_id 
            FROM students 
            WHERE CONCAT(firstname, ' ', COALESCE(middlename, ''), ' ', lastname) = ?
            OR CONCAT(firstname, ' ', COALESCE(CONCAT(middlename, ' '), ''), lastname) = ?
            OR CONCAT(firstname, ' ', lastname) = ?
            LIMIT 1
        ");
        $findStudent->bind_param('sss', $studentName, $studentName, $studentName);
        $findStudent->execute();
        $studentResult = $findStudent->get_result();
        $student = $studentResult->fetch_assoc();
        $findStudent->close();

        if ($student) {
            // Update the attendance record with student_id
            $updateStmt = $conn->prepare("UPDATE student_attendance SET student_id = ? WHERE id = ?");
            $updateStmt->bind_param('si', $student['student_id'], $attendanceId);
            $success = $updateStmt->execute();
            $updateStmt->close();

            if ($success) {
                echo "<tr style='background: #d4edda;'><td>{$attendanceId}</td><td>{$studentName}</td><td>{$student['student_id']}</td><td>✅ Updated</td></tr>";
                $updated++;
            } else {
                echo "<tr style='background: #f8d7da;'><td>{$attendanceId}</td><td>{$studentName}</td><td>{$student['student_id']}</td><td>❌ Update Failed</td></tr>";
            }
        } else {
            echo "<tr style='background: #fff3cd;'><td>{$attendanceId}</td><td>{$studentName}</td><td>-</td><td>⚠️ Student Not Found</td></tr>";
            $notFound++;
        }
    }

    echo "</table>";
    echo "<h3>Summary:</h3>";
    echo "<p>✅ Updated: <strong>{$updated}</strong> records</p>";
    echo "<p>⚠️ Not matched: <strong>{$notFound}</strong> records</p>";

} else {
    echo "<p>✅ No records need updating. All records already have student_id!</p>";
}

// Show sample of updated records
echo "<h3>Verification - Recent Records:</h3>";
$verify = $conn->query("SELECT id, student_id, student_name, attendance_date, attendance_time, status FROM student_attendance ORDER BY id DESC LIMIT 10");
if ($verify && $verify->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Student Name</th><th>Date</th><th>Time</th><th>Status</th></tr>";
    while ($row = $verify->fetch_assoc()) {
        $highlight = !empty($row['student_id']) ? "" : "style='background: #f8d7da;'";
        echo "<tr {$highlight}>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . ($row['student_id'] ?? '<span style="color:red;">NULL</span>') . "</td>";
        echo "<td>{$row['student_name']}</td>";
        echo "<td>{$row['attendance_date']}</td>";
        echo "<td>{$row['attendance_time']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>
<br>
<a href="student-attendance.php">Go to Student Attendance Page</a>