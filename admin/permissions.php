<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Guarded by a direct role check (not requirePermission) on purpose:
// this IS the page that controls permissions. If it were permission-gated
// itself, an admin could accidentally revoke their own access to it.
// Only the built-in "admin" superuser role may open this page.
requireLogin();
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/unauthorized.php');
    exit;
}

// Roles that are actually configurable. Admin is excluded on purpose -
// it always has full access everywhere (see hasPermission() in functions.php).
$editableRoles = mysqli_fetch_all(
    mysqli_query($conn, "SELECT * FROM roles WHERE name != 'admin' ORDER BY id"),
    MYSQLI_ASSOC
);
$permissions = getAllPermissions($conn);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected = $_POST['perm'] ?? []; // format: perm[role_id][] = permission_id

    mysqli_begin_transaction($conn);
    try {
        foreach ($editableRoles as $role) {
            $roleId = $role['id'];

            $del = mysqli_prepare($conn, "DELETE FROM role_permissions WHERE role_id = ?");
            mysqli_stmt_bind_param($del, 'i', $roleId);
            mysqli_stmt_execute($del);
            mysqli_stmt_close($del);

            $checkedForRole = $selected[$roleId] ?? [];
            if ($checkedForRole) {
                $insert = mysqli_prepare($conn, "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach ($checkedForRole as $permId) {
                    $permId = (int)$permId;
                    mysqli_stmt_bind_param($insert, 'ii', $roleId, $permId);
                    mysqli_stmt_execute($insert);
                }
                mysqli_stmt_close($insert);
            }
        }
        mysqli_commit($conn);
        $success = 'Permissions updated successfully.';
    } catch (Exception $ex) {
        mysqli_rollback($conn);
        $error = 'Something went wrong while saving permissions.';
    }
}

// Re-fetch fresh state after any save, grouped as [role_id][permission_id] = true
$current = [];
$rows = mysqli_fetch_all(mysqli_query($conn, "SELECT role_id, permission_id FROM role_permissions"), MYSQLI_ASSOC);
foreach ($rows as $row) {
    $current[$row['role_id']][$row['permission_id']] = true;
}

// Group permissions by category for a cleaner matrix
$grouped = [];
foreach ($permissions as $p) {
    $grouped[$p['category']][] = $p;
}

include __DIR__ . '/../includes/header.php';
?>
<h2 class="page-title">🛡 Roles &amp; Permissions</h2>
<p style="color:#555; font-size:14px;">
    Tick the boxes to decide exactly what each role is allowed to do.
    Everything here is read live from the database — no role is hardcoded anywhere else in the app.
    <strong>Admin</strong> is a built-in superuser and always has full access, so it isn't listed below.
</p>

<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<form method="POST">
    <?php foreach ($grouped as $category => $perms): ?>
        <div class="card">
            <h3 style="margin-top:0;"><?= e($category) ?></h3>
            <table>
                <tr>
                    <th style="width:50%;">Permission</th>
                    <?php foreach ($editableRoles as $role): ?>
                        <th style="text-align:center;"><?= e(ucfirst($role['name'])) ?></th>
                    <?php endforeach; ?>
                </tr>
                <?php foreach ($perms as $perm): ?>
                <tr>
                    <td><?= e($perm['label']) ?> <span style="color:#999;">(<?= e($perm['key_name']) ?>)</span></td>
                    <?php foreach ($editableRoles as $role): ?>
                        <td style="text-align:center;">
                            <input type="checkbox"
                                   name="perm[<?= $role['id'] ?>][]"
                                   value="<?= $perm['id'] ?>"
                                   <?= isset($current[$role['id']][$perm['id']]) ? 'checked' : '' ?>
                                   style="width:18px;height:18px;">
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endforeach; ?>

    <button class="btn" type="submit">💾 Save Permissions</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
