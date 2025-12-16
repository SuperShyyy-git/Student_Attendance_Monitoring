<?php
// View failed Telegram messages and retry them
header('Content-Type: application/json');

$logsFile = __DIR__ . "/../logs/php_rfid_errors.log";

if (!file_exists($logsFile)) {
    echo json_encode(['error' => 'Log file not found']);
    exit;
}

$content = file_get_contents($logsFile);
$lines = explode("\n", $content);

$failedMessages = [];
$successMessages = [];

foreach ($lines as $line) {
    if (strpos($line, '[TELEGRAM_QUEUE]') !== false) {
        // Extract failed message
        if (preg_match('/Chat=(\d+).*Message=(.+)$/', $line, $matches)) {
            $failedMessages[] = [
                'chat_id' => $matches[1],
                'message_snippet' => $matches[2],
                'timestamp' => substr($line, 1, 19)
            ];
        }
    }
    if (strpos($line, '[TELEGRAM_SUCCESS]') !== false) {
        $successMessages[] = [
            'timestamp' => substr($line, 1, 19),
            'line' => $line
        ];
    }
}

echo json_encode([
    'total_failed' => count($failedMessages),
    'total_successful' => count($successMessages),
    'failed_messages' => $failedMessages,
    'recent_successes' => array_slice($successMessages, -5)
], JSON_PRETTY_PRINT);
?>
