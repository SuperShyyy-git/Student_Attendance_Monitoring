<?php
/**
 * Face Recognition Check-in Process
 * Identifies student by face and records attendance
 */

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

include __DIR__ . "/../config/db_connect.php";
require_once __DIR__ . '/email_notifier.php';

// Get image data
$imageData = isset($_POST['image']) ? $_POST['image'] : '';

if (empty($imageData) || strpos($imageData, 'data:') !== 0) {
    echo json_encode(['success' => false, 'message' => 'No image provided']);
    exit;
}

// Save image temporarily
$uploadsDir = __DIR__ . "/../uploads";
if (!is_dir($uploadsDir))
    mkdir($uploadsDir, 0755, true);

// Extract base64
@list($meta, $b64) = explode(';', $imageData);
@list(, $b64data) = explode(',', $imageData);

if (!$b64data) {
    echo json_encode(['success' => false, 'message' => 'Invalid image data']);
    exit;
}

$decoded = base64_decode($b64data);
if ($decoded === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to decode image']);
    exit;
}

$tempFileName = 'face_scan_' . time() . '_' . bin2hex(random_bytes(4)) . '.jpg';
$tempFilePath = $uploadsDir . '/' . $tempFileName;
file_put_contents($tempFilePath, $decoded);

// Get all students with face encodings
$students = $conn->query("SELECT id, student_id, firstname, middlename, lastname, section, grade_level, guardian_name, guardian_email, face_encoding FROM students WHERE face_encoding IS NOT NULL AND face_encoding != ''");

if (!$students || $students->num_rows === 0) {
    @unlink($tempFilePath);
    echo json_encode(['success' => false, 'message' => 'No students registered with face data']);
    exit;
}

// Try to identify the face using Python script
$python = "C:/Users/shiel/AppData/Local/Programs/Python/Python310/python.exe";
$identifyScript = __DIR__ . "/../python/identify_face.py";

if (!file_exists($identifyScript)) {
    @unlink($tempFilePath);
    echo json_encode(['success' => false, 'message' => 'Face recognition script not found']);
    exit;
}

// Build encodings JSON for Python script
$encodingsData = [];
$studentsById = [];

while ($s = $students->fetch_assoc()) {
    $encoding = json_decode($s['face_encoding'], true);
    if ($encoding) {
        $encodingsData[$s['id']] = $encoding;
        $studentsById[$s['id']] = $s;
    }
}

if (empty($encodingsData)) {
    @unlink($tempFilePath);
    echo json_encode(['success' => false, 'message' => 'No valid face encodings found']);
    exit;
}

$encodingsJson = json_encode($encodingsData, JSON_UNESCAPED_SLASHES);

// Write encodings to temp file (avoids command line escaping issues)
$encodingsFile = $uploadsDir . '/temp_encodings_' . time() . '.json';
file_put_contents($encodingsFile, $encodingsJson);

// Run Python identification with file path instead of JSON string
$cmd = escapeshellarg($python) . ' ' . escapeshellarg($identifyScript) . ' ' . escapeshellarg($tempFilePath) . ' ' . escapeshellarg($encodingsFile);
$output = shell_exec($cmd . ' 2>&1');

// Clean up temp files
@unlink($tempFilePath);
@unlink($encodingsFile);

// Parse Python output
$jsonStart = strpos($output, '{');
if ($jsonStart === false) {
    echo json_encode(['success' => false, 'message' => 'Face recognition failed', 'debug' => $output]);
    exit;
}

$jsonStr = substr($output, $jsonStart);
$result = json_decode($jsonStr, true);

if (!$result || !isset($result['success'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid response from face recognition']);
    exit;
}

if (!$result['success']) {
    echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Face not recognized']);
    exit;
}

// Get matched student
$matchedStudentId = $result['student_id'];
if (!isset($studentsById[$matchedStudentId])) {
    echo json_encode(['success' => false, 'message' => 'Student data not found']);
    exit;
}

$student = $studentsById[$matchedStudentId];
$fullName = trim($student['firstname'] . ' ' . ($student['middlename'] ?? '') . ' ' . $student['lastname']);
$section = $student['section'] ?? '';
$gradeLevel = $student['grade_level'] ?? '';

// Determine TIME IN or TIME OUT
$today = date('Y-m-d');
$nowTime = date('H:i:s');

// Check last status
$lastCheck = $conn->prepare("SELECT status FROM student_attendance WHERE student_name = ? AND attendance_date = ? ORDER BY attendance_time DESC LIMIT 1");
$lastCheck->bind_param('ss', $fullName, $today);
$lastCheck->execute();
$lastResult = $lastCheck->get_result();
$lastRow = $lastResult->fetch_assoc();
$lastCheck->close();

$status = 'TIME IN';
if ($lastRow && strtoupper(trim($lastRow['status'])) === 'TIME IN') {
    $status = 'TIME OUT';
}

// Save the scan image permanently (don't delete it)
$safeStudentId = preg_replace('/[^A-Za-z0-9_\-]/', '', ($student['student_id'] ?? $matchedStudentId));
$imageFileName = 'scan_' . $safeStudentId . '_' . date('Ymd_His') . '.jpg';
$permanentImagePath = $uploadsDir . '/' . $imageFileName;

// Re-save the image (since we already decoded it earlier)
@list($meta, $b64) = explode(';', $imageData);
@list(, $b64data) = explode(',', $imageData);
if ($b64data) {
    file_put_contents($permanentImagePath, base64_decode($b64data));
}

// Insert attendance record with image
$insertSql = "INSERT INTO student_attendance (student_name, section, grade_level, attendance_date, attendance_time, status, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)";
$ins = $conn->prepare($insertSql);
$ins->bind_param('sssssss', $fullName, $section, $gradeLevel, $today, $nowTime, $status, $imageFileName);
$ok = $ins->execute();
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
        'message' => "$status recorded for $fullName",
        'student_name' => $fullName,
        'status' => $status,
        'time' => $timeDisplay
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>