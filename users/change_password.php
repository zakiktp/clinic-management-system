<?php
require_once '../core/init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /clinic/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if ($new_password === '' || $confirm_password === '') {
        $error = "All fields required";
    }
    elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    }
    elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters";
    }
    else {

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            UPDATE users
            SET password = ?, force_password_change = 0
            WHERE id = ?
        ");
        $stmt->bind_param("si", $hashed, $user_id);
        $stmt->execute();

        session_destroy();

        header("Location: /clinic/login.php?msg=password_changed");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <style>
        body{
            font-family:Arial;
            background:#f5f5f5;
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
        }
        .box{
            background:#fff;
            padding:30px;
            border-radius:10px;
            width:350px;
            box-shadow:0 4px 15px rgba(0,0,0,.1);
        }
        input,button{
            width:100%;
            padding:10px;
            margin:10px 0;
        }
        button{
            background:#0b6fa4;
            color:#fff;
            border:none;
        }
        .error{color:red;}
    </style>
</head>
<body>

<div class="box">
    <h2>Change Your Password</h2>

    <?php if($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Update Password</button>
    </form>
</div>

</body>
</html>