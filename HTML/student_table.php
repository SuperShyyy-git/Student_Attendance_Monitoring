<?php
include "../config/db_connect.php";

// Get stats
$totalStudents = 0;
$activeStudents = 0;
$archivedStudents = 0;

$statsQuery = $conn->query("SELECT COUNT(*) as total FROM students");
if ($statsQuery) {
    $totalStudents = $statsQuery->fetch_assoc()['total'];
}
$activeQuery = $conn->query("SELECT COUNT(*) as count FROM students WHERE is_archived = 0 OR is_archived IS NULL");
if ($activeQuery) {
    $activeStudents = $activeQuery->fetch_assoc()['count'];
}
$archivedQuery = $conn->query("SELECT COUNT(*) as count FROM students WHERE is_archived = 1");
if ($archivedQuery) {
    $archivedStudents = $archivedQuery->fetch_assoc()['count'];
}
?>

<div class="header-bar">
    <h2 class='table-title'>Student Management</h2>
    <button id="btn-logout" class="btn-logout">Logout</button>
</div>

<!-- ADD STUDENT BUTTON -->
<div style="display: flex; gap: 10px; margin: 15px 0;">
    <button id="btn-open-add-student" class="btn-add-student">➕ Add Student</button>
    <button onclick="loadPage('student_archive.php')" class="btn-view-archive">📦 View Archived</button>
</div>

<!-- SEARCH & FILTER BAR -->
<div style="display: flex; gap: 15px; margin: 15px 0; align-items: center; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 200px;">
        <input type="text" id="search-student" placeholder="🔍 Search by name, ID, or section..."
            style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
    </div>
    <div>
        <select id="filter-grade"
            style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; min-width: 150px;">
            <option value="">All Grade Levels</option>
            <?php
            $grades = $conn->query("SELECT DISTINCT grade_level FROM section_yrlevel ORDER BY grade_level");
            if ($grades) {
                while ($g = $grades->fetch_assoc()) {
                    echo "<option value=\"{$g['grade_level']}\">{$g['grade_level']}</option>";
                }
            }
            ?>
        </select>
    </div>
    <div>
        <select id="filter-section"
            style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; min-width: 150px;">
            <option value="">All Sections</option>
            <?php
            $sections = $conn->query("SELECT DISTINCT section FROM section_yrlevel ORDER BY section");
            if ($sections) {
                while ($s = $sections->fetch_assoc()) {
                    echo "<option value=\"{$s['section']}\">{$s['section']}</option>";
                }
            }
            ?>
        </select>
    </div>
</div>

<!-- STATS ROW -->
<div style="display: flex; gap: 15px; margin-bottom: 15px;">
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #3498db;">
        <div style="font-size: 24px; font-weight: 700; color: #2c3e50;">
            <?php echo $totalStudents; ?>
        </div>
        <div style="font-size: 12px; color: #7f8c8d;">Total Students</div>
    </div>
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #27ae60;">
        <div style="font-size: 24px; font-weight: 700; color: #27ae60;">
            <?php echo $activeStudents; ?>
        </div>
        <div style="font-size: 12px; color: #7f8c8d;">Active</div>
    </div>
    <div
        style="background: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #e74c3c;">
        <div style="font-size: 24px; font-weight: 700; color: #e74c3c;">
            <?php echo $archivedStudents; ?>
        </div>
        <div style="font-size: 12px; color: #7f8c8d;">Archived</div>
    </div>
</div>

<hr style="margin-bottom: 15px; border: none; border-top: 1px solid #ecf0f1;">

