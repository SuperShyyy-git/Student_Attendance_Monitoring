<?php
/**
 * Auto Chat ID Fetcher
 * Polls Telegram for new messages and auto-registers Chat IDs to students
 */

require_once __DIR__ . '/telegram_queue.php';

class AutoChatIDFetcher {
    private $token;
    private $offsetFile;
    private $conn;
    
    public function __construct($token, $conn, $offsetDir = null) {
        $this->token = $token;
        $this->conn = $conn;
        
        if ($offsetDir === null) {
            $offsetDir = __DIR__ . '/logs';
        }
        if (!is_dir($offsetDir)) {
            mkdir($offsetDir, 0755, true);
        }
        $this->offsetFile = $offsetDir . '/telegram_auto_offset.txt';
        // Ensure telegram_inbox table exists for storing incoming updates
        $createSql = "CREATE TABLE IF NOT EXISTS telegram_inbox (
            id INT AUTO_INCREMENT PRIMARY KEY,
            update_id BIGINT UNIQUE,
            chat_id VARCHAR(64),
            username VARCHAR(255),
            first_name VARCHAR(255),
            text TEXT,
            phone_normalized VARCHAR(32),
            received_at DATETIME,
            processed TINYINT DEFAULT 0,
            processed_at DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        try {
            $this->conn->query($createSql);
        } catch (Exception $e) {
            error_log('[AUTO_CHATID] Failed to create telegram_inbox table: ' . $e->getMessage());
        }
    }
    
    /**
     * Get the next offset for getUpdates
     */
    private function getOffset() {
        if (file_exists($this->offsetFile)) {
            return (int)trim(file_get_contents($this->offsetFile));
        }
        return 0;
    }
    
    /**
     * Save offset for next poll
     */
    private function saveOffset($offset) {
        file_put_contents($this->offsetFile, $offset);
    }
    
    /**
     * Fetch new messages from Telegram
     */
    public function fetchUpdates() {
        $offset = $this->getOffset();
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.telegram.org/bot' . $this->token . '/getUpdates?offset=' . $offset . '&limit=10',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($error) {
            error_log("[AUTO_CHATID] Fetch error: $error");
            return ['success' => false, 'error' => $error, 'updates' => []];
        }
        
        $data = json_decode($response, true);
        if (!($data['ok'] ?? false)) {
            error_log("[AUTO_CHATID] API error: " . ($data['description'] ?? 'Unknown'));
            return ['success' => false, 'error' => $data['description'] ?? 'Unknown', 'updates' => []];
        }
        
        $updates = $data['result'] ?? [];
        
        if (!empty($updates)) {
            // Update offset to next update
            $lastUpdate = end($updates);
            $nextOffset = $lastUpdate['update_id'] + 1;
            $this->saveOffset($nextOffset);
            
            error_log("[AUTO_CHATID] Fetched " . count($updates) . " updates, next offset: $nextOffset");
        }
        
