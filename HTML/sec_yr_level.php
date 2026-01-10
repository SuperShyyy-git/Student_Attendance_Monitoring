<?php
include __DIR__ . "/../config/db_connect.php";

// Get stats
$totalSections = 0;
$sectionsQuery = $conn->query("SELECT COUNT(*) as count FROM section_yrlevel");
if ($sectionsQuery) {
    $totalSections = $sectionsQuery->fetch_assoc()['count'];
}

$totalAdvisers = 0;
$advisersQuery = $conn->query("SELECT COUNT(*) as count FROM advisers");
if ($advisersQuery) {
    $totalAdvisers = $advisersQuery->fetch_assoc()['count'];
}

$assignedCount = 0;
$assignedQuery = $conn->query("SELECT COUNT(*) as count FROM section_yrlevel WHERE adviser_id IS NOT NULL");
if ($assignedQuery) {
    $assignedCount = $assignedQuery->fetch_assoc()['count'];
}
?>

<div class="header-bar">
    <h2 class='table-title'>Section & Grade Level</h2>
    <button id="btn-logout" class="btn-logout">Logout</button>
</div>

<!-- ADD SECTION BUTTON -->
<div style="display: flex; gap: 10px; margin: 15px 0;">
    <button id="btn-open-add-section" class="btn-add-student">➕ Add Section</button>
</div>

<!-- SEARCH BAR -->
<div style="display: flex; gap: 15px; margin: 15px 0; align-items: center; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 200px;">
        <input type="text" id="search-section" placeholder="🔍 Search by section or grade..."
            style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
    </div>
</div>

<!-- STATS ROW -->
<div style="display: flex; gap: 15px; margin-bottom: 15px;">
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #3498db;">
        <div style="font-size: 24px; font-weight: 700; color: #2c3e50;"><?php echo $totalSections; ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">Total Sections</div>
    </div>
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #27ae60;">
        <div style="font-size: 24px; font-weight: 700; color: #27ae60;"><?php echo $assignedCount; ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">With Adviser</div>
    </div>
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #9b59b6;">
        <div style="font-size: 24px; font-weight: 700; color: #9b59b6;"><?php echo $totalAdvisers; ?></div>
        <div style="font-size: 12px; color: #7f8c8d;">Advisers</div>
    </div>
</div>

<hr style="margin-bottom: 15px; border: none; border-top: 1px solid #ecf0f1;">

