<?php
include __DIR__ . "/../config/db_connect.php";

// Get stats
$totalAdvisers = 0;
$advisersQuery = $conn->query("SELECT COUNT(*) as count FROM advisers");
if ($advisersQuery) {
    $totalAdvisers = $advisersQuery->fetch_assoc()['count'];
}

$assignedCount = 0;
$assignedQuery = $conn->query("SELECT COUNT(DISTINCT adviser_id) as count FROM section_yrlevel WHERE adviser_id IS NOT NULL");
if ($assignedQuery) {
    $assignedCount = $assignedQuery->fetch_assoc()['count'];
}

$unassignedCount = $totalAdvisers - $assignedCount;
if ($unassignedCount < 0)
    $unassignedCount = 0;

// Get archived advisers
$archivedAdvisers = [];
$archivedCount = 0;
$tableExists = $conn->query("SHOW TABLES LIKE 'archived_advisers'");
$hasArchivedTable = $tableExists && $tableExists->num_rows > 0;

if ($hasArchivedTable) {
    $archivedQuery = $conn->query("SELECT * FROM archived_advisers ORDER BY archived_at DESC");
    if ($archivedQuery) {
        while ($row = $archivedQuery->fetch_assoc()) {
            $archivedAdvisers[] = $row;
        }
        $archivedCount = count($archivedAdvisers);
    }
}
?>

<div class="header-bar">
    <h2 class='table-title'>Manage Advisers</h2>
    <button id="btn-logout" class="btn-logout">Logout</button>
</div>

<!-- ADD ADVISER BUTTON -->
<div style="display: flex; gap: 10px; margin: 15px 0;">
    <button id="btn-open-add-adviser" class="btn-add-student" onclick="window.openAddAdviserModal()">➕ Add
        Adviser</button>
</div>

<!-- SEARCH BAR -->
<div style="display: flex; gap: 15px; margin: 15px 0; align-items: center; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 200px;">
        <input type="text" id="search-adviser" placeholder="🔍 Search by name or contact..."
            style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
    </div>
</div>

<!-- STATS ROW -->
<div style="display: flex; gap: 15px; margin-bottom: 15px;">
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #3498db;">
        <div style="font-size: 24px; font-weight: 700; color: #2c3e50;"><?php echo $totalAdvisers; ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">Total Advisers</div>
    </div>
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #27ae60;">
        <div style="font-size: 24px; font-weight: 700; color: #27ae60;"><?php echo $assignedCount; ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">Assigned</div>
    </div>
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #e67e22;">
        <div style="font-size: 24px; font-weight: 700; color: #e67e22;"><?php echo $unassignedCount; ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">Unassigned</div>
    </div>
</div>

<!-- TAB NAVIGATION -->
<div class="tab-container">
    <button class="tab-btn active" onclick="switchTab('active')">
        👥 Active Advisers
    </button>
    <button class="tab-btn" onclick="switchTab('archive')">
        📦 Archived Advisers
    </button>
</div>

