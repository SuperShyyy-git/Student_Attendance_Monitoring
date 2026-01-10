<?php
include __DIR__ . "/../config/db_connect.php";
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html>

<head>
    <title>Quick Check</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 10px 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f2f2f2;
        }

        .error {
            background: #f8d7da;
        }

        .success {
            background: #d4edda;
        }
    </style>
</head>

<body>
    <h1>Quick Database Check</h1>

    <h2>1. What's in attendance table for TODAY (
        <?php echo $today; ?>)?
    </h2>
    <?php
    $att = $conn->query("SELECT * FROM student_attendance WHERE attendance_date = '{$today}' ORDER BY id DESC");
    if ($att && $att->num_rows > 0) {
        echo "<table><tr><th>ID</th><th>Student ID</th><th>Student Name</th><th>Time</th><th>Status</th></tr>";
        while ($row = $att->fetch_assoc()) {
            $class = empty($row['student_id']) ? "error" : "success";
            echo "<tr class='{$class}'>";
            echo "<td>{$row['id']}</td>";
            echo "<td>" . ($row['student_id'] ?? '<strong style="color:red;">NULL</strong>') . "</td>";
            echo "<td>{$row['student_name']}</td>";
            echo "<td>{$row['attendance_time']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><strong>Total today: {$att->num_rows}</strong></p>";
    } else {
        echo "<p class='error'>❌ NO RECORDS FOR TODAY!</p>";
    }
    ?>

    <h2>2. What students exist in students table?</h2>
    <?php
    $students = $conn->query("SELECT student_id, firstname, middlename, lastname FROM students LIMIT 5");
    if ($students && $students->num_rows > 0) {
        echo "<table><tr><th>Student ID</th><th>First</th><th>Middle</th><th>Last</th></tr>";
        while ($row = $students->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>{$row['student_id']}</strong></td>";
            echo "<td>{$row['firstname']}</td>";
            echo "<td>" . ($row['middlename'] ?? 'NULL') . "</td>";
            echo "<td>{$row['lastname']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>

    <h2>3. Test the JOIN query:</h2>
    <?php
    $sql = "
        SELECT 
            s.student_id,
            CONCAT(s.firstname, ' ', COALESCE(s.middlename, ''), ' ', s.lastname) AS student_name,
            (SELECT attendance_time FROM student_attendance sa 
             WHERE sa.student_id = s.student_id 
             AND sa.attendance_date = ? AND UPPER(sa.status) = 'TIME IN' 
             LIMIT 1) AS time_in,
            (SELECT attendance_time FROM student_attendance sa 
             WHERE sa.student_id = s.student_id 
             AND sa.attendance_date = ? AND UPPER(sa.status) = 'TIME OUT' 
             LIMIT 1) AS time_out
        FROM students s
        LIMIT 5
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $today, $today);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        echo "<table><tr><th>Student ID</th><th>Name</th><th>Time In</th><th>Time Out</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['student_id']}</td>";
            echo "<td>{$row['student_name']}</td>";
            echo "<td>" . ($row['time_in'] ?? '<span style="color:orange;">NULL</span>') . "</td>";
            echo "<td>" . ($row['time_out'] ?? '<span style="color:orange;">NULL</span>') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>

    <h2>4. Do student_ids MATCH?</h2>
    <?php
    $check = $conn->query("
        SELECT 
            sa.student_id as att_student_id,
            s.student_id as students_student_id,
            sa.student_name,
            CASE 
                WHEN sa.student_id = s.student_id THEN '✅ MATCH'
                ELSE '❌ NO MATCH'
            END as match_status
        FROM student_attendance sa
        LEFT JOIN students s ON sa.student_id = s.student_id
        WHERE sa.attendance_date = '{$today}'
        LIMIT 10
    ");

    if ($check && $check->num_rows > 0) {
        echo "<table><tr><th>Attendance student_id</th><th>Students student_id</th><th>Name</th><th>Match?</th></tr>";
        while ($row = $check->fetch_assoc()) {
            $class = ($row['match_status'] == '✅ MATCH') ? 'success' : 'error';
            echo "<tr class='{$class}'>";
            echo "<td>" . ($row['att_student_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['students_student_id'] ?? 'NULL') . "</td>";
            echo "<td>{$row['student_name']}</td>";
            echo "<td><strong>{$row['match_status']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>

    <hr>
    <p><strong>What to look for:</strong></p>
    <ul>
        <li>Section 1 should show today's attendance records with student_id filled in (green)</li>
        <li>Section 4 should show "✅ MATCH" - if it shows "❌ NO MATCH", the student_ids don't match!</li>
    </ul>
</body>

</html>