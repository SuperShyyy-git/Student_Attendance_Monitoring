<?php
/**
 * Get Face Encodings API
 * Returns all registered student face encodings for JavaScript-based face matching
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include __DIR__ . "/../config/db_connect.php";

// Get all students with face encodings
$result = $conn->query("
    SELECT id, student_id, firstname, middlename, lastname, section, grade_level, 
           guardian_name, guardian_email, face_encoding 
    FROM students 
    WHERE face_encoding IS NOT NULL 
    AND face_encoding != '' 
    AND face_encoding != '[]'
");

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Database query failed']);
    exit;
}

$students = [];

while ($row = $result->fetch_assoc()) {
    $encoding = json_decode($row['face_encoding'], true);
    
    // Only include if encoding is valid array with 128 elements
    if (is_array($encoding) && count($encoding) === 128) {
        $students[] = [
            'id' => (int)$row['id'],
            'student_id' => $row['student_id'],
            'name' => trim($row['firstname'] . ' ' . ($row['middlename'] ?? '') . ' ' . $row['lastname']),
            'section' => $row['section'],
            'grade_level' => $row['grade_level'],
            'guardian_name' => $row['guardian_name'],
            'guardian_email' => $row['guardian_email'],
            'encoding' => $encoding
        ];
    }
}

echo json_encode([
    'success' => true,
    'count' => count($students),
    'students' => $students
]);
?>
