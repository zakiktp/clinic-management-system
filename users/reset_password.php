<?php
require_once '../core/init.php';
requireRole(['admin']);

$id = (int)($_POST['id'] ?? 0);
$password = $_POST['password'] ?? '';

if (!$id || !$password) {
    die('Missing data');
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password=?, force_password_change=1 WHERE id=?");
$stmt->bind_param("si", $hash, $id);
$stmt->execute();

header('Location: user_list.php?msg=password_reset');
exit;