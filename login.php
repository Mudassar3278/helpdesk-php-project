<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirectToDashboard();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter email and password.';
    } else {
        $sql = "SELECT u.*, r.name AS role_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$user) {
            $error = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $error = 'Your account is inactive. Contact the admin.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Invalid email or password.';
        } else {
            // Login success - store role + role_id from DB in session.
            // role_id lets every page look up live permissions.
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role']  = $user['role_name'];
            $_SESSION['role_id']    = $user['role_id'];
            redirectToDashboard();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Helpdesk System</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-box">
        <h2>🎫 Helpdesk Login</h2>
        <?php if ($error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button class="btn" type="submit">Login</button>
        </form>
        <div class="hint">
            Demo accounts (seeded in database.sql):<br>
            admin@demo.com / admin123<br>
            agent@demo.com / agent123<br>
            customer@demo.com / customer123
        </div>
    </div>
</div>
</body>
</html>
