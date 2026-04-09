<?php
require_once '../core/init.php';
requireRole(['admin']);

$id = (int)($_POST['id'] ?? 0);

if ($id === ($_SESSION['user_id'] ?? -1)) {
    die('You cannot delete your own account.');
}

$stmt = $conn->prepare("DELETE FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

header('Location: user_list.php?msg=user_deleted');
exit;