<?php
session_start();

// Clear session data
$_SESSION = array();

// If session cookie exists, remove it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: http://localhost/attendance/HTML/login.php');
exit;

?>

