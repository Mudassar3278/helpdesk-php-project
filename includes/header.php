<?php
// Expects config.php + functions.php to already be included, with $conn available
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Helpdesk System</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="brand">🎫 Helpdesk System</div>
    <div class="nav-links">
        <?php if ($user): ?>
            <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>

            <?php if (hasPermission($conn, 'tickets_view_all') || hasPermission($conn, 'tickets_view_assigned') || hasPermission($conn, 'tickets_view_own')): ?>
                <a href="<?= BASE_URL ?>/tickets.php">Tickets</a>
            <?php endif; ?>

            <?php if (hasPermission($conn, 'tickets_create')): ?>
                <a href="<?= BASE_URL ?>/ticket_create.php">+ New Ticket</a>
            <?php endif; ?>

            <?php if (hasPermission($conn, 'users_view')): ?>
                <a href="<?= BASE_URL ?>/admin/users.php">Users</a>
            <?php endif; ?>

            <?php if ($user['role'] === 'admin'): ?>
                <a href="<?= BASE_URL ?>/admin/permissions.php">Permissions</a>
            <?php endif; ?>

            <span class="user-info">👤 <?= e($user['name']) ?> &middot; <span class="badge <?= e($user['role']) ?>"><?= e($user['role']) ?></span></span>
            <a href="<?= BASE_URL ?>/logout.php" class="logout">Logout</a>
        <?php endif; ?>
    </div>
</nav>
<div class="container">
