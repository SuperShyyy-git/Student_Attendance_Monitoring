<?php
/**
 * Remove machine user from database
 * Run this once to clean up the machine role
 */

include __DIR__ . "/../config/db_connect.php";

// Delete machine user(s)
$result = $conn->query("DELETE FROM users WHERE role = 'machine'");

if ($result) {
    $affected = $conn->affected_rows;
    echo "✅ Removed $affected machine user(s) from the database.\n";
    echo "<br><a href='login.php'>← Go to Login</a>";
} else {
    echo "❌ Error: " . $conn->error;
}

$conn->close();
?>