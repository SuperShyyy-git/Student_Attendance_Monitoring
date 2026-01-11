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
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+7 days')); // Include future dates to ensure today shows

// Check if image_path column exists
$columnCheck = $conn->query("SHOW COLUMNS FROM student_attendance LIKE 'image_path'");
$hasImagePath = $columnCheck && $columnCheck->num_rows > 0;

// Build query
$sql = "SELECT id, student_name, section, grade_level, 
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

// ============================================================================
// QUERY ARCHIVED RECORDS
// ============================================================================
// Check if archived_attendance table exists
$checkArchiveTable = $conn->query("SHOW TABLES LIKE 'archived_attendance'");
$hasArchive = $checkArchiveTable && $checkArchiveTable->num_rows > 0;

$archivedRecords = [];
$archivedTotalRecords = 0;

if ($hasArchive) {
    // Build archived records query
    $archiveSql = "SELECT id, original_id, student_name, section, grade_level, 
            attendance_date, attendance_time, status, archived_at
            FROM archived_attendance 
            WHERE attendance_date BETWEEN ? AND ?";

    $archiveParams = [$startDate, $endDate];
    $archiveTypes = "ss";

    if ($filterSection) {
        $archiveSql .= " AND section = ?";
        $archiveParams[] = $filterSection;
        $archiveTypes .= "s";
    }
    if ($filterGradeLevel) {
        $archiveSql .= " AND grade_level = ?";
        $archiveParams[] = $filterGradeLevel;
        $archiveTypes .= "s";
    }
    if ($filterStudent) {
        $archiveSql .= " AND student_name LIKE ?";
        $archiveParams[] = "%$filterStudent%";
        $archiveTypes .= "s";
    }
    if ($filterStatus) {
        $archiveSql .= " AND UPPER(status) = ?";
        $archiveParams[] = strtoupper($filterStatus);
        $archiveTypes .= "s";
    }

    $archiveSql .= " ORDER BY archived_at DESC";

    $archiveStmt = $conn->prepare($archiveSql);
    $archiveStmt->bind_param($archiveTypes, ...$archiveParams);
    $archiveStmt->execute();
    $archiveResult = $archiveStmt->get_result();

    while ($row = $archiveResult->fetch_assoc()) {
        $archivedRecords[] = $row;
    }
    $archivedTotalRecords = count($archivedRecords);
}
?>

<div class="header-bar">
    <h2 class='table-title'>📜 Attendance Management - History</h2>
    <div style="display: flex; align-items: center; gap: 10px; margin-left: auto;">
        <span
            style="background: #3498db; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 500; white-space: nowrap; margin-right: 15px;">
            <?php echo date('F d, Y'); ?>
        </span>
    </div>
    <button id="btn-logout" class="btn-logout">Logout</button>
</div>

<!-- Success Message -->
<div id="success-message"
    style="display: none; background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 8px; margin: 15px 0; border: 1px solid #c3e6cb;">
    ✅ Attendance record archived successfully!
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal"
    style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 12px; max-width: 400px; text-align: center;">
        <h3 style="margin: 0 0 15px 0; color: #1e293b;">Confirm Delete</h3>
        <p style="margin: 0 0 20px 0; color: #64748b;">Are you sure you want to delete the attendance record for <strong
                id="delete-student-name"></strong>?</p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button onclick="closeDeleteModal()"
                style="padding: 10px 20px; background: #e2e8f0; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Cancel</button>
            <button id="confirm-delete-btn" onclick="deleteRecord()"
                style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Delete</button>
        </div>
    </div>
</div>

<!-- TAB NAVIGATION -->
<div class="tab-container">
    <button class="tab-btn active" onclick="switchTab('history')">
        📜 Attendance History
    </button>
    <button class="tab-btn" onclick="switchTab('archive')">
        📦 Archived Records
    </button>
</div>

