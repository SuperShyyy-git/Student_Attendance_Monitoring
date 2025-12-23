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

// Get filter parameters
$filterSection = isset($_GET['section']) ? $_GET['section'] : '';
$filterGradeLevel = isset($_GET['grade_level']) ? $_GET['grade_level'] : '';
$filterStudent = isset($_GET['student']) ? $_GET['student'] : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Check if image_path column exists
$columnCheck = $conn->query("SHOW COLUMNS FROM student_attendance LIKE 'image_path'");
$hasImagePath = $columnCheck && $columnCheck->num_rows > 0;

// Build query
$sql = "SELECT attendance_id, student_name, section, grade_level, 
        attendance_date, attendance_time, status" .
    ($hasImagePath ? ", image_path" : "") . "
        FROM student_attendance 
        WHERE attendance_date BETWEEN ? AND ?";

$params = [$startDate, $endDate];
$types = "ss";

if ($filterSection) {
    $sql .= " AND section = ?";
    $params[] = $filterSection;
    $types .= "s";
}
if ($filterGradeLevel) {
    $sql .= " AND grade_level = ?";
    $params[] = $filterGradeLevel;
    $types .= "s";
}
if ($filterStudent) {
    $sql .= " AND student_name LIKE ?";
    $params[] = "%$filterStudent%";
    $types .= "s";
}
if ($filterStatus) {
    $sql .= " AND UPPER(status) = ?";
    $params[] = strtoupper($filterStatus);
    $types .= "s";
}

$sql .= " ORDER BY attendance_date DESC, attendance_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get sections and grade levels for dropdowns
$sections = $conn->query("SELECT DISTINCT section FROM student_attendance WHERE section IS NOT NULL ORDER BY section");
$gradeLevels = $conn->query("SELECT DISTINCT grade_level FROM student_attendance WHERE grade_level IS NOT NULL ORDER BY grade_level");

// Calculate stats
$records = [];
$timeInCount = 0;
$timeOutCount = 0;

while ($row = $result->fetch_assoc()) {
    $records[] = $row;
    if (strtoupper($row['status']) === 'TIME IN')
        $timeInCount++;
    if (strtoupper($row['status']) === 'TIME OUT')
        $timeOutCount++;
}
$totalRecords = count($records);
?>

<div class="header-bar">
    <h2 class='table-title'>📜 Attendance History</h2>
    <button id="btn-logout" class="btn-logout">Logout</button>
</div>

