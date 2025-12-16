<?php
session_start();
include "../config/db_connect.php";

// Get input
$username = $_POST["username"];
$password = $_POST["password"];

// Prepare query
$query = $conn->prepare("SELECT * FROM users WHERE username = ?");
$query->bind_param("s", $username);
$query->execute();
$result = $query->get_result();

// Check user
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verify password
    if (password_verify($password, $user["password"])) {

        // Store session data
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["role"] = $user["role"];

        // Role-based redirect (SAFE)
        switch ($user["role"]) {

            case "admin":
                header("Location: dashboard.php");
                exit;

            case "teacher":
                header("Location: teacher_dashboard.php");
                exit;

            case "machine":
                header("Location: attendance_capture.php");
                exit;

            default:
                // fallback
                header("Location: dashboard.php");
                exit;
        }
    }
}

// Wrong login
echo "<script>
alert('Invalid username or password');
window.location.href='login.php';
</script>";
?>
