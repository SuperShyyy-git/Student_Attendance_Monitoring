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

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_attendance'])) {
        $studentId = (int) $_POST['student_id'];
        $date = $_POST['attendance_date'];
        $time = $_POST['attendance_time'];
        $status = $_POST['status'];
        $reason = $_POST['reason'] ?? '';

        $stmt = $conn->prepare("SELECT firstname, middlename, lastname, section, grade_level FROM students WHERE id = ?");
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($student) {
            $fullName = trim($student['firstname'] . ' ' . ($student['middlename'] ?? '') . ' ' . $student['lastname']);
            $ins = $conn->prepare("INSERT INTO student_attendance (student_name, section, grade_level, attendance_date, attendance_time, status) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->bind_param('ssssss', $fullName, $student['section'], $student['grade_level'], $date, $time, $status);

            if ($ins->execute()) {
                $message = "✅ Attendance record added for {$fullName} - {$status} on {$date}";
                $messageType = 'success';
            } else {
                $message = "❌ Failed to add record: " . $conn->error;
                $messageType = 'error';
            }
            $ins->close();
        } else {
            $message = "❌ Student not found";
            $messageType = 'error';
        }
    }

    if (isset($_POST['delete_attendance'])) {
        $attendanceId = (int) $_POST['attendance_id'];
        $del = $conn->prepare("DELETE FROM student_attendance WHERE attendance_id = ?");
        $del->bind_param('i', $attendanceId);
        if ($del->execute()) {
            $message = "✅ Record #{$attendanceId} deleted";
            $messageType = 'success';
        }
        $del->close();
    }

    if (isset($_POST['update_attendance'])) {
        $attendanceId = (int) $_POST['attendance_id'];
        $newStatus = $_POST['new_status'];
        $newTime = $_POST['new_time'];

        $upd = $conn->prepare("UPDATE student_attendance SET status = ?, attendance_time = ? WHERE attendance_id = ?");
        $upd->bind_param('ssi', $newStatus, $newTime, $attendanceId);
        if ($upd->execute()) {
            $message = "✅ Record #{$attendanceId} updated";
            $messageType = 'success';
        }
        $upd->close();
    }
}

$students = $conn->query("SELECT id, student_id, CONCAT(firstname, ' ', lastname) as name FROM students ORDER BY lastname, firstname");
$recentAttendance = $conn->query("SELECT attendance_id, student_name, section, attendance_date, attendance_time, status FROM student_attendance ORDER BY attendance_date DESC, attendance_time DESC LIMIT 20");
?>

<div class="header-bar">
    <h2 class='table-title'>🔧 Manual Override</h2>
    <button id="btn-logout" class="btn-logout">Logout</button>
</div>

<div style="background: #fff3cd; border: 1px solid #f39c12; border-radius: 8px; padding: 15px; margin: 15px 0;">
    <strong>⚠️ Admin Tool</strong>
    <p style="font-size: 13px; color: #856404; margin: 0;">Use this to manually add, edit, or delete attendance records.
        All changes are logged.</p>
</div>

<?php if ($message): ?>
    <div
        style="background: <?php echo $messageType === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $messageType === 'success' ? '#155724' : '#721c24'; ?>; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- TABS -->
<div style="display: flex; gap: 5px; margin-bottom: 15px;">
    <button class="tab-btn active" onclick="showOverrideTab('add')">➕ Add Record</button>
    <button class="tab-btn" onclick="showOverrideTab('edit')">✏️ Edit/Delete</button>
</div>

<hr style="margin-bottom: 15px; border: none; border-top: 1px solid #ecf0f1;">