<!-- SEARCH & FILTER BAR -->
<div id="attendance-filter-bar" style="display: flex; gap: 15px; margin: 15px 0; align-items: center; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 200px;">
        <input type="text" id="att-filter-student" placeholder="🔍 Search by student name..."
            value="<?php echo htmlspecialchars($filterStudent); ?>"
            style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
    </div>
    <div>
        <input type="date" id="att-filter-start" value="<?php echo $startDate; ?>"
            style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
    </div>
    <div>
        <input type="date" id="att-filter-end" value="<?php echo $endDate; ?>"
            style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
    </div>
    <div>
        <select id="att-filter-section" onchange="window.doAttFilter();"
            style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; min-width: 130px;">
            <option value="">All Sections</option>
            <?php while ($s = $sections->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($s['section']); ?>" <?php echo $filterSection === $s['section'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($s['section']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <select id="att-filter-status" onchange="window.doAttFilter();"
            style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; min-width: 120px;">
            <option value="">All Status</option>
            <option value="TIME IN" <?php echo $filterStatus === 'TIME IN' ? 'selected' : ''; ?>>Time In</option>
            <option value="TIME OUT" <?php echo $filterStatus === 'TIME OUT' ? 'selected' : ''; ?>>Time Out</option>
        </select>
    </div>
    <button type="button" onclick="window.doAttFilter();"
        style="padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; pointer-events: auto !important; position: relative; z-index: 9999;">🔍
        Filter</button>
    <button type="button" onclick="window.doAttReset();"
        style="padding: 10px 20px; background: #95a5a6; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; pointer-events: auto !important; position: relative; z-index: 9999;">↻
        Reset</button>
</div>

<!-- STATS ROW -->
<div style="display: flex; gap: 15px; margin-bottom: 15px;">
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #3498db;">
        <div style="font-size: 24px; font-weight: 700; color: #2c3e50;"><?php echo $totalRecords; ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">Total Records</div>
    </div>
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #27ae60;">
        <div style="font-size: 24px; font-weight: 700; color: #27ae60;"><?php echo $timeInCount; ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">Time In</div>
    </div>
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #e74c3c;">
        <div style="font-size: 24px; font-weight: 700; color: #e74c3c;"><?php echo $timeOutCount; ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">Time Out</div>
    </div>
</div>

<hr style="margin-bottom: 15px; border: none; border-top: 1px solid #ecf0f1;">

<table class="student-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Student Name</th>
            <th>Section</th>
            <th>Grade</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
            <?php if ($hasImagePath): ?>
                <th>Image</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if ($totalRecords === 0): ?>
            <tr>
                <td colspan="<?php echo $hasImagePath ? 8 : 7; ?>"
                    style="text-align:center; padding: 40px; color: #7f8c8d;">No attendance records found.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($records as $row):
                $statusClass = strtoupper($row['status']) === 'TIME IN' ? 'status-connected' : 'status-not-connected';
                ?>
                <tr>
                    <td><?php echo $row['attendance_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['section'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['grade_level'] ?? '-'); ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['attendance_date'])); ?></td>
                    <td style="font-family: monospace;"><?php echo date('h:i:s A', strtotime($row['attendance_time'])); ?></td>
                    <td><span class="<?php echo $statusClass; ?>"><?php echo $row['status']; ?></span></td>
                    <?php if ($hasImagePath): ?>
                        <td>
                            <?php if (!empty($row['image_path'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($row['image_path']); ?>"
                                    style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover; cursor: pointer;"
                                    onclick="window.open('../uploads/<?php echo htmlspecialchars($row['image_path']); ?>', '_blank')">
                            <?php else: ?>-<?php endif; ?>
                        </td>
                    <?php endif; ?>
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
        font-size: 14px;
        transition: transform 0.2s, box-shadow 0.2s;
        pointer-events: auto !important;
        position: relative;
        z-index: 10;
    }

    .btn-add-student:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
</style>

<script>
    // Define filter function on window (globally accessible)
    window.doAttFilter = function () {
        var student = document.getElementById('att-filter-student').value;
        var start = document.getElementById('att-filter-start').value;
        var end = document.getElementById('att-filter-end').value;
        var section = document.getElementById('att-filter-section').value;
        var status = document.getElementById('att-filter-status').value;

        var params = [];
        if (student) params.push('student=' + encodeURIComponent(student));
        if (start) params.push('start_date=' + encodeURIComponent(start));
        if (end) params.push('end_date=' + encodeURIComponent(end));
        if (section) params.push('section=' + encodeURIComponent(section));
        if (status) params.push('status=' + encodeURIComponent(status));

        var url = 'attendance_table.php';
        if (params.length > 0) url += '?' + params.join('&');

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

    window.doAttReset = function () {
        if (window.loadPage) {
            window.loadPage('attendance_table.php');
        } else {
            var contentArea = document.getElementById('main-content');
            if (contentArea) {
                contentArea.innerHTML = '<p style="text-align:center;padding:50px;">Loading...</p>';
                fetch('attendance_table.php').then(function (r) { return r.text(); }).then(function (html) { contentArea.innerHTML = html; });
            }
        }
    };

    // Attach listener for Enter key on search input
    setTimeout(function () {
        var searchInput = document.getElementById('att-filter-student');
        if (searchInput) {
            searchInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    window.doAttFilter();
                }
            });
        }
    }, 0);
</script>