<!-- TABLE -->
<div class="table-container">
    <table class="student-table" id="section-table">
        <thead>
            <tr>
                <th>Section & Grade Level</th>
                <th>Adviser</th>
                <th style="width: 160px;">Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php
            $query = "SELECT s.*, a.name as adviser_name FROM section_yrlevel s LEFT JOIN advisers a ON s.adviser_id = a.id ORDER BY s.grade_level ASC, s.section ASC";
            $result = $conn->query($query);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {

                    $id = (int) $row['id'];
                    $section_display = htmlspecialchars($row['section'], ENT_QUOTES);
                    $grade_display = htmlspecialchars($row['grade_level'], ENT_QUOTES);
                    $combined_display = "Grade " . $grade_display . " - " . $section_display;
                    $adviser_display = $row['adviser_name'] ? htmlspecialchars($row['adviser_name'], ENT_QUOTES) : '<span style="color:#94a3b8; font-style: italic;">Not assigned</span>';
                    $adviser_id = isset($row['adviser_id']) ? $row['adviser_id'] : '';

                    $section_attr = htmlspecialchars($row['section'], ENT_QUOTES);
                    $grade_attr = htmlspecialchars($row['grade_level'], ENT_QUOTES);

                    echo "
                    <tr>
                        <td style='font-weight: 500;'>{$combined_display}</td>
                        <td>{$adviser_display}</td>
                        <td class='actions-cell'>
                            <button 
                                type='button'
                                class='btn-edit'
                                data-id='{$id}'
                                data-section=\"{$section_attr}\"
                                data-grade=\"{$grade_attr}\"
                                data-adviser='{$adviser_id}'
                            >
                                ✏️ Edit
                            </button>

                            <button 
                                type='button'
                                class='btn-delete'
                                data-id='{$id}'
                            >
                                🗑️ Delete
                            </button>
                        </td>
                    </tr>
                    ";
                }
            } else {
                echo "<tr><td colspan='3' style='text-align:center; padding: 40px; color: #64748b;'>No sections found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>


<!-- ADD SECTION MODAL -->
<div id="add-section-modal" class="edit-modal hidden">
    <div class="edit-modal-box" style="width: 400px;">

        <h3>Add Section & Grade Level</h3>

        <form id="add-section-form">

            <label>Section</label>
            <input type="text" name="section" required>

            <label>Grade Level</label>
            <input type="text" name="grade_level" required>

            <label>Adviser</label>
            <select name="adviser_id"
                style="width: 100%; padding: 10px; margin-bottom: 14px; border: 1px solid #ddd; border-radius: 6px;">
                <option value="">-- Select Adviser --</option>
                <?php
                $advisers = $conn->query("SELECT id, name FROM advisers ORDER BY name");
                if ($advisers) {
                    while ($adv = $advisers->fetch_assoc()) {
                        echo "<option value='{$adv['id']}'>{$adv['name']}</option>";
                    }
                }
                ?>
            </select>

            <div class="modal-buttons">
                <button type="submit" class="btn-save-edit">Save</button>
                <button type="button" id="btn-cancel-add-section" class="btn-cancel">Cancel</button>
            </div>

        </form>

    </div>
</div>


<!-- EDIT MODAL -->
<div id="edit-modal" class="edit-modal hidden">
    <div class="edit-modal-box" style="width: 400px;">

        <h3>Edit Section & Grade Level</h3>

        <form id="edit-section-form">
            <input type="hidden" id="edit-id" name="id">

            <label>Section</label>
            <input type="text" id="edit-section" name="section" required>

            <label>Grade Level</label>
            <input type="text" id="edit-grade-level" name="grade_level" required>

            <label>Adviser</label>
            <select id="edit-adviser" name="adviser_id"
                style="width: 100%; padding: 10px; margin-bottom: 14px; border: 1px solid #ddd; border-radius: 6px;">
                <option value="">-- Select Adviser --</option>
                <?php
                $advisers = $conn->query("SELECT id, name FROM advisers ORDER BY name");
                if ($advisers) {
                    while ($adv = $advisers->fetch_assoc()) {
                        echo "<option value='{$adv['id']}'>{$adv['name']}</option>";
                    }
                }
                ?>
            </select>

            <div class="modal-buttons">
                <button type="submit" class="btn-save-edit">Save</button>
                <button type="button" id="btn-cancel-edit" class="btn-cancel">Cancel</button>
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
        min-width: 600px;
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

    .btn-edit, .btn-delete {
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

    .btn-delete {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fee2e2;
    }

    .btn-delete:hover {
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

    .edit-modal-box input, .edit-modal-box select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        margin-bottom: 10px;
        background: #fcfcfc;
    }

    .edit-modal-box input:focus, .edit-modal-box select:focus {
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
        // Search functionality
        var searchInput = document.getElementById('search-section');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var q = this.value.toLowerCase();
                var rows = document.querySelectorAll('#section-table tbody tr');
                for (var i = 0; i < rows.length; i++) {
                    var text = rows[i].textContent.toLowerCase();
                    rows[i].style.display = text.indexOf(q) !== -1 ? '' : 'none';
                }
            });
        }

        // Open Add Modal
        var addBtn = document.getElementById('btn-open-add-section');
        if (addBtn) {
            addBtn.onclick = function () {
                document.getElementById('add-section-modal').classList.remove('hidden');
            };
        }

        // Cancel Add Modal
        var cancelAddBtn = document.getElementById('btn-cancel-add-section');
        if (cancelAddBtn) {
            cancelAddBtn.onclick = function () {
                document.getElementById('add-section-modal').classList.add('hidden');
            };
        }

        // Add Form Submit
        var addForm = document.getElementById('add-section-form');
        if (addForm) {
            addForm.onsubmit = function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                fetch('sec_yr_level_save.php', { method: 'POST', body: formData })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            window.loadPage('sec_yr_level.php');
                        } else {
                            alert(data.message || 'Error saving');
                        }
                    });
                return false;
            };
        }

        // Edit buttons
        var editBtns = document.querySelectorAll('.btn-edit');
        for (var i = 0; i < editBtns.length; i++) {
            editBtns[i].onclick = function () {
                document.getElementById('edit-id').value = this.dataset.id;
                document.getElementById('edit-section').value = this.dataset.section;
                document.getElementById('edit-grade-level').value = this.dataset.grade;
                document.getElementById('edit-adviser').value = this.dataset.adviser || '';
                document.getElementById('edit-modal').classList.remove('hidden');
            };
        }

        // Cancel Edit Modal
        var cancelEditBtn = document.getElementById('btn-cancel-edit');
        if (cancelEditBtn) {
            cancelEditBtn.onclick = function () {
                document.getElementById('edit-modal').classList.add('hidden');
            };
        }

        // Edit Form Submit
        var editForm = document.getElementById('edit-section-form');
        if (editForm) {
            editForm.onsubmit = function (e) {
                e.preventDefault();
                var formData = new FormData(this);
                fetch('sec_yr_level_update.php', { method: 'POST', body: formData })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            window.loadPage('sec_yr_level.php');
                        } else {
                            alert(data.message || 'Error updating');
                        }
                    });
                return false;
            };
        }

        // Delete buttons
        var deleteBtns = document.querySelectorAll('.btn-delete');
        for (var i = 0; i < deleteBtns.length; i++) {
            deleteBtns[i].onclick = function () {
                var id = this.dataset.id;
                if (confirm('Are you sure you want to delete this section?')) {
                    var formData = new FormData();
                    formData.append('id', id);
                    fetch('sec_yr_level_delete.php', { method: 'POST', body: formData })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (data.success) {
                                window.loadPage('sec_yr_level.php');
                            } else {
                                alert(data.message || 'Error deleting');
                            }
                        });
                }
            };
        }
    }, 0);
</script>