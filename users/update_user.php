<?php
require_once '../core/init.php';
requireRole(['admin']);

$id    = (int)($_POST['id'] ?? 0);
$name  = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role  = trim($_POST['role'] ?? '');

$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

if (!$id || !$name || !$email || !$role) {
    die("Missing required fields.");
}

/* Password mismatch */
if ($password !== '' && $password !== $confirm) {
    die("Passwords do not match.");
}

/* WITH PASSWORD UPDATE */
if ($password !== '') {

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        UPDATE users
        SET name=?, email=?, role=?, password=?, force_password_change=0
        WHERE id=?
    ");

    $stmt->bind_param(
        "ssssi",
        $name,
        $email,
        $role,
        $hashedPassword,
        $id
    );
}

/* WITHOUT PASSWORD UPDATE */
else {

    $stmt = $conn->prepare("
        UPDATE users
        SET name=?, email=?, role=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "sssi",
        $name,
        $email,
        $role,
        $id
    );
}

$stmt->execute();
$stmt->close();

header("Location: user_list.php?msg=user_updated");
exit;
?>