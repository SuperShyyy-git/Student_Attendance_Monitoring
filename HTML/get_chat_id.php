<?php
// This page has been disabled.
// Chat IDs are now automatically registered by the background auto-fetch service.
header('Content-Type: text/plain; charset=utf-8');
http_response_code(410);
echo "This page is disabled. Chat IDs are auto-registered when guardians message @AGSNHS_bot.\n";
exit;
?>