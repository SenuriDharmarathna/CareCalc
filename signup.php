<?php
include "config.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$role     = $_POST["role"];
$username = trim($_POST["username"]);
$email    = trim($_POST["email"]);
$password = password_hash($_POST["password"], PASSWORD_BCRYPT);
$city     = isset($_POST["city"])    ? trim($_POST["city"])    : NULL;
$contact  = isset($_POST["contact"]) ? trim($_POST["contact"]) : NULL;

// Check for duplicate email
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $_SESSION["signup_error"] = "email_exists";
    header("Location: index.php?signup=error#signupModal");
    exit();
}
$stmt->close();

// Insert new user
$insert = $conn->prepare("INSERT INTO users (username, email, password, city, contact, role) VALUES (?, ?, ?, ?, ?, ?)");
$insert->bind_param("ssssss", $username, $email, $password, $city, $contact, $role);

if ($insert->execute()) {
    $_SESSION["signup_success"] = true;
    header("Location: login.php?registered=1");
    exit();
} else {
    $_SESSION["signup_error"] = "db_error";
    header("Location: index.php?signup=error");
    exit();
}
?>