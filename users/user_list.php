<?php
require_once '../core/init.php';
requireRole(['admin']);

$stmt = $conn->prepare("SELECT * FROM users ORDER BY id DESC");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>User Management</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
    background:#f4f8fc;
}

/* Card */
.user-card{
    border:none;
    border-radius:18px;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    overflow:hidden;
}

/* Header */
.user-header{
    background:linear-gradient(135deg,#0d6efd,#0a58ca);
    color:#fff;
    padding:20px 24px;
}

/* Table */
.table thead{
    background:#eef5ff;
}

.table tbody tr:hover{
    background:#f8fbff;
}

/* Badge Styling */
.role-badge{
    font-size:12px;
    padding:6px 10px;
    border-radius:20px;
}

.status-active{
    background:#d1f7df;
    color:#0f7b39;
}

.status-inactive{
    background:#ffe1e1;
    color:#b42318;
}

/* Buttons */
.btn-premium{
    border-radius:10px;
    font-size:14px;
    padding:6px 12px;
}

/* Page spacing */
.page-wrap{
    max-width:1200px;
    margin:auto;
}
</style>
</head>
<body>

<div class="container py-4 page-wrap">

    <!-- TOP ACTIONS -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-0">User Management</h2>
            <small class="text-muted">Manage system users, roles and access</small>
        </div>

        <div class="d-flex gap-2">
            <a href="add_user.php" class="btn btn-success btn-premium">
                <i class="bi bi-person-plus me-1"></i> Add User
            </a>

            <a href="../index.php" class="btn btn-secondary btn-premium">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- CARD -->
    <div class="card user-card">

        <div class="user-header">
            <h5 class="mb-0">
                <i class="bi bi-people-fill me-2"></i>
                Registered Users
            </h5>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">

                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th class="text-center pe-4">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach($users as $u): ?>
                        <tr>
                            <td class="ps-4 fw-semibold">
                                #<?= $u['id'] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($u['name']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($u['email']) ?>
                            </td>

                            <td>
                                <span class="badge bg-primary role-badge">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>

                            <td>
                                <?php if($u['status']): ?>
                                    <span class="badge status-active role-badge">Active</span>
                                <?php else: ?>
                                    <span class="badge status-inactive role-badge">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center pe-4">
                                <a href="edit_user.php?id=<?= $u['id'] ?>"
                                   class="btn btn-sm btn-primary btn-premium">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form method="POST"
                                      action="delete_user.php"
                                      style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">

                                    <button type="submit"
                                            class="btn btn-sm btn-danger btn-premium"
                                            onclick="return confirm('Delete user?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

</body>
</html>