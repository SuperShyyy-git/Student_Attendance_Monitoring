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

if (isset($_POST['clear_message']) && isset($_POST['index'])) {
    $messages = $queueStatus['messages'];
    $index = (int) $_POST['index'];
    if (isset($messages[$index])) {
        array_splice($messages, $index, 1);
        $queueDir = __DIR__ . '/../logs/telegram_queue';
        $queueFile = $queueDir . '/message_queue.json';
        if (count($messages) > 0) {
            file_put_contents($queueFile, json_encode($messages, JSON_PRETTY_PRINT));
        } else {
            @unlink($queueFile);
        }
        $queueStatus = $queue->getQueueStatus();
    }
}
?>

<div class="header-bar">
    <h2 class='table-title'>🔔 Pending Notifications</h2>
    <button id="btn-logout" class="btn-logout">Logout</button>
</div>

<?php if ($processResult): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin: 15px 0;">
        ✅ Processed: <?php echo $processResult['processed']; ?> sent, <?php echo $processResult['failed']; ?> failed
    </div>
<?php endif; ?>

<!-- STATS ROW -->
<div style="display: flex; gap: 15px; margin: 15px 0; align-items: center;">
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #f39c12;">
        <div style="font-size: 32px; font-weight: 700; color: #f39c12;"><?php echo $queueStatus['queue_size']; ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">Pending</div>
    </div>

    <?php if ($queueStatus['queue_size'] > 0): ?>
        <form method="POST" style="display: inline;">
            <button type="submit" name="process_queue" class="btn-add-student" style="background: #f39c12;">
                🔄 Send All Now
            </button>
        </form>
    <?php endif; ?>
</div>

<hr style="margin-bottom: 15px; border: none; border-top: 1px solid #ecf0f1;">

<?php if ($queueStatus['queue_size'] === 0): ?>
    <div style="text-align: center; padding: 60px; color: #27ae60;">
        <div style="font-size: 64px; margin-bottom: 15px;">✅</div>
        <h3>All Clear!</h3>
        <p style="color: #7f8c8d;">No pending notifications. All messages have been sent successfully.</p>
    </div>
<?php else: ?>
    <?php foreach ($queueStatus['messages'] as $index => $msg):
        $isPhoto = isset($msg['type']) && $msg['type'] === 'photo';
        ?>
        <div
            style="background: <?php echo $isPhoto ? '#f9f0ff' : '#fffbf0'; ?>; border: 1px solid <?php echo $isPhoto ? '#9b59b6' : '#f39c12'; ?>; border-left: 4px solid <?php echo $isPhoto ? '#9b59b6' : '#f39c12'; ?>; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <div style="font-size: 12px; color: #7f8c8d;">
                    <span class="<?php echo $isPhoto ? 'status-not-connected' : 'status-warning'; ?>"
                        style="background: <?php echo $isPhoto ? '#e8daef' : '#fff3cd'; ?>; color: <?php echo $isPhoto ? '#6c3483' : '#856404'; ?>;">
                        <?php echo $isPhoto ? '📷 Photo' : '💬 Message'; ?>
                    </span>
                    Chat ID: <strong><?php echo htmlspecialchars($msg['chat_id']); ?></strong> |
                    Queued: <?php echo $msg['queued_at']; ?> |
                    Attempts: <?php echo $msg['attempts']; ?>
                </div>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                    <button type="submit" name="clear_message" class="btn-add-student"
                        style="background: #e74c3c; padding: 6px 12px; font-size: 12px;"
                        onclick="return confirm('Remove this notification from queue?')">
                        ✕ Remove
                    </button>
                </form>
            </div>
            <div
                style="background: white; padding: 12px; border-radius: 6px; font-size: 13px; white-space: pre-wrap; max-height: 150px; overflow-y: auto;">
                <?php
                if ($isPhoto) {
                    echo "📷 " . basename($msg['file']) . "\n\n" . htmlspecialchars($msg['caption'] ?? '');
                } else {
                    echo htmlspecialchars($msg['message'] ?? '');
                }
                ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

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
        transition: transform 0.2s;
    }

    .btn-add-student:hover {
        transform: translateY(-2px);
    }

    .status-warning {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        margin-right: 10px;
    }
</style>