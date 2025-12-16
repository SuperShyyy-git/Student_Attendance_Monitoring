<?php
include "../config/db_connect.php";
?>

<div class="header-bar">
    <h2 class='table-title'>Student List</h2>
    <button id="btn-logout" class="btn-logout">Logout</button>
</div>

<!-- ADD STUDENT BUTTON -->
<button id="btn-open-add-student" class="btn-add-student">➕ Add Student</button>

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
            <th>Contact Number</th>
            <th>API Status</th>
        </tr>
    </thead>

    <tbody>
        <?php
        $query = "SELECT * FROM students ORDER BY lastname ASC, firstname ASC";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $apiStatus = !empty($row['chat_id']) ? '<span class=\'status-connected\'>Connected</span>' : '<span class=\'status-not-connected\'>Not Connected</span>';
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
                    <td>{$apiStatus}</td>
                </tr>
                ";
            }
        } else {
            echo "
            <tr>
                <td colspan='10' style='text-align:center;'>No students found.</td>
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
                <input type="text" name="guardian_contact" required>

                <!-- Hidden input for Base64 Image -->
                <input type="hidden" name="photo_data" id="photo-data">

                <div class="modal-buttons">
                    <button type="submit" class="btn-save-edit">Save</button>
                    <button type="button" id="btn-cancel-add-student">Cancel</button>
                </div>

            </form>

            <!-- RIGHT SIDE = CAMERA -->
            <div class="camera-box">

                <h4>Student Photo</h4>

                <video id="camera-preview" autoplay></video>

                <button type="button" id="capture-btn" class="btn-save-edit" style="margin-top:10px;">
                    📷 Capture Photo
                </button>

                <canvas id="snapshot-canvas" width="480" height="360" style="display:none;"></canvas>

                <img id="photo-preview" style="display:none; margin-top:10px;">

            </div>

        </div>
    </div>
</div>
</div>

<!-- PROMPT GUARDIAN MODAL (INSTRUCTION + SMS TEMPLATE) -->
<div id="prompt-modal" class="edit-modal hidden">
    <div class="edit-modal-box">
        <h3>Prompt Guardian</h3>
        <p id="prompt-instruction">Use the message below to ask the guardian to open Telegram and send their registered
            mobile number to the school bot so the system can link their account.</p>

        <p><strong>Steps for Guardian</strong></p>
        <ol>
            <li>Open Telegram app.</li>
            <li>Search for <strong>@AGSNHS_bot</strong>.</li>
            <li>Send the registered mobile number (e.g., <code>09171234567</code>) as a plain message.</li>
        </ol>

        <p><strong>Message to send to the guardian (copy and send as SMS/WhatsApp):</strong></p>
        <textarea id="prompt-sms" style="width:100%; height:90px; padding:8px;"></textarea>

        <div style="margin-top:10px; display:flex; gap:8px;">
            <button id="copy-prompt-sms" class="btn-save-edit">Copy Message</button>
            <button id="close-prompt-modal" class="btn-save-edit">Close</button>
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
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // delegate click for prompt buttons
        document.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('btn-prompt')) {
                var contact = e.target.getAttribute('data-contact') || '';
                var guardian = e.target.getAttribute('data-guardian') || '';

                // Compose SMS message for admin to send to guardian
                var msg = "Hello " + guardian + ", please open Telegram and send your registered mobile number to @AGSNHS_bot so the school system can link your account. Your registered number: " + contact + ".";

                var smsEl = document.getElementById('prompt-sms');
                var modal = document.getElementById('prompt-modal');
                if (smsEl && modal) {
                    smsEl.value = msg;
                    modal.classList.remove('hidden');
                }
            }
        });

        var closeBtn = document.getElementById('close-prompt-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                var modal = document.getElementById('prompt-modal');
                if (modal) modal.classList.add('hidden');
            });
        }

        var copyBtn = document.getElementById('copy-prompt-sms');
        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                var el = document.getElementById('prompt-sms');
                if (!el) return;
                el.select();
                try {
                    document.execCommand('copy');
                    alert('Message copied to clipboard. Send this to the guardian via SMS or messaging app.');
                } catch (err) {
                    alert('Copy failed. Please select and copy manually.');
                }
            });
        }

        // Add quick action buttons to open WhatsApp Web or Telegram Web directly (optional)
        // Create buttons dynamically so we don't show them if elements missing
        var smsArea = document.getElementById('prompt-sms');
        if (smsArea) {
            var actionsDiv = document.createElement('div');
            actionsDiv.style.marginTop = '8px';

            var waBtn = document.createElement('button');
            waBtn.textContent = 'Open WhatsApp Web';
            waBtn.className = 'btn-save-edit';
            waBtn.style.marginRight = '8px';
            waBtn.addEventListener('click', function () {
                var text = encodeURIComponent(smsArea.value);
                // admin will need to enter the guardian's phone when WhatsApp opens
                window.open('https://web.whatsapp.com/', '_blank');
            });

            var tgBtn = document.createElement('button');
            tgBtn.textContent = 'Open Telegram Web';
            tgBtn.className = 'btn-save-edit';
            tgBtn.addEventListener('click', function () {
                window.open('https://web.telegram.org/', '_blank');
            });

            actionsDiv.appendChild(waBtn);
            actionsDiv.appendChild(tgBtn);
            smsArea.parentNode.insertBefore(actionsDiv, smsArea.nextSibling);
        }

        // ========================================
        // SEARCH & FILTER FUNCTIONALITY
        // ========================================
        var searchInput = document.getElementById('search-student');
        var gradeFilter = document.getElementById('filter-grade');
        var studentTable = document.querySelector('.student-table tbody');

        function filterTable() {
            if (!studentTable) return;
            
            var searchText = searchInput ? searchInput.value.toLowerCase() : '';
            var gradeValue = gradeFilter ? gradeFilter.value : '';
            
            var rows = studentTable.querySelectorAll('tr');
            
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                var gradeCell = row.cells[5]; // Grade Level column (index 5)
                var gradeText = gradeCell ? gradeCell.textContent : '';
                
                var matchesSearch = text.indexOf(searchText) !== -1;
                var matchesGrade = gradeValue === '' || gradeText === gradeValue;
                
                row.style.display = (matchesSearch && matchesGrade) ? '' : 'none';
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterTable);
        }
        if (gradeFilter) {
            gradeFilter.addEventListener('change', filterTable);
        }
    });
</script>