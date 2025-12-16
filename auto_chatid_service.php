<?php
/**
 * Background Auto Chat ID Fetcher Service
 * Runs silently in the background, no UI
 * Call this from a cron job or keep it running
 */

require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/auto_chatid_fetcher.php';

set_time_limit(0); // No timeout
ignore_user_abort(true); // Continue even if user disconnects

$token = '8591636394:AAGC95x20enHEhHoLrvcDDiUXfrCWJ5fJ2g';
$logFile = __DIR__ . '/logs/auto_chatid_service.log';

if (!isset($conn) || !($conn instanceof mysqli)) {
    error_log('[AUTO_SERVICE] Database connection failed', 3, $logFile);
    exit(1);
}

$fetcher = new AutoChatIDFetcher($token, $conn);

// Log service start
error_log('[AUTO_SERVICE] Service started at ' . date('Y-m-d H:i:s'), 3, $logFile);

// Main loop - fetch every 3 seconds
$lastFetchTime = 0;
$fetchInterval = 3; // seconds

while (true) {
    $currentTime = time();
    
    // Fetch every 3 seconds
    if ($currentTime - $lastFetchTime >= $fetchInterval) {
        $result = $fetcher->run();
        $lastFetchTime = $currentTime;
        
        // Log results
        if ($result['success']) {
            if ($result['total_registered'] > 0) {
                error_log(
                    '[REGISTERED] ' . json_encode($result['registered']),
                    3,
                    $logFile
                );
            }
        } else {
            error_log('[ERROR] ' . $result['error'], 3, $logFile);
        }
    }
    
    // Sleep for 100ms to avoid 100% CPU usage
    usleep(100000);
}
?>
