<?php
/**
 * Telegram Message Queue System
 * Stores messages locally when Telegram is unreachable
 * Automatically sends them when network becomes available
 */

class TelegramQueue {
    private $queueFile;
    private $token;
    
    public function __construct($token, $queueDir = null) {
        $this->token = $token;
        if ($queueDir === null) {
            $queueDir = __DIR__ . '/logs/telegram_queue';
        }
        if (!is_dir($queueDir)) {
            mkdir($queueDir, 0755, true);
        }
        $this->queueFile = $queueDir . '/message_queue.json';
    }
    
    /**
     * Try to send message, queue if fails
     */
    public function sendOrQueue($chatId, $message, $parseMode = 'Markdown') {
        // Try to send immediately
        $sent = $this->sendMessage($chatId, $message, $parseMode);
        
        if ($sent['success']) {
            error_log("[TELEGRAM_QUEUE] Message sent immediately to $chatId");
            return ['queued' => false, 'sent' => true, 'message' => 'Message sent'];
        }
        
        // If failed, queue it
        $this->addToQueue($chatId, $message, $parseMode);
        error_log("[TELEGRAM_QUEUE] Message queued for $chatId - " . $sent['error']);
        
        return ['queued' => true, 'sent' => false, 'message' => 'Message queued - will send when network available'];
    }

    /**
     * Try to send photo, queue if fails
     * @param string $chatId
     * @param string $filePath - absolute path to image file
     * @param string $caption
     * @param string $parseMode
     */
    public function sendOrQueuePhoto($chatId, $filePath, $caption = '', $parseMode = 'Markdown') {
        // Try to send immediately
        $sent = $this->sendPhoto($chatId, $filePath, $caption, $parseMode);

        if ($sent['success']) {
            error_log("[TELEGRAM_QUEUE] Photo sent immediately to $chatId");
            return ['queued' => false, 'sent' => true, 'message' => 'Photo sent'];
        }

        // If failed, queue it
        $this->addPhotoToQueue($chatId, $filePath, $caption, $parseMode);
        error_log("[TELEGRAM_QUEUE] Photo queued for $chatId - " . $sent['error']);

        return ['queued' => true, 'sent' => false, 'message' => 'Photo queued - will send when network available'];
    }
    
    /**
     * Send single message via cURL
     */
    private function sendMessage($chatId, $message, $parseMode) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.telegram.org/bot' . $this->token . '/sendMessage',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => $parseMode
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => $error];
        }
        
        $data = json_decode($response, true);
        if ($data['ok'] ?? false) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => $data['description'] ?? 'Unknown error'];
    }

    /**
     * Send a photo via Telegram Bot API by uploading the file
     */
    private function sendPhoto($chatId, $filePath, $caption, $parseMode) {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found: ' . $filePath];
        }

        $ch = curl_init();
        $post = [
            'chat_id' => $chatId,
            'caption' => $caption,
            'parse_mode' => $parseMode,
            'photo' => new CURLFile($filePath)
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.telegram.org/bot' . $this->token . '/sendPhoto',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_HTTPHEADER => [],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        $data = json_decode($response, true);
        if ($data['ok'] ?? false) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => $data['description'] ?? 'Unknown error'];
    }
    
    /**
     * Add message to queue
     */
    private function addToQueue($chatId, $message, $parseMode) {
        $queue = [];
        if (file_exists($this->queueFile)) {
            $queue = json_decode(file_get_contents($this->queueFile), true) ?? [];
        }
        
        $queue[] = [
            'chat_id' => $chatId,
            'message' => $message,
            'parse_mode' => $parseMode,
            'queued_at' => date('Y-m-d H:i:s'),
            'attempts' => 0
        ];
        
        file_put_contents($this->queueFile, json_encode($queue, JSON_PRETTY_PRINT));
        error_log("[TELEGRAM_QUEUE] Added to queue. Queue size: " . count($queue));
    }

    /**
     * Add photo item to queue
     */
    private function addPhotoToQueue($chatId, $filePath, $caption, $parseMode) {
        $queue = [];
        if (file_exists($this->queueFile)) {
            $queue = json_decode(file_get_contents($this->queueFile), true) ?? [];
        }

        $queue[] = [
            'type' => 'photo',
            'chat_id' => $chatId,
            'file' => $filePath,
            'caption' => $caption,
            'parse_mode' => $parseMode,
            'queued_at' => date('Y-m-d H:i:s'),
            'attempts' => 0
        ];

        file_put_contents($this->queueFile, json_encode($queue, JSON_PRETTY_PRINT));
        error_log("[TELEGRAM_QUEUE] Photo added to queue. Queue size: " . count($queue));
    }
    
    /**
     * Process all queued messages
     */
    public function processQueue() {
        if (!file_exists($this->queueFile)) {
            return ['processed' => 0, 'failed' => 0, 'messages' => 'Queue is empty'];
        }
        
        $queue = json_decode(file_get_contents($this->queueFile), true) ?? [];
        $processed = 0;
        $failed = 0;
        $remaining = [];
        
        foreach ($queue as $item) {
            $item['attempts'] = ($item['attempts'] ?? 0) + 1;
            
            // Skip if too many attempts (more than 10 tries over time)
            if ($item['attempts'] > 10) {
                error_log("[TELEGRAM_QUEUE] Discarding message after 10 attempts: " . $item['message']);
                continue;
            }
            
            if (($item['type'] ?? 'message') === 'photo') {
                $result = $this->sendPhoto($item['chat_id'], $item['file'], $item['caption'] ?? '', $item['parse_mode'] ?? 'Markdown');
                if ($result['success']) {
                    $processed++;
                    error_log("[TELEGRAM_QUEUE] Sent queued photo: {$item['file']}");
                } else {
                    $failed++;
                    $remaining[] = $item;
                    error_log("[TELEGRAM_QUEUE] Failed to send queued photo (attempt {$item['attempts']}): {$result['error']}");
                }
            } else {
                $result = $this->sendMessage($item['chat_id'], $item['message'], $item['parse_mode']);

                if ($result['success']) {
                    $processed++;
                    error_log("[TELEGRAM_QUEUE] Sent queued message: {$item['message']}");
                } else {
                    $failed++;
                    $remaining[] = $item;
                    error_log("[TELEGRAM_QUEUE] Failed to send queued message (attempt {$item['attempts']}): {$result['error']}");
                }
            }
        }
        
        // Save remaining messages back to queue
        if (!empty($remaining)) {
            file_put_contents($this->queueFile, json_encode($remaining, JSON_PRETTY_PRINT));
        } else {
            if (file_exists($this->queueFile)) {
                unlink($this->queueFile);
            }
        }
        
        return [
            'processed' => $processed,
            'failed' => $failed,
            'queue_remaining' => count($remaining),
            'messages' => "Processed $processed messages, $failed still pending"
        ];
    }
    
    /**
     * Get queue status
     */
    public function getQueueStatus() {
        if (!file_exists($this->queueFile)) {
            return [
                'queue_size' => 0,
                'messages' => [],
                'status' => 'Queue is empty'
            ];
        }
        
        $queue = json_decode(file_get_contents($this->queueFile), true) ?? [];
        
        return [
            'queue_size' => count($queue),
            'messages' => $queue,
            'status' => count($queue) . ' messages waiting to be sent'
        ];
    }
}
?>