<!-- TAB 1: ACTIVE ADVISERS -->
<div id="tab-active" class="tab-content active">
    <!-- TABLE -->
    <div class="table-container">
        <table class="student-table" id="adviser-table">
            <thead>
                <tr>
                    <th>Adviser ID</th>
                    <th>Adviser Name</th>
                    <th>Contact Number</th>
                    <th style="width: 160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT * FROM advisers ORDER BY name ASC";
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $id = (int) $row['id'];
                        $adviserId = isset($row['adviser_id']) ? htmlspecialchars($row['adviser_id'], ENT_QUOTES) : 'ADV-' . str_pad($id, 4, '0', STR_PAD_LEFT);
                        $name = htmlspecialchars($row['name'], ENT_QUOTES);
                        $contact = isset($row['contact']) ? htmlspecialchars($row['contact'], ENT_QUOTES) : '';

                        echo "
                    <tr data-id='{$id}'>
                        <td style='font-family: monospace; font-weight: 600; color: #3b82f6;'>{$adviserId}</td>
                        <td style='font-weight: 500;'>{$name}</td>
                        <td style='font-family: monospace;'>" . ($contact ? $contact : '<span style="color:#94a3b8; font-style: italic;">N/A</span>') . "</td>
                        <td class='actions-cell'>
                            <button class='btn-edit' onclick=\"window.editAdviser({$id}, '{$name}', '{$contact}')\">
                                ✏️ Edit
                            </button>
                            <button class='btn-archive' onclick=\"window.archiveAdviser({$id}, '{$name}')\">
                                📦 Archive
                            </button>
                        </td>
                    </tr>
                    ";
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align:center; padding: 40px; color: #64748b;'>No advisers found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- TAB 2: ARCHIVED ADVISERS -->
<div id="tab-archive" class="tab-content">
    <!-- ARCHIVED STATS -->
    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
        <div
            style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #f59e0b;">
            <div style="font-size: 24px; font-weight: 700; color: #f59e0b;"><?php echo $archivedCount; ?></div>
            <div style="font-size: 12px; color: #7f8c8d;">Total Archived</div>
        </div>
    </div>

    <!-- ARCHIVED TABLE -->
    <div class="table-container">
        <table class="student-table">
            <thead>
                <tr>
                    <th>Archive ID</th>
                    <th>Adviser ID</th>
                    <th>Adviser Name</th>
                    <th>Contact Number</th>
                    <th>Archived At</th>
                    <th style="width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$hasArchivedTable): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding: 40px; color: #64748b;">
                            Archive table not created yet. Archive will be created when you archive the first adviser.
                        </td>
                    </tr>
                <?php elseif ($archivedCount === 0): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding: 40px; color: #64748b;">No archived advisers
                            found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($archivedAdvisers as $row): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo $row['id']; ?></td>
                            <td style="font-family: monospace; font-weight: 600; color: #3b82f6;">
                                <?php echo htmlspecialchars($row['adviser_id'] ?? 'N/A'); ?>
                            </td>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td style="font-family: monospace;">
                                <?php echo $row['contact'] ? htmlspecialchars($row['contact']) : '<span style="color:#94a3b8; font-style: italic;">N/A</span>'; ?>
                            </td>
                            <td style="color: #64748b; font-size: 12px;">
                                <?php echo date('M d, Y h:i A', strtotime($row['archived_at'])); ?>
                            </td>
                            <td class='actions-cell'>
                                <button class='btn-restore'
                                    onclick="window.restoreAdviser(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')">
                                    ↩️ Restore
                                </button>
                                <button class='btn-delete-permanent'
                                    onclick="window.deleteArchivedAdviser(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')">
                                    🗑️ Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ADD ADVISER MODAL -->
<div id="add-adviser-modal" class="edit-modal hidden">
    <div class="edit-modal-box">
        <h3>Add Adviser</h3>
        <form id="add-adviser-form">
            <label>Full Name</label>
            <input type="text" name="name" required>

            <label>Contact Number</label>
            <input type="text" name="contact" placeholder="e.g. 09123456789">

            <div class="modal-buttons">
                <button type="submit" class="btn-save-edit">Save</button>
                <button type="button" onclick="window.closeAddAdviserModal()" class="btn-cancel">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT ADVISER MODAL -->
