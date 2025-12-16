<?php
header('Content-Type: text/html; charset=utf-8');
<?php
// This page has been disabled.
// Chat IDs are now automatically registered by the background auto-fetch service.
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
echo "This page is disabled. Chat IDs are auto-registered when guardians message @AGSNHS_bot.\n";
<?php
// Manual registration page removed. Use background auto-registration service.
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
echo "Manual chat ID registration has been removed. Chat IDs are auto-registered by the background service.\n";
exit;
