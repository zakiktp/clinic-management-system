<?php
require_once '../core/init.php';

if (empty($_SESSION['user_id'])) {
    header("Location: /clinic/login.php");
    exit;
}

$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

if ($password === '' || $confirm === '') {
    die("All fields required.");
}

if ($password !== $confirm) {
    die("Passwords do not match.");
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    UPDATE users
    SET password=?, force_password_change=0
    WHERE id=?
");

$stmt->execute([
    $hashedPassword,
    $_SESSION['user_id']
]);

header("Location: /clinic/dashboard.php?msg=password_changed");
exit;