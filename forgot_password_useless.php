<?php
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("UPDATE users SET password=? WHERE email=?");
    $stmt->bind_param("ss", $new_password, $email);
    $stmt->execute();

    $msg = "Password updated successfully";
}
?>

<h3>Forgot Password</h3>

<form method="POST">
    <input type="email" name="email" placeholder="Enter Email" required><br><br>
    <input type="password" name="new_password" placeholder="New Password" required><br><br>
    <button type="submit">Reset Password</button>
    <a href="/clinic/login.php" class="exit-btn">Go to Login Window</a>
</form>

<?php if(isset($msg)) echo "<p style='color:green'>$msg</p>"; ?>