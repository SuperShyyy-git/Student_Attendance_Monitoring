<?php
header('Content-Type: text/html; charset=utf-8');

$offsetFile = __DIR__ . '/../logs/telegram_offset.txt';
$chatIdFile = __DIR__ . '/../logs/telegram_chat_ids.json';
$logFile = __DIR__ . '/../logs/php_rfid_errors.log';

$currentOffset = 0;
$chatIds = [];

if (file_exists($offsetFile)) {
    $currentOffset = (int)file_get_contents($offsetFile);
}

if (file_exists($chatIdFile)) {
    $chatIds = json_decode(file_get_contents($chatIdFile), true) ?: [];
}

// Get recent logs
$recentLogs = [];
if (file_exists($logFile)) {
    $lines = array_reverse(file($logFile));
    $recentLogs = array_slice($lines, 0, 20);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Telegram Chat ID Polling</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        h1 { color: #333; margin-bottom: 30px; }
        
        .panel { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .panel h2 { color: #0088cc; margin-bottom: 15px; font-size: 18px; border-bottom: 2px solid #0088cc; padding-bottom: 10px; }
        
        .button-group { display: flex; gap: 10px; margin-bottom: 20px; }
        button { padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s; }
        .btn-primary { background: #0088cc; color: white; }
        .btn-primary:hover { background: #006699; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        
        .status { padding: 15px; border-radius: 6px; margin-bottom: 15px; }
        .status.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .stat { display: inline-block; margin-right: 30px; padding: 10px; }
        .stat-value { font-size: 24px; font-weight: bold; color: #0088cc; }
        .stat-label { font-size: 12px; color: #666; margin-top: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; font-weight: bold; }
        tr:hover { background: #f9f9f9; }
        
        .code { background: #f0f0f0; padding: 8px 12px; border-radius: 4px; font-family: monospace; font-size: 12px; }
        
        .log-viewer { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 12px; max-height: 400px; overflow-y: auto; }
        .log-line { margin-bottom: 3px; }
        .log-error { color: #f48771; }
        .log-success { color: #6a9955; }
        .log-info { color: #569cd6; }
        
        .empty { color: #999; font-style: italic; padding: 20px; text-align: center; }
        
        .steps { list-style: none; }
        .steps li { padding: 10px; margin: 5px 0; background: #f9f9f9; border-left: 4px solid #0088cc; }
        .steps li strong { color: #0088cc; }
    </style>
</head>
<body>
<div class="container">
    <h1>🤖 Telegram Chat ID Polling System</h1>
    
    <!-- Control Panel -->
    <div class="panel">
        <?php
        // This page has been removed. Chat ID polling and management run automatically in the background service.
        http_response_code(410);
        header('Content-Type: text/plain; charset=utf-8');
        echo "This page is removed. Chat IDs are auto-registered by the background service.\n";
        exit;
    <div class="panel">
        <h2>Setup Instructions</h2>
        <ol class="steps">
            <li><strong>Step 1:</strong> Open Telegram and search for <strong>@AGSNHS_bot</strong></li>
            <li><strong>Step 2:</strong> Tap the <strong>START</strong> button</li>
            <li><strong>Step 3:</strong> Click the <strong>"🔄 Poll for Updates Now"</strong> button above</li>
            <li><strong>Step 4:</strong> Your Chat ID will appear in the table above</li>
            <li><strong>Step 5:</strong> Click <strong>"📝 Register Chat IDs"</strong> to link it to your student record</li>
        </ol>
    </div>
    
    <!-- Recent Logs -->
    <div class="panel">
        <h2>Recent Activity Log</h2>
        <div class="log-viewer">
            <?php if (empty($recentLogs)): ?>
                <div class="log-line log-info">No activity yet</div>
            <?php else: ?>
                <?php foreach ($recentLogs as $line): 
                    $line = trim($line);
                    $class = '';
                    if (strpos($line, 'ERROR') !== false || strpos($line, 'error') !== false) {
                        $class = 'log-error';
                    } elseif (strpos($line, 'SUCCESS') !== false || strpos($line, 'success') !== false) {
                        $class = 'log-success';
                    } else {
                        $class = 'log-info';
                    }
                ?>
                    <div class="log-line <?php echo $class; ?>"><?php echo htmlspecialchars($line); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function pollUpdates() {
    const statusDiv = document.getElementById('pollStatus');
    statusDiv.innerHTML = '<div class="status info">⏳ Polling for updates...</div>';
    
    fetch('telegram_poller.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                let msg = '✅ Poll successful!';
                if (data.new_chat_ids.length > 0) {
                    msg += ' Found ' + data.new_chat_ids.length + ' new chat ID(s): ' + data.new_chat_ids.join(', ');
                } else {
                    msg += ' No new messages.';
                }
                statusDiv.innerHTML = '<div class="status success">' + msg + '</div>';
                setTimeout(() => location.reload(), 2000);
            } else {
                statusDiv.innerHTML = '<div class="status error">❌ Error: ' + data.error + '</div>';
            }
        })
        .catch(err => {
            statusDiv.innerHTML = '<div class="status error">❌ Network error: ' + err.message + '</div>';
        });
}
</script>
</body>
</html>
