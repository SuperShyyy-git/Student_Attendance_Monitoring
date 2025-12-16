<?php
include "../config/db_connect.php";
header("Content-Type: application/json");


// =============================================================================
// 0. REQUIRED FIELDS
// =============================================================================

$required = [
    'student_id', 'firstname', 'lastname',
    'year_level', 'section', 'guardian_name', 'guardian_contact',
    'rfid_code', 'photo_data'
];

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(["success" => false, "message" => "Missing field: $field"]);
        exit;
    }
}


// =============================================================================
// 1. SAVE BASE64 PHOTO → /uploads/
// =============================================================================

$photoData = $_POST['photo_data'];

$folder = "../uploads/";
if (!is_dir($folder)) mkdir($folder, 0777, true);

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
// 2. RUN PYTHON ENCODER (generate_encoding.py)
// =============================================================================

// ✔ YOUR REAL PYTHON LOCATION
$python = "C:/Users/shiel/AppData/Local/Programs/Python/Python310/python.exe";

// ✔ Path to your encoding script
$script = "C:/xampp/htdocs/attendance/python/generate_encoding.py";

if (!file_exists($script)) {
    echo json_encode(["success" => false, "message" => "Python script not found: $script"]);
    exit;
}

// Ensure python executable exists (common issue when Apache runs under another user)
if (!file_exists($python)) {
    echo json_encode(["success" => false, "message" => "Python executable not found: $python"]);
    exit;
}

$imgFullPath = realpath($filepath);

// Build command and redirect stderr to stdout so we capture all messages
$cmd = "\"$python\" \"$script\" \"$imgFullPath\" 2>&1";

// Prepare debug log
$logDir = __DIR__ . "/../logs";
if (!is_dir($logDir)) mkdir($logDir, 0777, true);
$logFile = $logDir . "/python_debug.log";

// Run python
$output = shell_exec($cmd);

// Log command and output for debugging
file_put_contents($logFile, date("Y-m-d H:i:s") . " CMD: $cmd\nOUTPUT: " . var_export($output, true) . "\n\n", FILE_APPEND);

if ($output === null) {
    echo json_encode(["success" => false, "message" => "shell_exec failed or is disabled on this server", "cmd" => $cmd]);
    exit;
}

if (trim($output) === "") {
    echo json_encode(["success" => false, "message" => "Python returned no output", "cmd" => $cmd]);
    exit;
}

$response = json_decode($output, true);

// If json_decode failed (null), try to extract JSON from output
// Python warnings appear before the JSON, so find and extract just the JSON part
if ($response === null && !empty($output)) {
    // Find where the JSON starts (look for leading { or [)
    $json_start = strpos($output, '{');
    if ($json_start !== false) {
        $json_str = substr($output, $json_start);
        $response = json_decode($json_str, true);
    }
}

// Decode response and provide more debug info when things fail
if (isset($response["error"])) {
    echo json_encode(["success" => false, "message" => "Encoder error: " . $response["error"], "python_output" => $response, "raw_output" => $output, "saved_image" => $savePathForDB]);
    exit;
}

if (!isset($response["encoding"])) {
    // If script provided a debug image path or model info, include it
    $debug = [];
    if (isset($response["debug_image"]) && !empty($response["debug_image"])) {
        $debug['debug_image'] = $response['debug_image'];
    }
    if (isset($response["model"])) {
        $debug['model_tried'] = $response['model'];
    }

    echo json_encode(["success" => false, "message" => "Face encoding missing", "python_output" => $response, "raw_output" => $output, "debug" => $debug, "saved_image" => $savePathForDB]);
    exit;
}

$encodingJson = json_encode($response["encoding"]);


// =============================================================================
// 3. INSERT INTO DATABASE
// =============================================================================

// Server-side duplicate check: ensure student_id or RFID not already present
$check = $conn->prepare("SELECT id FROM students WHERE student_id = ? OR rfid_code = ? LIMIT 1");
if ($check) {
    $check->bind_param('ss', $_POST['student_id'], $_POST['rfid_code']);
    $check->execute();
    $checkRes = $check->get_result();
    if ($checkRes && $checkRes->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Student with same Student ID or RFID already exists."]);
        exit;
    }
    $check->close();
}


$stmt = $conn->prepare("
    INSERT INTO students 
    (student_id, rfid_code, firstname, middlename, lastname, year_level, section,
     guardian_name, guardian_contact, photo_path, face_encoding)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
    "sssssssssss",
    $_POST['student_id'],
    $_POST['rfid_code'],
    $_POST['firstname'],
    $_POST['middlename'],
    $_POST['lastname'],
    $_POST['year_level'],
    $_POST['section'],
    $_POST['guardian_name'],
    $_POST['guardian_contact'],
    $savePathForDB,
    $encodingJson
);

if ($stmt->execute()) {
    // After inserting student, attempt to reconcile any previously-received guardian messages
    $newStudentId = $_POST['student_id'];
    $guardianContact = preg_replace('/[^0-9+]/', '', $_POST['guardian_contact']);
    // normalize +63 -> 0
    if (preg_match('/^\+63[0-9]{9}$/', $guardianContact)) {
        $guardianContact = '0' . substr($guardianContact, 3);
    }

    // Look for unprocessed telegram_inbox entries matching this guardian contact
    // Skip reconciliation if the `telegram_inbox` table does not exist (prevents fatal on restored DB)
    $tableCheck = $conn->query("SHOW TABLES LIKE 'telegram_inbox'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $find = $conn->prepare("SELECT chat_id, update_id FROM telegram_inbox WHERE phone_normalized = ? AND processed = 0 ORDER BY received_at DESC LIMIT 1");
        if ($find) {
            $find->bind_param('s', $guardianContact);
            $find->execute();
            $res = $find->get_result();
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $foundChat = $row['chat_id'];
                $foundUpdate = $row['update_id'];

                if (!empty($foundChat)) {
                    // assign chat_id to all students with this guardian contact that are missing chat_id
                    $upd = $conn->prepare("UPDATE students SET chat_id = ? WHERE guardian_contact = ? AND (chat_id IS NULL OR chat_id = '')");
                    if ($upd) {
                        $upd->bind_param('ss', $foundChat, $guardianContact);
                        $upd->execute();
                        $upd->close();
                    }

                    // mark telegram_inbox processed
                    $mark = $conn->prepare("UPDATE telegram_inbox SET processed = 1, processed_at = NOW() WHERE update_id = ?");
                    if ($mark) {
                        $mark->bind_param('i', $foundUpdate);
                        $mark->execute();
                        $mark->close();
                    }
                }
            }
            $find->close();
        }
    } else {
        // Table not present — skip automatic chat_id reconciliation
        error_log('[STUDENT_ADD_SAVE] telegram_inbox table not found; skipping chat_id reconciliation');
    }

    echo json_encode(["success" => true, "message" => "Student registered successfully", "student_id" => $_POST['student_id']]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error, "sql_error" => $conn->error]);
}

?>

