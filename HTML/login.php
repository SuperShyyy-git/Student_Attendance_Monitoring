<?php
session_start();
include __DIR__ . "/../config/db_connect.php";

// *************
// HANDLE LOGIN
// *************
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    // Query user with prepared statement (prevents SQL injection)
    $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $_SESSION["login_error"] = "Database error. Please try again.";
        header("Location: login.php");
        exit;
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password hash
        if (password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["role"] = $user["role"];

            // Redirect based on role
            if ($user["role"] === "machine") {
                header("Location: /attendance/HTML/attendance_capture.php");
                exit;
            }

            if ($user["role"] === "admin") {
                header("Location: /attendance/HTML/dashboard.php");
                exit;
            }

            if ($user["role"] === "teacher") {
                header("Location: /attendance/HTML/dashboard.php");
                exit;
            }
        }
    }

    // FAILED LOGIN → store error in session
    $_SESSION["login_error"] = "Invalid username or password!";
    header("Location: login.php");   // <-- PRG fix
    exit;
}

// *************
// SHOW ERROR ON GET
// *************
$error = "";
if (isset($_SESSION["login_error"])) {
    $error = $_SESSION["login_error"];
    unset($_SESSION["login_error"]);   // remove after displaying
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <link rel="stylesheet" href="../css/loginpage.css">
</head>

<body>
  <div class="wrapper">
    <div class="login-card">

      <div class="logo-circle">
        <img src="../resources/image/school-logo.jpg" alt="Logo">
      </div>

      <h2>Login</h2>

      <?php if (!empty($error)): ?>
        <p style="color: red; text-align:center; margin-bottom:10px;">
          <?php echo $error; ?>
        </p>
      <?php endif; ?>

      <form action="" method="POST">

        <div class="input-group">
          <input type="text" name="username" required placeholder=" " />
          <label>Username</label>
        </div>

        <div class="input-group">
          <input type="password" name="password" required placeholder=" " />
          <label>Password</label>
        </div>

        <button type="submit" class="login-btn">Login</button>

      </form>

    </div>
  </div>
</body>
</html>

