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

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$filterSection = isset($_GET['section']) ? $_GET['section'] : '';
$filterGrade = isset($_GET['grade']) ? $_GET['grade'] : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$searchType = isset($_GET['type']) ? $_GET['type'] : 'students';

$sections = $conn->query("SELECT DISTINCT section FROM students WHERE section IS NOT NULL ORDER BY section");
$grades = $conn->query("SELECT DISTINCT grade_level FROM students WHERE grade_level IS NOT NULL ORDER BY grade_level");

$results = [];
$hasSearch = $searchQuery || $filterSection || $filterGrade || $filterStatus || $startDate;

if ($hasSearch) {
    if ($searchType === 'students') {
        $sql = "SELECT s.id, s.student_id, s.firstname, s.middlename, s.lastname, s.section, s.grade_level, s.guardian_name, s.chat_id, a.name as adviser_name 
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
        $sql .= " ORDER BY s.lastname, s.firstname LIMIT 100";

        $stmt = $conn->prepare($sql);
        if ($params)
            $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $sql = "SELECT attendance_id, student_name, section, grade_level, attendance_date, attendance_time, status FROM student_attendance WHERE 1=1";
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
        $sql .= " ORDER BY attendance_date DESC, attendance_time DESC LIMIT 100";

        $stmt = $conn->prepare($sql);
        if ($params)
            $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>

<div class="header-bar">
    <h2 class='table-title'>🔍 Search & Filter</h2>
    <button id="btn-logout" class="btn-logout">Logout</button>
</div>

<!-- SEARCH TYPE TOGGLE -->
<div style="display: flex; gap: 10px; margin: 15px 0;">
    <label
        style="padding: 10px 20px; background: <?php echo $searchType === 'students' ? '#3498db' : '#ecf0f1'; ?>; color: <?php echo $searchType === 'students' ? 'white' : '#2c3e50'; ?>; border-radius: 20px; cursor: pointer;">
        <input type="radio" name="search_type" value="students" <?php echo $searchType === 'students' ? 'checked' : ''; ?>
            onchange="changeSearchType('students')" style="display: none;">
        👤 Students
    </label>
    <label
        style="padding: 10px 20px; background: <?php echo $searchType === 'attendance' ? '#3498db' : '#ecf0f1'; ?>; color: <?php echo $searchType === 'attendance' ? 'white' : '#2c3e50'; ?>; border-radius: 20px; cursor: pointer;">
        <input type="radio" name="search_type" value="attendance" <?php echo $searchType === 'attendance' ? 'checked' : ''; ?> onchange="changeSearchType('attendance')" style="display: none;">
        📋 Attendance Records
    </label>
</div>

<!-- SEARCH BAR -->
<div style="display: flex; gap: 15px; margin: 15px 0; align-items: center; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 250px;">
        <input type="text" id="search-query" placeholder="🔍 Search by name or student ID..."
            value="<?php echo htmlspecialchars($searchQuery); ?>"
            style="width: 100%; padding: 12px 16px; border: 2px solid #3498db; border-radius: 8px; font-size: 16px;">
    </div>
    <div>
        <select id="filter-section"
            style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; min-width: 130px;">
            <option value="">All Sections</option>
            <?php while ($s = $sections->fetch_assoc()): ?>
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
            <?php while ($g = $grades->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($g['grade_level']); ?>" <?php echo $filterGrade === $g['grade_level'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($g['grade_level']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div id="status-filter" style="<?php echo $searchType === 'students' ? 'display:none' : ''; ?>">
        <select id="filter-status" style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px;">
            <option value="">All Status</option>
            <option value="TIME IN" <?php echo $filterStatus === 'TIME IN' ? 'selected' : ''; ?>>Time In</option>
            <option value="TIME OUT" <?php echo $filterStatus === 'TIME OUT' ? 'selected' : ''; ?>>Time Out</option>
        </select>
    </div>
    <button onclick="applySearchFilter()" class="btn-add-student" style="background: #3498db;">🔍 Search</button>
    <button onclick="resetSearchFilter()" class="btn-add-student" style="background: #95a5a6;">↻ Reset</button>
</div>

<hr style="margin-bottom: 15px; border: none; border-top: 1px solid #ecf0f1;">

<?php if ($hasSearch): ?>
    <div style="background: #f8f9fa; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px;">
        <strong><?php echo count($results); ?></strong> result(s) found
    </div>

    <?php if (count($results) === 0): ?>
        <div style="text-align: center; padding: 60px; color: #7f8c8d;">
            <div style="font-size: 48px;">🔍</div>
            <h3>No Results</h3>
            <p>Try adjusting your search criteria</p>
        </div>
    <?php elseif ($searchType === 'students'): ?>
        <table class="student-table">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Section</th>
                    <th>Grade</th>
                    <th>Adviser</th>
                    <th>Guardian</th>
                    <th>Telegram</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row):
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
                        <td>
                            <?php if (!empty($row['chat_id'])): ?>
                                <span class="status-connected">Connected</span>
                            <?php else: ?>
                                <span class="status-not-connected">Not Connected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <table class="student-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Name</th>
                    <th>Section</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td>#<?php echo $row['attendance_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['section'] ?? '-'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['attendance_date'])); ?></td>
                        <td style="font-family: monospace;"><?php echo date('h:i A', strtotime($row['attendance_time'])); ?></td>
                        <td><span
                                class="<?php echo strtoupper($row['status']) === 'TIME IN' ? 'status-connected' : 'status-not-connected'; ?>"><?php echo $row['status']; ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php else: ?>
    <div style="text-align: center; padding: 60px; color: #7f8c8d;">
        <div style="font-size: 48px;">🔍</div>
        <h3>Start Searching</h3>
        <p>Enter a name, student ID, or use the filters above</p>
    </div>
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

    .student-table tr:hover {
        background: #f8f9fa;
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
</style>

<script>
    var currentSearchType = '<?php echo $searchType; ?>';

    window.changeSearchType = function (type) {
        currentSearchType = type;
        document.getElementById('status-filter').style.display = type === 'attendance' ? '' : 'none';
    };

    window.applySearchFilter = function () {
        var query = document.getElementById('search-query').value;
        var section = document.getElementById('filter-section').value;
        var grade = document.getElementById('filter-grade').value;
        var status = document.getElementById('filter-status').value;

        var url = 'search_filter.php?type=' + currentSearchType;
        if (query) url += '&q=' + encodeURIComponent(query);
        if (section) url += '&section=' + encodeURIComponent(section);
        if (grade) url += '&grade=' + encodeURIComponent(grade);
        if (status && currentSearchType === 'attendance') url += '&status=' + encodeURIComponent(status);

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

    window.resetSearchFilter = function () {
        if (window.loadPage) {
            window.loadPage('search_filter.php');
        } else {
            var contentArea = document.getElementById('main-content');
            if (contentArea) {
                contentArea.innerHTML = '<p style="text-align:center;padding:50px;">Loading...</p>';
                fetch('search_filter.php').then(function (r) { return r.text(); }).then(function (html) { contentArea.innerHTML = html; });
            }
        }
    };

    // Enter key triggers search
    var searchInput = document.getElementById('search-query');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') window.applySearchFilter();
        });
    }
</script>