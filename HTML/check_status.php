<?php
session_start();
include __DIR__ . "/../config/db_connect.php";

// Get recent attendance records
$records = $conn->query("SELECT id, student_name, attendance_date, attendance_time, status FROM student_attendance ORDER BY id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html>

<head>
    <title>Status Diagnostic</title>
    <style>
        body {
            font-family: Arial;
            padding: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #333;
            color: white;
        }

        .raw {
            background: #ffffcc;
            font-family: monospace;
        }
    </style>
</head>

<body>
    <h2>🔍 Attendance Status Diagnostic</h2>
    <p>This page shows the RAW status values from the database to help debug the empty badge issue.</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Student Name</th>
                <th>Date</th>
                <th>Time</th>
                <th>RAW Status Value</th>
                <th>Status Length</th>
                <th>Contains "TIME IN"?</th>
                <th>Contains "TIME OUT"?</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $records->fetch_assoc()):
                $status = $row['status'];
                $statusUpper = strtoupper($status);
                $hasTimeIn = strpos($statusUpper, 'TIME IN') !== false ? 'YES' : 'NO';
                $hasTimeOut = strpos($statusUpper, 'TIME OUT') !== false ? 'YES' : 'NO';
                ?>
                <tr>
                    <td>
                        <?php echo $row['id']; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['student_name']); ?>
                    </td>
                    <td>
                        <?php echo $row['attendance_date']; ?>
                    </td>
                    <td>
                        <?php echo $row['attendance_time']; ?>
                    </td>
                    <td class="raw">"
                        <?php echo htmlspecialchars($status); ?>"
                    </td>
                    <td>
                        <?php echo strlen($status); ?> chars
                    </td>
                    <td style="color: <?php echo $hasTimeIn == 'YES' ? 'green' : 'red'; ?>; font-weight: bold;">
                        <?php echo $hasTimeIn; ?>
                    </td>
                    <td style="color: <?php echo $hasTimeOut == 'YES' ? 'green' : 'red'; ?>; font-weight: bold;">
                        <?php echo $hasTimeOut; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h3>What to look for:</h3>
    <ul>
        <li><strong>OLD FORMAT:</strong> Status shows just "TIME IN" or "TIME OUT" (no AM/PM)</li>
        <li><strong>NEW FORMAT:</strong> Status shows "TIME IN (AM)", "TIME OUT (PM)", etc.</li>
        <li><strong>PROBLEM:</strong> Status is empty or has unexpected value</li>
    </ul>

    <h3>Expected Results:</h3>
    <p>✅ Status should be one of: <code>TIME IN (AM)</code>, <code>TIME OUT (AM)</code>, <code>TIME IN (PM)</code>,
        <code>TIME OUT (PM)</code></p>
    <p>✅ "Contains TIME IN?" or "Contains TIME OUT?" should show YES</p>

    <hr>
    <p><a href="dashboard.php">← Back to Dashboard</a></p>
</body>

</html>