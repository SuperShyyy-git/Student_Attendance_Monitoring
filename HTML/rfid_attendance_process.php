<?php
// rfid_attendance_process.php
// Start output buffering to catch any stray output
ob_start();

header('Content-Type: application/json');
session_start();

// Load Telegram notification modules
require_once __DIR__ . '/telegram_notifier.php';
require_once __DIR__ . '/../telegram_queue.php';

// Enable error logging to diagnose issues
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_rfid_errors.log');

// Set execution timeout to 30 seconds to prevent hanging
set_time_limit(30);

// Suppress PHP warnings/notices that break JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Error handler to suppress HTML error output
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile:$errline");
    return true; // Suppress normal PHP error output
});

// Flag to prevent shutdown function from clearing output when json_exit is called
$jsonExited = false;

// Catch fatal errors before they output HTML
register_shutdown_function(function() {
    global $jsonExited;
    
    // Only clear buffer if we didn't exit via json_exit
    if (!$jsonExited && ob_get_length()) {
        ob_clean();
    }
    
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!$jsonExited) {
            error_log("FATAL: " . json_encode($error));
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error - check logs']);
        }
    }
});

// Helper function to output JSON and exit cleanly
function json_exit($data, $statusCode = 200) {
    global $jsonExited;
    $jsonExited = true;
    
    // Log before exiting
    error_log("[JSON_EXIT] Status: $statusCode, Data: " . json_encode($data));
    
    // Ensure all buffers are cleared EXCEPT the current one
    while (ob_get_level() > 1) {
        ob_end_clean();
    }
    ob_clean();
    
    http_response_code($statusCode);
    header('Content-Type: application/json', true);
    
    $json = json_encode($data);
    echo $json;
    error_log("[JSON_SENT] " . $json);
    flush();
    exit(0);
}

// TELEGRAM NOTIFICATION FUNCTION
function sendTelegram($chat_id, $message) {
    $token = "8570035647:AAHzmsXlROqMT90aS5rUTIFRxT93R-o7USQ"; // ← REPLACE THIS
    $url = "https://api.telegram.org/bot$token/sendMessage";

    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];

    error_log("[TELEGRAM_SEND_START] Chat ID: $chat_id | Message length: " . strlen($message));

    // Try HTTP first (port 80, less likely to be blocked)
    $success = false;
    
    if (function_exists('curl_init')) {
        // Try HTTP first
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=" . urlencode($message) . "&parse_mode=Markdown");
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if (!$curl_error && $http_code === 200) {
            error_log("[TELEGRAM_SUCCESS] HTTP port 80 worked! Chat ID: $chat_id");
            $success = true;
        } else {
            // Try HTTPS if HTTP failed
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            if (!$curl_error && $http_code === 200) {
                error_log("[TELEGRAM_SUCCESS] HTTPS worked! Chat ID: $chat_id");
                $success = true;
            } else {
                error_log("[TELEGRAM_ERROR] Both HTTP and HTTPS failed: $curl_error | Chat ID: $chat_id");
                // Queue message for later
                queueTelegramMessage($chat_id, $message);
            }
        }
    }
}

// Queue message locally when network is unavailable
function queueTelegramMessage($chat_id, $message) {
    $queueFile = __DIR__ . "/../logs/telegram_queue.json";
    
    $queued_message = [
        'chat_id' => $chat_id,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'sent' => false
    ];
    
    // Load existing queue
    $queue = [];
    if (file_exists($queueFile)) {
        $content = file_get_contents($queueFile);
        $queue = json_decode($content, true) ?: [];
    }
    
    // Add new message
    $queue[] = $queued_message;
    
    // Save queue
    file_put_contents($queueFile, json_encode($queue, JSON_PRETTY_PRINT));
    
    error_log("[TELEGRAM_QUEUED] Message queued for later: Chat=$chat_id | Timestamp=" . $queued_message['timestamp']);
}


