<?php
include "../config/db_connect.php";
?>

<div class="header-bar">
    <h2 class='table-title'>Student List</h2>
    <button id="btn-logout" class="btn-logout">Logout</button>
</div>

<!-- ADD STUDENT BUTTON -->
<div style="display: flex; gap: 10px; margin: 15px 20px;">
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
</div>

<hr>

<table class="student-table">
    <thead>
        <tr>
            <th>Student ID</th>
            <th>First Name</th>
            <th>Middle Name</th>
            <th>Last Name</th>
            <th>Address</th>
            <th>Grade Level</th>
            <th>Section</th>
            <th>Guardian</th>
            <th>Contact</th>
            <th>Email</th>
            <th>Email Status</th>
            <th>Actions</th>
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

                $emailStatus = !empty($row['guardian_email']) ? '<span class=\'status-connected\'>✓ Set</span>' : '<span class=\'status-not-connected\'>Not Set</span>';
                $guardianEmail = !empty($row['guardian_email']) ? htmlspecialchars($row['guardian_email']) : '<span style="color:#999">-</span>';

                echo "
                <tr>
                    <td>{$row['student_id']}</td>
                    <td>{$row['firstname']}</td>
                    <td>{$row['middlename']}</td>
                    <td>{$row['lastname']}</td>
                    <td>{$row['address']}</td>
                    <td>{$row['grade_level']}</td>
                    <td>{$row['section']}</td>
                    <td>{$row['guardian_name']}</td>
                    <td>{$row['guardian_contact']}</td>
                    <td>{$guardianEmail}</td>
                    <td>{$emailStatus}</td>
                    <td class='actions-cell'>
                        <button class='btn-edit' data-id='{$row['id']}' data-student='{$row['student_id']}' data-firstname='{$row['firstname']}' data-middlename='{$row['middlename']}' data-lastname='{$row['lastname']}' data-address='{$row['address']}' data-grade='{$row['grade_level']}' data-section='{$row['section']}' data-guardian='{$row['guardian_name']}' data-contact='{$row['guardian_contact']}' data-email='{$row['guardian_email']}'>✏️ Edit</button>
                        <button class='btn-delete' data-id='{$row['id']}' data-name='{$row['firstname']} {$row['lastname']}'>🗑️ Delete</button>
                    </td>
                </tr>
                ";
            }
        } else {
            echo "
            <tr>
                <td colspan='12' style='text-align:center;'>No students found.</td>
            </tr>
            ";
        }
        ?>
    </tbody>
</table>


<!-- ADD STUDENT MODAL (UPDATED WITH RFID + CAMERA CAPTURE + 2-COLUMN LAYOUT) -->
<div id="add-student-modal" class="edit-modal hidden">
    <div class="edit-modal-box student-modal">

        <h3>Add Student</h3>

        <!-- WRAPPER (2 COLUMNS) -->
        <div class="student-modal-body">

            <!-- LEFT SIDE = FORM -->
            <form id="add-student-form" class="student-form">

                <p
                    style="background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px;">
                    📋 Student Number will be auto-generated (e.g., STU-2025-0001)
                </p>

                <label>Firstname</label>
                <input type="text" name="firstname" required>

                <label>Middlename</label>
                <input type="text" name="middlename">

                <label>Lastname</label>
                <input type="text" name="lastname" required>

                <label>Address</label>
                <input type="text" name="address" placeholder="Complete address...">

                <label>Grade Level</label>
                <select name="grade_level" id="add-grade-level" required>
                    <option value="">Loading...</option>
                </select>

                <label>Section</label>
                <select name="section" id="add-section" required>
                    <option value="">Loading...</option>
                </select>

                <label>Guardian's Name</label>
                <input type="text" name="guardian_name" required>

                <label>Guardian's Contact Number</label>
                <input type="text" name="guardian_contact" id="guardian-contact" pattern="^09[0-9]{9}$" maxlength="11"
                    placeholder="09XXXXXXXXX" title="Must be 11 digits starting with 09" required>
                <small style="color: #666; font-size: 12px;">Format: 09XXXXXXXXX (11 digits)</small>

                <label>Guardian's Email (for notifications)</label>
                <input type="email" name="guardian_email" placeholder="parent@email.com" required>

                <!-- Hidden input for Base64 Image -->
                <input type="hidden" name="photo_data" id="photo-data">

                <div class="modal-buttons">
                    <button type="submit" class="btn-save-edit">Save</button>
                    <button type="button" id="btn-cancel-add-student">Cancel</button>
                </div>

            </form>

            <!-- RIGHT SIDE = CAMERA -->
            <div class="camera-box">

                <h4>📷 Student Photo</h4>

                <div class="camera-preview-wrapper" style="position: relative; display: inline-block;">
                    <video id="camera-preview" autoplay
                        style="width: 320px; height: 240px; border-radius: 8px; transform: scaleX(-1);"></video>
                    <canvas id="face-overlay-canvas" width="320" height="240"
                        style="position: absolute; top: 0; left: 0; pointer-events: none; transform: scaleX(-1);"></canvas>
                    <div id="face-badge" class="face-status-badge no-face">👤 No Face</div>
                </div>

                <button type="button" id="capture-btn" class="btn-save-edit" style="margin-top:10px;">
                    📷 Capture Photo
                </button>

                <canvas id="snapshot-canvas" width="480" height="360" style="display:none;"></canvas>

                <img id="photo-preview" style="display:none; margin-top:10px; max-width: 320px; border-radius: 8px;">

            </div>

        </div>
    </div>
