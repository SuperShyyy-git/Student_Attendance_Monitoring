<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include __DIR__ . "/../config/db_connect.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("<b>ERROR:</b> Database connection not created.");
}

$today = date('Y-m-d');

// Fetch today's attendance records
$sql = "
    SELECT 
        s.student_id,
        CONCAT(s.firstname, ' ', COALESCE(s.middlename, ''), ' ', s.lastname) AS student_name,
        s.section,
        s.grade_level,
        (SELECT attendance_time FROM student_attendance sa 
         WHERE sa.student_name = CONCAT(s.firstname, ' ', COALESCE(s.middlename, ''), ' ', s.lastname) 
         AND sa.attendance_date = ? AND UPPER(sa.status) = 'TIME IN' 
         ORDER BY sa.attendance_time ASC LIMIT 1) AS time_in,
        (SELECT attendance_time FROM student_attendance sa 
         WHERE sa.student_name = CONCAT(s.firstname, ' ', COALESCE(s.middlename, ''), ' ', s.lastname) 
         AND sa.attendance_date = ? AND UPPER(sa.status) = 'TIME OUT' 
         ORDER BY sa.attendance_time DESC LIMIT 1) AS time_out
    FROM students s
    ORDER BY s.lastname, s.firstname
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $today, $today);
$stmt->execute();
$result = $stmt->get_result();

// Calculate stats
$totalStudents = 0;
$presentCount = 0;
$lateCount = 0;
$absentCount = 0;
$lateThreshold = '08:00:00';

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
    $totalStudents++;
    
    if ($row['time_in']) {
        if ($row['time_in'] > $lateThreshold) {
            $lateCount++;
        } else {
            $presentCount++;
        }
    } else {
        $absentCount++;
    }
}
?>

<div class="header-bar">
    <h2 class='table-title'>⏰ Check-in / Check-out Time</h2>
    <div style="display: flex; align-items: center; gap: 15px;">
        <span style="background: #3498db; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 500;">
            <?php echo date('F d, Y'); ?>
        </span>
        <button id="btn-logout" class="btn-logout">Logout</button>
    </div>
</div>

<!-- SEARCH BAR -->
<div style="display: flex; gap: 15px; margin: 15px 0; align-items: center;">
    <div style="flex: 1; min-width: 200px;">
        <input type="text" id="search-checkin" placeholder="🔍 Search by student name..."
            style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
    </div>
</div>

<!-- STATS ROW -->
<div style="display: flex; gap: 15px; margin-bottom: 15px;">
    <div style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #27ae60;">
        <div style="font-size: 24px; font-weight: 700; color: #27ae60;"><?php echo $presentCount; ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">On Time</div>
    </div>
    <div style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #f39c12;">
        <div style="font-size: 24px; font-weight: 700; color: #f39c12;"><?php echo $lateCount; ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">Late</div>
    </div>
    <div style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #e74c3c;">
        <div style="font-size: 24px; font-weight: 700; color: #e74c3c;"><?php echo $absentCount; ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">No Record</div>
    </div>
</div>

<hr style="margin-bottom: 15px; border: none; border-top: 1px solid #ecf0f1;">

<table class="student-table">
    <thead>
        <tr>
            <th>Student ID</th>
            <th>Student Name</th>
            <th>Section</th>
            <th>Grade Level</th>
            <th>Time In</th>
            <th>Time Out</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($students) === 0): ?>
            <tr><td colspan="7" style="text-align:center; padding: 40px; color: #7f8c8d;">No students found.</td></tr>
        <?php else: ?>
            <?php foreach ($students as $row): 
                $timeIn = $row['time_in'];
                $timeOut = $row['time_out'];
                
                if (!$timeIn) {
                    $status = 'No Record';
                    $statusClass = 'status-not-connected';
                } elseif ($timeIn > $lateThreshold) {
                    $status = 'Late';
                    $statusClass = 'status-warning';
                } else {
                    $status = 'Present';
                    $statusClass = 'status-connected';
                }
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['student_id'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['section'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['grade_level'] ?? '-'); ?></td>
                    <td style="font-family: monospace; color: <?php echo $timeIn ? '#27ae60' : '#bdc3c7'; ?>;">
                        <?php echo $timeIn ? date('h:i A', strtotime($timeIn)) : '--:-- --'; ?>
                    </td>
                    <td style="font-family: monospace; color: <?php echo $timeOut ? '#e74c3c' : '#bdc3c7'; ?>;">
                        <?php echo $timeOut ? date('h:i A', strtotime($timeOut)) : '--:-- --'; ?>
                    </td>
                    <td><span class="<?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<style>
    .header-bar {
        display: flex;
        justify-content: space-between;
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
    }

    .btn-logout:hover { background: #b71c1c; }

    .student-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    .student-table th {
        background: #1e293b;
        color: white;
        padding: 14px 12px;
        text-align: left;
        font-size: 12px;
        text-transform: uppercase;
    }

    .student-table td {
        padding: 12px;
        border-bottom: 1px solid #ecf0f1;
        font-size: 14px;
    }

    .student-table tr:hover { background: #f8f9fa; }

    .status-connected {
        display: inline-block;
        padding: 6px 12px;
        background: #d4edda;
        color: #155724;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
    }

    .status-not-connected {
        display: inline-block;
        padding: 6px 12px;
        background: #f8d7da;
        color: #721c24;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
    }

    .status-warning {
        display: inline-block;
        padding: 6px 12px;
        background: #fff3cd;
        color: #856404;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
    }
</style>

<script>
    document.getElementById('search-checkin').addEventListener('input', function() {
        var searchText = this.value.toLowerCase();
        var rows = document.querySelectorAll('.student-table tbody tr');
        rows.forEach(function(row) {
            var text = row.textContent.toLowerCase();
            row.style.display = text.indexOf(searchText) !== -1 ? '' : 'none';
        });
    });
</script>
