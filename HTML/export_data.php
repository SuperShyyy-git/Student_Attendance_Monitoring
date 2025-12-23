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

$studentCount = 0;
$attendanceCount = 0;
$result = $conn->query("SELECT COUNT(*) as cnt FROM students");
if ($result)
    $studentCount = $result->fetch_assoc()['cnt'];
$result = $conn->query("SELECT COUNT(*) as cnt FROM student_attendance");
if ($result)
    $attendanceCount = $result->fetch_assoc()['cnt'];

// Handle download
if (isset($_GET['download'])) {
    $type = $_GET['type'] ?? 'students';
    $filename = $type === 'students' ? 'students_export_' . date('Y-m-d') . '.csv' : 'attendance_export_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    if ($type === 'students') {
        fputcsv($output, ['Student ID', 'First Name', 'Middle Name', 'Last Name', 'Section', 'Grade Level', 'Guardian Name', 'Guardian Contact', 'Telegram Status']);
        $result = $conn->query("SELECT student_id, firstname, middlename, lastname, section, grade_level, guardian_name, guardian_contact, chat_id FROM students ORDER BY lastname, firstname");
        while ($row = $result->fetch_assoc()) {
            $row['chat_id'] = !empty($row['chat_id']) ? 'Connected' : 'Not Connected';
            fputcsv($output, $row);
        }
    } else {
        fputcsv($output, ['ID', 'Student Name', 'Section', 'Grade Level', 'Date', 'Time', 'Status']);
        $result = $conn->query("SELECT attendance_id, student_name, section, grade_level, attendance_date, attendance_time, status FROM student_attendance ORDER BY attendance_date DESC, attendance_time DESC");
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }

    fclose($output);
    exit;
}
?>

<div class="header-bar">
    <h2 class='table-title'>📥 Export PDF / Excel</h2>
    <button id="btn-logout" class="btn-logout">Logout</button>
</div>

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
                <?php echo number_format($studentCount); ?> <span
                    style="font-size: 14px; color: #7f8c8d; font-weight: normal;">records</span>
            </div>
            <a href="export_data.php?download=1&type=students" class="btn-add-student"
                style="background: #27ae60; display: inline-block; text-decoration: none;">
                📥 Download CSV
            </a>
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
                <?php echo number_format($attendanceCount); ?> <span
                    style="font-size: 14px; color: #7f8c8d; font-weight: normal;">records</span>
            </div>
            <a href="export_data.php?download=1&type=attendance" class="btn-add-student"
                style="background: #27ae60; display: inline-block; text-decoration: none;">
                📥 Download CSV
            </a>
        </div>
    </div>

</div>

<!-- INFO BOX -->
<div style="background: #e8f4fd; border: 1px solid #3498db; border-radius: 8px; padding: 15px; margin-top: 20px;">
    <strong>💡 Tip:</strong> CSV files can be opened in Microsoft Excel, Google Sheets, or any spreadsheet application.
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
</style>