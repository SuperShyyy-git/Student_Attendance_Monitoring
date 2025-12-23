<?php
include "config/db_connect.php";

$firstname = "John";
$middlename = "D";
$lastname = "Doe";
$address = "123 Test St, Sampaloc, Manila";
$grade_level = "Grade 10";
$section = "A";
$guardian_name = "Jane Doe";
$guardian_contact = "09123456789";

// Auto-generate student ID
$year = date('Y');
$prefix = "STU-{$year}-";
$result = $conn->query("SELECT student_id FROM students WHERE student_id LIKE '{$prefix}%' ORDER BY student_id DESC LIMIT 1");

if ($result && $result->num_rows > 0) {
    $lastId = $result->fetch_assoc()['student_id'];
    $lastNum = (int) substr($lastId, -4);
    $newNum = $lastNum + 1;
} else {
    $newNum = 1;
}
$student_id = $prefix . str_pad($newNum, 4, '0', STR_PAD_LEFT);

// Dummy face encoding (128-d vector of zeros)
$encoding = array_fill(0, 128, 0.0);
$encodingJson = json_encode($encoding);
$photo_path = "uploads/default_student.png";

$stmt = $conn->prepare("
    INSERT INTO students 
    (student_id, firstname, middlename, lastname, address, grade_level, section,
     guardian_name, guardian_contact, photo_path, face_encoding)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
    "sssssssssss",
    $student_id,
    $firstname,
    $middlename,
    $lastname,
    $address,
    $grade_level,
    $section,
    $guardian_name,
    $guardian_contact,
    $photo_path,
    $encodingJson
);

if ($stmt->execute()) {
    echo "Student created successfully! Student ID: " . $student_id . "\n";
} else {
    echo "Error: " . $stmt->error . "\n";
}
?>