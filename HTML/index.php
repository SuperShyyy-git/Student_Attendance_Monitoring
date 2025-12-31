<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Attendance Monitoring System</title>
  <meta name="description" content="Student Attendance Monitoring System - Track student attendance with face recognition technology" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/landing.css">
</head>

<body>

  <div class="landing-container">

    <!-- Header -->
    <div class="header-section">
      <div class="logo-container">
        <img src="../resources/image/school-logo.jpg" alt="School Logo">
      </div>
      <h1>Student Attendance</h1>
      <p>Monitoring System</p>
    </div>

    <!-- Two Big Cards -->
    <div class="cards-container">

      <!-- Admin Login Card -->
      <a href="login.php" class="option-card admin">
        <div class="card-icon">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
          </svg>
        </div>
        <h2>Admin Login</h2>
        <p>Access dashboard, manage students, view attendance reports and system settings</p>
        <span class="action-btn">
          Login
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
          </svg>
        </span>
      </a>

      <!-- Student Check-In Card -->
      <a href="student_kiosk.php" class="option-card student">
        <div class="card-icon">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 11.75c-.69 0-1.25.56-1.25 1.25s.56 1.25 1.25 1.25 1.25-.56 1.25-1.25-.56-1.25-1.25-1.25zm6 0c-.69 0-1.25.56-1.25 1.25s.56 1.25 1.25 1.25 1.25-.56 1.25-1.25-.56-1.25-1.25-1.25zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8 0-.29.02-.58.05-.86 2.36-1.05 4.23-2.98 5.21-5.37C11.07 8.33 14.05 10 17.42 10c.78 0 1.53-.09 2.25-.26.21.71.33 1.47.33 2.26 0 4.41-3.59 8-8 8z"/>
          </svg>
        </div>
        <h2>Student Check-In</h2>
        <p>Use your face to check in or check out. Fast, easy, and contactless attendance</p>
        <span class="action-btn">
          Start Scan
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
          </svg>
        </span>
      </a>

    </div>

    <!-- Footer -->
    <div class="footer">
      <p>&copy; <?php echo date('Y'); ?> Student Attendance Monitoring System</p>
    </div>

  </div>

</body>
</html>