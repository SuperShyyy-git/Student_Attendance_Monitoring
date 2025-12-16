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
                        btn.dataset.grade
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
               CAMERA MODULE
            ---------------------------- */

            const video = document.getElementById("camera-preview");
            const captureBtn = document.getElementById("capture-btn");
            const canvas = document.getElementById("snapshot-canvas");
            const photoPreview = document.getElementById("photo-preview");
            const photoDataInput = document.getElementById("photo-data");


            // Start camera when modal opens
            if (video) {
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then(stream => video.srcObject = stream)
                    .catch(err => console.log("Camera error:", err));
            }

            // Capture photo
            if (captureBtn) {
                captureBtn.onclick = () => {
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

// LOAD DROPDOWNS
function loadStudentDropdowns() {
    fetch('./student_load_dropdowns.php')
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;

            const gradeSel = document.getElementById("add-grade-level");
            const secSel = document.getElementById("add-section");

            if (!gradeSel || !secSel) return;

            gradeSel.innerHTML = "";
            secSel.innerHTML = "";

            res.grade_levels.forEach(g => {
                gradeSel.innerHTML += `<option value="${g}">${g}</option>`;
            });

            res.sections.forEach(s => {
                secSel.innerHTML += `<option value="${s}">${s}</option>`;
            });
        });
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
window.editSection = function (id, section, grade) {
    const modal = document.getElementById("edit-modal");
    modal.classList.remove("hidden");

    document.getElementById("edit-id").value = id;
    document.getElementById("edit-section").value = section;
    document.getElementById("edit-grade-level").value = grade;
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




