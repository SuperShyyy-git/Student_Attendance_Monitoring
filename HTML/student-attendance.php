<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Manila');
include __DIR__ . "/../config/db_connect.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("<b>ERROR:</b> Database connection not created.");
}

$today = date('Y-m-d');

// SIMPLIFIED QUERY - Using LEFT JOIN with LIKE to include late records
$sql = "
    SELECT DISTINCT
        s.student_id,
        CONCAT(s.firstname, ' ', COALESCE(s.middlename, ''), ' ', s.lastname) AS student_name,
        s.section,
        s.grade_level,
        sa_in.attendance_time as time_in,
        sa_out.attendance_time as time_out
    FROM students s
    LEFT JOIN student_attendance sa_in ON sa_in.student_id = s.student_id 
        AND sa_in.attendance_date = '$today' 
        AND UPPER(sa_in.status) LIKE '%TIME IN%'
    LEFT JOIN student_attendance sa_out ON sa_out.student_id = s.student_id 
        AND sa_out.attendance_date = '$today' 
        AND UPPER(sa_out.status) LIKE '%TIME OUT%'
    ORDER BY s.lastname, s.firstname
";

$result = $conn->query($sql);

// Calculate stats
$students = [];
$presentCount = 0;
$absentCount = 0;

while ($row = $result->fetch_assoc()) {
    $students[] = $row;
    if ($row['time_in']) {
        $presentCount++;
    } else {
        $absentCount++;
    }
}
$totalStudents = count($students);
?>

<div class="header-bar">
    <h2 class='table-title'>👥 Student Attendance - Daily Check-in / Check-out</h2>
    <div style="display: flex; align-items: center; gap: 10px; margin-left: auto;">
        <span
            style="background: #3498db; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 500; white-space: nowrap; margin-right: 15px;">
            <?php echo date('F d, Y'); ?>
        </span>
    </div>
    <button id="btn-logout" class="btn-logout">Logout</button>
</div>

<!-- SEARCH BAR -->
<div style="display: flex; gap: 15px; margin: 20px 0; align-items: center;">
    <div style="flex: 1; min-width: 200px;">
        <input type="text" id="search-checkin" placeholder="🔍 Search by student name..."
            style="width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none;">
    </div>
</div>

<!-- STATS ROW -->
<div style="display: flex; gap: 20px; margin-bottom: 25px;">
    <div
        style="flex: 1; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-left: 5px solid #10b981;">
        <span
            style="display: block; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Present</span>
        <span style="font-size: 28px; font-weight: 800; color: #10b981;">
            <?php echo $presentCount; ?>
        </span>
    </div>
    <div
        style="flex: 1; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-left: 5px solid #ef4444;">
        <span
            style="display: block; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Absent</span>
        <span style="font-size: 28px; font-weight: 800; color: #ef4444;">
            <?php echo $absentCount; ?>
        </span>
    </div>
</div>

