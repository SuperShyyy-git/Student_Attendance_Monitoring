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

$absenceThreshold = isset($_GET['threshold']) ? (int) $_GET['threshold'] : 3;
$lookbackDays = isset($_GET['days']) ? (int) $_GET['days'] : 30;

$startDate = date('Y-m-d', strtotime("-{$lookbackDays} days"));
$endDate = date('Y-m-d');

// Get all students
$students = [];
$result = $conn->query("
    SELECT id, student_id, firstname, middlename, lastname, section, grade_level, guardian_name, chat_id
    FROM students 
    ORDER BY lastname, firstname
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[$row['id']] = $row;
        $students[$row['id']]['full_name'] = trim($row['firstname'] . ' ' . ($row['middlename'] ?? '') . ' ' . $row['lastname']);
        $students[$row['id']]['attendance_days'] = 0;
        $students[$row['id']]['late_days'] = 0;
        $students[$row['id']]['absent_days'] = 0;
    }
}

// Count school days
$totalSchoolDays = 0;
$current = new DateTime($startDate);
$end = new DateTime($endDate);
while ($current <= $end) {
    if ($current->format('N') < 6)
        $totalSchoolDays++;
    $current->modify('+1 day');
}

// Get attendance records
foreach ($students as $id => &$student) {
    $stmt = $conn->prepare("
        SELECT DISTINCT attendance_date, MIN(attendance_time) as first_time
        FROM student_attendance 
        WHERE student_name = ? AND attendance_date BETWEEN ? AND ? AND UPPER(status) = 'TIME IN'
        GROUP BY attendance_date
    ");
    $stmt->bind_param('sss', $student['full_name'], $startDate, $endDate);
    $stmt->execute();
    $attResult = $stmt->get_result();

    while ($row = $attResult->fetch_assoc()) {
        if ($row['first_time'] > '08:00:00')
            $student['late_days']++;
        $student['attendance_days']++;
    }
    $student['absent_days'] = $totalSchoolDays - $student['attendance_days'];
    $student['attendance_rate'] = $totalSchoolDays > 0 ? round(($student['attendance_days'] / $totalSchoolDays) * 100, 1) : 0;
    $stmt->close();
}
unset($student);

// Filter at-risk students
$atRiskStudents = array_filter($students, fn($s) => $s['absent_days'] >= $absenceThreshold);
usort($atRiskStudents, fn($a, $b) => $b['absent_days'] - $a['absent_days']);
?>

<div class="header-bar">
    <h2 class='table-title'>⚠️ At-Risk Students</h2>
    <button id="btn-logout" class="btn-logout">Logout</button>
</div>

<!-- FILTER BAR -->
<div style="display: flex; gap: 15px; margin: 15px 0; align-items: center; flex-wrap: wrap;">
    <div>
        <label style="font-size: 12px; color: #7f8c8d; display: block;">Absence Threshold</label>
        <input type="number" id="filter-threshold" value="<?php echo $absenceThreshold; ?>" min="1" max="30"
            style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; width: 100px;">
    </div>
    <div>
        <label style="font-size: 12px; color: #7f8c8d; display: block;">Look Back (Days)</label>
        <input type="number" id="filter-days" value="<?php echo $lookbackDays; ?>" min="7" max="90"
            style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; width: 100px;">
    </div>
    <button onclick="applyRiskFilter()" class="btn-add-student" style="background: #3498db; margin-top: 18px;">🔍 Apply
        Filter</button>
</div>

<!-- STATS ROW -->
<div style="display: flex; gap: 15px; margin-bottom: 15px;">
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #e74c3c;">
        <div style="font-size: 24px; font-weight: 700; color: #e74c3c;"><?php echo count($atRiskStudents); ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">At-Risk Students</div>
    </div>
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #3498db;">
        <div style="font-size: 24px; font-weight: 700; color: #3498db;"><?php echo count($students); ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">Total Students</div>
    </div>
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #f39c12;">
        <div style="font-size: 24px; font-weight: 700; color: #f39c12;"><?php echo $totalSchoolDays; ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">School Days (<?php echo $lookbackDays; ?>d)</div>
    </div>
</div>

<hr style="margin-bottom: 15px; border: none; border-top: 1px solid #ecf0f1;">

<?php if (count($atRiskStudents) === 0): ?>
    <div style="text-align: center; padding: 60px; color: #27ae60;">
        <div style="font-size: 64px;">🎉</div>
        <h3>Great News!</h3>
        <p style="color: #7f8c8d;">No students are currently at risk.</p>
    </div>
<?php else: ?>
    <table class="student-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Section</th>
                <th>Grade</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Late</th>
                <th>Attendance Rate</th>
                <th>Guardian</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($atRiskStudents as $student):
                $rate = $student['attendance_rate'];
                $rateColor = $rate < 50 ? '#e74c3c' : ($rate < 75 ? '#f39c12' : '#27ae60');
                ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                        <div style="font-size: 11px; color: #7f8c8d;">
                            <?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($student['section'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($student['grade_level'] ?? '-'); ?></td>
                    <td><span class="status-connected"><?php echo $student['attendance_days']; ?></span></td>
                    <td><span class="status-not-connected"><?php echo $student['absent_days']; ?></span></td>
                    <td><span class="status-warning"><?php echo $student['late_days']; ?></span></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 100px; height: 8px; background: #ecf0f1; border-radius: 4px; overflow: hidden;">
                                <div style="width: <?php echo $rate; ?>%; height: 100%; background: <?php echo $rateColor; ?>;">
                                </div>
                            </div>
                            <span><?php echo $rate; ?>%</span>
                        </div>
                    </td>
                    <td style="font-size: 12px;">
                        <?php echo htmlspecialchars($student['guardian_name'] ?? 'N/A'); ?>
                        <?php if (!empty($student['chat_id'])): ?>
                            <br><span style="color: #27ae60;">📱 Telegram linked</span>
                        <?php else: ?>
                            <br><span style="color: #e74c3c;">❌ No contact</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

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
    }

    .btn-logout:hover {
        background: #b71c1c;
    }

    .btn-add-student {
        padding: 10px 20px;
        background: #9b59b6;
        color: white;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
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
        background: #c0392b;
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

    .student-table tr:hover {
        background: #fdf2f2;
    }

    .status-connected {
        display: inline-block;
        padding: 4px 10px;
        background: #d4edda;
        color: #155724;
        border-radius: 12px;
        font-weight: 600;
        font-size: 12px;
    }

    .status-not-connected {
        display: inline-block;
        padding: 4px 10px;
        background: #f8d7da;
        color: #721c24;
        border-radius: 12px;
        font-weight: 600;
        font-size: 12px;
    }

    .status-warning {
        display: inline-block;
        padding: 4px 10px;
        background: #fff3cd;
        color: #856404;
        border-radius: 12px;
        font-weight: 600;
        font-size: 12px;
    }
</style>

<script>
    window.applyRiskFilter = function () {
        var threshold = document.getElementById('filter-threshold').value;
        var days = document.getElementById('filter-days').value;
        var url = 'at_risk_students.php?threshold=' + threshold + '&days=' + days;

        if (window.loadPage) {
            window.loadPage(url);
        } else {
            var contentArea = document.getElementById('main-content');
            if (contentArea) {
                contentArea.innerHTML = '<p style="text-align:center;padding:50px;">Loading...</p>';
                fetch(url).then(function (r) { return r.text(); }).then(function (html) { contentArea.innerHTML = html; });
            }
        }
    };
</script>