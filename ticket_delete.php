<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
requirePermission($conn, 'tickets_delete');

$id  = (int)($_GET['id'] ?? 0);
$uid = (int)$_SESSION['user_id'];

$canViewAll      = hasPermission($conn, 'tickets_view_all');
$canViewAssigned = hasPermission($conn, 'tickets_view_assigned');
$canViewOwn      = hasPermission($conn, 'tickets_view_own');

$stmt = mysqli_prepare($conn, "SELECT created_by, assigned_to FROM tickets WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$ticket = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$ticket) {
    flash('error', 'Ticket not found.');
    header('Location: ' . BASE_URL . '/tickets.php');
    exit;
}

$isOwner    = ((int)$ticket['created_by'] === $uid);
$isAssignee = ((int)$ticket['assigned_to'] === $uid);
$canOpen = $canViewAll || ($canViewAssigned && $isAssignee) || ($canViewOwn && $isOwner);

if (!$canOpen) {
    header('Location: ' . BASE_URL . '/unauthorized.php');
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM tickets WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

flash('success', 'Ticket deleted.');
header('Location: ' . BASE_URL . '/tickets.php');
exit;
