<?php
/**
 * Attendance Status Helper Functions
 * 
 * Reusable functions for determining and managing attendance status
 */

/**
 * Determine the attendance status for a student based on their attendance records
 * 
 * @param mysqli $conn Database connection
 * @param string $studentName Full name of the student
 * @param string $date Date to check (Y-m-d format), defaults to today
 * @return array Contains 'status' (TIME IN/TIME OUT), 'last_record', and 'count'
 */
function getAttendanceStatus($conn, $studentName, $date = null)
{
    if ($date === null) {
        $date = date('Y-m-d');
    }

    // Find the latest attendance record for this student on the given date
    $stmt = $conn->prepare("
        SELECT attendance_id, status, attendance_time 
        FROM student_attendance 
        WHERE student_name = ? AND attendance_date = ? 
        ORDER BY attendance_time DESC, attendance_id DESC 
        LIMIT 1
    ");
    $stmt->bind_param('ss', $studentName, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $lastRecord = $result->fetch_assoc();
    $stmt->close();

    // Count total records for today
    $countStmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM student_attendance 
        WHERE student_name = ? AND attendance_date = ?
    ");
    $countStmt->bind_param('ss', $studentName, $date);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countRow = $countResult->fetch_assoc();
    $totalRecords = $countRow['total'] ?? 0;
    $countStmt->close();

    // Determine next status
    $nextStatus = 'TIME IN';
    if ($lastRecord) {
        $lastStatus = strtoupper(trim($lastRecord['status'] ?? ''));
        if ($lastStatus === 'TIME IN') {
            $nextStatus = 'TIME OUT';
        }
    }

    return [
        'next_status' => $nextStatus,
        'last_record' => $lastRecord,
        'records_today' => $totalRecords,
        'is_present' => $totalRecords > 0
    ];
}

/**
 * Get the current attendance state for a student (present, absent, late, etc.)
 * 
 * @param mysqli $conn Database connection
 * @param string $studentName Full name of the student
 * @param string $date Date to check (Y-m-d format), defaults to today
 * @param string $lateThreshold Time after which student is considered late (H:i:s format)
 * @return array Contains 'state' (PRESENT/ABSENT/LATE), 'time_in', 'time_out'
 */
function getAttendanceState($conn, $studentName, $date = null, $lateThreshold = '08:00:00')
{
    if ($date === null) {
        $date = date('Y-m-d');
    }

    // Get all records for this student on the given date
    $stmt = $conn->prepare("
        SELECT status, attendance_time 
        FROM student_attendance 
        WHERE student_name = ? AND attendance_date = ? 
        ORDER BY attendance_time ASC
    ");
    $stmt->bind_param('ss', $studentName, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $timeIn = null;
    $timeOut = null;
    $isLate = false;

    while ($row = $result->fetch_assoc()) {
        $status = strtoupper(trim($row['status'] ?? ''));
        if ($status === 'TIME IN' && $timeIn === null) {
            $timeIn = $row['attendance_time'];
            // Check if late
            if ($timeIn > $lateThreshold) {
                $isLate = true;
            }
        } elseif ($status === 'TIME OUT') {
            $timeOut = $row['attendance_time'];
        }
    }
    $stmt->close();

    // Determine state
    $state = 'ABSENT';
    if ($timeIn !== null) {
        $state = $isLate ? 'LATE' : 'PRESENT';
    }

    return [
        'state' => $state,
        'time_in' => $timeIn,
        'time_out' => $timeOut,
        'is_late' => $isLate,
        'is_present' => $timeIn !== null
    ];
}

/**
 * Get attendance summary for a student over a date range
 * 
 * @param mysqli $conn Database connection
 * @param string $studentName Full name of the student
 * @param string $startDate Start date (Y-m-d format)
 * @param string $endDate End date (Y-m-d format)
 * @return array Contains counts for present, late, absent days
 */
function getAttendanceSummary($conn, $studentName, $startDate, $endDate)
{
    // Get distinct dates with TIME IN records
    $stmt = $conn->prepare("
        SELECT DISTINCT attendance_date, MIN(attendance_time) as first_time_in
        FROM student_attendance 
        WHERE student_name = ? 
        AND attendance_date BETWEEN ? AND ?
        AND UPPER(status) = 'TIME IN'
        GROUP BY attendance_date
        ORDER BY attendance_date ASC
    ");
    $stmt->bind_param('sss', $studentName, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $present = 0;
    $late = 0;
    $dates = [];

    while ($row = $result->fetch_assoc()) {
        $dates[] = $row['attendance_date'];
        if ($row['first_time_in'] > '08:00:00') {
            $late++;
        } else {
            $present++;
        }
    }
    $stmt->close();

    // Calculate total possible days (simple approach - count all days in range)
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $totalDays = $start->diff($end)->days + 1;

    return [
        'present' => $present,
        'late' => $late,
        'absent' => $totalDays - $present - $late,
        'total_days' => $totalDays,
        'attendance_rate' => $totalDays > 0 ? round((($present + $late) / $totalDays) * 100, 1) : 0,
        'dates_attended' => $dates
    ];
}

/**
 * Format attendance status for display with badge styling
 * 
 * @param string $status The attendance status (TIME IN, TIME OUT, PRESENT, LATE, ABSENT)
 * @return array Contains 'label', 'class', and 'icon'
 */
function formatAttendanceStatus($status)
{
    $status = strtoupper(trim($status));

    $formats = [
        'TIME IN' => [
            'label' => 'Time In',
            'class' => 'badge-success',
            'icon' => '↓',
            'color' => '#28a745'
        ],
        'TIME OUT' => [
            'label' => 'Time Out',
            'class' => 'badge-info',
            'icon' => '↑',
            'color' => '#17a2b8'
        ],
        'PRESENT' => [
            'label' => 'Present',
            'class' => 'badge-success',
            'icon' => '✓',
            'color' => '#28a745'
        ],
        'LATE' => [
            'label' => 'Late',
            'class' => 'badge-warning',
            'icon' => '⏰',
            'color' => '#ffc107'
        ],
        'ABSENT' => [
            'label' => 'Absent',
            'class' => 'badge-danger',
            'icon' => '✗',
            'color' => '#dc3545'
        ]
    ];

    return $formats[$status] ?? [
        'label' => $status,
        'class' => 'badge-secondary',
        'icon' => '•',
        'color' => '#6c757d'
    ];
}
