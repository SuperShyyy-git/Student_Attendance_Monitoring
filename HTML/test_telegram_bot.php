<?php
header('Content-Type: application/json');

require_once __DIR__ . '/telegram_notifier.php';

$token = '8591636394:AAGC95x20enHEhHoLrvcDDiUXfrCWJ5fJ2g';
$telegram = new TelegramNotifier($token);

// Test sending to a test chat ID
// Replace this with an actual chat ID to test
$testChatId = isset($_GET['chat_id']) ? $_GET['chat_id'] : null;

if (!$testChatId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Please provide chat_id as query parameter: ?chat_id=YOUR_CHAT_ID'
    ]);
    exit;
}

$testMessage = "✅ *Test Message*\n\nYour Telegram bot is working correctly! This is a test notification from the Student Attendance System.";

$result = $telegram->send($testChatId, $testMessage);

echo json_encode($result);
?>