<!-- DATA TABLE -->
<div class="table-container"
    style="background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden;">
    <table class="student-table" id="attendance-table">
        <thead>
            <tr>
                <th
                    style="padding: 14px 12px; background: #1e293b; color: white; text-align: left; font-size: 12px; text-transform: uppercase;">
                    Student ID</th>
                <th
                    style="padding: 14px 12px; background: #1e293b; color: white; text-align: left; font-size: 12px; text-transform: uppercase;">
                    Student Name</th>
                <th
                    style="padding: 14px 12px; background: #1e293b; color: white; text-align: left; font-size: 12px; text-transform: uppercase;">
                    Section</th>
                <th
                    style="padding: 14px 12px; background: #1e293b; color: white; text-align: left; font-size: 12px; text-transform: uppercase;">
                    Grade Level</th>
                <th
                    style="padding: 14px 12px; background: #1e293b; color: white; text-align: left; font-size: 12px; text-transform: uppercase;">
                    Time In 1</th>
                <th
                    style="padding: 14px 12px; background: #1e293b; color: white; text-align: left; font-size: 12px; text-transform: uppercase;">
                    Time Out 1</th>
                <th
                    style="padding: 14px 12px; background: #1e293b; color: white; text-align: left; font-size: 12px; text-transform: uppercase;">
                    Time In 2</th>
                <th
                    style="padding: 14px 12px; background: #1e293b; color: white; text-align: left; font-size: 12px; text-transform: uppercase;">
                    Time Out 2</th>
                <th
                    style="padding: 14px 12px; background: #1e293b; color: white; text-align: left; font-size: 12px; text-transform: uppercase;">
                    Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($students) === 0): ?>
                <tr>
                    <td colspan="9" style="text-align:center; padding: 50px; color: #94a3b8; font-style: italic;">No
                        students found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($students as $row):
                    // Note: Removed the filter that skipped students without time_in
                    // This allows manual override records to appear even if not from regular check-in
            


                    // Get all 4 scan times for today
                    $today = date('Y-m-d');
                    $stmt = $conn->prepare("SELECT attendance_time, status FROM student_attendance WHERE student_id = ? AND attendance_date = ? ORDER BY id ASC");
                    $stmt->bind_param('ss', $row['student_id'], $today);
                    $stmt->execute();
                    $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();

                    // Skip students with no attendance records for today (but allow manual override)
                    if (count($records) === 0) {
                        continue;
                    }

                    $timeIn1 = '--:-- --';
                    $timeOut1 = '--:-- --';
                    $timeIn2 = '--:-- --';
                    $timeOut2 = '--:-- --';

                    // Map scans to columns based on order (use LIKE to detect TIME IN/OUT including LATE)
                    $scanIndex = 0;
                    foreach ($records as $rec) {
                        $time = date('h:i A', strtotime($rec['attendance_time']));
                        $recStatus = strtoupper(trim($rec['status']));

                        if ($scanIndex == 0 && strpos($recStatus, 'TIME IN') !== false) {
                            $timeIn1 = $time;
                            $scanIndex++;
                        } elseif ($scanIndex == 1 && strpos($recStatus, 'TIME OUT') !== false) {
                            $timeOut1 = $time;
                            $scanIndex++;
                        } elseif ($scanIndex == 2 && strpos($recStatus, 'TIME IN') !== false) {
                            $timeIn2 = $time;
                            $scanIndex++;
                        } elseif ($scanIndex == 3 && strpos($recStatus, 'TIME OUT') !== false) {
                            $timeOut2 = $time;
                            $scanIndex++;
                        }
                    }

                    $status = 'Present';
                    $statusClass = 'status-connected';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['student_id'] ?? 'N/A'); ?></td>
                        <td style="font-weight: 500;"><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['section'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['grade_level'] ?? '-'); ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace; font-weight: 600;"><?php echo $timeIn1; ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace; font-weight: 600;"><?php echo $timeOut1; ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace; font-weight: 600;"><?php echo $timeIn2; ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace; font-weight: 600;"><?php echo $timeOut2; ?></td>
                        <td><span class="<?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    .header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 20px;
        flex-wrap: wrap;
        gap: 15px;
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

    .btn-logout:hover {
        background: #b71c1c;
    }

    .student-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    .student-table th {
        background: #1e293b;
        color: white;
        padding: 14px 12px;
        text-align: left;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .student-table td {
        padding: 12px;
        border-bottom: 1px solid #ecf0f1;
        font-size: 14px;
        color: #334155;
    }

    .student-table tr:hover {
        background: #f8fafc;
    }

    .student-table td {
        padding: 12px;
        border-bottom: 1px solid #ecf0f1;
        font-size: 14px;
    }

    .student-table tr:hover {
        background: #f8f9fa;
    }

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
    document.getElementById('search-checkin').addEventListener('input', function () {
        var searchText = this.value.toLowerCase();
        var rows = document.querySelectorAll('#attendance-table tbody tr');
        rows.forEach(function (row) {
            var text = row.textContent.toLowerCase();
            row.style.display = text.indexOf(searchText) !== -1 ? '' : 'none';
        });
    });
</script>