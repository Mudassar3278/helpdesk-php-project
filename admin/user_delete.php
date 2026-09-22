<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
requirePermission($conn, 'users_delete');

$id = (int)($_GET['id'] ?? 0);

// Prevent deleting your own account
if ($id === (int)$_SESSION['user_id']) {
    flash('error', 'You cannot delete your own account.');
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

flash('success', 'User deleted.');
header('Location: ' . BASE_URL . '/admin/users.php');
exit;
