<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include __DIR__ . "/../config/db_connect.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("<b>ERROR:</b> Database connection not created.");
}

// Get filter parameters
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$filterSection = isset($_GET['section']) ? $_GET['section'] : '';
$filterGrade = isset($_GET['grade']) ? $_GET['grade'] : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$exportType = isset($_GET['type']) ? $_GET['type'] : 'students';

// Get sections and grades for dropdowns
$sections = $conn->query("SELECT DISTINCT section FROM students WHERE section IS NOT NULL ORDER BY section");
$grades = $conn->query("SELECT DISTINCT grade_level FROM students WHERE grade_level IS NOT NULL ORDER BY grade_level");

if (!$sections || !$grades) {
    die("<b>ERROR:</b> Failed to fetch sections or grades: " . $conn->error);
}

// Count total records
$studentCount = 0;
$attendanceCount = 0;
$result = $conn->query("SELECT COUNT(*) as cnt FROM students");
if ($result)
    $studentCount = $result->fetch_assoc()['cnt'];
$result = $conn->query("SELECT COUNT(*) as cnt FROM student_attendance");
if ($result)
    $attendanceCount = $result->fetch_assoc()['cnt'];

// Count filtered records
$filteredCount = 0;
$hasFilters = $searchQuery || $filterSection || $filterGrade || $filterStatus || $startDate || $endDate;

if ($hasFilters) {
    if ($exportType === 'students') {
        $sql = "SELECT COUNT(*) as cnt FROM students s WHERE 1=1";
        $params = [];
        $types = "";

        if ($searchQuery) {
            $sql .= " AND (CONCAT(s.firstname, ' ', s.lastname) LIKE ? OR s.student_id LIKE ?)";
            $params[] = "%{$searchQuery}%";
            $params[] = "%{$searchQuery}%";
            $types .= "ss";
        }
        if ($filterSection) {
            $sql .= " AND s.section = ?";
            $params[] = $filterSection;
            $types .= "s";
        }
        if ($filterGrade) {
            $sql .= " AND s.grade_level = ?";
            $params[] = $filterGrade;
            $types .= "s";
        }

        $stmt = $conn->prepare($sql);
        if ($params)
            $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $filteredCount = $stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();
    } else {
        $sql = "SELECT COUNT(*) as cnt FROM student_attendance WHERE 1=1";
        $params = [];
        $types = "";

        if ($searchQuery) {
            $sql .= " AND student_name LIKE ?";
            $params[] = "%{$searchQuery}%";
            $types .= "s";
        }
        if ($filterSection) {
            $sql .= " AND section = ?";
            $params[] = $filterSection;
            $types .= "s";
        }
        if ($filterGrade) {
            $sql .= " AND grade_level = ?";
            $params[] = $filterGrade;
            $types .= "s";
        }
        if ($filterStatus) {
            $sql .= " AND UPPER(status) = ?";
            $params[] = strtoupper($filterStatus);
            $types .= "s";
        }
        if ($startDate) {
            $sql .= " AND attendance_date >= ?";
            $params[] = $startDate;
            $types .= "s";
        }
        if ($endDate) {
            $sql .= " AND attendance_date <= ?";
            $params[] = $endDate;
            $types .= "s";
        }

        $stmt = $conn->prepare($sql);
        if ($params)
            $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $filteredCount = $stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();
    }
}

