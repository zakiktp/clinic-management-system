<?php
require_once '../core/init.php';
requireRole(['admin']);

$users = $conn->query("SELECT id,name,email FROM users ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
?>
<form method="POST" action="reset_password.php">
    <select name="id" required>
        <?php foreach($users as $u): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)</option>
        <?php endforeach; ?>
    </select>
    <input type="password" name="password" placeholder="New Temporary Password" required>
    <button type="submit">Reset Password</button>
</form>