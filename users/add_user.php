<?php
require_once '../core/init.php';
requireRole(['admin']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:700px;">
    <div class="card shadow border-0 rounded-4">
        <div class="card-body p-4">
            <h3 class="fw-bold mb-4">Add New User</h3>
            <form method="POST" action="create_user.php">
                <input type="text" name="name" class="form-control mb-3" placeholder="Full Name" required>
                <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
                <select name="role" class="form-select mb-3" required>
                    <option value="">Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="doctor">Doctor</option>
                    <option value="reception">Reception</option>
                </select>
                <input type="password" name="password" class="form-control mb-3" placeholder="Temporary Password" required>
                <button class="btn btn-primary">Create User</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>