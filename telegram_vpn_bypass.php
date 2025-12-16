<?php
/**
 * Telegram Proxy/VPN Bypass Solution
 * Uses alternative DNS and proxy methods to reach Telegram API
 * when ISP is blocking direct connections
 */

class TelegramVPNBypass {
    private $token;
    private $timeout = 15;
    private $methods = [
        'direct' => 'https://api.telegram.org',
        'proxy_1' => 'https://api.telegram.org',  // Will use proxy below
        'ipv6_fallback' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334', // IPv6 if available
    ];
    
    public function __construct($token) {
        $this->token = $token;
    }
    
    /**
     * Send message with multiple fallback methods
     */
    public function sendWithFallback($chatId, $message) {
        // Method 1: Try direct connection first
        $result = $this->sendDirect($chatId, $message);
        if ($result['success']) {
            error_log("[TELEGRAM_SUCCESS] Sent via direct connection");
            return $result;
        }
        error_log("[TELEGRAM_FALLBACK_1] Direct failed, trying proxy...");
        
        // Method 2: Try via proxy/DNS rotation
        $result = $this->sendViaProxy($chatId, $message);
        if ($result['success']) {
            error_log("[TELEGRAM_SUCCESS] Sent via proxy");
            return $result;
        }
        error_log("[TELEGRAM_FALLBACK_2] Proxy failed, trying alternative DNS...");
        
        // Method 3: Try with alternative DNS (Cloudflare, Google)
        $result = $this->sendViaAlternativeDNS($chatId, $message);
        if ($result['success']) {
            error_log("[TELEGRAM_SUCCESS] Sent via alternative DNS");
            return $result;
        }
        error_log("[TELEGRAM_FALLBACK_3] All methods failed");
        
        return [
            'success' => false,
            'error' => 'All connection methods failed. Try: 1) Different WiFi, 2) VPN, 3) Mobile hotspot',
            'methods_tried' => ['direct', 'proxy', 'alt_dns']
        ];
    }
    
    /**
     * Direct connection attempt
     */
    private function sendDirect($chatId, $message) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.telegram.org/bot' . $this->token . '/sendMessage',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => $error, 'http_code' => $httpCode];
        }
        
        $data = json_decode($response, true);
        return [
            'success' => $data['ok'] ?? false,
            'error' => $data['description'] ?? null,
            'http_code' => $httpCode
        ];
    }
    
    /**
     * Try via SOCKS5 proxy (if configured)
     */
    private function sendViaProxy($chatId, $message) {
        // Common free SOCKS5 proxies that might work
        // NOTE: This is a fallback attempt - quality varies
        $proxies = [
            // Format: socks5://user:pass@proxy:port
            // For testing, try these free proxy lists:
            // - https://www.proxy-list.download/
            // - https://www.sslproxies.org/
        ];
        
        if (empty($proxies)) {
            return ['success' => false, 'error' => 'No proxies configured'];
        }
        
        foreach ($proxies as $proxy) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.telegram.org/bot' . $this->token . '/sendMessage',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown'
                ]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_PROXY => $proxy,
                CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response, true);
            if ($data['ok'] ?? false) {
                return ['success' => true];
            }
        }
        
        return ['success' => false, 'error' => 'All proxies failed'];
    }
    
    /**
     * Try with alternative DNS servers
     */
    private function sendViaAlternativeDNS($chatId, $message) {
        // Alternative IPs for api.telegram.org
        $altIPs = [
            '149.154.167.220', // Primary Telegram IP
            '149.154.167.221',
            '149.154.167.222',
            '149.154.167.223',
            '149.154.167.224',
            '149.154.167.225',
        ];
        
        foreach ($altIPs as $ip) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://' . $ip . '/bot' . $this->token . '/sendMessage',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown'
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Host: api.telegram.org'
                ],
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
            
            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            
            if (!$error) {
                $data = json_decode($response, true);
                if ($data['ok'] ?? false) {
                    error_log("[TELEGRAM_ALT_IP] Success with IP: $ip");
                    return ['success' => true, 'ip_used' => $ip];
                }
            }
        }
        
        return ['success' => false, 'error' => 'All alternative IPs failed'];
    }
}

/**
 * Quick test function
 */
function testTelegramBypass($token, $chatId) {
    $bypass = new TelegramVPNBypass($token);
    $result = $bypass->sendWithFallback($chatId, '🔧 Testing Telegram bypass connection...');
    return $result;
}
?>
