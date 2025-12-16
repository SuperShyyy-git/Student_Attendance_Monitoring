<?php
/**
 * Telegram getUpdates Poller
 * Polls Telegram API for new messages and extracts chat IDs
 * Run this manually or via cron job: php telegram_poller.php
 */

header('Content-Type: application/json');

$token = '8591636394:AAGC95x20enHEhHoLrvcDDiUXfrCWJ5fJ2g';
$offsetFile = __DIR__ . '/../logs/telegram_offset.txt';
$chatIdFile = __DIR__ . '/../logs/telegram_chat_ids.json';

<?php
// This poller has been disabled. The background auto-fetch service handles Chat ID detection and registration.
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
echo "This endpoint is disabled. Chat IDs are auto-registered by the background service.\n";
exit;
    $processed++;
}

// Save updated chat IDs
if (!empty($newChatIds)) {
    file_put_contents($chatIdFile, json_encode($chatIds, JSON_PRETTY_PRINT));
    error_log("[POLLER_SAVED] Saved " . count($newChatIds) . " new chat IDs");
}

// Save new offset
file_put_contents($offsetFile, (string)$offset);

http_response_code(200);
echo json_encode([
    'success' => true,
    'processed' => $processed,
    'new_chat_ids' => $newChatIds,
    'current_offset' => $offset
]);

/**
 * Helper function to send message via Telegram API
 */
function sendMessage($chatId, $token, $message) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("[POLLER_RESPONSE] Sent to $chatId (HTTP $httpCode)");
    
    return $httpCode === 200;
}
?>