        return ['success' => true, 'updates' => $updates];
    }
    
    /**
     * Process updates and register Chat IDs
     */
    public function processUpdates($updates) {
        $registered = [];
        $queue = new TelegramQueue($this->token);

        foreach ($updates as $update) {
            if (!isset($update['message'])) {
                continue;
            }
            
            $msg = $update['message'];
            $chatId = $msg['chat']['id'] ?? null;
            $userName = $msg['chat']['username'] ?? null;
            $firstName = $msg['chat']['first_name'] ?? null;
            $text = $msg['text'] ?? '';
            $contact = $msg['contact'] ?? null;

            // Normalize phone from contact or text if present
            $phoneNumber = null;
            if (!empty($contact) && !empty($contact['phone_number'])) {
                $phoneNumber = preg_replace('/[^0-9+]/', '', $contact['phone_number']);
            } else {
                $t = trim($text);
                if (preg_match('/^\+63[0-9]{9}$/', $t)) {
                    $phoneNumber = '0' . substr($t, 3);
                } elseif (preg_match('/^09[0-9]{9}$/', $t) || preg_match('/^[0-9]{10,13}$/', $t)) {
                    $phoneNumber = preg_replace('/[^0-9]/', '', $t);
                }
            }

            $phoneNormalized = null;
            if (!empty($phoneNumber)) {
                $pn = $phoneNumber;
                if (preg_match('/^\+63[0-9]{9}$/', $pn)) {
                    $pn = '0' . substr($pn, 3);
                }
                if (preg_match('/^[0-9]{10,11}$/', $pn) && strpos($pn, '0') !== 0) {
                    $pn = '0' . $pn;
                }
                $phoneNormalized = $pn;
            }

            // Persist incoming update to telegram_inbox (if not exists)
            $updateId = $update['update_id'] ?? null;
            if ($updateId) {
                $ins = $this->conn->prepare("INSERT IGNORE INTO telegram_inbox (update_id, chat_id, username, first_name, text, phone_normalized, received_at, processed) VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)");
                if ($ins) {
                    $ins->bind_param('isssss', $updateId, $chatId, $userName, $firstName, $text, $phoneNormalized);
                    $ins->execute();
                    $ins->close();
                }
            }
            $contact = $msg['contact'] ?? null;
            
            if (!$chatId) {
                continue;
            }
            
            // New flow: If user sends /start → ask for registered mobile number only
            if (is_string($text) && preg_match('/^\/start\b/i', trim($text))) {
                $prompt = "Welcome! Please reply with the mobile number registered in the school system (e.g. 09171234567).\n\nJust send the mobile number — you don't need to send the student ID or name.";
                $queue->sendOrQueue($chatId, $prompt, 'Markdown');
                continue;
            }

            // If user shared a contact, normalize phone number
            $phoneNumber = null;
            if (!empty($contact) && !empty($contact['phone_number'])) {
                $phoneNumber = trim($contact['phone_number']);
            }

            // Try to extract student info from message
            // Format: mobile number (09...), or student id (STU123), or RFID numeric
            $studentId = null;
            $rfidCode = null;
            
            // If message looks like a phone number, treat it as phone number
            $normalizedText = trim($text);
            // Normalize +63 to 0 format
            if (preg_match('/^\+63[0-9]{9}$/', $normalizedText)) {
                $normalizedText = '0' . substr($normalizedText, 3);
            }
            if (preg_match('/^09[0-9]{9}$/', $normalizedText) || preg_match('/^[0-9]{10,13}$/', $normalizedText)) {
                // treat as phone
                $phoneNumber = $normalizedText;
            }

            // Try to match student ID format (STU001, STU123, etc.)
            if (preg_match('/STU\d+/i', $text, $matches)) {
                $studentId = strtoupper($matches[0]);
            }
            // Try to match RFID (usually long numeric)
            elseif (preg_match('/\d{10,}/', $text, $matches)) {
                $rfidCode = $matches[0];
            }
            // Try to find student by name or username
            elseif (!empty($firstName)) {
                // Will try to match by name
                $studentId = null;
            }
            
            // Search in database
            $student = null;
            
            if ($studentId) {
                $stmt = $this->conn->prepare("SELECT id, student_id, firstname, lastname FROM students WHERE student_id = ? LIMIT 1");
                $stmt->bind_param('s', $studentId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $student = $result->fetch_assoc();
                }
                $stmt->close();
            } elseif ($rfidCode) {
                $stmt = $this->conn->prepare("SELECT id, student_id, firstname, lastname FROM students WHERE rfid_code = ? LIMIT 1");
                $stmt->bind_param('s', $rfidCode);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $student = $result->fetch_assoc();
                }
                $stmt->close();
            } elseif (!empty($firstName)) {
                // Try to find by first name
                $stmt = $this->conn->prepare("SELECT id, student_id, firstname, lastname FROM students WHERE firstname LIKE ? LIMIT 1");
                $searchName = '%' . $firstName . '%';
                $stmt->bind_param('s', $searchName);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $student = $result->fetch_assoc();
                }
                $stmt->close();
            }
            
            // If no student matched yet but phone number was provided, try to match by guardian contact
            if (!$student && !empty($phoneNumber)) {
                // Normalize phone: accept +63 -> 09
                $pn = preg_replace('/[^0-9+]/', '', $phoneNumber);
                if (preg_match('/^\+63[0-9]{9}$/', $pn)) {
                    $pn = '0' . substr($pn, 3);
                }
                // Ensure leading 0 for local numbers of length 10 (add leading 0 if missing)
                if (preg_match('/^[0-9]{10,11}$/', $pn) && strpos($pn, '0') !== 0) {
                    $pn = '0' . $pn;
                }

                // Find all students for this guardian contact
                $stmt = $this->conn->prepare("SELECT id, student_id, firstname, lastname FROM students WHERE guardian_contact = ?");
                $stmt->bind_param('s', $pn);
                $stmt->execute();
                $result = $stmt->get_result();
                $matchedStudents = [];
                while ($row = $result->fetch_assoc()) {
                    $matchedStudents[] = $row;
                }
                $stmt->close();

                if (!empty($matchedStudents)) {
                    foreach ($matchedStudents as $stu) {
                        $updateStmt = $this->conn->prepare("UPDATE students SET chat_id = ? WHERE id = ?");
                        $updateStmt->bind_param('si', $chatId, $stu['id']);
                        if ($updateStmt->execute()) {
                            $registered[] = [
                                'student_id' => $stu['student_id'],
                                'name' => $stu['firstname'] . ' ' . $stu['lastname'],
                                'chat_id' => $chatId,
                                'method' => 'phone_match'
                            ];
                            error_log("[AUTO_CHATID_REGISTERED] Student: {$stu['student_id']} | Chat ID: $chatId");
                        }
                        $updateStmt->close();
                    }
                    // Send one confirmation mentioning how many students were linked
                    $count = count($matchedStudents);
                    $confirm = "✅ Your Telegram has been linked to the school system for {$count} student" . ($count > 1 ? 's' : '') . ". You will now receive attendance notifications.";
                    $queue->sendOrQueue($chatId, $confirm, 'Markdown');
                    // continue to next update
                    continue;
                }
            }

            // Register Chat ID for single matched student (studentId, rfid, or name)
            if ($student) {
                    $updateStmt = $this->conn->prepare("UPDATE students SET chat_id = ? WHERE id = ?");
                    $updateStmt->bind_param('si', $chatId, $student['id']);
                    if ($updateStmt->execute()) {
                    $registered[] = [
                        'student_id' => $student['student_id'],
                        'name' => $student['firstname'] . ' ' . $student['lastname'],
                        'chat_id' => $chatId,
                        'method' => !empty($studentId) ? 'student_id' : (!empty($rfidCode) ? 'rfid' : (!empty($phoneNumber) ? 'phone_match' : 'name_match'))
                    ];
                    error_log("[AUTO_CHATID_REGISTERED] Student: {$student['student_id']} | Chat ID: $chatId");
                    // mark telegram_inbox as processed for this update_id if present
                    if (!empty($updateId)) {
                        $mark = $this->conn->prepare("UPDATE telegram_inbox SET processed = 1, processed_at = NOW() WHERE update_id = ?");
                        if ($mark) {
                            $mark->bind_param('i', $updateId);
                            $mark->execute();
                            $mark->close();
                        }
                    }
                    // Send confirmation message
                    $confirm = "✅ Your Telegram has been linked to the school system for *{$student['firstname']} {$student['lastname']}* (ID: {$student['student_id']}). You will now receive attendance notifications.";
                    $queue->sendOrQueue($chatId, $confirm, 'Markdown');
                }
                $updateStmt->close();
            } else {
                error_log("[AUTO_CHATID_SKIPPED] Could not match student. Text: $text | Contact: " . ($phoneNumber ?? 'none') . " | Chat ID: $chatId");
                // If user sent a phone number but it didn't match, respond with an error prompt
                if (!empty($phoneNumber)) {
                    $err = "Mobile number not found in school records. Please check the number you sent or contact the school office for assistance.";
                    $queue->sendOrQueue($chatId, $err, 'Markdown');
                } else {
                    // If we reached here and it wasn't a phone or start, ask them to send the registered mobile number
                    $prompt = "Please send the mobile number registered in the school system (e.g. 09171234567). Just send the mobile number — you don't need to send student ID or name.";
                    $queue->sendOrQueue($chatId, $prompt, 'Markdown');
                }
            }
        }
        
        return $registered;
    }
    
    /**
     * Full process: fetch and register
     */
    public function run() {
        $fetchResult = $this->fetchUpdates();
        
        if (!$fetchResult['success']) {
            return [
                'success' => false,
                'error' => $fetchResult['error'],
                'registered' => []
            ];
        }
        
        $registered = $this->processUpdates($fetchResult['updates']);
        
        return [
            'success' => true,
            'updates_fetched' => count($fetchResult['updates']),
            'registered' => $registered,
            'total_registered' => count($registered)
        ];
    }
}
?>
