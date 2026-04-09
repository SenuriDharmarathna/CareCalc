<?php
$host = "localhost";   // Database host (default for WAMP/XAMPP)
$user = "root";        // Default MySQL username
$pass = "";            // Leave blank unless you set a password in phpMyAdmin
$db   = "carecalc_db"; // Your database name

// Create a new connection
$conn = new mysqli($host, $user, $pass, $db);

// Check the connection
if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}


?>