</div>
</div>
<!-- EDIT STUDENT MODAL -->
<div id="edit-student-modal" class="edit-modal hidden">
    <div class="edit-modal-box" style="width: 480px;">
        <h3>✏️ Edit Student</h3>
        <form id="edit-student-form">
            <input type="hidden" name="id" id="edit-id">

            <p
                style="background: #e3f2fd; color: #1565c0; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px;">
                📋 Student ID: <strong id="edit-student-id-display"></strong>
            </p>

            <label>Firstname</label>
            <input type="text" name="firstname" id="edit-firstname" required>

            <label>Middlename</label>
            <input type="text" name="middlename" id="edit-middlename">

            <label>Lastname</label>
            <input type="text" name="lastname" id="edit-lastname" required>

            <label>Address</label>
            <input type="text" name="address" id="edit-address">

            <label>Grade Level</label>
            <select name="grade_level" id="edit-grade-level" required>
                <option value="">Select Grade Level</option>
            </select>

            <label>Section</label>
            <select name="section" id="edit-section" required>
                <option value="">Select Section</option>
            </select>

            <label>Guardian's Name</label>
            <input type="text" name="guardian_name" id="edit-guardian-name" required>

            <label>Guardian's Contact Number</label>
            <input type="text" name="guardian_contact" id="edit-guardian-contact" pattern="^09[0-9]{9}$" maxlength="11"
                placeholder="09XXXXXXXXX">

            <label>Guardian's Email</label>
            <input type="email" name="guardian_email" id="edit-guardian-email">

            <div class="modal-buttons" style="margin-top: 20px;">
                <button type="submit" class="btn-save-edit">💾 Save Changes</button>
                <button type="button" id="btn-cancel-edit" class="btn-cancel">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE CONFIRMATION MODAL -->
<div id="delete-confirm-modal" class="edit-modal hidden">
    <div class="edit-modal-box" style="width: 400px; text-align: center;">
        <h3 style="color: #dc3545;">🗑️ Delete Student</h3>
        <p style="margin: 20px 0; font-size: 16px;">Are you sure you want to delete<br><strong
                id="delete-student-name"></strong>?</p>
        <p style="color: #666; font-size: 13px;">This action cannot be undone.</p>
        <input type="hidden" id="delete-student-id">
        <div class="modal-buttons" style="margin-top: 20px; justify-content: center;">
            <button id="btn-confirm-delete" class="btn-delete-confirm">Yes, Delete</button>
            <button id="btn-cancel-delete" class="btn-cancel">Cancel</button>
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

    .status-connected {
        display: inline-block;
        padding: 6px 10px;
        background: #d4edda;
        color: #155724;
        border-radius: 6px;
        font-weight: 600;
    }

    .status-not-connected {
        display: inline-block;
        padding: 6px 10px;
        background: #f8d7da;
        color: #721c24;
        border-radius: 6px;
        font-weight: 600;
    }

    .btn-prompt {
        padding: 6px 10px;
        background: #ffcc00;
        border: none;
        border-radius: 6px;
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
    }

    .edit-modal-box {
        background: white;
        padding: 20px;
        border-radius: 8px;
        width: 520px;
        max-width: 95%;
    }

    .face-status-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        z-index: 10;
    }

    .face-status-badge.face-ok {
        background: rgba(34, 197, 94, 0.9);
        color: white;
    }

    .face-status-badge.no-face {
        background: rgba(239, 68, 68, 0.9);
        color: white;
    }

    /* Actions Column Styles */
    .actions-cell {
        white-space: nowrap;
    }

    .btn-edit,
    .btn-delete {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        margin: 2px;
        transition: all 0.2s ease;
    }

    .btn-edit {
        background: #3b82f6;
        color: white;
    }

    .btn-edit:hover {
        background: #2563eb;
    }

    .btn-delete {
        background: #ef4444;
        color: white;
    }

    .btn-delete:hover {
        background: #dc2626;
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

    /* Edit form styles */
    #edit-student-form label {
        display: block;
        margin-top: 12px;
        margin-bottom: 4px;
        font-weight: 500;
        color: #374151;
        font-size: 14px;
    }

    #edit-student-form input,
    #edit-student-form select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
    }

    #edit-student-form input:focus,
    #edit-student-form select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
