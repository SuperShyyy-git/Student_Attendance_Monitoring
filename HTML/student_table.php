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

                <!-- Hidden input for Base64 Image and Face Encoding -->
                <input type="hidden" name="photo_data" id="photo-data">
                <input type="hidden" name="face_encoding" id="face-encoding">

                <div class="modal-buttons">
                    <button type="submit" class="btn-save-edit">Save</button>
                    <button type="button" id="btn-cancel-add-student">Cancel</button>
                </div>

            </form>

            <!-- RIGHT SIDE = CAMERA -->
            <div class="camera-box">

                <h4>📷 Student Photo</h4>

                <div class="camera-preview-wrapper" style="position: relative; display: inline-block; border-radius: 12px; overflow: hidden; background: #1f2937;">
                    <video id="camera-preview" autoplay
                        style="width: 480px; height: 360px; border-radius: 8px; transform: scaleX(-1); display: block;"></video>
                    <canvas id="face-overlay-canvas" width="480" height="360"
                        style="position: absolute; top: 0; left: 0; pointer-events: none; transform: scaleX(-1);"></canvas>
                    <div id="face-badge" class="face-status-badge no-face">👤 No Face</div>
                </div>

                <button type="button" id="capture-btn" class="btn-save-edit" style="margin-top:10px; width: 480px;">
                    📷 Capture Photo
                </button>

                <canvas id="snapshot-canvas" width="480" height="360" style="display:none;"></canvas>

                <img id="photo-preview" style="display:none; margin-top:10px; max-width: 480px; border-radius: 8px;">

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

<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>

<script>
    (function () {
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
                var matchesGrade = gradeValue === '' || gradeText.includes(gradeValue);
                row.style.display = (matchesSearch && matchesGrade) ? '' : 'none';
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterTable);
        if (gradeFilter) gradeFilter.addEventListener('change', filterTable);

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
            let minDistance = 0.6; // Threshold for matching

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
                } catch (e) {}
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
            cancelBtn.addEventListener('click', function() {
                stopFaceDetection();
                stopCamera();
                document.getElementById('add-student-modal').classList.add('hidden');
            });
        }

        // Photo Capture (Manual)
        document.getElementById('capture-btn')?.addEventListener('click', async function() {
            const video = document.getElementById('camera-preview');
            const canvas = document.getElementById('snapshot-canvas');
            const preview = document.getElementById('photo-preview');
            const photoInput = document.getElementById('photo-data');
            if (video && canvas && preview && photoInput) {
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const dataUrl = canvas.toDataURL('image/jpeg');
                preview.src = dataUrl;
                preview.style.display = 'block';
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
        document.getElementById('add-student-form')?.addEventListener('submit', function(e) {
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
