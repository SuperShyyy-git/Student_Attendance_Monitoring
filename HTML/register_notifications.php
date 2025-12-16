<?php
// Notification registration page removed. Chat IDs are auto-registered by the background service.
header('Content-Type: text/plain; charset=utf-8');
http_response_code(410);
echo "Notification registration removed. Chat IDs are auto-registered by the background service.\n";
exit;
?>