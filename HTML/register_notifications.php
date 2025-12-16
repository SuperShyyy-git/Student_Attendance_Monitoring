<?php
header('Content-Type: text/html; charset=utf-8');
<?php
// Notification registration page removed. Chat IDs are auto-registered by the background service.
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
echo "Notification registration removed. Chat IDs are auto-registered by the background service.\n";
exit;
            if ($studentId) {
                // Update by student ID
                $stmt = $conn->prepare("UPDATE students SET chat_id = ? WHERE id = ?");
                $stmt->bind_param('si', $chatId, $studentId);
            } else {
                // Update by RFID
                $stmt = $conn->prepare("UPDATE students SET chat_id = ? WHERE rfid_code = ?");
                $stmt->bind_param('ss', $chatId, $rfid);
            }
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success = true;
                    $message = '✅ Chat ID successfully registered! You will now receive attendance notifications.';
                } else {
                    $message = '❌ Student not found. Please check your Student ID or RFID.';
                }
            } else {
                $message = '❌ Database error: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = '❌ Database connection error';
        }
    } else {
        $message = '❌ Please fill in all fields';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register for Attendance Notifications</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 500px; width: 100%; }
        
        h1 { color: #333; margin-bottom: 10px; text-align: center; }
        .subtitle { color: #666; text-align: center; margin-bottom: 30px; font-size: 14px; }
        
        .step { margin-bottom: 30px; }
        .step-number { display: inline-block; background: #667eea; color: white; width: 30px; height: 30px; border-radius: 50%; text-align: center; line-height: 30px; margin-right: 10px; font-weight: bold; }
        .step h3 { color: #333; margin-bottom: 10px; }
        .step p { color: #666; font-size: 14px; margin-bottom: 10px; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; color: #333; font-weight: 500; margin-bottom: 8px; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        
        .message { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        button { width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: 500; cursor: pointer; transition: background 0.3s; }
        button:hover { background: #764ba2; }
        
        .info-box { background: #e7f3ff; border-left: 4px solid #0088cc; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; color: #0c5460; }
        
        .divider { text-align: center; margin: 25px 0; color: #999; }
        .divider::before { content: '─────'; display: inline; }
        .divider::after { content: '─────'; display: inline; }
        .divider span { margin: 0 10px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔔 Attendance Notifications</h1>
    <p class="subtitle">Register your Telegram Chat ID to receive real-time attendance alerts</p>
    
    <?php if ($message): ?>
        <div class="message <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="info-box">
        <strong>📱 How to get your Chat ID:</strong>
        <ol style="margin-left: 20px; margin-top: 8px;">
            <li>Open Telegram and search for <strong>@AGSNHS_bot</strong></li>
            <li>Tap <strong>START</strong> button</li>
            <li>Copy the Chat ID from the bot's response</li>
        </ol>
    </div>
    
    <form method="POST">
        <div class="form-group">
            <label><span class="step-number">1</span>Telegram Chat ID</label>
            <input type="text" name="chat_id" placeholder="e.g., 1234567890" required />
            <p style="font-size: 12px; color: #666; margin-top: 5px;">Your personal Chat ID from @AGSNHS_bot</p>
        </div>
        
        <div class="divider"><span>OR</span></div>
        
        <div class="form-group">
            <label><span class="step-number">2</span>Your Information</label>
            <input type="text" name="student_id" placeholder="Student ID (optional)" />
            <p style="font-size: 12px; color: #666; margin-top: 5px;">OR</p>
            <input type="text" name="rfid" placeholder="RFID Card Number (optional)" style="margin-top: 10px;" />
        </div>
        
        <button type="submit">✅ Register for Notifications</button>
    </form>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; font-size: 12px; color: #666;">
        <p>Your Chat ID will be securely stored and only used to send attendance notifications.</p>
    </div>
</div>
</body>
</html>
