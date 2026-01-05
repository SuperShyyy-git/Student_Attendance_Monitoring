<?php
/**
 * Face Recognition Check-in Process
 * Records attendance for a matched student (matching done in JavaScript)
 */

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

include __DIR__ . "/../config/db_connect.php";
require_once __DIR__ . '/email_notifier.php';

// Check if this is a JavaScript-matched request (new method) or legacy image-based request
if (isset($_POST['matched_student_id'])) {
    // NEW METHOD: JavaScript already matched the face, we just record attendance
    $matchedStudentId = (int)$_POST['matched_student_id'];
    $imageData = isset($_POST['image']) ? $_POST['image'] : '';
    $distance = isset($_POST['distance']) ? floatval($_POST['distance']) : 0;
    
    // Get student data
    $stmt = $conn->prepare("SELECT id, student_id, firstname, middlename, lastname, section, grade_level, guardian_name, guardian_email FROM students WHERE id = ?");
    $stmt->bind_param('i', $matchedStudentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    
    $fullName = trim($student['firstname'] . ' ' . ($student['middlename'] ?? '') . ' ' . $student['lastname']);
    $section = $student['section'] ?? '';
    $gradeLevel = $student['grade_level'] ?? '';
    
    // Save image if provided
    $imageFileName = '';
    if (!empty($imageData) && strpos($imageData, 'data:') === 0) {
        $uploadsDir = __DIR__ . "/../uploads";
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
        
        @list($meta, $b64) = explode(';', $imageData);
        @list(, $b64data) = explode(',', $imageData);
        
        if ($b64data) {
            $safeStudentId = preg_replace('/[^A-Za-z0-9_\-]/', '', ($student['student_id'] ?? $matchedStudentId));
            $imageFileName = 'scan_' . $safeStudentId . '_' . date('Ymd_His') . '.jpg';
            file_put_contents($uploadsDir . '/' . $imageFileName, base64_decode($b64data));
        }
    }
    
    // Determine TIME IN or TIME OUT
    $today = date('Y-m-d');
    $nowTime = date('H:i:s');
    
    // Check the latest record for today to determine next status
    $checkRecords = $conn->prepare("SELECT status FROM student_attendance WHERE student_name = ? AND attendance_date = ? ORDER BY id DESC LIMIT 1");
    $checkRecords->bind_param('ss', $fullName, $today);
    $checkRecords->execute();
    $latestRec = $checkRecords->get_result()->fetch_assoc();
    $checkRecords->close();
    
    $status = 'TIME IN';
    if ($latestRec) {
        $lastStatus = strtoupper(trim($latestRec['status']));
        if ($lastStatus === 'TIME IN') {
            $status = 'TIME OUT';
        } else if ($lastStatus === 'TIME OUT') {
            // Already completed one cycle today
            echo json_encode(['success' => false, 'message' => 'Attendance already completed for today']);
            exit;
        }
    }
    
    // Insert attendance record
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
            'time' => $timeDisplay,
            'match_distance' => $distance
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

// LEGACY METHOD: If no matched_student_id, return error (Python no longer supported)
echo json_encode([
    'success' => false, 
    'message' => 'Face matching must be done in browser. Please refresh the page.',
    'error' => 'legacy_method_deprecated'
]);
?>