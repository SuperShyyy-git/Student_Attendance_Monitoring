<?php
// Manual chat ID form removed. Background service auto-registers chat IDs.
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
echo "Manual chat ID form removed. Chat IDs are auto-registered by the background service.\n";
exit;
