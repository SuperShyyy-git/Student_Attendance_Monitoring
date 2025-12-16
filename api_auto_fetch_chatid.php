<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/auto_chatid_fetcher.php';

$token = '8591636394:AAGC95x20enHEhHoLrvcDDiUXfrCWJ5fJ2g';

if (!isset($conn) || !($conn instanceof mysqli)) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$fetcher = new AutoChatIDFetcher($token, $conn);
$result = $fetcher->run();

echo json_encode($result);
?>