<div class="table-container">
    <table class="student-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Grade & Section</th>
                <th>Guardian Contact</th>
                <th>Email Status</th>
                <th style="min-width: 140px;">Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php
            $query = "SELECT s.*, a.name as adviser_name 
                      FROM students s
                      LEFT JOIN section_yrlevel sy ON s.section = sy.section AND s.grade_level = sy.grade_level
                      LEFT JOIN advisers a ON sy.adviser_id = a.id
                      WHERE (s.is_archived = 0 OR s.is_archived IS NULL)
                      ORDER BY s.lastname ASC, s.firstname ASC";
            $result = $conn->query($query);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $fullName = htmlspecialchars($row['lastname'] . ", " . $row['firstname'] . " " . $row['middlename']);
                    $gradeSection = htmlspecialchars($row['grade_level'] . " - " . $row['section']);
                    $contact = htmlspecialchars($row['guardian_contact']);
                    $emailStatus = !empty($row['guardian_email']) ? '<span class=\'status-connected\'>✓ Set</span>' : '<span class=\'status-not-connected\'>Not Set</span>';

                    echo "
                    <tr>
                        <td style='font-family: monospace; font-weight: 600;'>{$row['student_id']}</td>
                        <td style='font-weight: 500;'>{$fullName}</td>
                        <td>{$gradeSection}</td>
                        <td>{$contact}</td>
                        <td>{$emailStatus}</td>
                        <td class='actions-cell'>
                            <button class='btn-edit' data-id='{$row['id']}' data-student='{$row['student_id']}' data-firstname='{$row['firstname']}' data-middlename='{$row['middlename']}' data-lastname='{$row['lastname']}' data-address='{$row['address']}' data-grade='{$row['grade_level']}' data-section='{$row['section']}' data-guardian='{$row['guardian_name']}' data-contact='{$row['guardian_contact']}' data-email='{$row['guardian_email']}'>✏️ Edit</button>
                            <button class='btn-delete' data-id='{$row['id']}' data-name='{$fullName}'>🗑️ Delete</button>
                        </td>
                    </tr>
                    ";
                }
            } else {
                echo "<tr><td colspan='6' style='text-align:center;'>No students found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>


<!-- ADD STUDENT MODAL -->
<div id="add-student-modal" class="edit-modal hidden">
    <div class="edit-modal-box student-modal" style="width: 1000px; max-width: 95vw;">
        <h3>➕ Add New Student</h3>

        <div class="student-modal-body" style="display: flex; gap: 30px; align-items: flex-start;">
            <!-- LEFT SIDE = FORM -->
            <form id="add-student-form" class="student-form" style="flex: 1;">
                <div
                    style="background: #f0f9ff; color: #0369a1; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; border-left: 4px solid #0ea5e9; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 18px;">ℹ️</span>
                    <span>Student Number will be auto-generated (e.g., STU-2025-0001)</span>
                </div>

                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Firstname</label>
                        <input type="text" name="firstname" placeholder="Enter first name" required>
                    </div>
                    <div class="form-group">
                        <label>Middlename</label>
                        <input type="text" name="middlename" placeholder="Enter middle name">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Lastname</label>
                        <input type="text" name="lastname" placeholder="Enter last name" required>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Address</label>
                        <input type="text" name="address" placeholder="Complete home address...">
                    </div>
                    <div class="form-group">
                        <label>Grade Level</label>
                        <select name="grade_level" id="add-grade-level" required>
                            <option value="">Loading...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Section</label>
                        <select name="section" id="add-section" required>
                            <option value="">Loading...</option>
                        </select>
                    </div>
                </div>

                <hr style="margin: 25px 0; border: none; border-top: 1.5px dashed #e2e8f0;">

                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Guardian's Name</label>
                    <input type="text" name="guardian_name" placeholder="Full name of parent/guardian" required>
                </div>

                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="guardian_contact" id="guardian-contact" pattern="^09[0-9]{9}$"
                            maxlength="11" placeholder="09XXXXXXXXX" required>
                    </div>
                    <div class="form-group">
                        <label>Parent Email</label>
                        <input type="email" name="guardian_email" placeholder="parent@email.com" required>
                    </div>
                </div>

                <input type="hidden" name="photo_data" id="photo-data">
                <input type="hidden" name="face_encoding" id="face-encoding">

                <div class="modal-buttons" style="margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                    <button type="submit" class="btn-save-edit"
                        style="background: #1e293b; color: white; padding: 12px 24px;">💾 Save Student Record</button>
                    <button type="button" id="btn-cancel-add-student" class="btn-cancel"
                        style="padding: 12px 24px;">Cancel</button>
                </div>
            </form>

            <!-- RIGHT SIDE = CAMERA -->
            <div class="camera-section" style="width: 480px; flex-shrink: 0;">
                <h4
                    style="margin-top: 0; margin-bottom: 15px; color: #475569; display: flex; align-items: center; gap: 10px;">
                    <span>📷 Facial Recognition Enrollment</span>
                    <span id="face-loading" style="font-size: 12px; font-weight: normal; color: #94a3b8;">Loading
                        Models...</span>
                </h4>

                <div class="camera-wrapper"
                    style="position: relative; border-radius: 12px; overflow: hidden; background: #0f172a; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
                    <video id="camera-preview" autoplay
                        style="width: 480px; height: 360px; transform: scaleX(-1); display: block; object-fit: cover;"></video>
                    <canvas id="face-overlay-canvas" width="480" height="360"
                        style="position: absolute; top: 0; left: 0; pointer-events: none; transform: scaleX(-1);"></canvas>
                    <div id="face-badge" class="face-status-badge no-face">👤 No Face Detected</div>
                </div>

                <button type="button" id="capture-btn" class="btn-save-edit"
                    style="margin-top: 15px; width: 100%; height: 50px; background: #3b82f6; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 16px;">
                    📸 Capture Face Data
                </button>

                <canvas id="snapshot-canvas" width="480" height="360" style="display:none;"></canvas>

                <!-- Photo Preview Section -->
                <div id="photo-preview-container"
                    style="display:none; margin-top: 20px; padding: 15px; background: #ecfdf5; border: 2px solid #10b981; border-radius: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <span style="font-size: 24px;">✅</span>
                        <div>
                            <p style="font-size: 14px; font-weight: 700; color: #047857; margin: 0;">Face Captured
                                Successfully!</p>
                            <p style="font-size: 12px; color: #059669; margin: 4px 0 0 0;">Face data ready for
                                enrollment</p>
                        </div>
                    </div>
                    <div style="position: relative;">
                        <img id="photo-preview"
                            style="width: 100%; border-radius: 8px; border: 3px solid #10b981; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                        <div
                            style="position: absolute; top: 8px; right: 8px; background: #10b981; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                            📷 CAPTURED
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!-- EDIT STUDENT MODAL -->
<div id="edit-student-modal" class="edit-modal hidden">
    <div class="edit-modal-box" style="width: 500px; max-width: 95vw;">
        <h3>✏️ Edit Student Profile</h3>
        <form id="edit-student-form">
            <input type="hidden" name="id" id="edit-id">

            <div
                style="background: #eff6ff; color: #1d4ed8; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid #dbeafe; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 18px;">🆔</span>
                <span>Student ID: <strong id="edit-student-id-display"></strong></span>
            </div>

            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label>Firstname</label>
                    <input type="text" name="firstname" id="edit-firstname" required>
                </div>
                <div>
                    <label>Middlename</label>
                    <input type="text" name="middlename" id="edit-middlename">
                </div>
                <div style="grid-column: span 2;">
                    <label>Lastname</label>
                    <input type="text" name="lastname" id="edit-lastname" required>
                </div>
            </div>

            <label style="margin-top: 15px;">Address</label>
            <input type="text" name="address" id="edit-address" placeholder="Residential address">

            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                <div>
                    <label>Grade Level</label>
                    <select name="grade_level" id="edit-grade-level" required>
                        <option value="">Select Grade</option>
                    </select>
                </div>
                <div>
                    <label>Section</label>
                    <select name="section" id="edit-section" required>
                        <option value="">Select Section</option>
                    </select>
                </div>
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">

            <label>Guardian's Name</label>
            <input type="text" name="guardian_name" id="edit-guardian-name" required>

            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                <div>
                    <label>Contact Number</label>
                    <input type="text" name="guardian_contact" id="edit-guardian-contact" pattern="^09[0-9]{9}$"
                        maxlength="11">
                </div>
                <div>
                    <label>Email Address</label>
                    <input type="email" name="guardian_email" id="edit-guardian-email">
                </div>
            </div>

            <div class="modal-buttons" style="margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                <button type="submit" class="btn-save-edit" style="background: #1e293b; color: white;">💾 Update
                    Record</button>
                <button type="button" id="btn-cancel-edit" class="btn-cancel">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE CONFIRMATION MODAL -->
<div id="delete-confirm-modal" class="edit-modal hidden">
    <div class="edit-modal-box" style="width: 420px; text-align: center; padding: 35px 25px;">
        <div
            style="width: 70px; height: 70px; background: #fee2e2; color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 35px; margin: 0 auto 20px;">
            ⚠️
        </div>
        <h3 style="color: #1e293b; border: none; margin-bottom: 10px;">Archive Student?</h3>
        <p style="margin: 0 0 20px; font-size: 15px; color: #475569;">Are you sure you want to move <strong
                id="delete-student-name" style="color: #1e293b;"></strong> to archives?</p>
        <p style="color: #94a3b8; font-size: 13px; margin-bottom: 25px;">You can still restore this student later from
            the archives section.</p>

        <input type="hidden" id="delete-student-id">
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button id="btn-confirm-delete" class="btn-delete-confirm"
                style="flex: 1; padding: 12px; background: #dc2626;">Yes, Archive</button>
            <button id="btn-cancel-delete" class="btn-cancel" style="flex: 1; padding: 12px;">Cancel</button>
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

    .btn-logout {
        background: #dc3545;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 8px 18px;
        font-weight: 600;
        cursor: pointer;
        font-size: 16px;
        margin-left: 20px;
    }

    .btn-logout:hover {
        background: #b71c1c;
    }

    .btn-view-archive {
        background: #6b7280;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 8px 18px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-view-archive:hover {
        background: #4b5563;
    }

    .logout-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .logout-modal-box {
        background: #fff;
        padding: 28px 32px;
        border-radius: 10px;
        box-shadow: 0 2px 16px rgba(0, 0, 0, 0.12);
        text-align: center;
        min-width: 320px;
    }

    .logout-modal-box h3 {
        margin-bottom: 18px;
    }

    .logout-modal-box .modal-buttons {
        display: flex;
        justify-content: center;
        gap: 18px;
        margin-top: 18px;
    }

    .logout-modal-box button {
        padding: 8px 22px;
        border-radius: 6px;
        border: none;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
    }

    .logout-modal-box .btn-yes {
        background: #dc3545;
        color: #fff;
    }

    .logout-modal-box .btn-no {
        background: #e2e3e5;
        color: #333;
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
        min-width: 800px;
    }

    .student-table th {
        background: #1e293b;
        color: white;
        padding: 14px 12px;
        text-align: left;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: sticky;
        top: 0;
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

    .status-connected {
        display: inline-block;
        padding: 4px 10px;
        background: #dcfce7;
        color: #166534;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
    }

    .status-not-connected {
        display: inline-block;
        padding: 4px 10px;
        background: #fee2e2;
        color: #991b1b;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
    }

    /* Modals */
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
        width: 520px;
        max-width: 95%;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .edit-modal-box h3 {
        margin-top: 0;
        color: #1e293b;
        font-size: 1.25rem;
        margin-bottom: 20px;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 12px;
    }

    /* Action Buttons */
    .actions-cell {
        display: flex;
        gap: 8px;
    }

    .btn-edit,
    .btn-delete {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        color: #0f172a;
        font-size: 1.75rem;
        font-weight: 800;
        margin-bottom: 28px;
        border-bottom: 2px solid #f8fafc;
        padding-bottom: 20px;
        letter-spacing: -0.025em;
    }

    /* Form Elements */
    .form-group {
        margin-bottom: 4px;
    }

    .edit-modal-box label {
        display: block;
        margin-bottom: 8px;
        font-weight: 700;
        color: #334155;
        font-size: 13px;
        letter-spacing: 0.01em;
    }

    .edit-modal-box input,
    .edit-modal-box select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 15px;
        color: #1e293b;
        background: #fcfcfc;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-sizing: border-box;
    }

    .edit-modal-box input:focus,
    .edit-modal-box select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        background: white;
        transform: translateY(-1px);
    }

    .edit-modal-box input::placeholder {
        color: #94a3b8;
    }

    /* Professional Buttons */
    .modal-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 16px;
    }

    .btn-save-edit {
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 12px;
        padding: 14px 28px;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
    }

    .btn-save-edit:hover {
        background: #1d4ed8;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
    }

    .btn-save-edit:active {
        transform: translateY(0);
    }

    .btn-cancel {
        background: #f8fafc;
        color: #64748b;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 28px;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-cancel:hover {
        background: #f1f5f9;
        color: #1e293b;
        border-color: #cbd5e1;
    }

    .btn-delete-confirm {
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 12px;
        padding: 14px 28px;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2);
    }

    .btn-delete-confirm:hover {
        background: #dc2626;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3);
    }

    /* Action Buttons */
    .actions-cell {
        display: flex;
        gap: 8px;
    }

    .btn-edit,
    .btn-delete {
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

    .face-status-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        z-index: 10;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .face-status-badge.face-ok {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .face-status-badge.no-face {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

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
</style>

<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>

<script>
    (function () {
        // ========================================
        // SEARCH & FILTER FUNCTIONALITY
        // ========================================
        var searchInput = document.getElementById('search-student');
        var gradeFilter = document.getElementById('filter-grade');
        var sectionFilter = document.getElementById('filter-section');
        var studentTable = document.querySelector('.student-table tbody');

        function filterTable() {
            if (!studentTable) return;

            var searchText = searchInput ? searchInput.value.toLowerCase().trim() : '';
            var gradeValue = gradeFilter ? gradeFilter.value.trim() : '';
            var sectionValue = sectionFilter ? sectionFilter.value.trim() : '';

            var rows = studentTable.querySelectorAll('tr');

            rows.forEach(function (row) {
                var text = row.textContent.toLowerCase();
                var gradeCell = row.cells[2]; // Grade & Section column (index 2)
                var gradeText = gradeCell ? gradeCell.textContent.trim() : '';

                var matchesSearch = searchText === '' || text.indexOf(searchText) !== -1;
                var matchesGrade = gradeValue === '' || gradeText.includes(gradeValue);
                var matchesSection = sectionValue === '' || gradeText.includes(sectionValue);

                row.style.display = (matchesSearch && matchesGrade && matchesSection) ? '' : 'none';
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterTable);
        if (gradeFilter) gradeFilter.addEventListener('change', filterTable);
        if (sectionFilter) sectionFilter.addEventListener('change', filterTable);

        // Guardian contact number validation
        var guardianContact = document.getElementById('guardian-contact');
        if (guardianContact) {
            guardianContact.addEventListener('input', function (e) {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 0 && !this.value.startsWith('09')) {
                    this.setCustomValidity('Number must start with 09');
                } else if (this.value.length > 0 && this.value.length !== 11) {
                    this.setCustomValidity('Must be exactly 11 digits');
                } else {
                    this.setCustomValidity('');
                }
            });
        }

        // ========================================
        // FACE DETECTION & CAMERA
        // ========================================
        let faceModelsLoaded = false;
        let faceDetectionInterval = null;
        let registeredStudents = [];

        async function loadFaceModels() {
            const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
            const loadingEl = document.getElementById('face-loading');
            const captureBtn = document.getElementById('capture-btn');
            try {
                if (typeof faceapi === 'undefined') {
                    setTimeout(loadFaceModels, 500);
                    return;
                }
                await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
                faceModelsLoaded = true;
                if (loadingEl) loadingEl.textContent = 'Face detection ready';
                if (captureBtn) captureBtn.disabled = false;

                // Load existing students for duplicate detection
                loadRegisteredStudents();
            } catch (error) {
                console.error('Error loading face models:', error);
            }
        }

        async function loadRegisteredStudents() {
            try {
                const response = await fetch('get_face_encodings.php');
                const data = await response.json();
                if (data.success && data.students) {
                    registeredStudents = data.students;
                }
            } catch (error) {
                console.error('Error loading students:', error);
            }
        }

        function findBestMatch(capturedDescriptor) {
            if (!registeredStudents.length) return null;
            let bestMatch = null;
            let minDistance = 0.4; // Stricter threshold to avoid false positives

            registeredStudents.forEach(student => {
                const distance = faceapi.euclideanDistance(capturedDescriptor, student.encoding);
                if (distance < minDistance) {
                    minDistance = distance;
                    bestMatch = { student, distance };
                }
            });
            return bestMatch;
        }

        let isCapturing = false;

        function startFaceDetectionForRegistration() {
            const video = document.getElementById('camera-preview');
            const overlayCanvas = document.getElementById('face-overlay-canvas');
            const faceBadge = document.getElementById('face-badge');
            if (!video || !overlayCanvas || !faceModelsLoaded) return;

            overlayCanvas.width = 480;
            overlayCanvas.height = 360;
            const ctx = overlayCanvas.getContext('2d');

            faceDetectionInterval = setInterval(async () => {
                if (!faceModelsLoaded || isCapturing) return;
                try {
                    const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.3 })).withFaceLandmarks();
                    ctx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
                    const scaleX = 480 / video.videoWidth;
                    const scaleY = 360 / video.videoHeight;

                    if (detections.length > 0) {
                        faceBadge.textContent = '✓ Face Detected';
                        faceBadge.className = 'face-status-badge face-ok';

                        // Auto-scan disabled as per user request

                        detections.forEach(detection => {
                            const pts = detection.landmarks.positions;
                            ctx.strokeStyle = '#3b82f6'; // Match kiosk solid blue
                            ctx.fillStyle = '#3b82f6';
                            ctx.lineWidth = 2; // Slightly thicker

                            // Draw landmarks as dots
                            pts.forEach(p => {
                                ctx.beginPath();
                                ctx.arc(p.x * scaleX, p.y * scaleY, 1.5, 0, 2 * Math.PI);
                                ctx.fill();
                            });

                            // Basic mesh lines
                            drawMeshPath(ctx, pts, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16], scaleX, scaleY); // jaw
                            drawMeshPath(ctx, pts, [17, 18, 19, 20, 21], scaleX, scaleY); // l-brow
                            drawMeshPath(ctx, pts, [22, 23, 24, 25, 26], scaleX, scaleY); // r-brow
                            drawMeshPath(ctx, pts, [27, 28, 29, 30, 31, 32, 33, 34, 35], scaleX, scaleY); // nose
                            drawMeshPath(ctx, pts, [36, 37, 38, 39, 40, 41, 36], scaleX, scaleY); // l-eye
                            drawMeshPath(ctx, pts, [42, 43, 44, 45, 46, 47, 42], scaleX, scaleY); // r-eye
                            drawMeshPath(ctx, pts, [48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 48], scaleX, scaleY); // mouth
                        });
                    } else {
                        faceBadge.textContent = '👤 No Face';
                        faceBadge.className = 'face-status-badge no-face';
                        faceStableCount = 0;
                    }
                } catch (e) { }
            }, 150);
        }

        function drawMeshPath(ctx, points, indices, scaleX, scaleY) {
            ctx.beginPath();
            ctx.moveTo(points[indices[0]].x * scaleX, points[indices[0]].y * scaleY);
            for (let i = 1; i < indices.length; i++) {
                ctx.lineTo(points[indices[i]].x * scaleX, points[indices[i]].y * scaleY);
            }
            ctx.stroke();
        }

        async function generateFaceDescriptor() {
            const video = document.getElementById('camera-preview');
            const faceBadge = document.getElementById('face-badge');
            if (!video) return;

            try {
                const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.3 }))
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (detection) {
                    const descriptor = Array.from(detection.descriptor);
                    document.getElementById('face-encoding').value = JSON.stringify(descriptor);

                    // Duplicate check
                    const match = findBestMatch(detection.descriptor);
                    if (match) {
                        faceBadge.textContent = '❌ ALREADY REGISTERED: ' + match.student.name;
                        faceBadge.style.background = '#dc2626';
                        alert('⚠️ WARNING: This face appears to be already registered to: ' + match.student.name);
                    } else {
                        faceBadge.textContent = '✅ Face Ready';
                        faceBadge.className = 'face-status-badge face-ok';
                        faceBadge.style.background = '';
                    }
                }
            } catch (err) {
                console.error("Descriptor error:", err);
            }
        }

        function stopFaceDetection() {
            if (faceDetectionInterval) {
                clearInterval(faceDetectionInterval);
                faceDetectionInterval = null;
            }
            isCapturing = false;
        }

        let cameraStream = null;
        async function startCamera() {
            const video = document.getElementById('camera-preview');
            if (!video) return;
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } });
                video.srcObject = cameraStream;
            } catch (err) {
                console.error("Camera error:", err);
                alert("❌ Cannot access camera. Please ensure permissions are granted.");
            }
        }

        function stopCamera() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
            }
            const video = document.getElementById('camera-preview');
            if (video) video.srcObject = null;
        }

        // ========================================
        // ADD STUDENT DROPDOWNS & MODAL
        // ========================================
        let sectionData = [];
        function loadAddStudentDropdowns() {
            fetch('student_load_dropdowns.php')
                .then(response => response.json())
                .then(data => {
                    sectionData = data;
                    const gradeSelect = document.getElementById('add-grade-level');
                    const sectionSelect = document.getElementById('add-section');
                    const uniqueGrades = [...new Set(data.map(item => item.grade_level))];
                    gradeSelect.innerHTML = '<option value="">Select Grade Level</option>';
                    uniqueGrades.forEach(grade => {
                        const option = document.createElement('option');
                        option.value = grade;
                        option.textContent = grade;
                        gradeSelect.appendChild(option);
                    });
                    sectionSelect.innerHTML = '<option value="">Select Grade First</option>';
                    gradeSelect.onchange = function () {
                        sectionSelect.innerHTML = '<option value="">Select Section</option>';
                        if (this.value) {
                            const sections = sectionData.filter(item => item.grade_level === this.value);
                            sections.forEach(item => {
                                const option = document.createElement('option');
                                option.value = item.section;
                                option.textContent = item.section;
                                sectionSelect.appendChild(option);
                            });
                        }
                    };
                })
                .catch(err => console.error('Error loading dropdowns:', err));
        }

        // Open/Close Modal
        const openBtn = document.getElementById('btn-open-add-student');
        if (openBtn) {
            openBtn.addEventListener('click', function () {
                document.getElementById('add-student-modal').classList.remove('hidden');
                loadAddStudentDropdowns();
                startCamera();
                setTimeout(startFaceDetectionForRegistration, 1000);
            });
        }
        const cancelBtn = document.getElementById('btn-cancel-add-student');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                stopFaceDetection();
                stopCamera();
                document.getElementById('add-student-modal').classList.add('hidden');
            });
        }

        // Photo Capture (Manual)
        document.getElementById('capture-btn')?.addEventListener('click', async function () {
            const video = document.getElementById('camera-preview');
            const canvas = document.getElementById('snapshot-canvas');
            const preview = document.getElementById('photo-preview');
            const previewContainer = document.getElementById('photo-preview-container');
            const photoInput = document.getElementById('photo-data');
            if (video && canvas && preview && photoInput) {
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const dataUrl = canvas.toDataURL('image/jpeg');
                preview.src = dataUrl;
                preview.style.display = 'block';
                previewContainer.style.display = 'block'; // Show the container
                photoInput.value = dataUrl;

                // Also generate descriptor
                this.disabled = true;
                this.textContent = '🔍 Generating encoding...';
                await generateFaceDescriptor();
                this.disabled = false;
                this.textContent = '📷 Capture Photo';
            }
        });

        // Form Submit
        document.getElementById('add-student-form')?.addEventListener('submit', function (e) {
            e.preventDefault();

            // Validate face encoding
            if (!document.getElementById('face-encoding').value) {
                alert('❓ Please capture a photo with a visible face first.');
                return;
            }

            const btnSubmit = this.querySelector('button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Saving...';
            fetch('student_add_save.php', { method: 'POST', body: new FormData(this) })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + (data.message || 'Student added!'));
                        stopFaceDetection();
                        stopCamera();
                        document.getElementById('add-student-modal').classList.add('hidden');
                        if (typeof loadPage === 'function') loadPage('student_table.php');
                        else location.reload();
                    } else {
                        alert('❌ ' + data.message);
                        btnSubmit.disabled = false;
                        btnSubmit.textContent = 'Save';
                    }
                })
                .catch(() => {
                    alert('❌ Error saving');
                    btnSubmit.disabled = false;
                });
        });

        // ========================================
        // EDIT STUDENT
        // ========================================
        function loadEditGradeLevels(selectedGrade, selectedSection) {
            fetch('student_load_dropdowns.php')
                .then(r => r.json())
                .then(data => {
                    const gradeSel = document.getElementById('edit-grade-level');
                    const secSel = document.getElementById('edit-section');
                    gradeSel.innerHTML = '<option value="">Select Grade Level</option>';
                    [...new Set(data.map(item => item.grade_level))].forEach(g => {
                        const opt = document.createElement('option');
                        opt.value = g; opt.textContent = g;
                        if (g === selectedGrade) opt.selected = true;
                        gradeSel.appendChild(opt);
                    });
                    updateEditSections(data, selectedGrade, selectedSection);
                    gradeSel.onchange = () => updateEditSections(data, gradeSel.value, '');
                });
        }
        function updateEditSections(data, grade, sel) {
            const secSel = document.getElementById('edit-section');
            secSel.innerHTML = '<option value="">Select Section</option>';
            data.filter(i => i.grade_level === grade).forEach(i => {
                const opt = document.createElement('option');
                opt.value = i.section; opt.textContent = i.section;
                if (i.section === sel) opt.selected = true;
                secSel.appendChild(opt);
            });
        }
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-edit');
            if (btn) {
                document.getElementById('edit-id').value = btn.dataset.id;
                document.getElementById('edit-student-id-display').textContent = btn.dataset.student;
                document.getElementById('edit-firstname').value = btn.dataset.firstname || '';
                document.getElementById('edit-middlename').value = btn.dataset.middlename || '';
                document.getElementById('edit-lastname').value = btn.dataset.lastname || '';
                document.getElementById('edit-address').value = btn.dataset.address || '';
                document.getElementById('edit-guardian-name').value = btn.dataset.guardian || '';
                document.getElementById('edit-guardian-contact').value = btn.dataset.contact || '';
                document.getElementById('edit-guardian-email').value = btn.dataset.email || '';
                loadEditGradeLevels(btn.dataset.grade, btn.dataset.section);
                document.getElementById('edit-student-modal').classList.remove('hidden');
            }
        });
        document.getElementById('btn-cancel-edit')?.addEventListener('click', () => {
            document.getElementById('edit-student-modal').classList.add('hidden');
        });
        document.getElementById('edit-student-form')?.addEventListener('submit', function (e) {
            e.preventDefault();
            fetch('student_update.php', { method: 'POST', body: new FormData(this) })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload();
                    } else alert('❌ ' + data.message);
                });
        });

        // ========================================
        // DELETE STUDENT
        // ========================================
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-delete');
            if (btn) {
                document.getElementById('delete-student-id').value = btn.dataset.id;
                document.getElementById('delete-student-name').textContent = btn.dataset.name;
                document.getElementById('delete-confirm-modal').classList.remove('hidden');
            }
        });
        document.getElementById('btn-cancel-delete')?.addEventListener('click', () => {
            document.getElementById('delete-confirm-modal').classList.add('hidden');
        });
        document.getElementById('btn-confirm-delete')?.addEventListener('click', () => {
            const fd = new FormData();
            fd.append('id', document.getElementById('delete-student-id').value);
            fetch('student_delete.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        if (typeof loadPage === 'function') loadPage('student_table.php');
                        else location.reload();
                    } else alert('❌ ' + data.message);
                });
        });

        loadFaceModels();
    })();
</script>