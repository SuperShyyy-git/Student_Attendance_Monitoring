<?php
/**
 * Export Attendance Records with Filters
 * Exports filtered attendance history to CSV format
 */
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include __DIR__ . "/../config/db_connect.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("<b>ERROR:</b> Database connection not created.");
}

// Get filter parameters (same as attendance_table.php)
$filterSection = isset($_GET['section']) ? $_GET['section'] : '';
$filterGradeLevel = isset($_GET['grade_level']) ? $_GET['grade_level'] : '';
$filterStudent = isset($_GET['student']) ? $_GET['student'] : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Build query with filters
$sql = "SELECT attendance_id, student_name, section, grade_level, 
        attendance_date, attendance_time, status
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

// Generate filename with date range
$filename = 'attendance_history_' . $startDate . '_to_' . $endDate . '.csv';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Add BOM for Excel compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Write CSV header
fputcsv($output, ['ID', 'Student Name', 'Section', 'Grade Level', 'Date', 'Time', 'Status']);

// Write data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['attendance_id'],
        $row['student_name'],
        $row['section'] ?? '-',
        $row['grade_level'] ?? '-',
        date('Y-m-d', strtotime($row['attendance_date'])),
        date('h:i:s A', strtotime($row['attendance_time'])),
        $row['status']
    ]);
}

fclose($output);
exit;
