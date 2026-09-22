<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requirePermission($conn, 'users_view');

$canAdd    = hasPermission($conn, 'users_add');
$canEdit   = hasPermission($conn, 'users_edit');
$canDelete = hasPermission($conn, 'users_delete');

$sql = "SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.id DESC";
$users = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<div class="actions-bar">
    <h2 class="page-title">Manage Users</h2>
    <?php if ($canAdd): ?>
        <a class="btn" href="<?= BASE_URL ?>/admin/user_add.php">+ Add User</a>
    <?php endif; ?>
</div>

<?php if ($msg = flash('success')): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('error')): ?><div class="alert error"><?= e($msg) ?></div><?php endif; ?>

<table>
    <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr>
    <?php foreach ($users as $u): ?>
    <tr>
        <td>#<?= $u['id'] ?></td>
        <td><?= e($u['full_name']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><span class="badge <?= e($u['role_name']) ?>"><?= e($u['role_name']) ?></span></td>
        <td><span class="badge <?= e($u['status']) ?>"><?= e($u['status']) ?></span></td>
        <td><?= e($u['created_at']) ?></td>
        <td>
            <?php if ($canEdit): ?>
                <a class="btn small" href="<?= BASE_URL ?>/admin/user_edit.php?id=<?= $u['id'] ?>">Edit</a>
            <?php endif; ?>
            <?php if ($canDelete && $u['id'] != $_SESSION['user_id']): ?>
                <a class="btn small danger" href="<?= BASE_URL ?>/admin/user_delete.php?id=<?= $u['id'] ?>"
                   onclick="return confirm('Delete this user permanently?');">Delete</a>
            <?php endif; ?>
            <?php if (!$canEdit && !$canDelete): ?><span style="color:#999;">—</span><?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
