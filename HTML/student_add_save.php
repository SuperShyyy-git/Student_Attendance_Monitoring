<?php
include "../config/db_connect.php";
header("Content-Type: application/json");


// =============================================================================
// 0. REQUIRED FIELDS (student_id is now auto-generated)
// =============================================================================

$required = [
    'firstname',
    'lastname',
    'grade_level',
    'section',
    'guardian_name',
    'guardian_contact',
    'photo_data'
];

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(["success" => false, "message" => "Missing field: $field"]);
        exit;
    }
}

// =============================================================================
// 0.5 AUTO-GENERATE STUDENT ID (Format: STU-YYYY-XXXX)
// =============================================================================

$year = date('Y');
$prefix = "STU-{$year}-";

// Get the last student ID for this year
$result = $conn->query("SELECT student_id FROM students WHERE student_id LIKE '{$prefix}%' ORDER BY student_id DESC LIMIT 1");

if ($result && $result->num_rows > 0) {
    $lastId = $result->fetch_assoc()['student_id'];
    // Extract the number part and increment
    $lastNum = (int) substr($lastId, -4);
    $newNum = $lastNum + 1;
} else {
    $newNum = 1;
}

$student_id = $prefix . str_pad($newNum, 4, '0', STR_PAD_LEFT);



// =============================================================================
// 1. SAVE BASE64 PHOTO → /uploads/
// =============================================================================

$photoData = $_POST['photo_data'];

$folder = "../uploads/";
if (!is_dir($folder))
    mkdir($folder, 0777, true);

$filename = "student_" . time() . ".png";
$filepath = $folder . $filename;

$photoData = preg_replace('#^data:image/\w+;base64,#i', '', $photoData);
$photoData = str_replace(" ", "+", $photoData);

if (!file_put_contents($filepath, base64_decode($photoData))) {
    echo json_encode(["success" => false, "message" => "Failed saving photo"]);
    exit;
}

$savePathForDB = "uploads/" . $filename;


// =============================================================================
// 2. GET FACE ENCODING FROM BROWSER (face-api.js)
// =============================================================================
// Face encoding is now generated in the browser using face-api.js
// and sent via the hidden 'face_encoding' form field

if (empty($_POST['face_encoding'])) {
    echo json_encode(["success" => false, "message" => "No face encoding provided. Please capture a photo with a visible face."]);
    exit;
}

$encodingJson = $_POST['face_encoding'];

// Validate that it's valid JSON
$testDecode = json_decode($encodingJson);
if ($testDecode === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["success" => false, "message" => "Invalid face encoding format"]);
    exit;
}


// =============================================================================
// 3. INSERT INTO DATABASE
// =============================================================================

// Server-side duplicate check: ensure student_id not already present
$check = $conn->prepare("SELECT id FROM students WHERE student_id = ? LIMIT 1");
if ($check) {
    $check->bind_param('s', $student_id);
    $check->execute();
    $checkRes = $check->get_result();
    if ($checkRes && $checkRes->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Student with same Student ID already exists."]);
        exit;
    }
    $check->close();
}


$stmt = $conn->prepare("
    INSERT INTO students 
    (student_id, firstname, middlename, lastname, address, grade_level, section,
     guardian_name, guardian_contact, guardian_email, photo_path, face_encoding)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
");

// Get guardian_email from POST, default to empty if not provided
$guardian_email = isset($_POST['guardian_email']) ? trim($_POST['guardian_email']) : '';

$stmt->bind_param(
    "ssssssssssss",
    $student_id,
    $_POST['firstname'],
    $_POST['middlename'],
    $_POST['lastname'],
    $_POST['address'],
    $_POST['grade_level'],
    $_POST['section'],
    $_POST['guardian_name'],
    $_POST['guardian_contact'],
    $guardian_email,
    $savePathForDB,
    $encodingJson
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Student registered successfully", "student_id" => $student_id]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error, "sql_error" => $conn->error]);
}

?>