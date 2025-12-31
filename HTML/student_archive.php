<?php
include "../config/db_connect.php";
?>

<div class="header-bar">
    <h2 class='table-title'>📦 Archived Students</h2>
    <a href="#" onclick="loadPage('student_table.php')" class="btn-back">← Back to Student List</a>
</div>

<p style="padding: 0 20px; color: #666;">These students have been archived and are no longer active. You can restore
    them to make them active again.</p>

<hr>

<table class="student-table">
    <thead>
        <tr>
            <th>Student ID</th>
            <th>First Name</th>
            <th>Middle Name</th>
            <th>Last Name</th>
            <th>Grade Level</th>
            <th>Section</th>
            <th>Guardian</th>
            <th>Archived Date</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>
        <?php
        $query = "SELECT s.*, a.name as adviser_name 
                  FROM students s
                  LEFT JOIN section_yrlevel sy ON s.section = sy.section AND s.grade_level = sy.grade_level
                  LEFT JOIN advisers a ON sy.adviser_id = a.id
                  WHERE s.is_archived = 1
                  ORDER BY s.archived_at DESC";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $archivedDate = $row['archived_at'] ? date('M d, Y h:i A', strtotime($row['archived_at'])) : 'Unknown';

                echo "
                <tr>
                    <td>{$row['student_id']}</td>
                    <td>{$row['firstname']}</td>
                    <td>{$row['middlename']}</td>
                    <td>{$row['lastname']}</td>
                    <td>{$row['grade_level']}</td>
                    <td>{$row['section']}</td>
                    <td>{$row['guardian_name']}</td>
                    <td>{$archivedDate}</td>
                    <td class='actions-cell'>
                        <button class='btn-restore' data-id='{$row['id']}' data-name='{$row['firstname']} {$row['lastname']}'>♻️ Restore</button>
                        <button class='btn-permanent-delete' data-id='{$row['id']}' data-name='{$row['firstname']} {$row['lastname']}'>🗑️ Delete Permanently</button>
                    </td>
                </tr>
                ";
            }
        } else {
            echo "
            <tr>
                <td colspan='9' style='text-align:center; padding: 40px; color: #666;'>
                    <div style='font-size: 48px; margin-bottom: 10px;'>📭</div>
                    No archived students found.
                </td>
            </tr>
            ";
        }
        ?>
    </tbody>
</table>

<!-- RESTORE CONFIRMATION MODAL -->
<div id="restore-confirm-modal" class="edit-modal hidden">
    <div class="edit-modal-box" style="width: 400px; text-align: center;">
        <h3 style="color: #10b981;">♻️ Restore Student</h3>
        <p style="margin: 20px 0; font-size: 16px;">Restore <strong id="restore-student-name"></strong> to active
            students?</p>
        <input type="hidden" id="restore-student-id">
        <div class="modal-buttons" style="margin-top: 20px; justify-content: center;">
            <button id="btn-confirm-restore" class="btn-restore-confirm">Yes, Restore</button>
            <button id="btn-cancel-restore" class="btn-cancel">Cancel</button>
        </div>
    </div>
</div>

<!-- PERMANENT DELETE CONFIRMATION MODAL -->
<div id="permanent-delete-modal" class="edit-modal hidden">
    <div class="edit-modal-box" style="width: 400px; text-align: center;">
        <h3 style="color: #dc3545;">⚠️ Permanent Delete</h3>
        <p style="margin: 20px 0; font-size: 16px;">Permanently delete <strong id="permanent-delete-name"></strong>?</p>
        <p style="color: #dc3545; font-size: 13px; font-weight: 600;">⚠️ This action CANNOT be undone!</p>
        <input type="hidden" id="permanent-delete-id">
        <div class="modal-buttons" style="margin-top: 20px; justify-content: center;">
            <button id="btn-confirm-permanent-delete" class="btn-delete-confirm">Yes, Delete Forever</button>
            <button id="btn-cancel-permanent-delete" class="btn-cancel">Cancel</button>
        </div>
    </div>
</div>

<style>
    .header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 20px 0 20px;
    }

    .btn-back {
        background: #6b7280;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 8px 18px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
    }

    .btn-back:hover {
        background: #4b5563;
    }

    .actions-cell {
        white-space: nowrap;
    }

    .btn-restore,
    .btn-permanent-delete {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        margin: 2px;
        transition: all 0.2s ease;
    }

    .btn-restore {
        background: #10b981;
        color: white;
    }

    .btn-restore:hover {
        background: #059669;
    }

    .btn-permanent-delete {
        background: #6b7280;
        color: white;
    }

    .btn-permanent-delete:hover {
        background: #dc2626;
    }

    .btn-restore-confirm {
        padding: 10px 20px;
        background: #10b981;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-restore-confirm:hover {
        background: #059669;
    }

    .btn-delete-confirm {
        padding: 10px 20px;
        background: #dc2626;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-delete-confirm:hover {
        background: #b91c1c;
    }

    .btn-cancel {
        padding: 10px 20px;
        background: #e5e7eb;
        color: #374151;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
    }

    .btn-cancel:hover {
        background: #d1d5db;
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
        padding: 20px;
        border-radius: 8px;
        max-width: 95%;
    }
</style>

<script>
    setTimeout(function () {
        // Restore button click
        document.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('btn-restore')) {
                document.getElementById('restore-student-id').value = e.target.dataset.id;
                document.getElementById('restore-student-name').textContent = e.target.dataset.name;
                document.getElementById('restore-confirm-modal').classList.remove('hidden');
            }
        });

        // Cancel restore
        document.getElementById('btn-cancel-restore')?.addEventListener('click', function () {
            document.getElementById('restore-confirm-modal').classList.add('hidden');
        });

        // Confirm restore
        document.getElementById('btn-confirm-restore')?.addEventListener('click', function () {
            const id = document.getElementById('restore-student-id').value;

            const formData = new FormData();
            formData.append('id', id);

            fetch('student_restore.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload();
                    } else {
                        alert('❌ ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('❌ Failed to restore student');
                });
        });

        // Permanent delete button click
        document.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('btn-permanent-delete')) {
                document.getElementById('permanent-delete-id').value = e.target.dataset.id;
                document.getElementById('permanent-delete-name').textContent = e.target.dataset.name;
                document.getElementById('permanent-delete-modal').classList.remove('hidden');
            }
        });

        // Cancel permanent delete
        document.getElementById('btn-cancel-permanent-delete')?.addEventListener('click', function () {
            document.getElementById('permanent-delete-modal').classList.add('hidden');
        });

        // Confirm permanent delete
        document.getElementById('btn-confirm-permanent-delete')?.addEventListener('click', function () {
            const id = document.getElementById('permanent-delete-id').value;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('permanent', '1');

            fetch('student_permanent_delete.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload();
                    } else {
                        alert('❌ ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('❌ Failed to delete student');
                });
        });
    }, 0);
</script>