<!-- TAB 1: ATTENDANCE HISTORY -->
<div id="tab-history" class="tab-content active">
    <div id="attendance-filter-bar"
        style="display: flex; gap: 15px; margin: 20px 0; align-items: center; flex-wrap: wrap; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
        <div style="flex: 1; min-width: 250px;">
            <label
                style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 5px; text-transform: uppercase;">Search
                Student</label>
            <input type="text" id="att-filter-student" placeholder="Enter name..."
                value="<?php echo htmlspecialchars($filterStudent); ?>"
                style="width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none;">
        </div>
        <div>
            <label
                style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 5px; text-transform: uppercase;">Start
                Date</label>
            <input type="date" id="att-filter-start" value="<?php echo $startDate; ?>"
                style="padding: 11px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none;">
        </div>
        <div>
            <label
                style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 5px; text-transform: uppercase;">End
                Date</label>
            <input type="date" id="att-filter-end" value="<?php echo $endDate; ?>"
                style="padding: 11px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none;">
        </div>
        <div>
            <label
                style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 5px; text-transform: uppercase;">Section</label>
            <select id="att-filter-section"
                style="padding: 11px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; min-width: 150px;">
                <option value="">All Sections</option>
                <?php while ($s = $sections->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($s['section']); ?>" <?php echo $filterSection === $s['section'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['section']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label
                style="display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 5px; text-transform: uppercase;">Status</label>
            <select id="att-filter-status"
                style="padding: 11px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; min-width: 120px;">
                <option value="">All Status</option>
                <option value="TIME IN" <?php echo $filterStatus === 'TIME IN' ? 'selected' : ''; ?>>Time In</option>
                <option value="TIME OUT" <?php echo $filterStatus === 'TIME OUT' ? 'selected' : ''; ?>>Time Out</option>
            </select>
        </div>
        <div style="display: flex; gap: 10px; align-self: flex-end;">
            <button type="button" onclick="window.doAttFilter();"
                style="padding: 12px 24px; background: #1e293b; color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">Apply
                Filter</button>
            <button type="button" onclick="window.doAttReset();"
                style="padding: 12px 24px; background: #f1f5f9; color: #64748b; border: 2px solid #e2e8f0; border-radius: 10px; font-weight: 700; cursor: pointer;">Reset</button>
        </div>
    </div>

    <!-- STATS ROW -->
    <div style="display: flex; gap: 20px; margin-bottom: 25px;">
        <div
            style="flex: 1; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-left: 5px solid #3b82f6;">
            <span
                style="display: block; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Total
                Records</span>
            <span style="font-size: 28px; font-weight: 800; color: #1e293b;"><?php echo $totalRecords; ?></span>
        </div>
        <div
            style="flex: 1; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-left: 5px solid #10b981;">
            <span
                style="display: block; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Time
                In</span>
            <span style="font-size: 28px; font-weight: 800; color: #10b981;"><?php echo $timeInCount; ?></span>
        </div>
        <div
            style="flex: 1; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-left: 5px solid #ef4444;">
            <span
                style="display: block; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Time
                Out</span>
            <span style="font-size: 28px; font-weight: 800; color: #ef4444;"><?php echo $timeOutCount; ?></span>
        </div>
    </div>

    <!-- DATA TABLE -->
    <div class="table-container"
        style="background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden;">
        <table class="student-table" id="history-table">
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
                        <th>Image</th>
                    <?php endif; ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($totalRecords === 0): ?>
                    <tr>
                        <td colspan="<?php echo $hasImagePath ? 9 : 8; ?>"
                            style="text-align:center; padding: 50px; color: #94a3b8; font-style: italic;">No attendance
                            records
                            found.</td>
                    </tr>
                <?php else: ?>
                    <?php
                    $rowNumber = 1; // Initialize row counter
                    foreach ($records as $row):
                        // Use pattern matching to detect TIME IN vs TIME OUT (includes LATE variants)
                        $statusUpper = strtoupper($row['status']);
                        $statusClass = strpos($statusUpper, 'TIME IN') !== false ? 'status-connected' : 'status-not-connected';
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['section'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['grade_level'] ?? '-'); ?></td>
                            <td style="font-weight: 600; color: #1e293b;">
                                <?php echo date('M d, Y', strtotime($row['attendance_date'])); ?>
                            </td>
                            <td style="font-family: 'JetBrains Mono', monospace; font-weight: 600;">
                                <?php echo date('h:i:s A', strtotime($row['attendance_time'])); ?>
                            </td>
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
                            <td>
                                <button
                                    onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['student_name']); ?>')"
                                    style="background: #f59e0b; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">
                                    📦 Archive
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- TAB 2: ARCHIVED RECORDS -->
<div id="tab-archive" class="tab-content">
    <!-- STATS CARD -->
    <div style="display: flex; gap: 20px; margin: 20px 0 25px 0;">
        <div
            style="flex: 1; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-left: 5px solid #f59e0b;">
            <span
                style="display: block; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">Total
                Archived Records</span>
            <span style="font-size: 28px; font-weight: 800; color: #f59e0b;"><?php echo $archivedTotalRecords; ?></span>
        </div>
    </div>

    <!-- ARCHIVED TABLE -->
    <div class="table-container"
        style="background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden;">
        <table class="student-table">
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$hasArchive): ?>
                    <tr>
                        <td colspan="10" style="text-align:center; padding: 50px; color: #94a3b8; font-style: italic;">
                            Archive table not created yet. Archive will be created when you archive the first record.
                        </td>
                    </tr>
                <?php elseif ($archivedTotalRecords === 0): ?>
                    <tr>
                        <td colspan="10" style="text-align:center; padding: 50px; color: #94a3b8; font-style: italic;">No
                            archived records found for this period.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($archivedRecords as $row):
                        // Use pattern matching to detect TIME IN vs TIME OUT (includes LATE variants)
                        $statusUpper = strtoupper($row['status']);
                        $statusClass = strpos($statusUpper, 'TIME IN') !== false ? 'status-connected' : 'status-not-connected';
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['original_id']; ?></td>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['section'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['grade_level'] ?? '-'); ?></td>
                            <td style="font-weight: 600; color: #1e293b;">
                                <?php echo date('M d, Y', strtotime($row['attendance_date'])); ?>
                            </td>
                            <td style="font-family: 'JetBrains Mono', monospace; font-weight: 600;">
                                <?php echo date('h:i:s A', strtotime($row['attendance_time'])); ?>
                            </td>
                            <td><span class="<?php echo $statusClass; ?>"><?php echo $row['status']; ?></span></td>
                            <td style="color: #64748b; font-size: 12px;">
                                <?php echo date('M d, Y h:i A', strtotime($row['archived_at'])); ?>
                            </td>
                            <td>
                                <button
                                    onclick="confirmDeleteArchived(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['student_name']); ?>')"
                                    style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">
                                    🗑️ Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
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

    .tab-container {
        display: flex;
        gap: 10px;
        margin: 20px 0 0 0;
        border-bottom: 2px solid #e0e0e0;
        padding-bottom: 0;
    }

    .tab-btn {
        padding: 12px 24px;
        background: #f5f5f5;
        border: none;
        border-radius: 8px 8px 0 0;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        color: #666;
        transition: all 0.3s ease;
        position: relative;
        bottom: -2px;
    }

    .tab-btn:hover {
        background: #e8e8e8;
        color: #333;
    }

    .tab-btn.active {
        background: #3498db;
        color: white;
        border-bottom: 2px solid #3498db;
    }

    .tab-content {
        display: none;
        padding: 20px 0;
    }

    .tab-content.active {
        display: block;
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
    // Tab switching
    function switchTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Show selected tab
        document.getElementById('tab-' + tabName).classList.add('active');
        event.target.classList.add('active');
    }

    // Delete confirmation modal
    var deleteIdToRemove = null;

    function confirmDelete(id, studentName) {
        deleteIdToRemove = id;
        document.getElementById('delete-student-name').textContent = studentName;
        document.getElementById('delete-modal').style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('delete-modal').style.display = 'none';
        deleteIdToRemove = null;
    }

    function deleteRecord() {
        if (!deleteIdToRemove) return;

        // Create form data
        var formData = new FormData();
        formData.append('delete_id', deleteIdToRemove);

        // Send AJAX request
        fetch('attendance_delete.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    var successMsg = document.getElementById('success-message');
                    successMsg.style.display = 'block';
                    setTimeout(function () {
                        successMsg.style.display = 'none';
                    }, 3000);

                    // Close modal
                    closeDeleteModal();

                    // Reload the page content
                    if (window.loadPage) {
                        window.loadPage('attendance_combined.php' + window.location.search);
                    } else {
                        location.reload();
                    }
                } else {
                    alert('Error: ' + data.message);
                    closeDeleteModal();
                }
            })
            .catch(error => {
                alert('Error deleting record. Please try again.');
                console.error('Delete error:', error);
                closeDeleteModal();
            });
    }

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

        var url = 'attendance_combined.php';
        if (params.length > 0) url += '?' + params.join('&');

        if (window.loadPage) {
            window.loadPage(url);
        } else {
            window.location.href = url;
        }
    };

    window.doAttReset = function () {
        if (window.loadPage) {
            window.loadPage('attendance_combined.php');
        } else {
            window.location.href = 'attendance_combined.php';
        }
    };

    // Attach listener for Enter key on search input
    var searchInput = document.getElementById('att-filter-student');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                window.doAttFilter();
            }
        });
    }

    // Delete archived record functions
    var deleteArchivedIdToRemove = null;

    function confirmDeleteArchived(archiveId, studentName) {
        deleteArchivedIdToRemove = archiveId;
        document.getElementById('delete-student-name').textContent = studentName;
        var modal = document.getElementById('delete-modal');
        modal.querySelector('h3').textContent = '⚠️ Delete Archived Record';
        modal.querySelector('p').innerHTML = 'Permanently delete archived record for <strong>' + studentName + '</strong>?<br><span style="color:#dc3545;font-size:12px;">This action CANNOT be undone!</span>';

        // Change the delete button to call deleteArchivedRecord
        document.getElementById('confirm-delete-btn').onclick = deleteArchivedRecord;

        modal.style.display = 'flex';
    }

    function deleteArchivedRecord() {
        if (!deleteArchivedIdToRemove) return;

        var formData = new FormData();
        formData.append('delete_archived_id', deleteArchivedIdToRemove);

        fetch('archived_attendance_delete.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    var successMsg = document.getElementById('success-message');
                    successMsg.textContent = '✅ Archived record deleted successfully!';
                    successMsg.style.display = 'block';
                    setTimeout(function () {
                        successMsg.style.display = 'none';
                    }, 3000);

                    closeDeleteModal();

                    if (window.loadPage) {
                        window.loadPage('attendance_combined.php' + window.location.search);
                    } else {
                        location.reload();
                    }
                } else {
                    alert('Error: ' + data.message);
                    closeDeleteModal();
                }
            })
            .catch(error => {
                alert('Error deleting archived record. Please try again.');
                console.error('Delete error:', error);
                closeDeleteModal();
            });
    }
</script>