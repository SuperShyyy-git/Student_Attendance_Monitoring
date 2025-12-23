<?php
// Direct export script - run this directly in browser
session_start();
include __DIR__ . "/../config/db_connect.php";

$type = isset($_GET['type']) ? $_GET['type'] : 'students';

if ($type === 'students') {
    $result = $conn->query("SELECT student_id, firstname, middlename, lastname, section, grade_level, guardian_name FROM students ORDER BY lastname, firstname");
    $filename = 'students_export_' . date('Y-m-d') . '.csv';
} else {
    $result = $conn->query("SELECT student_name, section, grade_level, attendance_date, attendance_time, status FROM student_attendance ORDER BY attendance_date DESC, attendance_time DESC");
    $filename = 'attendance_export_' . date('Y-m-d') . '.csv';
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

$first = true;
while ($row = $result->fetch_assoc()) {
    if ($first) {
        fputcsv($output, array_keys($row));
        $first = false;
    }
    fputcsv($output, $row);
}

fclose($output);
exit;
