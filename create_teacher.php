<?php
// Script to insert teacher account with proper password hash
$conn = new mysqli("localhost", "root", "", "attendance_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = 'teacher';
$password = 'teacher123';
$role = 'teacher';

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
$stmt->bind_param('sss', $username, $hash, $role);

if ($stmt->execute()) {
    echo "Teacher account created successfully!\n";
    echo "Username: teacher\n";
    echo "Password: teacher123\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

$stmt->close();
$conn->close();
?>
