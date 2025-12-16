<?php
session_start();

// Only allow the special machine account (role = "machine")
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'machine') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Attendance Capture</title>

    <!-- CSS (adjust path if your folder name differs; this assumes CSS/ is sibling of HTML/) -->
    <link rel="stylesheet" href="../CSS/attendance_capture.css">
</head>
<body>

<div class="capture-wrapper">

    <!-- LEFT: LOGO -->
    <div class="left-section">
        <img src="../resources/image/school-logo.jpg" alt="School Logo">
    </div>

    <!-- RIGHT: CAMERA + RFID -->
    <div class="right-section">

        <!-- Camera Preview -->
        <div class="camera-box">
            <video id="cameraPreview" autoplay playsinline></video>
        </div>

        <!-- RFID Input -->
        <div class="rfid-box">
            <input type="text" id="rfidInput" placeholder="SCAN RFID HERE..." autocomplete="off" />
            <div id="statusMessage" class="status-message" aria-live="polite"></div>
        </div>

    </div>

</div>

<!-- JS (adjust path if your js folder name differs) -->
<script src="../js/attendance_capture.js"></script>

</body>
</html>
