<?php
header('Content-Type: application/json');

$token = '8591636394:AAGC95x20enHEhHoLrvcDDiUXfrCWJ5fJ2g';
$testChatId = '6548492790'; // Guardian's chat ID

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.telegram.org/bot' . $token . '/sendMessage',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'chat_id' => $testChatId,
        'text' => '✅ VPN Connection Test - Windscribe is working!',
        'parse_mode' => 'Markdown'
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYPEER => true,
]);

$startTime = microtime(true);
$response = curl_exec($ch);
$elapsedMs = (int)((microtime(true) - $startTime) * 1000);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error) {
    echo json_encode([
        'success' => false,
        'error' => $error,
        'http_code' => $httpCode,
        'time_ms' => $elapsedMs,
        'message' => 'VPN test failed - Windscribe may not be active or blocking is still occurring'
    ]);
} else {
    $data = json_decode($response, true);
    if ($data['ok'] ?? false) {
        echo json_encode([
            'success' => true,
            'message' => '✅ Windscribe VPN is working! Telegram messages can now be sent.',
            'message_id' => $data['result']['message_id'] ?? null,
            'http_code' => $httpCode,
            'time_ms' => $elapsedMs,
            'chat_id' => $testChatId
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $data['description'] ?? 'Unknown API error',
            'http_code' => $httpCode,
            'time_ms' => $elapsedMs
        ]);
    }
}
?>