// only allow machine sessions (optionally allow other roles if you prefer)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'machine') {
    json_exit(['success' => false, 'message' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_exit(['success' => false, 'message' => 'Invalid request method'], 405);
}

// Check for POST size issues
$contentLength = intval($_SERVER['CONTENT_LENGTH'] ?? 0);
$maxPostSize = intval(ini_get('post_max_size')) * 1024 * 1024;
$maxInputVars = intval(ini_get('max_input_vars'));

if ($contentLength > $maxPostSize) {
    json_exit([
        'success' => false, 
        'message' => "Request too large. Max: " . ini_get('post_max_size') . "B, Sent: " . round($contentLength / 1024 / 1024, 2) . "MB"
    ], 413);
}

$rfid = isset($_POST['rfid']) ? trim($_POST['rfid']) : '';
$imageData = isset($_POST['image']) ? $_POST['image'] : '';

if ($rfid === '') {
    json_exit(['success' => false, 'message' => 'RFID is empty']);
}

/* include DB connection */
@include __DIR__ . "/../config/db_connect.php";
if (!isset($conn) || !($conn instanceof mysqli)) {
    json_exit(['success' => false, 'message' => 'Database connection error'], 500);
}

/* lookup student by rfid_code */
$stmt = $conn->prepare("SELECT id, student_id, firstname, middlename, lastname, section, year_level FROM students WHERE rfid_code = ? LIMIT 1");
$stmt->bind_param('s', $rfid);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    json_exit(['success' => false, 'message' => 'RFID not recognized']);
}

$student = $res->fetch_assoc();
$stmt->close();

/* compose full name (option A: firstname middlename lastname) */
$full_name = trim($student['firstname'] . ' ' . $student['middlename'] . ' ' . $student['lastname']);
$section = $student['section'] ?? '';
$year_level = $student['year_level'] ?? '';

/* FACIAL RECOGNITION VERIFICATION */
$faceVerified = false;
$faceDistance = null;
$faceError = null;

if ($imageData && strpos($imageData, 'data:') === 0) {
    // Extract base64 and save temporarily
    @list($meta, $b64) = explode(';', $imageData);
    @list(, $b64data) = explode(',', $imageData);
    
    if ($b64data) {
        $decoded = base64_decode($b64data);
        if ($decoded !== false) {
            $uploadsDir = __DIR__ . "/../uploads";
            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
            
            $tempFileName = 'temp_face_' . time() . '_' . bin2hex(random_bytes(4)) . '.jpg';
            $tempFilePath = $uploadsDir . '/' . $tempFileName;
            file_put_contents($tempFilePath, $decoded);
            
            // Get stored encoding from database
            $encStmt = $conn->prepare("SELECT face_encoding FROM students WHERE id = ? LIMIT 1");
            $encStmt->bind_param('i', $student['id']);
            $encStmt->execute();
            $encRes = $encStmt->get_result();
            
            if ($encRes->num_rows > 0) {
                $encRow = $encRes->fetch_assoc();
                $storedEncoding = $encRow['face_encoding'] ?? '';
                
                if ($storedEncoding) {
                    // Call Python verification script with timeout handling
                    $python = "C:/Users/shiel/AppData/Local/Programs/Python/Python310/python.exe";
                    $verifyScript = __DIR__ . "/../python/verify_attendance_face.py";
                    
                    // Verify Python executable exists
                    if (!file_exists($python)) {
                        $faceError = "Python executable not found at: $python";
                        error_log("[FACE_VERIFY_ERROR] $faceError");
                    } else if (!file_exists($verifyScript)) {
                        $faceError = "Python script not found at: $verifyScript";
                        error_log("[FACE_VERIFY_ERROR] $faceError");
                    } else {
                        $cmd = escapeshellarg($python) . ' ' . escapeshellarg($verifyScript) . ' ' . escapeshellarg($tempFilePath) . ' ' . escapeshellarg($storedEncoding);
                        
                        // Log command for debugging
                        error_log("[FACE_VERIFY_CMD] RFID: $rfid | Command: " . substr($cmd, 0, 200));
                        
                        // Execute with timeout (exec() has no timeout, so we use shell_exec)
                        $output = shell_exec($cmd . ' 2>&1');
                        
                        // Check if output is empty (possible timeout or crash)
                        if ($output === null || $output === '') {
                            $faceError = 'Python script produced no output (possible timeout or crash)';
                            error_log("[FACE_VERIFY_ERROR] RFID: $rfid | $faceError | Command: $cmd");
                        } else {
                            // Log the raw output for debugging
                            error_log("[FACE_VERIFY_RAW] RFID: $rfid | Output: " . substr($output, 0, 500));
                            
                            // Extract JSON from output (in case of warnings)
                            $jsonStart = strpos($output, '{');
                            if ($jsonStart !== false) {
                                $jsonStr = substr($output, $jsonStart);
                                
                                // Find the end of the JSON object (last closing brace)
                                $jsonEnd = strrpos($jsonStr, '}');
                                if ($jsonEnd !== false) {
                                    $jsonStr = substr($jsonStr, 0, $jsonEnd + 1);
                                }
                                
                                $verifyResult = json_decode($jsonStr, true);
                                
                                if ($verifyResult) {
                                    $faceVerified = $verifyResult['match'] ?? false;
                                    $faceDistance = $verifyResult['distance'] ?? null;
                                    $faceError = $verifyResult['error'] ?? null;
                                    
                                    // Log verification result with full details
                                    error_log("[FACE_VERIFY] RFID: $rfid | Match: " . ($faceVerified ? 'YES' : 'NO') . " | Distance: $faceDistance | Error: " . ($faceError ?: 'none') . " | Method: " . ($verifyResult['detection_method'] ?? 'unknown') . " | Time: " . date('Y-m-d H:i:s'));
                                } else {
                                    $faceError = 'Failed to parse Python JSON response. Raw: ' . substr($output, 0, 300);
                                    error_log("[FACE_VERIFY_ERROR] Failed to parse JSON. Extracted: " . substr($jsonStr, 0, 300) . " | Full output: " . $output);
                                }
                            } else {
                                $faceError = 'No JSON in Python output. Output: ' . substr($output, 0, 300);
                                error_log("[FACE_VERIFY_ERROR] No JSON found. Full output: " . $output);
                            }
                        }
                    }
                } else {
                    $faceError = 'No stored face encoding for this student';
                }
            }
            $encStmt->close();
            
            // Clean up temp file
            @unlink($tempFilePath);
        }
    }
}

