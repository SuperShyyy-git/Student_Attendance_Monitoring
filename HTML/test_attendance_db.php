<?php
// Comprehensive attendance diagnostic tool
include __DIR__ . "/../config/db_connect.php";
?>
<!DOCTYPE html>
<html>

<head>
    <title>Attendance Diagnostic</title>
    <style>
        body {
            font-family: Arial;
            padding: 20px;
        }

        .section {
            margin: 20px 0;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
        }

        .success {
            background: #d4edda;
            border-color: #28a745;
        }

        .error {
            background: #f8d7da;
            border-color: #dc3545;
        }

        .info {
            background: #d1ecf1;
            border-color: #17a2b8;
        }

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

        pre {
            background: #f5f5f5;
            padding: 10px;
            overflow-x: auto;
        }
    </style>
</head>

<body>
    <h1>📊 Attendance System Diagnostic</h1>

    <?php
    // 1. Check database connection
    echo "<div class='section " . (isset($conn) && $conn instanceof mysqli ? "success" : "error") . "'>";
    echo "<h2>1. Database Connection</h2>";
    if (isset($conn) && $conn instanceof mysqli) {
        echo "✅ Database connected successfully<br>";
        echo "Database name: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "<br>";
    } else {
        echo "❌ Database connection failed!<br>";
    }
    echo "</div>";

    // 2. Check table exists
    echo "<div class='section'>";
    echo "<h2>2. Table Check</h2>";
    $tableCheck = $conn->query("SHOW TABLES LIKE 'student_attendance'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        echo "✅ Table 'student_attendance' exists<br>";

        // Show structure
        echo "<h3>Table Structure:</h3>";
        $structure = $conn->query("DESCRIBE student_attendance");
        echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($col = $structure->fetch_assoc()) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Table 'student_attendance' does NOT exist!<br>";
    }
    echo "</div>";

    // 3. Check latest records
    echo "<div class='section'>";
    echo "<h2>3. Latest Records (Last 10)</h2>";
    $latest = $conn->query("SELECT id, student_name, section, grade_level, attendance_date, attendance_time, status, created_at FROM student_attendance ORDER BY id DESC LIMIT 10");

    if ($latest && $latest->num_rows > 0) {
        echo "✅ Found " . $latest->num_rows . " recent records<br>";
        echo "<table><tr><th>ID</th><th>Student Name</th><th>Section</th><th>Grade</th><th>Date</th><th>Time</th><th>Status</th><th>Created At</th></tr>";
        while ($row = $latest->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['student_name']}</td>";
            echo "<td>" . ($row['section'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['grade_level'] ?? 'NULL') . "</td>";
            echo "<td>{$row['attendance_date']}</td>";
            echo "<td>{$row['attendance_time']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>" . ($row['created_at'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ No records found in table (or table is empty)<br>";
        echo "Total rows: " . $conn->query("SELECT COUNT(*) as count FROM student_attendance")->fetch_assoc()['count'] . "<br>";
    }
    echo "</div>";

    // 4. Check today's records
    echo "<div class='section'>";
    echo "<h2>4. Today's Records (" . date('Y-m-d') . ")</h2>";
    $today = date('Y-m-d');
    $todayRecords = $conn->query("SELECT * FROM student_attendance WHERE attendance_date = '$today' ORDER BY attendance_time DESC");

    if ($todayRecords && $todayRecords->num_rows > 0) {
        echo "✅ Found " . $todayRecords->num_rows . " records for today<br>";
        echo "<table><tr><th>ID</th><th>Student</th><th>Time</th><th>Status</th></tr>";
        while ($row = $todayRecords->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['student_name']}</td><td>{$row['attendance_time']}</td><td>{$row['status']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ No records for today ({$today})<br>";
    }
    echo "</div>";

    // 5. Test INSERT
    echo "<div class='section'>";
    echo "<h2>5. Test INSERT</h2>";
    $testName = "DIAGNOSTIC TEST " . date('His');
    $testDate = date('Y-m-d');
    $testTime = date('H:i:s');

    $testStmt = $conn->prepare("INSERT INTO student_attendance (student_name, section, grade_level, attendance_date, attendance_time, status, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($testStmt) {
        $testSection = "Test Section";
        $testGrade = "Grade 1";
        $testStatus = "TIME IN";
        $testImage = "";

        $testStmt->bind_param('sssssss', $testName, $testSection, $testGrade, $testDate, $testTime, $testStatus, $testImage);

        if ($testStmt->execute()) {
            $insertId = $testStmt->insert_id;
            echo "✅ Test INSERT successful! New record ID: {$insertId}<br>";

            // Verify it was inserted
            $verify = $conn->query("SELECT * FROM student_attendance WHERE id = {$insertId}");
            if ($verify && $verify->num_rows > 0) {
                echo "✅ Verified: Record exists in database<br>";
                $verifyRow = $verify->fetch_assoc();
                echo "<pre>" . print_r($verifyRow, true) . "</pre>";

                // Clean up
                $conn->query("DELETE FROM student_attendance WHERE id = {$insertId}");
                echo "✅ Test record cleaned up<br>";
            } else {
                echo "❌ ERROR: Insert succeeded but record not found!<br>";
            }
        } else {
            echo "❌ Test INSERT FAILED: " . $testStmt->error . "<br>";
        }
        $testStmt->close();
    } else {
        echo "❌ Prepare FAILED: " . $conn->error . "<br>";
    }
    echo "</div>";

    // 6. Check students table
    echo "<div class='section'>";
    echo "<h2>6. Students Check</h2>";
    $studentCount = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
    echo "Total students registered: {$studentCount}<br>";

    $sample = $conn->query("SELECT student_id, firstname, middlename, lastname, section, grade_level FROM students LIMIT 5");
    if ($sample && $sample->num_rows > 0) {
        echo "<h3>Sample Students:</h3>";
        echo "<table><tr><th>ID</th><th>Name</th><th>Section</th><th>Grade</th></tr>";
        while ($row = $sample->fetch_assoc()) {
            $name = trim($row['firstname'] . ' ' . ($row['middlename'] ?? '') . ' ' . $row['lastname']);
            echo "<tr><td>{$row['student_id']}</td><td>{$name}</td><td>{$row['section']}</td><td>{$row['grade_level']}</td></tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    $conn->close();
    ?>

    <div class='section info'>
        <h2>💡 What to check next:</h2>
        <ol>
            <li>If Section 5 (Test INSERT) succeeds, the database is working fine</li>
            <li>If Today's Records shows 0, but you've scanned faces today, check the browser console for JavaScript
                errors</li>
            <li>Check if the face scanning page is calling the correct PHP file</li>
            <li>Make sure face_checkin_process.php is uploaded</li>
        </ol>
    </div>
</body>

</html>