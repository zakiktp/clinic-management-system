<?php
require_once '../core/init.php';
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit User</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
    background:#f4f8fc;
}

.edit-card{
    max-width:700px;
    margin:auto;
    border:none;
    border-radius:18px;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    overflow:hidden;
}

.edit-header{
    background:linear-gradient(135deg,#0d6efd,#0a58ca);
    color:#fff;
    padding:20px 25px;
}

.form-control,
.form-select{
    border-radius:12px;
    padding:12px 14px;
    border:1px solid #dce6f1;
}

.form-control:focus,
.form-select:focus{
    border-color:#0d6efd;
    box-shadow:0 0 0 4px rgba(13,110,253,.10);
}

.password-wrapper{
    position:relative;
}

.password-toggle{
    position:absolute;
    top:50%;
    right:14px;
    transform:translateY(-50%);
    cursor:pointer;
    color:#6c757d;
    font-size:18px;
}

.btn-premium{
    border-radius:12px;
    padding:12px 20px;
    font-weight:600;
}

.section-label{
    font-weight:600;
    color:#0d6efd;
    margin-bottom:6px;
}
</style>
</head>
<body>

<div class="container py-5">

    <div class="card edit-card">

        <div class="edit-header">
            <h3 class="mb-1"><i class="bi bi-person-gear me-2"></i>Edit User</h3>
            <small>Manage user profile, role and password</small>
        </div>

        <div class="card-body p-4">

            <form method="POST" action="update_user.php">

                <input type="hidden" name="id" value="<?= $user['id'] ?>">

                <div class="mb-3">
                    <label class="section-label">Full Name</label>
                    <input type="text"
                           name="name"
                           class="form-control"
                           value="<?= htmlspecialchars($user['name']) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label class="section-label">Email Address</label>
                    <input type="email"
                           name="email"
                           class="form-control"
                           value="<?= htmlspecialchars($user['email']) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label class="section-label">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
                        <option value="doctor" <?= $user['role']=='doctor'?'selected':'' ?>>Doctor</option>
                        <option value="reception" <?= $user['role']=='reception'?'selected':'' ?>>Reception</option>
                    </select>
                </div>

                <hr class="my-4">

                <h5 class="mb-3 text-primary">Change Password</h5>

                <div class="mb-3">
                    <label class="section-label">New Password</label>
                    <div class="password-wrapper">
                        <input type="password"
                               name="password"
                               id="password"
                               class="form-control"
                               placeholder="Leave blank to keep current password">
                        <i class="bi bi-eye password-toggle"
                           onclick="togglePassword('password', this)"></i>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="section-label">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password"
                               name="confirm_password"
                               id="confirm_password"
                               class="form-control">
                        <i class="bi bi-eye password-toggle"
                           onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary btn-premium">
                        <i class="bi bi-check-circle me-1"></i> Update User
                    </button>

                    <a href="user_list.php" class="btn btn-secondary btn-premium">
                        <i class="bi bi-arrow-left me-1"></i> Back to Users
                    </a>

                    <a href="/clinic/login.php" class="btn btn-outline-danger btn-premium">
                        <i class="bi bi-box-arrow-right me-1"></i> Login Page
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
function togglePassword(fieldId, icon){
    const field = document.getElementById(fieldId);

    if(field.type === "password"){
        field.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    } else {
        field.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }
}
</script>

</body>
</html>