/* If facial recognition failed, reject attendance */
if ($imageData && !$faceVerified) {
    $message = 'Face verification failed';
    if ($faceError) {
        $message .= ': ' . $faceError;
    }
    json_exit([
        'success' => false,
        'message' => $message,
        'face_verified' => false,
        'face_distance' => $faceDistance,
        'debug_info' => [
            'error' => $faceError,
            'distance' => $faceDistance,
            'student_id' => $student['id'] ?? null
        ]
    ]);
}

/* decide whether this should be TIME IN or TIME OUT
   logic:
   - if no record today for this student => TIME IN
   - if there is at least one TIME IN for today but no TIME OUT after the last TIME IN => TIME OUT
   - otherwise start a new TIME IN
*/

/* normalize date */
$today = date('Y-m-d');
$now_time = date('H:i:s');

/* check today's records for this student (by student_id if present) */
$studentId = isset($student['id']) ? (int)$student['id'] : null;

/* find latest record today for this student */
if ($studentId) {
    $q = $conn->prepare("SELECT attendance_id, status FROM student_attendance WHERE student_name = ? AND attendance_date = ? ORDER BY attendance_time DESC, attendance_id DESC LIMIT 1");
    // Note: table uses student_name; if student_attendance has student_id column you can change query to use it.
    $q->bind_param('ss', $full_name, $today);
    $q->execute();
    $r = $q->get_result();
    $last = $r->fetch_assoc();
    $q->close();
} else {
    // fallback: search by name only
    $q = $conn->prepare("SELECT attendance_id, status FROM student_attendance WHERE student_name = ? AND attendance_date = ? ORDER BY attendance_time DESC, attendance_id DESC LIMIT 1");
    $q->bind_param('ss', $full_name, $today);
    $q->execute();
    $r = $q->get_result();
    $last = $r->fetch_assoc();
    $q->close();
}

