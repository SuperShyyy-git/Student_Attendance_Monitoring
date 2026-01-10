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
$filterStudent = isset($_GET['student']) ? $_GET['student'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Check if archived_attendance table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'archived_attendance'");
$hasArchive = $tableExists && $tableExists->num_rows > 0;

$records = [];
$totalRecords = 0;

if ($hasArchive) {
    // Build query
    $sql = "SELECT id, original_id, student_name, section, grade_level, 
            attendance_date, attendance_time, status, image_path, archived_at
            FROM archived_attendance 
            WHERE archived_at BETWEEN ? AND ?";

    $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
    $types = "ss";

    if ($filterStudent) {
        $sql .= " AND student_name LIKE ?";
        $params[] = "%$filterStudent%";
        $types .= "s";
    }

    $sql .= " ORDER BY archived_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $totalRecords = count($records);
}
?>

<div class="header-bar">
    <h2 class='table-title'>📦 Archived Attendance Records</h2>
    <div style="display: flex; align-items: center; gap: 15px;">
        <span
            style="background: #f59e0b; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 500;">
            <?php echo date('F d, Y'); ?>
        </span>
        <button id="btn-logout" class="btn-logout">Logout</button>
    </div>
</div>

<!-- SEARCH & FILTER BAR -->
<div id="archive-filter-bar"
    style="display: flex; gap: 15px; margin: 20px 0; align-items: center; flex-wrap: wrap; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
    <div style="flex: 1; min-width: 250px;">
        <label
            style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 5px; text-transform: uppercase;">Search
            Student</label>
        <input type="text" id="archive-filter-student" placeholder="Enter name..."
            value="<?php echo htmlspecialchars($filterStudent); ?>"
            style="width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none;">
    </div>
    <div>
        <label
            style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 5px; text-transform: uppercase;">Start
            Date</label>
        <input type="date" id="archive-filter-start" value="<?php echo $startDate; ?>"
            style="padding: 11px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none;">
    </div>
    <div>
        <label
            style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 5px; text-transform: uppercase;">End
            Date</label>
        <input type="date" id="archive-filter-end" value="<?php echo $endDate; ?>"
            style="padding: 11px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none;">
    </div>
    <div style="display: flex; gap: 10px; align-self: flex-end;">
        <button type="button" onclick="window.doArchiveFilter();"
            style="padding: 12px 24px; background: #1e293b; color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">Apply
            Filter</button>
        <button type="button" onclick="window.doArchiveReset();"
            style="padding: 12px 24px; background: #f1f5f9; color: #64748b; border: 2px solid #e2e8f0; border-radius: 10px; font-weight: 700; cursor: pointer;">Reset</button>
    </div>
</div>

<!-- STATS CARD -->
<div style="display: flex; gap: 20px; margin-bottom: 25px;">
    <div
        style="flex: 1; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-left: 5px solid #f59e0b;">
        <span
            style="display: block; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Total
            Archived Records</span>
        <span style="font-size: 28px; font-weight: 800; color: #f59e0b;">
            <?php echo $totalRecords; ?>
        </span>
    </div>
</div>

<!-- DATA TABLE -->
<div class="table-container"
    style="background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden;">
    <table class="student-table" id="archive-table">
        <thead>
            <tr>
                <th>Archive ID</th>
                <th>Original ID</th>
                <th>Student Name</th>
                <th>Section</th>
                <th>Grade</th>
                <th>Attendance Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Archived At</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$hasArchive): ?>
                <tr>
                    <td colspan="9" style="text-align:center; padding: 50px; color: #94a3b8; font-style: italic;">
                        Archive table not created yet. Archive will be created when you archive the first record.
                    </td>
                </tr>
            <?php elseif ($totalRecords === 0): ?>
                <tr>
                    <td colspan="9" style="text-align:center; padding: 50px; color: #94a3b8; font-style: italic;">No
                        archived records found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($records as $row):
                    $statusClass = strtoupper($row['status']) === 'TIME IN' ? 'status-connected' : 'status-not-connected';
                    ?>
                    <tr>
                        <td>
                            <?php echo $row['id']; ?>
                        </td>
                        <td>
                            <?php echo $row['original_id']; ?>
                        </td>
                        <td style="font-weight: 500;">
                            <?php echo htmlspecialchars($row['student_name']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['section'] ?? '-'); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['grade_level'] ?? '-'); ?>
                        </td>
                        <td style="font-weight: 600; color: #1e293b;">
                            <?php echo date('M d, Y', strtotime($row['attendance_date'])); ?>
                        </td>
                        <td style="font-family: 'JetBrains Mono', monospace; font-weight: 600;">
                            <?php echo date('h:i:s A', strtotime($row['attendance_time'])); ?>
                        </td>
                        <td><span class="<?php echo $statusClass; ?>">
                                <?php echo $row['status']; ?>
                            </span></td>
                        <td style="color: #64748b; font-size: 12px;">
                            <?php echo date('M d, Y h:i A', strtotime($row['archived_at'])); ?>
                        </td>
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
    // Filter function
    window.doArchiveFilter = function () {
        var student = document.getElementById('archive-filter-student').value;
        var start = document.getElementById('archive-filter-start').value;
        var end = document.getElementById('archive-filter-end').value;

        var params = [];
        if (student) params.push('student=' + encodeURIComponent(student));
        if (start) params.push('start_date=' + encodeURIComponent(start));
        if (end) params.push('end_date=' + encodeURIComponent(end));

        var url = 'archived_attendance.php' + (params.length > 0 ? '?' + params.join('&') : '');

        if (window.loadPage) {
            window.loadPage(url);
        } else {
            window.location.href = url;
        }
    };

    window.doArchiveReset = function () {
        if (window.loadPage) {
            window.loadPage('archived_attendance.php');
        } else {
            window.location.href = 'archived_attendance.php';
        }
    };

    // Attach listener for Enter key on search input
    var searchInput = document.getElementById('archive-filter-student');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                window.doArchiveFilter();
            }
        });
    }
</script>