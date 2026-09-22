<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requirePermission($conn, 'users_edit');

$id = (int)($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) {
    flash('error', 'User not found.');
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

$roles = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM roles ORDER BY id"), MYSQLI_ASSOC);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $roleId   = (int)($_POST['role_id'] ?? 0);
    $status   = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $roleId === 0) {
        $error = 'Name, email and role are required.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($stmt, 'si', $email, $id);
        mysqli_stmt_execute($stmt);
        $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($exists) {
            $error = 'This email is already used by another account.';
        } else {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn,
                    "UPDATE users SET full_name=?, email=?, role_id=?, status=?, password=? WHERE id=?"
                );
                mysqli_stmt_bind_param($stmt, 'ssissi', $name, $email, $roleId, $status, $hash, $id);
            } else {
                $stmt = mysqli_prepare($conn,
                    "UPDATE users SET full_name=?, email=?, role_id=?, status=? WHERE id=?"
                );
                mysqli_stmt_bind_param($stmt, 'ssisi', $name, $email, $roleId, $status, $id);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            flash('success', 'User updated successfully.');
            header('Location: ' . BASE_URL . '/admin/users.php');
            exit;
        }
    }
    $user = array_merge($user, $_POST);
}

include __DIR__ . '/../includes/header.php';
?>
<h2 class="page-title">Edit User #<?= $user['id'] ?></h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<div class="card" style="max-width:500px;">
    <form method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required value="<?= e($user['full_name']) ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required value="<?= e($user['email']) ?>">
        </div>
        <div class="form-group">
            <label>New Password (leave blank to keep current)</label>
            <input type="password" name="password">
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role_id" required>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= $r['id'] == $user['role_id'] ? 'selected' : '' ?>>
                        <?= e(ucfirst($r['name'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <button class="btn" type="submit">Update User</button>
        <a class="btn secondary" href="<?= BASE_URL ?>/admin/users.php">Cancel</a>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