/* determine new status */
$newStatus = 'TIME IN';
if ($last) {
    $lastStatus = strtoupper(trim($last['status'] ?? ''));
    if ($lastStatus === 'TIME IN') {
        // last was TIME IN — so do TIME OUT
        $newStatus = 'TIME OUT';
    } else {
        // last was TIME OUT (or something else) — start a new TIME IN
        $newStatus = 'TIME IN';
    }
}

/* handle image saving if present (data:image/jpeg;base64,...) */
$imageFileName = null;
if ($imageData && strpos($imageData, 'data:') === 0) {
    // extract base64
    @list($meta, $b64) = explode(';', $imageData);
    @list(, $b64data) = explode(',', $imageData);
    if ($b64data) {
        $decoded = base64_decode($b64data);
        if ($decoded !== false) {
            $uploadsDir = __DIR__ . "/../uploads";
            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

            $safeStudent = preg_replace('/[^A-Za-z0-9_\-]/', '', ($student['student_id'] ?? $studentId));
            $fileName = 'face_' . ($safeStudent ?: 'unknown') . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.jpg';
            $filePath = $uploadsDir . '/' . $fileName;
            if (file_put_contents($filePath, $decoded) !== false) {
                $imageFileName = $fileName;
            }
        }
    }
}

/* insert attendance record */
$insertSql = "INSERT INTO student_attendance (student_name, section, year_level, attendance_date, attendance_time, status, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)";
$ins = $conn->prepare($insertSql);
if (!$ins) {
    error_log("[DB_ERROR] Prepare failed: " . $conn->error);
    json_exit(['success' => false, 'message' => 'DB prepare failed: ' . $conn->error], 500);
}
$ins->bind_param('sssssss', $full_name, $section, $year_level, $today, $now_time, $newStatus, $imageFileName);
$ok = $ins->execute();
if (!$ok) {
    error_log("[DB_ERROR] Execute failed: " . $ins->error . " | Student: $full_name, Status: $newStatus");
}
$ins->close();

error_log("[DB_INSERT] RFID: $rfid | Student: $full_name | Status: $newStatus | Result: " . ($ok ? 'OK' : 'FAILED'));

if ($ok) {

    // Send Telegram notification to parent/guardian
    $parentStmt = $conn->prepare("SELECT guardian_name, chat_id FROM students WHERE id = ? LIMIT 1");
    if ($parentStmt) {
        $parentStmt->bind_param("i", $studentId);
        $parentStmt->execute();
        $parentRes = $parentStmt->get_result();
        $parent = $parentRes->fetch_assoc();
        $parentStmt->close();
        
        if ($parent && !empty($parent['chat_id'])) {
            $chat_id = $parent['chat_id'];
            $guardianName = $parent['guardian_name'] ?? 'Parent';
            
            // Format message
            $message = TelegramNotifier::formatAttendanceMessage(
                $guardianName,
                $full_name,
                $newStatus,
                $today,
                $now_time,
                $year_level,
                $section
            );
            
            // Initialize queue system - tries to send immediately, queues if ISP blocks
            $token = '8591636394:AAGC95x20enHEhHoLrvcDDiUXfrCWJ5fJ2g';
            $queue = new TelegramQueue($token);
            
            // Try to send, queue if fails. If image captured, send photo with caption
            if (!empty($imageFileName)) {
                $filePath = __DIR__ . "/../uploads/" . $imageFileName;
                $caption = $message;
                $queueResult = $queue->sendOrQueuePhoto($chat_id, $filePath, $caption);
            } else {
                $queueResult = $queue->sendOrQueue($chat_id, $message);
            }
            
            if ($queueResult['sent']) {
                error_log("[NOTIFICATION_SENT] Telegram message sent immediately to chat_id: $chat_id");
            } else if ($queueResult['queued']) {
                error_log("[NOTIFICATION_QUEUED] Message queued for chat_id: $chat_id (will send when network available)");
            }
        } else {
            error_log("[NOTIFICATION_SKIPPED] No chat_id found for student ID: $studentId");
        }
    }

    

    json_exit([
        'success' => true,
        'message' => "Recorded {$newStatus} for {$full_name} at {$now_time}",
        'face_verified' => $faceVerified,
        'face_distance' => $faceDistance
    ]);

} else {
    json_exit(['success' => false, 'message' => 'DB insert failed: ' . $conn->error], 500);
}

