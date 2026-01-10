<?php
/**
 * Quick Check-in/Check-out Process
 * Handles attendance recording from the quick checkin form
 */

session_start();
if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

include __DIR__ . "/../config/db_connect.php";
require_once __DIR__ . '/email_notifier.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$studentRecordId = (int) $_POST['student_id']; // This is the ID from students table
$requestedStatus = $_POST['status'] ?? 'TIME IN';

// Get student data from students table
$stmt = $conn->prepare("SELECT id, student_id, firstname, middlename, lastname, section, grade_level, guardian_name, guardian_email FROM students WHERE id = ?");
$stmt->bind_param('i', $studentRecordId);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// Build full name properly (avoid double spaces)
$nameParts = [$student['firstname']];
if (!empty($student['middlename'])) {
    $nameParts[] = $student['middlename'];
}
$nameParts[] = $student['lastname'];
$fullName = implode(' ', $nameParts);

$section = $student['section'] ?? '';
$gradeLevel = $student['grade_level'] ?? '';
$studentId = $student['student_id']; // The actual student ID from the students table

// Determine TIME IN or TIME OUT
$today = date('Y-m-d');
$nowTime = date('H:i:s');

// Check the latest record for today to determine next status
$checkRecords = $conn->prepare("SELECT status FROM student_attendance WHERE student_id = ? AND attendance_date = ? ORDER BY id DESC LIMIT 1");
$checkRecords->bind_param('ss', $studentId, $today);
$checkRecords->execute();
$latestRec = $checkRecords->get_result()->fetch_assoc();
$checkRecords->close();

$status = $requestedStatus;

// Validate the requested status makes sense
if ($latestRec) {
    $lastStatus = strtoupper(trim($latestRec['status']));

    if ($lastStatus === 'TIME IN' && $requestedStatus === 'TIME IN') {
        echo json_encode(['success' => false, 'message' => 'Student already timed in. Please time out first.']);
        exit;
    }

    if ($lastStatus === 'TIME OUT' && $requestedStatus === 'TIME OUT') {
        echo json_encode(['success' => false, 'message' => 'Student already timed out. Cannot time out again.']);
        exit;
    }
} else {
    // No record today - can only TIME IN
    if ($requestedStatus !== 'TIME IN') {
        echo json_encode(['success' => false, 'message' => 'Student has not timed in yet today.']);
        exit;
    }
}

// Insert attendance record WITH student_id
$insertSql = "INSERT INTO student_attendance (student_id, student_name, section, grade_level, attendance_date, attendance_time, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
$ins = $conn->prepare($insertSql);

if (!$ins) {
    error_log("Prepare failed: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
    exit;
}

$ins->bind_param('sssssss', $studentId, $fullName, $section, $gradeLevel, $today, $nowTime, $status);
$ok = $ins->execute();

if (!$ok) {
    error_log("Execute failed: " . $ins->error);
    echo json_encode(['success' => false, 'message' => 'Database execute error: ' . $ins->error]);
    $ins->close();
    exit;
}

$ins->close();

if ($ok) {
    // Send email notification
    if (!empty($student['guardian_email'])) {
        $guardianName = $student['guardian_name'] ?? 'Parent/Guardian';
        $guardianEmail = $student['guardian_email'];

        $emailBody = EmailNotifier::formatAttendanceMessage(
            $guardianName,
            $fullName,
            $status,
            $today,
            date('h:i A', strtotime($nowTime)),
            $gradeLevel,
            $section
        );

        $subject = "Attendance Alert: $fullName - $status";

        $emailNotifier = new EmailNotifier();
        $emailNotifier->send($guardianEmail, $subject, $emailBody);
    }

    $timeDisplay = date('h:i A', strtotime($nowTime));
    echo json_encode([
        'success' => true,
        'message' => "✅ $status recorded for $fullName at $timeDisplay"
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>