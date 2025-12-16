<?php
/**
 * Telegram getUpdates Poller
 * This poller has been disabled. The background auto-fetch service handles Chat ID detection and registration.
 */
header('Content-Type: text/plain; charset=utf-8');
http_response_code(410);
echo "This endpoint is disabled. Chat IDs are auto-registered by the background service.\n";
exit;
?>