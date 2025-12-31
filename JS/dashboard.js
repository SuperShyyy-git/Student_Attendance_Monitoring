const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggle-btn");
const mainContent = document.getElementById("main-content");

// Toggle sidebar
toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed");
});

// ==========================
// GLOBAL LOGOUT HANDLER
// ==========================
function initLogoutButton() {
    const logoutBtns = document.querySelectorAll('.btn-logout');
    if (!logoutBtns || logoutBtns.length === 0) return;

    logoutBtns.forEach((logoutBtn) => {
        if (logoutBtn.dataset.logoutInitialized) return; // already attached
        logoutBtn.dataset.logoutInitialized = "1";

        logoutBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();

            // Create modal
            const modal = document.createElement("div");
            modal.className = "logout-modal";
            modal.innerHTML = `
                <div class="logout-modal-box">
                    <h3>Are you sure you want to logout?</h3>
                    <div class="modal-buttons">
                        <button class="btn-yes">Yes</button>
                        <button class="btn-no">No</button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // YES → logout
            modal.querySelector(".btn-yes").onclick = () => {
                window.location.href = "/attendance/HTML/logout.php";
            };

            // NO → close modal
            modal.querySelector(".btn-no").onclick = () => {
                modal.remove();
            };
        });
    });
}

// Initialize on full-page loads
document.addEventListener("DOMContentLoaded", initLogoutButton);

// LOAD PAGE
function loadPage(page) {
    mainContent.innerHTML = `<div class="loading">Loading…</div>`;

    fetch('./' + page)
        .then(r => r.text())
        .then(html => {
            mainContent.innerHTML = html;

            // Execute any scripts in the loaded content
            var scripts = mainContent.querySelectorAll('script');
            scripts.forEach(function (script) {
                var newScript = document.createElement('script');
                if (script.src) {
                    newScript.src = script.src;
                } else {
                    newScript.textContent = script.textContent;
                }
                document.head.appendChild(newScript);
                document.head.removeChild(newScript);
            });

            // Recreate icons in the injected fragment (if lucide is available)
            try { if (window.lucide && typeof lucide.createIcons === 'function') lucide.createIcons(); } catch (err) { /* ignore */ }

            // Initialize logout handler for injected content (fragments)
            try { initLogoutButton(); } catch (err) { console.error('initLogoutButton error', err); }

            /* ---------------------------
               SECTION & GRADE LEVEL BINDINGS
            ---------------------------- */

            // ADD SECTION
            const addForm = document.querySelector(".section-form");
            if (addForm) addForm.addEventListener("submit", submitAddForm);

            // EDIT SECTION FORM
            const editForm = document.getElementById("edit-section-form");
            if (editForm) editForm.addEventListener("submit", submitEditForm);

            // EDIT BUTTONS
            document.querySelectorAll(".btn-edit").forEach(btn => {
                btn.addEventListener("click", () => {
                    editSection(
                        btn.dataset.id,
                        btn.dataset.section,
                        btn.dataset.grade,
                        btn.dataset.adviser
                    );
                });
            });

            // DELETE BUTTONS
            document.querySelectorAll(".btn-delete").forEach(btn => {
                btn.addEventListener("click", () => {
                    deleteSection(btn.dataset.id);
                });
            });

            // CANCEL EDIT BUTTON
            const cancelBtn = document.getElementById("btn-cancel-edit");
            if (cancelBtn) cancelBtn.addEventListener("click", closeEditModal);

            /* ---------------------------
               STUDENT FORM BINDINGS
            ---------------------------- */

            // OPEN ADD STUDENT BUTTON
            const openAddStudentBtn = document.getElementById("btn-open-add-student");
            if (openAddStudentBtn)
                openAddStudentBtn.addEventListener("click", openAddStudentModal);

            // CANCEL ADD STUDENT
            const cancelAddStudent = document.getElementById("btn-cancel-add-student");
            if (cancelAddStudent)
                cancelAddStudent.addEventListener("click", closeAddStudentModal);

            // SUBMIT ADD STUDENT
            const addStudentForm = document.getElementById("add-student-form");
            if (addStudentForm)
                addStudentForm.addEventListener("submit", submitAddStudentForm);

            // LOAD DROPDOWNS ONLY IF FORM EXISTS
            if (document.getElementById("add-grade-level")) {
                loadStudentDropdowns();
            }

            /* ---------------------------
               CAMERA MODULE WITH FACE DETECTION
            ---------------------------- */

            const video = document.getElementById("camera-preview");
            const captureBtn = document.getElementById("capture-btn");
            const canvas = document.getElementById("snapshot-canvas");
            const photoPreview = document.getElementById("photo-preview");
            const photoDataInput = document.getElementById("photo-data");
            const faceOverlayCanvas = document.getElementById("face-overlay-canvas");
            const faceBadge = document.getElementById("face-badge");
            const faceLoading = document.getElementById("face-loading");

            let faceDetectionInterval = null;

            // Start camera when modal opens
            if (video) {
                navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } })
                    .then(stream => {
                        video.srcObject = stream;

                        // Start face detection after video is playing
                        video.onloadedmetadata = () => {
                            startFaceDetection();
                        };
                    })
                    .catch(err => {
                        console.log("Camera error:", err);
                        if (faceLoading) faceLoading.textContent = 'Camera error';
                    });
            }

            // Face detection function
            async function startFaceDetection() {
                if (!faceOverlayCanvas || !video) return;

                // Check if face-api is loaded
                if (typeof faceapi === 'undefined') {
                    if (faceLoading) faceLoading.textContent = 'Loading face detection...';
                    // Load face-api.js dynamically
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js';
                    script.onload = async () => {
                        await loadFaceModels();
                    };
                    document.head.appendChild(script);
                    return;
                }

                await loadFaceModels();
            }

            async function loadFaceModels() {
                const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';

                // Add timeout
                const timeout = new Promise((_, reject) =>
                    setTimeout(() => reject(new Error('Timeout')), 10000)
                );

                try {
                    await Promise.race([
                        Promise.all([
                            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL)
                        ]),
                        timeout
                    ]);

                    if (faceLoading) faceLoading.textContent = 'Face detection ready ✓';

                    // Start detection loop
                    runFaceDetectionLoop();
                } catch (error) {
                    console.error('Face model load error:', error);
                    if (faceLoading) faceLoading.textContent = '';
                    if (faceBadge) faceBadge.style.display = 'none';
                }
            }

            function runFaceDetectionLoop() {
                if (!faceOverlayCanvas || !video) return;

                faceOverlayCanvas.width = 320;
                faceOverlayCanvas.height = 240;
                const ctx = faceOverlayCanvas.getContext('2d');

                faceDetectionInterval = setInterval(async () => {
                    if (typeof faceapi === 'undefined') return;

                    try {
                        const detections = await faceapi.detectAllFaces(
                            video,
                            new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.3 })
                        ).withFaceLandmarks();

                        ctx.clearRect(0, 0, faceOverlayCanvas.width, faceOverlayCanvas.height);

                        const scaleX = 320 / video.videoWidth;
                        const scaleY = 240 / video.videoHeight;

                        if (detections.length > 0 && faceBadge) {
                            faceBadge.textContent = '✓ Face OK';
                            faceBadge.className = 'face-status-badge face-ok';

                            detections.forEach(detection => {
                                const points = detection.landmarks.positions;

                                // Draw mesh
                                ctx.strokeStyle = '#FFD700';
                                ctx.fillStyle = '#FFD700';
                                ctx.lineWidth = 1;

                                points.forEach(p => {
                                    ctx.beginPath();
                                    ctx.arc(p.x * scaleX, p.y * scaleY, 1.5, 0, 2 * Math.PI);
                                    ctx.fill();
                                });

                                // Jawline
                                drawMeshPath(ctx, points, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16], scaleX, scaleY);
                                // Eyebrows
                                drawMeshPath(ctx, points, [17, 18, 19, 20, 21], scaleX, scaleY);
                                drawMeshPath(ctx, points, [22, 23, 24, 25, 26], scaleX, scaleY);
                                // Nose
                                drawMeshPath(ctx, points, [27, 28, 29, 30], scaleX, scaleY);
                                // Eyes
                                drawMeshPath(ctx, points, [36, 37, 38, 39, 40, 41, 36], scaleX, scaleY);
                                drawMeshPath(ctx, points, [42, 43, 44, 45, 46, 47, 42], scaleX, scaleY);
                                // Mouth
                                drawMeshPath(ctx, points, [48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 48], scaleX, scaleY);

                                // Face box
                                const box = detection.detection.box;
                                ctx.lineWidth = 2;
                                ctx.strokeRect(box.x * scaleX, box.y * scaleY, box.width * scaleX, box.height * scaleY);
                            });
                        } else if (faceBadge) {
                            faceBadge.textContent = '✗ No Face';
                            faceBadge.className = 'face-status-badge no-face';
                        }
                    } catch (e) { /* ignore */ }
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

            // Capture photo - only when face is detected (or face detection is disabled)
            if (captureBtn) {
                captureBtn.onclick = () => {
                    // Check if face is detected (allow if badge is hidden/doesn't exist)
                    if (faceBadge && faceBadge.style.display !== 'none' && faceBadge.classList.contains('no-face')) {
                        alert('Please make sure your face is visible in the camera before capturing.');
                        return;
                    }

                    const context = canvas.getContext("2d");
                    context.drawImage(video, 0, 0, canvas.width, canvas.height);

                    const dataURL = canvas.toDataURL("image/png");

                    photoDataInput.value = dataURL;
                    photoPreview.src = dataURL;
                    photoPreview.style.display = "block";
                };
            }

            /* ---------------------------
               ADD SECTION MODAL BUTTON
            ---------------------------- */
            const openAddSectionBtn = document.getElementById("btn-open-add-section");
            const addSectionModal = document.getElementById("add-section-modal");

            if (openAddSectionBtn && addSectionModal) {
                openAddSectionBtn.onclick = () => {
                    addSectionModal.classList.remove("hidden");
                };
            }

            // CANCEL ADD SECTION
            const cancelAddSectionBtn = document.getElementById("btn-cancel-add-section");
            if (cancelAddSectionBtn)
                cancelAddSectionBtn.onclick = () => {
                    addSectionModal.classList.add("hidden");
                };

        })
        .catch(() => {
            mainContent.innerHTML = `<p>Error loading page.</p>`;
        });
}

// Expose loadPage globally so inline scripts in AJAX-loaded pages can use it
window.loadPage = loadPage;


/* -----------------------------------------
   STUDENT ADD FUNCTIONS
------------------------------------------ */

// OPEN MODAL
function openAddStudentModal() {
    document.getElementById("add-student-modal").classList.remove("hidden");
}

// CLOSE MODAL
function closeAddStudentModal() {
    document.getElementById("add-student-modal").classList.add("hidden");
}

// Global variable to store section data with advisers
let sectionDataGlobal = [];

// LOAD DROPDOWNS
function loadStudentDropdowns() {
    fetch('./student_load_dropdowns.php')
        .then(r => r.json())
        .then(data => {
            // Data is a flat array: [{grade_level, section, adviser}, ...]
            sectionDataGlobal = data;

            const gradeSel = document.getElementById("add-grade-level");
            const secSel = document.getElementById("add-section");
            const adviserDisplay = document.getElementById("adviser-display");

            if (!gradeSel || !secSel) return;

            // Get unique grade levels
            const uniqueGrades = [...new Set(data.map(item => item.grade_level))];

            // Populate grade levels
            gradeSel.innerHTML = '<option value="">Select Grade Level</option>';
            uniqueGrades.forEach(g => {
                gradeSel.innerHTML += `<option value="${g}">${g}</option>`;
            });

            // Reset section
            secSel.innerHTML = '<option value="">Select Grade First</option>';
            if (adviserDisplay) {
                adviserDisplay.textContent = '👤 Select grade level and section first';
                adviserDisplay.style.color = '#666';
                adviserDisplay.style.background = '#f8f9fa';
            }

            // Grade change event
            gradeSel.onchange = function () {
                const selectedGrade = this.value;
                secSel.innerHTML = '<option value="">Select Section</option>';
                if (adviserDisplay) {
                    adviserDisplay.textContent = '👤 Select section to see adviser';
                    adviserDisplay.style.color = '#666';
                    adviserDisplay.style.background = '#f8f9fa';
                }

                if (selectedGrade) {
                    const sections = sectionDataGlobal.filter(item => item.grade_level === selectedGrade);
                    sections.forEach(item => {
                        secSel.innerHTML += `<option value="${item.section}">${item.section}</option>`;
                    });
                }
            };

            // Section change event - show adviser
            secSel.onchange = function () {
                const selectedGrade = gradeSel.value;
                const selectedSection = this.value;

                if (adviserDisplay && selectedGrade && selectedSection) {
                    const sectionInfo = sectionDataGlobal.find(item =>
                        item.grade_level === selectedGrade && item.section === selectedSection
                    );
                    if (sectionInfo && sectionInfo.adviser && sectionInfo.adviser !== 'Not assigned') {
                        adviserDisplay.innerHTML = '👨‍🏫 <strong>' + sectionInfo.adviser + '</strong>';
                        adviserDisplay.style.color = '#155724';
                        adviserDisplay.style.background = '#d4edda';
                    } else {
                        adviserDisplay.textContent = '⚠️ No adviser assigned';
                        adviserDisplay.style.color = '#856404';
                        adviserDisplay.style.background = '#fff3cd';
                    }
                } else if (adviserDisplay) {
                    adviserDisplay.textContent = '👤 Select section to see adviser';
                    adviserDisplay.style.color = '#666';
                    adviserDisplay.style.background = '#f8f9fa';
                }
            };
        })
        .catch(err => console.error('Error loading dropdowns:', err));
}

// SUBMIT ADD STUDENT
function submitAddStudentForm(e) {
    e.preventDefault();
    const fd = new FormData(e.target);

    // Disable submit button to prevent double-submit
    const submitBtn = e.target.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';
    }

    fetch('./student_add_save.php', { method: "POST", body: fd })
        .then(async (r) => {
            // Attempt to parse JSON robustly
            let data;
            const text = await r.text();
            try {
                data = JSON.parse(text);
            } catch (err) {
                // Malformed JSON — show raw text for debugging
                throw new Error('Invalid server response: ' + text);
            }
            return data;
        })
        .then(res => {
            if (res && res.success) {
                showPopup("Student added successfully!");
                closeAddStudentModal();
                // Delay reload so popup is visible briefly
                setTimeout(() => loadPage("student_table.php"), 700);
            } else {
                const msg = (res && res.message) ? res.message : 'Unknown server error';
                showPopup("Error: " + msg);
            }
        })
        .catch(err => {
            showPopup('Save failed: ' + err.message);
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save';
            }
        });
}

/* -----------------------------------------
   SECTION FUNCTIONS
------------------------------------------ */

function submitAddForm(e) {
    e.preventDefault();
    const fd = new FormData(e.target);

    fetch('./sec_yr_level_save.php', { method: "POST", body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showPopup("Added successfully.");
                loadPage("sec_yr_level.php");
            } else {
                showPopup("Error adding: " + res.message);
            }
        });
}

// OPEN EDIT
window.editSection = function (id, section, grade, adviser) {
    const modal = document.getElementById("edit-modal");
    modal.classList.remove("hidden");

    document.getElementById("edit-id").value = id;
    document.getElementById("edit-section").value = section;
    document.getElementById("edit-grade-level").value = grade;

    // Set adviser dropdown if exists
    const adviserSelect = document.getElementById("edit-adviser");
    if (adviserSelect) {
        adviserSelect.value = adviser || '';
    }
};

// CLOSE EDIT
window.closeEditModal = function () {
    document.getElementById("edit-modal").classList.add("hidden");
};

// SUBMIT EDIT
function submitEditForm(e) {
    e.preventDefault();
    const fd = new FormData(e.target);

    fetch('./sec_yr_level_update.php', { method: "POST", body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showPopup("Updated successfully.");
                closeEditModal();
                loadPage("sec_yr_level.php");
            } else {
                showPopup("Update failed: " + res.message);
            }
        });
}

// DELETE
window.deleteSection = function (id) {
    if (!confirm("Delete this record?")) return;

    const fd = new FormData();
    fd.append("id", id);

    fetch('./sec_yr_level_delete.php', { method: "POST", body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showPopup("Deleted successfully.");
                loadPage("sec_yr_level.php");
            } else {
                showPopup("Delete failed: " + res.message);
            }
        });
};

/* -----------------------------------------
   POPUP
------------------------------------------ */

window.closePopup = function () {
    const popup = document.getElementById("popup-message-box");
    if (popup) popup.style.display = "none";

};

/* -----------------------------------------
   POPUP (MATCHES CSS DESIGN)
------------------------------------------ */
function showPopup(message) {
    let popup = document.getElementById("popup-message-box");

    // Create popup only if not existing
    if (!popup) {
        popup = document.createElement("div");
        popup.id = "popup-message-box";
        popup.className = "popup-message";  // <-- matches CSS

        const text = document.createElement("p");
        text.id = "popup-message-text";

        const btn = document.createElement("button");
        btn.className = "popup-ok-btn";     // <-- matches CSS
        btn.textContent = "OK";

        // Close popup
        btn.onclick = () => {
            popup.style.display = "none";
        };

        popup.appendChild(text);
        popup.appendChild(btn);
        document.body.appendChild(popup);
    }

    // Set message & show popup
    document.getElementById("popup-message-text").textContent = message;
    popup.style.display = "block";
}