<!-- Add Tab -->
<div id="override-tab-add" class="override-tab-content">
    <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <h3 style="margin-bottom: 20px; color: #2c3e50;">Add Attendance Record</h3>
        <form id="add-attendance-form" onsubmit="return submitAddAttendance(this);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label
                        style="font-size: 12px; color: #7f8c8d; text-transform: uppercase; display: block; margin-bottom: 5px;">Student</label>
                    <select name="student_id" required
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="">Select Student</option>
                        <?php while ($s = $students->fetch_assoc()): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?>
                                (<?php echo $s['student_id'] ?? 'N/A'; ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label
                        style="font-size: 12px; color: #7f8c8d; text-transform: uppercase; display: block; margin-bottom: 5px;">Date</label>
                    <input type="date" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div>
                    <label
                        style="font-size: 12px; color: #7f8c8d; text-transform: uppercase; display: block; margin-bottom: 5px;">Time</label>
                    <input type="time" name="attendance_time" value="<?php echo date('H:i'); ?>" required
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div>
                    <label
                        style="font-size: 12px; color: #7f8c8d; text-transform: uppercase; display: block; margin-bottom: 5px;">Status</label>
                    <select name="status" required
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="TIME IN">TIME IN</option>
                        <option value="TIME OUT">TIME OUT</option>
                    </select>
                </div>
            </div>
            <div style="margin-top: 15px;">
                <label
                    style="font-size: 12px; color: #7f8c8d; text-transform: uppercase; display: block; margin-bottom: 5px;">Reason
                    (Optional)</label>
                <textarea name="reason" rows="2" placeholder="e.g., RFID not working..."
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;"></textarea>
            </div>
            <input type="hidden" name="add_attendance" value="1">
            <button type="submit" class="btn-add-student" style="margin-top: 15px; background: #9b59b6;">➕ Add
                Record</button>
        </form>
    </div>
</div>

<!-- Edit Tab -->
<div id="override-tab-edit" class="override-tab-content" style="display: none;">
    <h3 style="margin-bottom: 15px; color: #2c3e50;">Recent Attendance Records</h3>
    <table class="student-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Student</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $recentAttendance->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['attendance_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['attendance_date'])); ?></td>
                    <td style="font-family: monospace;"><?php echo date('h:i A', strtotime($row['attendance_time'])); ?>
                    </td>
                    <td><span
                            class="<?php echo strtoupper($row['status']) === 'TIME IN' ? 'status-connected' : 'status-not-connected'; ?>"><?php echo $row['status']; ?></span>
                    </td>
                    <td>
                        <button
                            onclick="showEditModal(<?php echo $row['attendance_id']; ?>, '<?php echo $row['status']; ?>', '<?php echo $row['attendance_time']; ?>')"
                            class="btn-add-student"
                            style="background: #f39c12; padding: 6px 12px; font-size: 12px;">✏️</button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this record?');">
                            <input type="hidden" name="attendance_id" value="<?php echo $row['attendance_id']; ?>">
                            <button type="submit" name="delete_attendance" class="btn-add-student"
                                style="background: #e74c3c; padding: 6px 12px; font-size: 12px;">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div id="editModal"
    style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 25px; border-radius: 10px; max-width: 400px; width: 90%;">
        <h3 style="margin-bottom: 15px;">Edit Record</h3>
        <form method="POST">
            <input type="hidden" name="attendance_id" id="edit_id">
            <div style="margin-bottom: 15px;">
                <label style="font-size: 12px; color: #7f8c8d;">Status</label>
                <select name="new_status" id="edit_status"
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    <option value="TIME IN">TIME IN</option>
                    <option value="TIME OUT">TIME OUT</option>
                </select>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="font-size: 12px; color: #7f8c8d;">Time</label>
                <input type="time" name="new_time" id="edit_time"
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <button type="submit" name="update_attendance" class="btn-add-student"
                style="background: #f39c12;">Update</button>
            <button type="button" onclick="closeEditModal()" class="btn-add-student"
                style="background: #95a5a6;">Cancel</button>
        </form>
    </div>
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

    .tab-btn {
        padding: 12px 24px;
        background: white;
        border: none;
        border-radius: 8px 8px 0 0;
        cursor: pointer;
        font-weight: 600;
        color: #7f8c8d;
    }

    .tab-btn.active {
        background: #9b59b6;
        color: white;
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
        background: #9b59b6;
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
        background: #f8f4fc;
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
    function showOverrideTab(tabId) {
        document.querySelectorAll('.override-tab-content').forEach(tab => tab.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById('override-tab-' + tabId).style.display = 'block';
        event.target.classList.add('active');
    }

    function showEditModal(id, status, time) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_time').value = time;
        document.getElementById('editModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Submit add attendance form via AJAX
    window.submitAddAttendance = function (form) {
        var formData = new FormData(form);

        fetch('manual_override.php', {
            method: 'POST',
            body: formData
        })
            .then(function (response) { return response.text(); })
            .then(function (html) {
                // Reload the page to show the success message
                if (window.loadPage) {
                    window.loadPage('manual_override.php');
                } else {
                    var contentArea = document.getElementById('main-content');
                    if (contentArea) {
                        contentArea.innerHTML = html;
                    }
                }
            })
            .catch(function (err) {
                alert('Error: ' + err.message);
            });

        return false; // Prevent form submission
    };
</script>