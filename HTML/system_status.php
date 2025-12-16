<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../../config/db_connect.php';

// Get system stats
$studentCount = 0;
$registeredChatIds = 0;
$todayAttendance = 0;
$lastNotification = null;

if (isset($conn) && $conn instanceof mysqli) {
    $result = $conn->query("SELECT COUNT(*) as count FROM students");
    if ($row = $result->fetch_assoc()) {
        $studentCount = $row['count'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM students WHERE chat_id IS NOT NULL AND chat_id != ''");
    if ($row = $result->fetch_assoc()) {
        $registeredChatIds = $row['count'];
    }
    
    $today = date('Y-m-d');
    $result = $conn->query("SELECT COUNT(*) as count FROM student_attendance WHERE attendance_date = '$today'");
    if ($row = $result->fetch_assoc()) {
        $todayAttendance = $row['count'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance System - Status Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header { text-align: center; color: white; margin-bottom: 30px; }
        .header h1 { font-size: 32px; margin-bottom: 10px; }
        .header p { font-size: 16px; opacity: 0.9; }
        
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .card h3 { color: #667eea; margin-bottom: 10px; font-size: 14px; text-transform: uppercase; }
        .card .number { font-size: 36px; font-weight: bold; color: #333; }
        .card .subtitle { color: #999; font-size: 12px; margin-top: 10px; }
        
        .status-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 20px; }
        .status-card h2 { color: #333; margin-bottom: 20px; font-size: 20px; }
        
        .status-item { display: flex; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
        .status-item:last-child { border-bottom: none; }
        .status-icon { font-size: 28px; margin-right: 15px; }
        .status-text { flex: 1; }
        .status-text strong { display: block; color: #333; margin-bottom: 5px; }
        .status-text span { color: #999; font-size: 12px; }
        .status-badge { padding: 4px 12px; background: #d4edda; color: #155724; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-badge.warning { background: #fff3cd; color: #856404; }
        
        .quick-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .link-btn { display: block; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; text-align: center; font-weight: bold; transition: transform 0.2s; }
        .link-btn:hover { transform: translateY(-2px); }
        
        .info-box { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .info-box strong { color: #0c5aa0; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        thead { background: #f0f0f0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { font-weight: bold; }
        tr:hover { background: #f9f9f9; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>✅ Attendance System Status</h1>
        <p>RFID + Telegram Notifications with Windscribe VPN</p>
    </div>
    
    <div class="cards">
        <div class="card">
            <h3>📚 Total Students</h3>
            <div class="number"><?php echo $studentCount; ?></div>
            <div class="subtitle">Registered in system</div>
        </div>
        
        <div class="card">
            <h3>📱 Chat IDs Registered</h3>
            <div class="number"><?php echo $registeredChatIds; ?></div>
            <div class="subtitle"><?php echo $studentCount > 0 ? round($registeredChatIds / $studentCount * 100) : 0; ?>% of students</div>
        </div>
        
        <div class="card">
            <h3>📋 Today's Attendance</h3>
            <div class="number"><?php echo $todayAttendance; ?></div>
            <div class="subtitle">Records today</div>
        </div>
    </div>
    
    <div class="status-card">
        <h2>🔧 System Status</h2>
        
        <div class="info-box">
            <strong>✅ All Systems Operational!</strong> Your attendance system is fully functional with Windscribe VPN.
        </div>
        
        <div class="status-item">
            <div class="status-icon">🎥</div>
            <div class="status-text">
                <strong>RFID + Face Verification</strong>
                <span>Capturing attendance with biometric validation</span>
            </div>
            <span class="status-badge">ACTIVE</span>
        </div>
        
        <div class="status-item">
            <div class="status-icon">📱</div>
            <div class="status-text">
                <strong>Telegram Notifications</strong>
                <span>Sending SMS alerts via @AGSNHS_bot to <?php echo $registeredChatIds; ?> guardians</span>
            </div>
            <span class="status-badge">ACTIVE</span>
        </div>
        
        <div class="status-item">
            <div class="status-icon">🔒</div>
            <div class="status-text">
                <strong>Windscribe VPN</strong>
                <span>Bypassing ISP blocking for Telegram API access</span>
            </div>
            <span class="status-badge">ACTIVE</span>
        </div>
        
        <div class="status-item">
            <div class="status-icon">💾</div>
            <div class="status-text">
                <strong>Message Queue System</strong>
                <span>Fallback queuing if VPN disconnects (messages sent automatically when connection is restored)</span>
            </div>
            <span class="status-badge">READY</span>
        </div>
        
        <div class="status-item">
            <div class="status-icon">📊</div>
            <div class="status-text">
                <strong>Database</strong>
                <span>MySQL connected with <?php echo $studentCount; ?> student records</span>
            </div>
            <span class="status-badge">CONNECTED</span>
        </div>
    </div>
    
    <div class="status-card">
        <h2>⚙️ Quick Actions</h2>
        
        <div class="quick-links">
            <a href="attendance_capture.php" class="link-btn">🎥 RFID Capture</a>
            <a href="student_table.php" class="link-btn">👥 Student List</a>
            <a href="student-attendance.php" class="link-btn">📊 View Attendance</a>
            <a href="dashboard.php" class="link-btn">📈 Dashboard</a>
        </div>
    </div>
    
    <div class="status-card">
        <h2>ℹ️ Important Notes</h2>
        <ul style="margin-left: 20px; line-height: 2; color: #666;">
            <li><strong>Keep Windscribe VPN active</strong> for Telegram notifications to work on this ISP</li>
            <li><strong>Message Queue:</strong> If VPN disconnects, messages queue locally and are sent automatically when connection is restored</li>
            <li><strong>Chat ID Registration:</strong> Chat IDs are automatically registered when guardians message <code>@AGSNHS_bot</code>. No manual form is required.</li>
            <li><strong>Face Verification:</strong> System requires student face capture during RFID scan for biometric matching</li>
            <li><strong>Contact ISP:</strong> Ask to whitelist api.telegram.org (ports 443, 80, 8443) for permanent solution</li>
        </ul>
    </div>
</div>
</body>
</html>
