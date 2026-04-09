<?php
require_once '../core/init.php';
requireRole(['admin']);

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$name = trim($_POST['name']);
$email = trim($_POST['email']);
$password = password_hash($_POST['password'], PASSWORD_BCRYPT);
$role = $_POST['role'];

// Prepare statement
$stmt = $conn->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
$stmt->bind_param("ssss", $name, $email, $password, $role);
$stmt->execute();

header("Location: user_list.php?msg=user_created");
exit();