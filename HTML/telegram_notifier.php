<?php
/**
 * Telegram Notification Module
 * Sends attendance alerts via Telegram Bot API
 */

class TelegramNotifier {
    private $token;
    private $timeout = 5;
    
    public function __construct($token) {
        $this->token = $token;
    }
    
    /**
     * Send message via Telegram Bot API
     * @param string $chat_id - Telegram chat ID
     * @param string $message - Message text
     * @param string $parse_mode - 'Markdown' or 'HTML'
     * @return array - Response with success status
     */
    public function send($chat_id, $message, $parse_mode = 'Markdown') {
        if (!$chat_id || !$message) {
            return [
                'success' => false,
                'error' => 'Missing chat_id or message',
                'http_code' => 0
            ];
        }
        
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";
        
        $payload = [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => $parse_mode
        ];
        
        error_log("[TELEGRAM_SEND] Chat: $chat_id | Message length: " . strlen($message));
        
        if (!function_exists('curl_init')) {
            error_log("[TELEGRAM_ERROR] cURL not available");
            return [
                'success' => false,
                'error' => 'cURL not available',
                'http_code' => 0
            ];
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        $result = [
            'success' => false,
            'http_code' => $http_code,
            'error' => $curl_error,
            'chat_id' => $chat_id
        ];
        
        if ($curl_error) {
            error_log("[TELEGRAM_ERROR] cURL failed: $curl_error | Chat: $chat_id");
            return $result;
        }
        
        if ($http_code === 200) {
            $json = json_decode($response, true);
            if ($json && $json['ok'] === true) {
                error_log("[TELEGRAM_SUCCESS] Message sent to chat: $chat_id");
                $result['success'] = true;
                $result['message_id'] = $json['result']['message_id'] ?? null;
                return $result;
            } else {
                $error_msg = $json['description'] ?? 'Unknown API error';
                error_log("[TELEGRAM_ERROR] API error: $error_msg | Chat: $chat_id");
                $result['error'] = $error_msg;
                return $result;
            }
        } else {
            error_log("[TELEGRAM_ERROR] HTTP $http_code | Response: " . substr($response, 0, 200) . " | Chat: $chat_id");
            $result['error'] = "HTTP $http_code: " . substr($response, 0, 100);
            return $result;
        }
    }
    
    /**
     * Format attendance notification message
     */
    public static function formatAttendanceMessage($guardianName, $studentName, $status, $date, $time, $yearLevel, $section) {
        return "📘 *Student Attendance Notification*\n\n" .
               "Good day, *{$guardianName}!*\n\n" .
               "This is to inform you that *{$studentName}* has *{$status}* today.\n\n" .
               "*Details:*\n" .
               "• *Date:* {$date}\n" .
               "• *Time:* {$time}\n" .
               "• *Year Level:* {$yearLevel}\n" .
               "• *Section:* {$section}";
    }
}
?>
