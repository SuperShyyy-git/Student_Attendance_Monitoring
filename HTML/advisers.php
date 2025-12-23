<?php
include __DIR__ . "/../config/db_connect.php";
?>

<div class="header-bar">
    <h2 class='table-title'>Manage Advisers</h2>
    <button id="btn-logout" class="btn-logout">Logout</button>
</div>

<!-- ADD ADVISER BUTTON -->
<button id="btn-open-add-adviser" class="btn-add-student" onclick="window.openAddAdviserModal()">➕ Add Adviser</button>

<!-- SEARCH BAR -->
<div style="display: flex; gap: 15px; margin: 15px 0; align-items: center; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 200px;">
        <input type="text" id="search-adviser" placeholder="🔍 Search by name or contact..."
            style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
    </div>
</div>

<hr>

<!-- TABLE -->
<table class="student-table" id="adviser-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Contact Number</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $query = "SELECT * FROM advisers ORDER BY name ASC";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = (int) $row['id'];
                $name = htmlspecialchars($row['name'], ENT_QUOTES);
                $contact = htmlspecialchars($row['contact'] ?? '', ENT_QUOTES);

                echo "
                <tr data-id='{$id}'>
                    <td>{$name}</td>
                    <td>" . ($contact ?: '<span style="color:#999">N/A</span>') . "</td>
                    <td>
                        <button class='btn-edit' onclick=\"window.editAdviser({$id}, '{$name}', '{$contact}')\">
                            <span class='icon'>✏️</span> Edit
                        </button>
                        <button class='btn-delete' onclick=\"window.deleteAdviser({$id})\">
                            <span class='icon'>⛔</span> Delete
                        </button>
                    </td>
                </tr>
                ";
            }
        } else {
            echo "<tr><td colspan='3' style='text-align:center;'>No advisers found.</td></tr>";
        }
        ?>
    </tbody>
</table>

<!-- ADD ADVISER MODAL -->
<div id="add-adviser-modal" class="edit-modal hidden">
    <div class="edit-modal-box">
        <h3>Add Adviser</h3>
        <form id="add-adviser-form">
            <label>Full Name</label>
            <input type="text" name="name" required
                style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px;">

            <label>Contact Number</label>
            <input type="text" name="contact" placeholder="e.g. 09123456789"
                style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px;">

            <div class="modal-buttons">
                <button type="submit" class="btn-save-edit">Save</button>
                <button type="button" onclick="window.closeAddAdviserModal()">Cancel</button>
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
            <input type="text" name="name" id="edit-name" required
                style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px;">

            <label>Contact Number</label>
            <input type="text" name="contact" id="edit-contact" placeholder="e.g. 09123456789"
                style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px;">

            <div class="modal-buttons">
                <button type="submit" class="btn-save-edit">Update</button>
                <button type="button" onclick="window.closeEditAdviserModal()">Cancel</button>
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
        cursor: pointer;
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
        padding: 25px;
        border-radius: 10px;
        width: 400px;
    }

    .modal-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 15px;
    }

    .modal-buttons button {
        padding: 8px 15px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
    }

    .btn-save-edit {
        background: #27ae60;
        color: white;
    }
</style>

<script>
    setTimeout(function () {
        // Search functionality
        var searchInput = document.getElementById('search-adviser');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var q = this.value.toLowerCase();
                var rows = document.querySelectorAll('#adviser-table tbody tr');
                rows.forEach(function (row) {
                    var text = row.textContent.toLowerCase();
                    row.style.display = text.indexOf(q) !== -1 ? '' : 'none';
                });
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
                    .then(r => r.json())
                    .then(data => {
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
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            window.loadPage('advisers.php');
                        } else {
                            alert(data.message || 'Error updating adviser');
                        }
                    });
                return false;
            };
        }

        // Delete
        window.deleteAdviser = function (id) {
            if (confirm('Are you sure you want to delete this adviser? This may affect sections assigned to them.')) {
                var formData = new FormData();
                formData.append('id', id);
                fetch('adviser_delete.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            window.loadPage('advisers.php');
                        } else {
                            alert(data.message || 'Error deleting adviser');
                        }
                    });
            }
        };

    }, 0);
</script>