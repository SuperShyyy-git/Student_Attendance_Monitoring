<?php
/**
 * Database Connection Configuration
 * 
 * SETUP INSTRUCTIONS:
 * 1. Copy this file to: config/db_connect.php
 * 2. Update the credentials if needed
 * 3. Make sure your database exists and migration is run
 */

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "attendance_system";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    error_log("Database Connection Error: " . $conn->connect_error);
    header('Content-Type: application/json', true, 500);
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Set charset
$conn->set_charset("utf8mb4");
?>