// Fetch preview data (limited to 20 rows) - always show preview
$previewData = [];
if (true) { // Always show preview
    if ($exportType === 'students') {
        $sql = "SELECT s.student_id, s.firstname, s.middlename, s.lastname, s.section, s.grade_level, s.guardian_name, a.name as adviser_name 
                FROM students s
                LEFT JOIN section_yrlevel sy ON s.section = sy.section AND s.grade_level = sy.grade_level
                LEFT JOIN advisers a ON sy.adviser_id = a.id
                WHERE 1=1";
        $params = [];
        $types = "";

        if ($searchQuery) {
            $sql .= " AND (CONCAT(s.firstname, ' ', s.lastname) LIKE ? OR s.student_id LIKE ?)";
            $params[] = "%{$searchQuery}%";
            $params[] = "%{$searchQuery}%";
            $types .= "ss";
        }
        if ($filterSection) {
            $sql .= " AND s.section = ?";
            $params[] = $filterSection;
            $types .= "s";
        }
        if ($filterGrade) {
            $sql .= " AND s.grade_level = ?";
            $params[] = $filterGrade;
            $types .= "s";
        }
        $sql .= " ORDER BY s.lastname, s.firstname LIMIT 20";

        if ($params) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $previewData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $result = $conn->query($sql);
            $previewData = $result->fetch_all(MYSQLI_ASSOC);
        }
    } else {
        $sql = "SELECT id, student_name, section, grade_level, attendance_date, attendance_time, status FROM student_attendance WHERE 1=1";
        $params = [];
        $types = "";

        if ($searchQuery) {
            $sql .= " AND student_name LIKE ?";
            $params[] = "%{$searchQuery}%";
            $types .= "s";
        }
        if ($filterSection) {
            $sql .= " AND section = ?";
            $params[] = $filterSection;
            $types .= "s";
        }
        if ($filterGrade) {
            $sql .= " AND grade_level = ?";
            $params[] = $filterGrade;
            $types .= "s";
        }
        if ($filterStatus) {
            $sql .= " AND UPPER(status) = ?";
            $params[] = strtoupper($filterStatus);
            $types .= "s";
        }
        if ($startDate) {
            $sql .= " AND attendance_date >= ?";
            $params[] = $startDate;
            $types .= "s";
        }
        if ($endDate) {
            $sql .= " AND attendance_date <= ?";
            $params[] = $endDate;
            $types .= "s";
        }
        $sql .= " ORDER BY attendance_date DESC, attendance_time DESC LIMIT 20";

        if ($params) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $previewData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $result = $conn->query($sql);
            $previewData = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// Handle download
if (isset($_GET['download'])) {
    $filename = $exportType === 'students' ? 'students_export_' . date('Y-m-d') . '.csv' : 'attendance_export_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    if ($exportType === 'students') {
        fputcsv($output, ['Student ID', 'First Name', 'Middle Name', 'Last Name', 'Section', 'Grade Level', 'Guardian Name', 'Guardian Contact']);

        $sql = "SELECT student_id, firstname, middlename, lastname, section, grade_level, guardian_name, guardian_contact FROM students WHERE 1=1";
        $params = [];
        $types = "";

        if ($searchQuery) {
            $sql .= " AND (CONCAT(firstname, ' ', lastname) LIKE ? OR student_id LIKE ?)";
            $params[] = "%{$searchQuery}%";
            $params[] = "%{$searchQuery}%";
            $types .= "ss";
        }
        if ($filterSection) {
            $sql .= " AND section = ?";
            $params[] = $filterSection;
            $types .= "s";
        }
        if ($filterGrade) {
            $sql .= " AND grade_level = ?";
            $params[] = $filterGrade;
            $types .= "s";
        }
        $sql .= " ORDER BY lastname, firstname";

        if ($params) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($sql);
        }

        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    } else {
        fputcsv($output, ['ID', 'Student Name', 'Section', 'Grade Level', 'Date', 'Time', 'Status']);

        $sql = "SELECT id, student_name, section, grade_level, attendance_date, attendance_time, status FROM student_attendance WHERE 1=1";
        $params = [];
        $types = "";

        if ($searchQuery) {
            $sql .= " AND student_name LIKE ?";
            $params[] = "%{$searchQuery}%";
            $types .= "s";
        }
        if ($filterSection) {
            $sql .= " AND section = ?";
            $params[] = $filterSection;
            $types .= "s";
        }
        if ($filterGrade) {
            $sql .= " AND grade_level = ?";
            $params[] = $filterGrade;
            $types .= "s";
        }
        if ($filterStatus) {
            $sql .= " AND UPPER(status) = ?";
            $params[] = strtoupper($filterStatus);
            $types .= "s";
        }
        if ($startDate) {
            $sql .= " AND attendance_date >= ?";
            $params[] = $startDate;
            $types .= "s";
        }
        if ($endDate) {
            $sql .= " AND attendance_date <= ?";
            $params[] = $endDate;
            $types .= "s";
        }
        $sql .= " ORDER BY attendance_date DESC, attendance_time DESC";

        if ($params) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($sql);
        }

        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }

    fclose($output);
    exit;
}
?>

<div class="header-bar">
    <h2 class='table-title'>📥 Export Data</h2>
</div>

