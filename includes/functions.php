<?php
// ================================================================
// Helper functions: auth + DB-driven permission checks
// config.php must be included BEFORE this file ($conn must exist)
// ================================================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'      => $_SESSION['user_id'],
        'name'    => $_SESSION['user_name'],
        'email'   => $_SESSION['user_email'],
        'role'    => $_SESSION['user_role'],
        'role_id' => $_SESSION['role_id'],
    ];
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function redirectToDashboard() {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

// ================================================================
// Permission system (fully DB-driven, mysqli powered)
// Admin manages role_permissions from admin/permissions.php
// ================================================================

// Returns every permission key assigned to a role, straight from the DB.
// Cached per-request (static array) so one page load only hits the
// database once per role, no matter how many times we check.
function getRolePermissionKeys(mysqli $conn, $roleId) {
    static $cache = [];
    if (isset($cache[$roleId])) {
        return $cache[$roleId];
    }

    $keys = [];
    $sql = "SELECT p.key_name
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $roleId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $keys[] = $row['key_name'];
    }
    mysqli_stmt_close($stmt);

    $cache[$roleId] = $keys;
    return $keys;
}

// The "admin" role is a built-in superuser and always has every
// permission. This guarantees an admin can never lock themselves out
// by mistake. Agent / Customer (and any future role) are fully
// controlled by the role_permissions table, which only Admin can edit.
function hasPermission(mysqli $conn, $permissionKey) {
    if (!isLoggedIn()) return false;
    if ($_SESSION['user_role'] === 'admin') return true;
    $keys = getRolePermissionKeys($conn, $_SESSION['role_id']);
    return in_array($permissionKey, $keys, true);
}

function requirePermission(mysqli $conn, $permissionKey) {
    requireLogin();
    if (!hasPermission($conn, $permissionKey)) {
        header('Location: ' . BASE_URL . '/unauthorized.php');
        exit;
    }
}

function getAllPermissions(mysqli $conn) {
    $rows = [];
    $result = mysqli_query($conn, "SELECT * FROM permissions ORDER BY category, id");
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}
