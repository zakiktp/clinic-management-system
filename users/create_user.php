<?php
require_once '../core/init.php';
requireRole(['admin']);

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? '');
$password = $_POST['password'] ?? '';

if (!$name || !$email || !$role || !$password) {
    die('All fields required');
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (name,email,role,password,status,force_password_change) VALUES (?,?,?,?,1,1)");
$stmt->bind_param("ssss", $name, $email, $role, $hash);
$stmt->execute();

header('Location: user_list.php?msg=user_created');
exit;