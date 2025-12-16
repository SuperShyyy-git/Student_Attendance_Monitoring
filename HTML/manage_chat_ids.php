<?php
header('Content-Type: text/html; charset=utf-8');
<?php
// Chat ID management UI removed. Background service handles registrations.
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
echo "Chat ID management UI removed. Chat IDs are auto-registered by the background service.\n";
exit;
        body { font-family: Arial; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 30px; }
        
        .section { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section h2 { color: #0088cc; margin-bottom: 15px; border-bottom: 2px solid #0088cc; padding-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; font-weight: bold; }
        tr:hover { background: #f9f9f9; }
        
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .status-active { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        
        .info-box { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 4px; margin-bottom: 15px; }
        .copy-btn { background: #0088cc; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
        .copy-btn:hover { background: #006699; }
        
        .empty { color: #666; font-style: italic; padding: 20px; text-align: center; }
    <?php
    // This management page has been disabled.
    // Chat IDs are automatically registered by the background service when guardians message @AGSNHS_bot.
    http_response_code(410);
    header('Content-Type: text/plain; charset=utf-8');
    echo "This page is disabled. Chat IDs are auto-registered when guardians message @AGSNHS_bot.\n";
    exit;
                            <span class="status-badge <?php echo $info['registered'] ? 'status-active' : 'status-pending'; ?>">
                                <?php echo $info['registered'] ? '✓ Yes' : '○ No'; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($info['timestamp']); ?></td>
                        <td>
                            <button onclick="registerChatId('<?php echo $info['chat_id']; ?>')" style="background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">Register</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Manual Chat ID Entry</h2>
        <p>If user already has their Chat ID from another conversation:</p>
        <div style="margin-top: 15px;">
            <input type="text" id="manualChatId" placeholder="Enter Chat ID manually" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 300px;" />
            <button onclick="addManualChatId()" style="background: #0088cc; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-left: 10px;">Add</button>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    alert('Chat ID copied: ' + text);
}

function registerChatId(chatId) {
    alert('Chat ID ' + chatId + ' needs to be linked to a student in the database.\n\nYou can do this via the admin student management panel.');
}

function addManualChatId() {
    const chatId = document.getElementById('manualChatId').value.trim();
    if (!chatId) {
        alert('Please enter a Chat ID');
        return;
    }
    alert('Chat ID added. Admin needs to link it to a student record.');
    location.reload();
}
</script>
</body>
</html>
