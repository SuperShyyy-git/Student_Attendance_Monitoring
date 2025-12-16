<?php
// Chat ID management UI removed. Background service handles registrations.
header('Content-Type: text/plain; charset=utf-8');
http_response_code(410);
echo "Chat ID management UI removed. Chat IDs are auto-registered by the background service.\n";
exit;
?>