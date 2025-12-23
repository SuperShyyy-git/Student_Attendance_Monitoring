<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include __DIR__ . "/../config/db_connect.php";
require_once __DIR__ . "/../telegram_queue.php";

$token = '8591636394:AAGC95x20enHEhHoLrvcDDiUXfrCWJ5fJ2g';
$queue = new TelegramQueue($token);
$queueStatus = $queue->getQueueStatus();

$processResult = null;
if (isset($_POST['process_queue'])) {
    $processResult = $queue->processQueue();
    $queueStatus = $queue->getQueueStatus();
}

// Read log file
$logFile = __DIR__ . '/../logs/php_rfid_errors.log';
$logEntries = [];

if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($lines);

    foreach ($lines as $line) {
        if (strpos($line, 'TELEGRAM') !== false || strpos($line, 'NOTIFICATION') !== false) {
            $logEntries[] = $line;
        }
    }
    $logEntries = array_slice($logEntries, 0, 100);
}

// Get students with chat IDs
$studentsWithChat = [];
if (isset($conn) && $conn instanceof mysqli) {
    $result = $conn->query("
        SELECT id, student_id, CONCAT(firstname, ' ', lastname) as name, guardian_name, chat_id 
        FROM students 
        WHERE chat_id IS NOT NULL AND chat_id != ''
        ORDER BY lastname, firstname
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $studentsWithChat[] = $row;
        }
    }
}
?>

<div class="header-bar">
    <h2 class='table-title'>📱 SMS / Telegram Notification Log</h2>
    <button id="btn-logout" class="btn-logout">Logout</button>
</div>

<?php if ($processResult): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin: 15px 0;">
        ✅ Queue processed: <?php echo $processResult['processed']; ?> sent,
        <?php echo $processResult['failed']; ?> failed,
        <?php echo $processResult['queue_remaining']; ?> remaining
    </div>
<?php endif; ?>

<!-- STATS ROW -->
<div style="display: flex; gap: 15px; margin: 15px 0;">
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #f39c12;">
        <div style="font-size: 24px; font-weight: 700; color: #f39c12;"><?php echo $queueStatus['queue_size']; ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">Pending Messages</div>
    </div>
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #3498db;">
        <div style="font-size: 24px; font-weight: 700; color: #3498db;"><?php echo count($studentsWithChat); ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">Registered Parents</div>
    </div>
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #27ae60;">
        <div style="font-size: 24px; font-weight: 700; color: #27ae60;"><?php echo count($logEntries); ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">Log Entries</div>
    </div>
</div>

<!-- TABS -->
<div style="display: flex; gap: 5px; margin-bottom: 15px;">
    <button class="tab-btn active" onclick="showNotifTab('queue')">📬 Message Queue</button>
    <button class="tab-btn" onclick="showNotifTab('log')">📋 Activity Log</button>
    <button class="tab-btn" onclick="showNotifTab('parents')">👪 Registered Parents</button>
</div>

<hr style="margin-bottom: 15px; border: none; border-top: 1px solid #ecf0f1;">

<!-- Queue Tab -->
<div id="notif-tab-queue" class="notif-tab-content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 style="color: #2c3e50;">Pending Notifications</h3>
        <?php if ($queueStatus['queue_size'] > 0): ?>
            <form method="POST" style="display: inline;">
                <button type="submit" name="process_queue" class="btn-add-student" style="background: #f39c12;">
                    🔄 Process Queue Now
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($queueStatus['queue_size'] === 0): ?>
        <div style="text-align: center; padding: 40px; color: #27ae60;">
            ✅ No pending messages. All notifications have been sent!
        </div>
    <?php else: ?>
        <?php foreach ($queueStatus['messages'] as $msg): ?>
            <div
                style="background: #f8f9fa; padding: 12px; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid #f39c12;">
                <div style="font-size: 12px; color: #7f8c8d; margin-bottom: 5px;">
                    <strong>Chat ID:</strong> <?php echo htmlspecialchars($msg['chat_id']); ?> |
                    <strong>Queued:</strong> <?php echo $msg['queued_at']; ?> |
                    <strong>Attempts:</strong> <?php echo $msg['attempts']; ?>
                </div>
                <div style="font-size: 13px; color: #2c3e50; white-space: pre-wrap;">
                    <?php echo htmlspecialchars($msg['message'] ?? ''); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Log Tab -->
<div id="notif-tab-log" class="notif-tab-content" style="display: none;">
    <h3 style="color: #2c3e50; margin-bottom: 15px;">Recent Notification Activity</h3>

    <?php if (empty($logEntries)): ?>
        <div style="text-align: center; padding: 40px; color: #7f8c8d;">No notification logs found.</div>
    <?php else: ?>
        <div style="max-height: 400px; overflow-y: auto; background: white; border-radius: 8px;">
            <?php foreach ($logEntries as $entry):
                $bgColor = '#f8f9fa';
                if (strpos($entry, 'SUCCESS') !== false)
                    $bgColor = '#d4edda';
                elseif (strpos($entry, 'ERROR') !== false)
                    $bgColor = '#f8d7da';
                elseif (strpos($entry, 'QUEUED') !== false)
                    $bgColor = '#fff3cd';
                ?>
                <div
                    style="font-family: monospace; font-size: 12px; padding: 8px 12px; border-bottom: 1px solid #ecf0f1; background: <?php echo $bgColor; ?>;">
                    <?php echo htmlspecialchars($entry); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Parents Tab -->
<div id="notif-tab-parents" class="notif-tab-content" style="display: none;">
    <h3 style="color: #2c3e50; margin-bottom: 15px;">Parents/Guardians with Telegram Registered</h3>

    <?php if (empty($studentsWithChat)): ?>
        <div style="text-align: center; padding: 40px; color: #7f8c8d;">No parents have registered yet.</div>
    <?php else: ?>
        <table class="student-table">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Guardian Name</th>
                    <th>Chat ID</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($studentsWithChat as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                        <td><?php echo htmlspecialchars($student['guardian_name'] ?? '-'); ?></td>
                        <td><code><?php echo htmlspecialchars($student['chat_id']); ?></code></td>
                        <td><span class="status-connected">✅ Active</span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
    .header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 20px 0 20px;
    }

    .btn-logout {
        background: #dc3545;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 8px 18px;
        font-weight: 600;
        cursor: pointer;
        font-size: 16px;
    }

    .btn-logout:hover {
        background: #b71c1c;
    }

    .btn-add-student {
        padding: 10px 20px;
        background: #9b59b6;
        color: white;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
    }

    .tab-btn {
        padding: 12px 24px;
        background: white;
        border: none;
        border-radius: 8px 8px 0 0;
        cursor: pointer;
        font-weight: 600;
        color: #7f8c8d;
    }

    .tab-btn.active {
        background: #3498db;
        color: white;
    }

    .student-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    .student-table th {
        background: #1e293b;
        color: white;
        padding: 14px 12px;
        text-align: left;
        font-size: 12px;
        text-transform: uppercase;
    }

    .student-table td {
        padding: 12px;
        border-bottom: 1px solid #ecf0f1;
        font-size: 14px;
    }

    .student-table tr:hover {
        background: #f8f9fa;
    }

    .status-connected {
        display: inline-block;
        padding: 6px 12px;
        background: #d4edda;
        color: #155724;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
    }
</style>

<script>
    function showNotifTab(tabId) {
        document.querySelectorAll('.notif-tab-content').forEach(tab => tab.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

        document.getElementById('notif-tab-' + tabId).style.display = 'block';
        event.target.classList.add('active');
    }
</script>