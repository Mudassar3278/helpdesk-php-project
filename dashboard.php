<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$uid = (int)$_SESSION['user_id'];

function countQuery(mysqli $conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return (int)$row['c'];
}

include __DIR__ . '/includes/header.php';
?>
<h2 class="page-title">Dashboard</h2>
<p>Welcome, <strong><?= e($_SESSION['user_name']) ?></strong>
   &middot; role: <span class="badge <?= e($_SESSION['user_role']) ?>"><?= e($_SESSION['user_role']) ?></span></p>

<?php if (hasPermission($conn, 'users_view')): ?>
    <?php $totalUsers = countQuery($conn, "SELECT COUNT(*) c FROM users"); ?>
    <div class="card">
        <h3>👥 Users</h3>
        <div class="stats-grid">
            <div class="stat-box"><div class="num"><?= $totalUsers ?></div><div class="label">Total Users</div></div>
        </div>
        <a class="btn" style="margin-top:12px;" href="<?= BASE_URL ?>/admin/users.php">Manage Users</a>
    </div>
<?php endif; ?>

<?php if (hasPermission($conn, 'tickets_view_all')): ?>
    <?php
    $total    = countQuery($conn, "SELECT COUNT(*) c FROM tickets");
    $open     = countQuery($conn, "SELECT COUNT(*) c FROM tickets WHERE status='open'");
    $progress = countQuery($conn, "SELECT COUNT(*) c FROM tickets WHERE status='in_progress'");
    $closed   = countQuery($conn, "SELECT COUNT(*) c FROM tickets WHERE status='closed'");
    ?>
    <div class="card">
        <h3>🎫 All Tickets (system-wide)</h3>
        <div class="stats-grid">
            <div class="stat-box"><div class="num"><?= $total ?></div><div class="label">Total</div></div>
            <div class="stat-box"><div class="num"><?= $open ?></div><div class="label">Open</div></div>
            <div class="stat-box"><div class="num"><?= $progress ?></div><div class="label">In Progress</div></div>
            <div class="stat-box"><div class="num"><?= $closed ?></div><div class="label">Closed</div></div>
        </div>
        <a class="btn" style="margin-top:12px;" href="<?= BASE_URL ?>/tickets.php">View All Tickets</a>
    </div>
<?php elseif (hasPermission($conn, 'tickets_view_assigned')): ?>
    <?php
    $total = countQuery($conn, "SELECT COUNT(*) c FROM tickets WHERE assigned_to=?", 'i', [$uid]);
    $open  = countQuery($conn, "SELECT COUNT(*) c FROM tickets WHERE assigned_to=? AND status='open'", 'i', [$uid]);
    $done  = countQuery($conn, "SELECT COUNT(*) c FROM tickets WHERE assigned_to=? AND status IN ('resolved','closed')", 'i', [$uid]);
    ?>
    <div class="card">
        <h3>🎫 Tickets Assigned to Me</h3>
        <div class="stats-grid">
            <div class="stat-box"><div class="num"><?= $total ?></div><div class="label">Assigned</div></div>
            <div class="stat-box"><div class="num"><?= $open ?></div><div class="label">Still Open</div></div>
            <div class="stat-box"><div class="num"><?= $done ?></div><div class="label">Resolved/Closed</div></div>
        </div>
        <a class="btn" style="margin-top:12px;" href="<?= BASE_URL ?>/tickets.php">View My Assigned Tickets</a>
    </div>
<?php endif; ?>

<?php if (hasPermission($conn, 'tickets_view_own')): ?>
    <?php
    $myTotal  = countQuery($conn, "SELECT COUNT(*) c FROM tickets WHERE created_by=?", 'i', [$uid]);
    $myOpen   = countQuery($conn, "SELECT COUNT(*) c FROM tickets WHERE created_by=? AND status NOT IN ('resolved','closed')", 'i', [$uid]);
    ?>
    <div class="card">
        <h3>🗂 My Tickets</h3>
        <div class="stats-grid">
            <div class="stat-box"><div class="num"><?= $myTotal ?></div><div class="label">Total Raised</div></div>
            <div class="stat-box"><div class="num"><?= $myOpen ?></div><div class="label">Awaiting Resolution</div></div>
        </div>
        <a class="btn" style="margin-top:12px;" href="<?= BASE_URL ?>/tickets.php">View My Tickets</a>
        <?php if (hasPermission($conn, 'tickets_create')): ?>
            <a class="btn secondary" style="margin-top:12px;" href="<?= BASE_URL ?>/ticket_create.php">+ Raise New Ticket</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($_SESSION['user_role'] === 'admin'): ?>
    <div class="card">
        <h3>🛡 Roles &amp; Permissions</h3>
        <p style="font-size:14px;color:#555;">Decide exactly what Agents and Customers are allowed to do.</p>
        <a class="btn secondary" href="<?= BASE_URL ?>/admin/permissions.php">Manage Permissions</a>
    </div>
<?php endif; ?>

<?php
$hasAnyBlock = hasPermission($conn,'users_view') || hasPermission($conn,'tickets_view_all')
    || hasPermission($conn,'tickets_view_assigned') || hasPermission($conn,'tickets_view_own');
if (!$hasAnyBlock && $_SESSION['user_role'] !== 'admin'):
?>
    <div class="card"><p>No permissions have been assigned to your account yet. Please contact your Admin.</p></div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