</style>

<script>
    setTimeout(function () {
        // ========================================
        // SEARCH & FILTER FUNCTIONALITY
        // ========================================
        var searchInput = document.getElementById('search-student');
        var gradeFilter = document.getElementById('filter-grade');
        var studentTable = document.querySelector('.student-table tbody');

        function filterTable() {
            if (!studentTable) return;

            var searchText = searchInput ? searchInput.value.toLowerCase().trim() : '';
            var gradeValue = gradeFilter ? gradeFilter.value.trim() : '';

            var rows = studentTable.querySelectorAll('tr');

            rows.forEach(function (row) {
                var text = row.textContent.toLowerCase();
                var gradeCell = row.cells[5]; // Grade Level column (index 5)
                var gradeText = gradeCell ? gradeCell.textContent.trim() : '';

                var matchesSearch = searchText === '' || text.indexOf(searchText) !== -1;
                // Use includes for flexible matching (e.g., "10" matches "Grade 10")
                var matchesGrade = gradeValue === '' || gradeText.includes(gradeValue);

                row.style.display = (matchesSearch && matchesGrade) ? '' : 'none';
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterTable);
        }
        if (gradeFilter) {
            gradeFilter.addEventListener('change', filterTable);
        }

        // Guardian contact number validation - only allow numbers
        var guardianContact = document.getElementById('guardian-contact');
        if (guardianContact) {
            guardianContact.addEventListener('input', function (e) {
                // Remove any non-digit characters
                this.value = this.value.replace(/[^0-9]/g, '');

                // Validate format
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
        // FACE DETECTION FOR ADD STUDENT CAMERA
        // ========================================
        let faceModelsLoaded = false;
        let faceDetectionInterval = null;

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

                faceModelsLoaded = true;
                if (loadingEl) loadingEl.textContent = 'Face detection ready';
                if (captureBtn) captureBtn.disabled = false;
            } catch (error) {
                console.error('Error loading face models:', error);
                if (loadingEl) loadingEl.textContent = 'Face detection failed to load';
                if (captureBtn) captureBtn.disabled = false;
            }
        }

        function startFaceDetectionForRegistration() {
            const video = document.getElementById('camera-preview');
            const overlayCanvas = document.getElementById('face-overlay-canvas');
            const faceBadge = document.getElementById('face-badge');
            const captureBtn = document.getElementById('capture-btn');

            if (!video || !overlayCanvas || !faceModelsLoaded) return;

            overlayCanvas.width = 320;
            overlayCanvas.height = 240;
            const ctx = overlayCanvas.getContext('2d');

            // Triangular mesh connections for professional look
            const MESH_TRIANGLES = [
                // Forehead
                [17, 18, 36], [18, 36, 37], [18, 19, 37], [19, 37, 38], [19, 20, 38], [20, 38, 39], [20, 21, 39],
                [21, 39, 27], [22, 27, 42], [22, 42, 43], [22, 23, 43], [23, 43, 44], [23, 24, 44], [24, 44, 45],
                [24, 25, 45], [25, 45, 46], [25, 26, 46],
                // Eyes
                [36, 37, 41], [37, 38, 40], [37, 40, 41], [38, 39, 40],
                [42, 43, 47], [43, 44, 46], [43, 46, 47], [44, 45, 46],
                // Nose
                [27, 28, 39], [27, 28, 42], [28, 29, 39], [28, 29, 42],
                [29, 30, 31], [29, 30, 35], [30, 31, 32], [30, 32, 33], [30, 33, 34], [30, 34, 35],
                // Cheeks
                [0, 1, 36], [1, 36, 41], [1, 2, 41], [2, 41, 31], [2, 3, 31], [3, 31, 48], [3, 4, 48],
                [16, 15, 45], [15, 45, 46], [15, 14, 46], [14, 46, 35], [14, 13, 35], [13, 35, 54], [13, 12, 54],
                // Mouth
                [48, 49, 60], [49, 50, 60], [50, 60, 61], [50, 51, 61], [51, 61, 62], [51, 52, 62],
                [52, 62, 63], [52, 53, 63], [53, 63, 64], [53, 54, 64],
                // Chin
                [4, 5, 48], [5, 6, 48], [6, 48, 59], [6, 7, 59], [7, 8, 59],
                [12, 11, 54], [11, 10, 54], [10, 54, 55], [10, 9, 55], [9, 8, 55]
            ];

            faceDetectionInterval = setInterval(async () => {
                if (!faceModelsLoaded) return;

                try {
                    const detections = await faceapi.detectAllFaces(
                        video,
                        new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 })
                    ).withFaceLandmarks();

                    ctx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);

                    const scaleX = 320 / video.videoWidth;
                    const scaleY = 240 / video.videoHeight;

                    if (detections.length > 0) {
                        faceBadge.textContent = '✓ Face Detected';
                        faceBadge.className = 'face-status-badge face-ok';

                        detections.forEach(detection => {
                            const pts = detection.landmarks.positions;

                            // Draw triangular mesh
                            ctx.strokeStyle = 'rgba(255, 200, 100, 0.6)';
                            ctx.lineWidth = 1;

                            MESH_TRIANGLES.forEach(tri => {
                                if (pts[tri[0]] && pts[tri[1]] && pts[tri[2]]) {
                                    ctx.beginPath();
                                    ctx.moveTo(pts[tri[0]].x * scaleX, pts[tri[0]].y * scaleY);
                                    ctx.lineTo(pts[tri[1]].x * scaleX, pts[tri[1]].y * scaleY);
                                    ctx.lineTo(pts[tri[2]].x * scaleX, pts[tri[2]].y * scaleY);
                                    ctx.closePath();
                                    ctx.stroke();
                                }
                            });

                            // Draw landmark points
                            ctx.fillStyle = 'rgba(255, 200, 100, 0.9)';
                            pts.forEach(p => {
                                ctx.beginPath();
                                ctx.arc(p.x * scaleX, p.y * scaleY, 2, 0, Math.PI * 2);
                                ctx.fill();
                            });
                        });
                    } else {
                        faceBadge.textContent = '👤 No Face';
                        faceBadge.className = 'face-status-badge no-face';
                    }
                } catch (e) {
                    // Ignore detection errors
                }
            }, 150);
        }

        function drawScaledPath(ctx, points, indices, scaleX, scaleY) {
            ctx.beginPath();
            ctx.moveTo(points[indices[0]].x * scaleX, points[indices[0]].y * scaleY);
            for (let i = 1; i < indices.length; i++) {
                ctx.lineTo(points[indices[i]].x * scaleX, points[indices[i]].y * scaleY);
            }
            ctx.stroke();
        }

        function stopFaceDetection() {
            if (faceDetectionInterval) {
                clearInterval(faceDetectionInterval);
                faceDetectionInterval = null;
            }
        }

        // Global variable to store section data
        let sectionData = [];

        // Load dropdowns for Add Student form
        function loadAddStudentDropdowns() {
            fetch('student_load_dropdowns.php')
                .then(response => response.json())
                .then(data => {
                    sectionData = data;
                    const gradeSelect = document.getElementById('add-grade-level');
                    const sectionSelect = document.getElementById('add-section');

                    // Get unique grade levels
                    const uniqueGrades = [...new Set(data.map(item => item.grade_level))];

                    // Populate grade levels
                    gradeSelect.innerHTML = '<option value="">Select Grade Level</option>';
                    uniqueGrades.forEach(grade => {
                        const option = document.createElement('option');
                        option.value = grade;
                        option.textContent = grade;
                        gradeSelect.appendChild(option);
                    });

                    // Reset section
                    sectionSelect.innerHTML = '<option value="">Select Grade First</option>';

                    // Grade change event
                    gradeSelect.onchange = function () {
                        const selectedGrade = this.value;
                        sectionSelect.innerHTML = '<option value="">Select Section</option>';

                        if (selectedGrade) {
                            const sections = sectionData.filter(item => item.grade_level === selectedGrade);
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

        // Load models when page loads
        document.addEventListener('DOMContentLoaded', function () {
            loadFaceModels();

            // Start face detection when modal opens
            var openBtn = document.getElementById('btn-open-add-student');
            if (openBtn) {
                openBtn.addEventListener('click', function () {
                    // Load dropdowns when modal opens
                    loadAddStudentDropdowns();
                    setTimeout(startFaceDetectionForRegistration, 1000);
                });
            }

            // Stop face detection when modal closes
            var cancelBtn = document.getElementById('btn-cancel-add-student');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', stopFaceDetection);
            }
        });

        // ========================================
        // EDIT STUDENT FUNCTIONALITY
        // ========================================

        // Load grade levels for edit modal
        function loadEditGradeLevels(selectedGrade, selectedSection) {
            fetch('student_load_dropdowns.php')
                .then(response => response.json())
                .then(data => {
                    const gradeSelect = document.getElementById('edit-grade-level');
                    const sectionSelect = document.getElementById('edit-section');

                    // Clear and populate grade levels
                    gradeSelect.innerHTML = '<option value="">Select Grade Level</option>';
                    const uniqueGrades = [...new Set(data.map(item => item.grade_level))];
                    uniqueGrades.forEach(grade => {
                        const option = document.createElement('option');
                        option.value = grade;
                        option.textContent = grade;
                        if (grade === selectedGrade) option.selected = true;
                        gradeSelect.appendChild(option);
                    });

                    // Populate sections for selected grade
                    updateEditSections(data, selectedGrade, selectedSection);

                    // Add change listener for grade
                    gradeSelect.onchange = function () {
                        updateEditSections(data, this.value, '');
                    };
                })
                .catch(err => console.error('Error loading dropdowns:', err));
        }

        function updateEditSections(data, grade, selectedSection) {
            const sectionSelect = document.getElementById('edit-section');
            sectionSelect.innerHTML = '<option value="">Select Section</option>';

            const sections = data.filter(item => item.grade_level === grade);
            sections.forEach(item => {
                const option = document.createElement('option');
                option.value = item.section;
                option.textContent = item.section;
                if (item.section === selectedSection) option.selected = true;
                sectionSelect.appendChild(option);
            });
        }

        // Edit button click handler
        document.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('btn-edit')) {
                const btn = e.target;

                // Populate edit form
                document.getElementById('edit-id').value = btn.dataset.id;
                document.getElementById('edit-student-id-display').textContent = btn.dataset.student;
                document.getElementById('edit-firstname').value = btn.dataset.firstname || '';
                document.getElementById('edit-middlename').value = btn.dataset.middlename || '';
                document.getElementById('edit-lastname').value = btn.dataset.lastname || '';
                document.getElementById('edit-address').value = btn.dataset.address || '';
                document.getElementById('edit-guardian-name').value = btn.dataset.guardian || '';
                document.getElementById('edit-guardian-contact').value = btn.dataset.contact || '';
                document.getElementById('edit-guardian-email').value = btn.dataset.email || '';

                // Load dropdowns with current values selected
                loadEditGradeLevels(btn.dataset.grade, btn.dataset.section);

                // Show modal
                document.getElementById('edit-student-modal').classList.remove('hidden');
            }
        });

        // Cancel edit
        document.getElementById('btn-cancel-edit')?.addEventListener('click', function () {
            document.getElementById('edit-student-modal').classList.add('hidden');
        });

        // Submit edit form
        document.getElementById('edit-student-form')?.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('student_update.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        document.getElementById('edit-student-modal').classList.add('hidden');
                        // Reload the page to show updated data
                        location.reload();
                    } else {
                        alert('❌ ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('❌ Failed to update student');
                });
        });

        // ========================================
        // DELETE STUDENT FUNCTIONALITY
        // ========================================

        // Delete button click handler
        document.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('btn-delete')) {
                const btn = e.target;

                document.getElementById('delete-student-id').value = btn.dataset.id;
                document.getElementById('delete-student-name').textContent = btn.dataset.name;
                document.getElementById('delete-confirm-modal').classList.remove('hidden');
            }
        });

        // Cancel delete
        document.getElementById('btn-cancel-delete')?.addEventListener('click', function () {
            document.getElementById('delete-confirm-modal').classList.add('hidden');
        });

        // Confirm delete (archive)
        document.getElementById('btn-confirm-delete')?.addEventListener('click', function () {
            const id = document.getElementById('delete-student-id').value;

            const formData = new FormData();
            formData.append('id', id);

            fetch('student_delete.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        document.getElementById('delete-confirm-modal').classList.add('hidden');
                        // Reload using parent's loadPage function or full reload
                        if (typeof loadPage === 'function') {
                            loadPage('student_table.php');
                        } else if (parent && typeof parent.loadPage === 'function') {
                            parent.loadPage('student_table.php');
                        } else {
                            window.location.href = 'student_table.php';
                        }
                    } else {
                        alert('❌ ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
           alert('❌ Failed to archive student');
                });
        });

    }, 0);
</script>