<div id="edit-adviser-modal" class="edit-modal hidden">
    <div class="edit-modal-box">
        <h3>Edit Adviser</h3>
        <form id="edit-adviser-form">
            <input type="hidden" name="id" id="edit-id">
            <label>Full Name</label>
            <input type="text" name="name" id="edit-name" required>

            <label>Contact Number</label>
            <input type="text" name="contact" id="edit-contact" placeholder="e.g. 09123456789">

            <div class="modal-buttons">
                <button type="submit" class="btn-save-edit">Update</button>
                <button type="button" onclick="window.closeEditAdviserModal()" class="btn-cancel">Cancel</button>
            </div>
        </form>
    </div>
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

    .tab-container {
        display: flex;
        gap: 10px;
        margin: 20px 0;
        border-bottom: 2px solid #e0e0e0;
        padding-bottom: 0;
    }

    .tab-btn {
        padding: 12px 24px;
        background: #f5f5f5;
        border: none;
        border-radius: 8px 8px 0 0;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        color: #666;
        transition: all 0.3s ease;
        position: relative;
        bottom: -2px;
    }

    .tab-btn:hover {
        background: #e8e8e8;
        color: #333;
    }

    .tab-btn.active {
        background: #3498db;
        color: white;
        border-bottom: 2px solid #3498db;
    }

    .tab-content {
        display: none;
        padding: 20px 0;
    }

    .tab-content.active {
        display: block;
    }

    .table-container {
        width: 100%;
        overflow-x: auto;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        margin-top: 15px;
    }

    .student-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 500px;
    }

    .student-table th {
        background: #1e293b;
        color: white;
        padding: 14px 12px;
        text-align: left;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .student-table td {
        padding: 12px;
        border-bottom: 1px solid #ecf0f1;
        font-size: 14px;
        color: #334155;
    }

    .student-table tr:hover {
        background: #f8fafc;
    }

    .actions-cell {
        display: flex;
        gap: 8px;
    }

    .btn-edit,
    .btn-archive {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .btn-edit {
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #dbeafe;
    }

    .btn-edit:hover {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .btn-archive {
        background: #fffbeb;
        color: #f59e0b;
        border: 1px solid #fef3c7;
    }

    .btn-archive:hover {
        background: #fef3c7;
        color: #d97706;
    }

    .btn-restore {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #d1fae5;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .btn-restore:hover {
        background: #d1fae5;
        color: #047857;
    }

    .btn-delete-permanent {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fee2e2;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .btn-delete-permanent:hover {
        background: #fee2e2;
        color: #b91c1c;
    }

    .btn-cancel {
        padding: 10px 20px;
        background: #f8fafc;
        color: #64748b;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
    }

    .btn-cancel:hover {
        background: #f1f5f9;
    }

    .edit-modal.hidden {
        display: none;
    }

    .edit-modal {
        position: fixed;
        left: 0;
        top: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .edit-modal-box {
        background: white;
        padding: 24px;
        border-radius: 12px;
        width: 400px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .edit-modal-box h3 {
        margin-top: 0;
        color: #1e293b;
        font-size: 1.15rem;
        margin-bottom: 20px;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 12px;
    }

    .edit-modal-box label {
        display: block;
        margin-top: 12px;
        margin-bottom: 4px;
        font-weight: 500;
        color: #475569;
        font-size: 14px;
    }

    .edit-modal-box input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        margin-bottom: 10px;
        background: #fcfcfc;
    }

    .edit-modal-box input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        background: white;
    }

    .modal-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 15px;
    }
</style>

<script>
    setTimeout(function () {
        // Tab switching
        window.switchTab = function (tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        };

        // Search functionality
        var searchInput = document.getElementById('search-adviser');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var q = this.value.toLowerCase();
                var rows = document.querySelectorAll('#adviser-table tbody tr');
                for (var i = 0; i < rows.length; i++) {
                    var text = rows[i].textContent.toLowerCase();
                    rows[i].style.display = text.indexOf(q) !== -1 ? '' : 'none';
                }
            });
        }

        // Add Modal
        window.openAddAdviserModal = function () {
            document.getElementById('add-adviser-modal').classList.remove('hidden');
        };
        window.closeAddAdviserModal = function () {
            document.getElementById('add-adviser-modal').classList.add('hidden');
        };

        // Form Submit: Add
        var addForm = document.getElementById('add-adviser-form');
        if (addForm) {
            addForm.onsubmit = function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                fetch('adviser_save.php', { method: 'POST', body: formData })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            window.loadPage('advisers.php');
                        } else {
                            alert(data.message || 'Error saving adviser');
                        }
                    });
                return false;
            };
        }

        // Edit Modal
        window.editAdviser = function (id, name, contact) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-contact').value = contact;
            document.getElementById('edit-adviser-modal').classList.remove('hidden');
        };
        window.closeEditAdviserModal = function () {
            document.getElementById('edit-adviser-modal').classList.add('hidden');
        };

        // Form Submit: Edit
        var editForm = document.getElementById('edit-adviser-form');
        if (editForm) {
            editForm.onsubmit = function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                fetch('adviser_update.php', { method: 'POST', body: formData })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            window.loadPage('advisers.php');
                        } else {
                            alert(data.message || 'Error updating adviser');
                        }
                    });
                return false;
            };
        }

        // Archive
        window.archiveAdviser = function (id, name) {
            if (confirm('Are you sure you want to archive ' + name + '? The adviser will be moved to the archive.')) {
                var formData = new FormData();
                formData.append('id', id);
                fetch('adviser_delete.php', { method: 'POST', body: formData })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            window.loadPage('advisers.php');
                        } else {
                            alert(data.message || 'Error archiving adviser');
                        }
                    });
            }
        };

        // Restore archived adviser
        window.restoreAdviser = function (archiveId, name) {
            if (confirm('Are you sure you want to restore ' + name + ' back to active advisers?')) {
                var formData = new FormData();
                formData.append('archive_id', archiveId);
                fetch('adviser_restore.php', { method: 'POST', body: formData })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            window.loadPage('advisers.php');
                        } else {
                            alert(data.message || 'Error restoring adviser');
                        }
                    });
            }
        };

        // Permanently delete archived adviser
        window.deleteArchivedAdviser = function (archiveId, name) {
            if (confirm('⚠️ WARNING: This will PERMANENTLY delete ' + name + ' from the archive. This action cannot be undone. Continue?')) {
                var formData = new FormData();
                formData.append('archive_id', archiveId);
                fetch('adviser_delete_permanent.php', { method: 'POST', body: formData })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            window.loadPage('advisers.php');
                        } else {
                            alert(data.message || 'Error deleting archived adviser');
                        }
                    });
            }
        };

    }, 0);
</script>