<!-- EXPORT TYPE TOGGLE -->
<div style="display: flex; gap: 10px; margin: 15px 0;">
    <label
        style="padding: 10px 20px; background: <?php echo $exportType === 'students' ? '#3498db' : '#ecf0f1'; ?>; color: <?php echo $exportType === 'students' ? 'white' : '#2c3e50'; ?>; border-radius: 20px; cursor: pointer;">
        <input type="radio" name="export_type" value="students" <?php echo $exportType === 'students' ? 'checked' : ''; ?>
            onchange="changeExportType('students')" style="display: none;">
        👤 Students
    </label>
    <label
        style="padding: 10px 20px; background: <?php echo $exportType === 'attendance' ? '#3498db' : '#ecf0f1'; ?>; color: <?php echo $exportType === 'attendance' ? 'white' : '#2c3e50'; ?>; border-radius: 20px; cursor: pointer;">
        <input type="radio" name="export_type" value="attendance" <?php echo $exportType === 'attendance' ? 'checked' : ''; ?> onchange="changeExportType('attendance')" style="display: none;">
        📋 Attendance Records
    </label>
</div>

<!-- FILTER BAR -->
<div
    style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px;">
    <h3 style="margin: 0 0 15px 0; color: #2c3e50;">🔍 Filter Data</h3>

    <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 200px;">
            <input type="text" id="search-query" placeholder="🔍 Search by name or student ID..."
                value="<?php echo htmlspecialchars($searchQuery); ?>"
                style="width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
        </div>
        <div>
            <select id="filter-section"
                style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; min-width: 130px;">
                <option value="">All Sections</option>
                <?php
                $sections->data_seek(0);
                while ($s = $sections->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($s['section']); ?>" <?php echo $filterSection === $s['section'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['section']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <select id="filter-grade"
                style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; min-width: 130px;">
                <option value="">All Grades</option>
                <?php
                $grades->data_seek(0);
                while ($g = $grades->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($g['grade_level']); ?>" <?php echo $filterGrade === $g['grade_level'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($g['grade_level']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>

    <!-- Attendance-specific filters -->
    <div id="attendance-filters" style="<?php echo $exportType === 'students' ? 'display:none;' : ''; ?>">
        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <div>
                <label
                    style="display: block; font-size: 11px; color: #7f8c8d; margin-bottom: 5px; text-transform: uppercase;">Status</label>
                <select id="filter-status"
                    style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; min-width: 130px;">
                    <option value="">All Status</option>
                    <option value="TIME IN" <?php echo $filterStatus === 'TIME IN' ? 'selected' : ''; ?>>Time In</option>
                    <option value="TIME OUT" <?php echo $filterStatus === 'TIME OUT' ? 'selected' : ''; ?>>Time Out
                    </option>
                </select>
            </div>
            <div>
                <label
                    style="display: block; font-size: 11px; color: #7f8c8d; margin-bottom: 5px; text-transform: uppercase;">Start
                    Date</label>
                <input type="date" id="start-date" value="<?php echo htmlspecialchars($startDate); ?>"
                    style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <div style="align-self: flex-end; color: #7f8c8d; padding-bottom: 10px;">to</div>
            <div>
                <label
                    style="display: block; font-size: 11px; color: #7f8c8d; margin-bottom: 5px; text-transform: uppercase;">End
                    Date</label>
                <input type="date" id="end-date" value="<?php echo htmlspecialchars($endDate); ?>"
                    style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
        </div>
    </div>

    <div style="display: flex; gap: 10px; margin-top: 15px;">
        <button onclick="applyFilters()" class="btn-add-student" style="background: #3498db;">
            🔍 Apply Filters
        </button>
        <button onclick="resetFilters()" class="btn-add-student" style="background: #95a5a6;">
            ↻ Reset
        </button>
    </div>
</div>

<!-- Active Filters Indicator -->
<?php if ($hasFilters): ?>
    <div style="background: #fff3cd; border: 1px solid #f39c12; border-radius: 8px; padding: 12px 15px; margin: 15px 0;">
        <strong>🔍 Active Filters:</strong>
        <?php
        $activeFilters = [];
        if ($searchQuery)
            $activeFilters[] = "Search: \"" . htmlspecialchars($searchQuery) . "\"";
        if ($filterSection)
            $activeFilters[] = "Section: " . htmlspecialchars($filterSection);
        if ($filterGrade)
            $activeFilters[] = "Grade: " . htmlspecialchars($filterGrade);
        if ($filterStatus)
            $activeFilters[] = "Status: " . htmlspecialchars($filterStatus);
        if ($startDate)
            $activeFilters[] = "From: " . date('M d, Y', strtotime($startDate));
        if ($endDate)
            $activeFilters[] = "To: " . date('M d, Y', strtotime($endDate));
        echo implode(' • ', $activeFilters);
        ?>
    </div>
<?php endif; ?>

<!-- STATS ROW -->
<div style="display: flex; gap: 15px; margin: 15px 0;">
    <div
        style="background: white; padding: 20px 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #3498db; text-align: center;">
        <div style="font-size: 32px; font-weight: 700; color: #3498db;"><?php echo number_format($studentCount); ?>
        </div>
        <div style="font-size: 12px; color: #7f8c8d;">Total Students</div>
    </div>
    <div
        style="background: white; padding: 20px 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #27ae60; text-align: center;">
        <div style="font-size: 32px; font-weight: 700; color: #27ae60;"><?php echo number_format($attendanceCount); ?>
        </div>
        <div style="font-size: 12px; color: #7f8c8d;">Attendance Records</div>
    </div>
    <?php if ($hasFilters): ?>
        <div
            style="background: white; padding: 20px 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #e74c3c; text-align: center;">
            <div style="font-size: 32px; font-weight: 700; color: #e74c3c;"><?php echo number_format($filteredCount); ?>
            </div>
            <div style="font-size: 12px; color: #7f8c8d;">Filtered Results</div>
        </div>
    <?php endif; ?>
</div>

<hr style="margin-bottom: 20px; border: none; border-top: 1px solid #ecf0f1;">

<!-- EXPORT CARDS -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">

    <!-- Students Export Card -->
    <div style="background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #3498db, #2980b9); padding: 25px; color: white;">
            <h3 style="margin: 0; font-size: 20px;">👤 Student Data</h3>
            <p style="margin: 5px 0 0; opacity: 0.9; font-size: 14px;">Export complete student list</p>
        </div>
        <div style="padding: 25px;">
            <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 15px;">
                Includes: Student ID, Name, Section, Grade Level, Guardian Info, Telegram Status
            </p>
            <div style="font-size: 36px; font-weight: 700; color: #3498db; margin-bottom: 15px;">
                <?php echo $hasFilters && $exportType === 'students' ? number_format($filteredCount) : number_format($studentCount); ?>
                <span style="font-size: 14px; color: #7f8c8d; font-weight: normal;">records</span>
            </div>
            <button onclick="downloadExport('students')" class="btn-add-student"
                style="background: #27ae60; display: inline-block; text-decoration: none; width: 100%;">
                📥 Download CSV
            </button>
        </div>
    </div>

    <!-- Attendance Export Card -->
    <div style="background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #9b59b6, #8e44ad); padding: 25px; color: white;">
            <h3 style="margin: 0; font-size: 20px;">📋 Attendance Data</h3>
            <p style="margin: 5px 0 0; opacity: 0.9; font-size: 14px;">Export attendance records</p>
        </div>
        <div style="padding: 25px;">
            <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 15px;">
                Includes: Student Name, Section, Date, Time, Status (Time In/Out)
            </p>
            <div style="font-size: 36px; font-weight: 700; color: #9b59b6; margin-bottom: 15px;">
                <?php echo $hasFilters && $exportType === 'attendance' ? number_format($filteredCount) : number_format($attendanceCount); ?>
                <span style="font-size: 14px; color: #7f8c8d; font-weight: normal;">records</span>
            </div>
            <button onclick="downloadExport('attendance')" class="btn-add-student"
                style="background: #27ae60; display: inline-block; text-decoration: none; width: 100%;">
                📥 Download CSV
            </button>
        </div>
    </div>

</div>

<!-- INFO BOX -->
<div style="background: #e8f4fd; border: 1px solid #3498db; border-radius: 8px; padding: 15px; margin-top: 20px;">
    <strong>💡 Tip:</strong> CSV files can be opened in Microsoft Excel, Google Sheets, or any spreadsheet application.
    <?php if ($hasFilters): ?>
        <br><strong>✅ Active Filters:</strong> Your export will only include the filtered results shown above.
    <?php endif; ?>
</div>

<!-- PREVIEW TABLE -->
<?php if (!empty($previewData)): ?>
    <div style="margin-top: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; color: #2c3e50;">👁️ Data Preview</h3>
            <span style="background: #f8f9fa; padding: 6px 12px; border-radius: 6px; font-size: 13px; color: #7f8c8d;">
                Showing first 20 of
                <?php echo $hasFilters ? number_format($filteredCount) : ($exportType === 'students' ? number_format($studentCount) : number_format($attendanceCount)); ?>
                records
            </span>
        </div>

        <?php if ($exportType === 'students'): ?>
            <!-- Students Preview Table -->
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Section</th>
                        <th>Grade Level</th>
                        <th>Adviser</th>
                        <th>Guardian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($previewData as $row):
                        $fullName = trim($row['firstname'] . ' ' . ($row['middlename'] ?? '') . ' ' . $row['lastname']);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['student_id'] ?? 'N/A'); ?></td>
                            <td><strong><?php echo htmlspecialchars($fullName); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['section'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['grade_level'] ?? '-'); ?></td>
                            <td><?php echo !empty($row['adviser_name']) ? htmlspecialchars($row['adviser_name']) : '<span style="color:#999">Not assigned</span>'; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['guardian_name'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <!-- Attendance Preview Table -->
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Section</th>
                        <th>Grade</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($previewData as $row): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['section'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['grade_level'] ?? '-'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['attendance_date'])); ?></td>
                            <td style="font-family: monospace;"><?php echo date('h:i A', strtotime($row['attendance_time'])); ?>
                            </td>
                            <td>
                                <span
                                    class="status-badge <?php echo strtoupper($row['status']) === 'TIME IN' ? 'status-connected' : 'status-not-connected'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

<style>
    .header-bar {
        display: flex;
        align-items: center;
        padding: 10px 20px 0 20px;
        margin-bottom: 15px;
    }

    .btn-add-student {
        padding: 12px 24px;
        background: #9b59b6;
        color: white;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .btn-add-student:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .preview-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    .preview-table th {
        background: #2c3e50;
        color: white;
        padding: 14px 12px;
        text-align: left;
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 600;
    }

    .preview-table td {
        padding: 12px;
        border-bottom: 1px solid #ecf0f1;
        font-size: 14px;
    }

    .preview-table tbody tr:hover {
        background: #f8f9fa;
    }

    .preview-table tbody tr:last-child td {
        border-bottom: none;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 12px;
    }

    .status-connected {
        background: #d4edda;
        color: #155724;
    }

    .status-not-connected {
        background: #f8d7da;
        color: #721c24;
    }
</style>

<script>
    var currentExportType = '<?php echo $exportType; ?>';

    window.changeExportType = function (type) {
        currentExportType = type;
        document.getElementById('attendance-filters').style.display = type === 'attendance' ? '' : 'none';
    };

    window.applyFilters = function () {
        var query = document.getElementById('search-query').value;
        var section = document.getElementById('filter-section').value;
        var grade = document.getElementById('filter-grade').value;
        var status = document.getElementById('filter-status').value;
        var startDate = document.getElementById('start-date').value;
        var endDate = document.getElementById('end-date').value;

        var url = 'export_data.php?type=' + currentExportType;
        if (query) url += '&q=' + encodeURIComponent(query);
        if (section) url += '&section=' + encodeURIComponent(section);
        if (grade) url += '&grade=' + encodeURIComponent(grade);
        if (status && currentExportType === 'attendance') url += '&status=' + encodeURIComponent(status);
        if (startDate && currentExportType === 'attendance') url += '&start_date=' + encodeURIComponent(startDate);
        if (endDate && currentExportType === 'attendance') url += '&end_date=' + encodeURIComponent(endDate);

        if (window.loadPage) {
            window.loadPage(url);
        } else {
            window.location.href = url;
        }
    };

    window.resetFilters = function () {
        if (window.loadPage) {
            window.loadPage('export_data.php');
        } else {
            window.location.href = 'export_data.php';
        }
    };

    window.downloadExport = function (type) {
        var query = document.getElementById('search-query').value;
        var section = document.getElementById('filter-section').value;
        var grade = document.getElementById('filter-grade').value;
        var status = document.getElementById('filter-status').value;
        var startDate = document.getElementById('start-date').value;
        var endDate = document.getElementById('end-date').value;

        var url = 'export_data.php?download=1&type=' + type;
        if (query) url += '&q=' + encodeURIComponent(query);
        if (section) url += '&section=' + encodeURIComponent(section);
        if (grade) url += '&grade=' + encodeURIComponent(grade);
        if (status && type === 'attendance') url += '&status=' + encodeURIComponent(status);
        if (startDate && type === 'attendance') url += '&start_date=' + encodeURIComponent(startDate);
        if (endDate && type === 'attendance') url += '&end_date=' + encodeURIComponent(endDate);

        window.location.href = url;
    };

    // Enter key triggers search
    var searchInput = document.getElementById('search-query');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') window.applyFilters();
        });
    }
</script>