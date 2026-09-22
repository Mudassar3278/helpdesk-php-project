<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requirePermission($conn, 'users_add');

$roles = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM roles ORDER BY id"), MYSQLI_ASSOC);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $roleId   = (int)($_POST['role_id'] ?? 0);
    $status   = $_POST['status'] ?? 'active';

    if ($name === '' || $email === '' || $password === '' || $roleId === 0) {
        $error = 'All fields are required.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($exists) {
            $error = 'This email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn,
                "INSERT INTO users (full_name, email, password, role_id, status) VALUES (?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, 'sssis', $name, $email, $hash, $roleId, $status);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            flash('success', 'User created successfully.');
            header('Location: ' . BASE_URL . '/admin/users.php');
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<h2 class="page-title">Add User</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<div class="card" style="max-width:500px;">
    <form method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role_id" required>
                <option value="">-- Select role --</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= e(ucfirst($r['name'])) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <button class="btn" type="submit">Save User</button>
        <a class="btn secondary" href="<?= BASE_URL ?>/admin/users.php">Cancel</a>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
