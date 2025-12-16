<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <!-- Lucide Icons (local copy to avoid Tracking Prevention warnings) -->
    <script src="../JS/lucide.min.js"></script>

    <!-- CSS -->
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../CSS/student_table.css">


</head>

<body>
    <button id="btn-logout" class="btn-logout">Logout</button>
    <style>
        .btn-logout {
            position: fixed;
            top: 18px;
            right: 28px;
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 18px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            z-index: 2001;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
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
    </style>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">

        <!-- REQUIRED TO FIX JS CRASH -->
        <button id="toggle-btn" class="toggle-btn">
            <i data-lucide="menu"></i>
        </button>

        <div class="logo-section">
            <div class="logo-circle">
                <img src="../resources/image/school-logo.jpg" alt="Logo">
            </div>
        </div>

        <ul class="menu">

            <li class="menu-title">Student Information Panel</li>

            <li onclick="loadPage('student_table.php')">
                <i data-lucide="user"></i>
                <span>Student Name / ID</span>
            </li>

            <li onclick="loadPage('sec_yr_level.php')">
                <i data-lucide="layers"></i>
                <span>Section / Grade Level</span>
            </li>

            <li class="menu-title">Attendance Status</li>

            <li onclick="loadPage('student-attendance.php')">
                <i data-lucide="check-circle"></i>
                <span>Student Attendance</span>
            </li>


            <li>
                <i data-lucide="clock"></i>
                <span>Check-in / Check-out Time</span>
            </li>

            <li onclick="loadPage('attendance_table.php')">
                <i data-lucide="history"></i>
                <span>Attendance History</span>
            </li>

            <li class="menu-title">Notifications & Alerts</li>

            <li>
                <i data-lucide="message-circle"></i>
                <span>SMS Notification Log</span>
            </li>

            <li>
                <i data-lucide="bell"></i>
                <span>Pending Notifications</span>
            </li>

            <li>
                <i data-lucide="alert-triangle"></i>
                <span>At-risk Students</span>
            </li>

            <li class="menu-title">Administrative Tools</li>

            <li>
                <i data-lucide="switch-camera"></i>
                <span>Manual Override</span>
            </li>

            <li>
                <i data-lucide="search"></i>
                <span>Search / Filter</span>
            </li>

            <li>
                <i data-lucide="file-down"></i>
                <span>Export PDF / Excel</span>
            </li>

        </ul>

    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="main-content">
        <h1>Dashboard</h1>
        <p>Welcome to the Attendance Monitoring System</p>
    </div>

    <script src="../js/dashboard.js"></script>

    <!-- INIT ICONS -->
    <script>
        lucide.createIcons();
    </script>

</body>

</html>