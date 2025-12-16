<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

ini_set("display_errors", 1);
error_reporting(E_ALL);

include __DIR__ . "/../config/db_connect.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("<b>ERROR:</b> Database connection not created. Check db_connect.php");
}

// =======================
//  CHECK IF TABLE EXISTS
// =======================
$checkTable = $conn->query("SHOW TABLES LIKE 'student_attendance'");
if ($checkTable->num_rows === 0) {
    die("<b>ERROR:</b> Table <code>student_attendance</code> does NOT exist in database <code>attendance_system</code>.");
}

// =======================
//   MAIN QUERY
// =======================
$sql = "
    SELECT 
        attendance_id,
        student_name,
        section,
        grade_level,
        attendance_date,
        attendance_time,
        status,
        image_path
    FROM student_attendance
    ORDER BY attendance_date DESC, attendance_time DESC
";

$result = $conn->query($sql);

// If query failed → print MySQL error
if (!$result) {
    die("<b>QUERY ERROR:</b> " . $conn->error . "<br><br>
         <b>SQL:</b><br><code>$sql</code>");
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Student Attendance</title>
    <link rel="stylesheet" href="../css/student-attendance.css">


</head>

<body>
    <div class="header-bar"><button id="btn-logout" class="btn-logout">Logout</button></div>
    <style>
        .header-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 10px 20px 0 20px;
        }

        .btn-logout {
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 18px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            margin-left: 20px;
        }

        .btn-logout:hover {
            background: #b71c1c;
        }

        .logout-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .logout-modal-box {
            background: #fff;
            padding: 28px 32px;
            border-radius: 10px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.12);
            text-align: center;
            min-width: 320px;
        }

        .logout-modal-box h3 {
            margin-bottom: 18px;
        }

        .logout-modal-box .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 18px;
            margin-top: 18px;
        }

        .logout-modal-box button {
            padding: 8px 22px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
        }

        .logout-modal-box .btn-yes {
            background: #dc3545;
            color: #fff;
        }

        .logout-modal-box .btn-no {
            background: #e2e3e5;
            color: #333;
        }
    </style>

    <h2>Student Attendance</h2>

    <table class="attendance-table">

        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Section</th>
                <th>Grade Level</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Image</th>
            </tr>
        </thead>
        <tbody>

            <?php
            if ($result->num_rows === 0) {
                echo "<tr><td colspan='8'>No records found</td></tr>";
            }

            while ($row = $result->fetch_assoc()) {
                echo "<tr>
            <td>{$row['attendance_id']}</td>
            <td>{$row['student_name']}</td>
            <td>{$row['section']}</td>
            <td>{$row['grade_level']}</td>
            <td>{$row['attendance_date']}</td>
            <td>{$row['attendance_time']}</td>
            <td>{$row['status']}</td>
            <td>" . ($row['image_path'] ? "<img src='../uploads/{$row['image_path']}' width='60'>" : "No image") . "</td>
          </tr>";
            }
            ?>

        </tbody>
    </table>

</body>

</html>