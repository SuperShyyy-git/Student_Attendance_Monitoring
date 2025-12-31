<?php
include "../config/db_connect.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = $_POST['id'] ?? '';
$firstname = $_POST['firstname'] ?? '';
$middlename = $_POST['middlename'] ?? '';
$lastname = $_POST['lastname'] ?? '';
$address = $_POST['address'] ?? '';
$grade_level = $_POST['grade_level'] ?? '';
$section = $_POST['section'] ?? '';
$guardian_name = $_POST['guardian_name'] ?? '';
$guardian_contact = $_POST['guardian_contact'] ?? '';
$guardian_email = $_POST['guardian_email'] ?? '';

if (empty($id) || empty($firstname) || empty($lastname)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

// Validate contact number format
if (!empty($guardian_contact) && !preg_match('/^09[0-9]{9}$/', $guardian_contact)) {
    echo json_encode(['success' => false, 'message' => 'Invalid contact number format. Must be 11 digits starting with 09.']);
    exit;
}

$sql = "UPDATE students SET 
        firstname = ?, 
        middlename = ?, 
        lastname = ?, 
        address = ?, 
        grade_level = ?, 
        section = ?, 
        guardian_name = ?, 
        guardian_contact = ?,
        guardian_email = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ssssssssssi", $firstname, $middlename, $lastname, $address, $grade_level, $section, $guardian_name, $guardian_contact, $guardian_email, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update student: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>