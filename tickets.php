<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$uid = (int)$_SESSION['user_id'];

$canViewAll      = hasPermission($conn, 'tickets_view_all');
$canViewAssigned = hasPermission($conn, 'tickets_view_assigned');
$canViewOwn      = hasPermission($conn, 'tickets_view_own');
$canDelete       = hasPermission($conn, 'tickets_delete');

if (!$canViewAll && !$canViewAssigned && !$canViewOwn) {
    header('Location: ' . BASE_URL . '/unauthorized.php');
    exit;
}

// Optional status filter (?status=open)
$statusFilter = $_GET['status'] ?? '';
$allowedStatuses = ['open', 'in_progress', 'resolved', 'closed'];

$sql = "SELECT t.*, c.full_name AS creator_name, a.full_name AS agent_name
        FROM tickets t
        JOIN users c ON t.created_by = c.id
        LEFT JOIN users a ON t.assigned_to = a.id
        WHERE 1=1";
$types = '';
$params = [];

if (!$canViewAll) {
    // Build the visible scope from whichever permissions this role has
    $scopeParts = [];
    if ($canViewAssigned) { $scopeParts[] = "t.assigned_to = ?"; $types .= 'i'; $params[] = $uid; }
    if ($canViewOwn)      { $scopeParts[] = "t.created_by = ?";  $types .= 'i'; $params[] = $uid; }
    $sql .= " AND (" . implode(' OR ', $scopeParts) . ")";
}

if (in_array($statusFilter, $allowedStatuses, true)) {
    $sql .= " AND t.status = ?";
    $types .= 's';
    $params[] = $statusFilter;
}

$sql .= " ORDER BY t.id DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tickets = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$pageTitle = $canViewAll ? 'All Tickets (system-wide)' : ($canViewAssigned ? 'Tickets Assigned to Me' : 'My Tickets');

include __DIR__ . '/includes/header.php';
?>
<div class="actions-bar">
    <h2 class="page-title"><?= e($pageTitle) ?></h2>
    <?php if (hasPermission($conn, 'tickets_create')): ?>
        <a class="btn" href="<?= BASE_URL ?>/ticket_create.php">+ New Ticket</a>
    <?php endif; ?>
</div>

<?php if ($msg = flash('success')): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('error')): ?><div class="alert error"><?= e($msg) ?></div><?php endif; ?>

<div class="card" style="padding:12px 20px;">
    <form method="GET" style="display:flex; gap:10px; align-items:center;">
        <label style="margin:0;">Filter by status:</label>
        <select name="status" onchange="this.form.submit()">
            <option value="">All</option>
            <option value="open" <?= $statusFilter=='open'?'selected':'' ?>>Open</option>
            <option value="in_progress" <?= $statusFilter=='in_progress'?'selected':'' ?>>In Progress</option>
            <option value="resolved" <?= $statusFilter=='resolved'?'selected':'' ?>>Resolved</option>
            <option value="closed" <?= $statusFilter=='closed'?'selected':'' ?>>Closed</option>
        </select>
    </form>
</div>

<table>
    <tr>
        <th>ID</th><th>Subject</th><th>Category</th><th>Priority</th><th>Status</th>
        <th>Raised By</th><th>Assigned Agent</th><th>Created</th><th>Actions</th>
    </tr>
    <?php foreach ($tickets as $t): ?>
    <tr>
        <td>#<?= $t['id'] ?></td>
        <td><?= e($t['subject']) ?></td>
        <td><span class="cat-pill"><?= e($t['category']) ?></span></td>
        <td><span class="badge <?= e($t['priority']) ?>"><?= e($t['priority']) ?></span></td>
        <td><span class="badge <?= e($t['status']) ?>"><?= e(str_replace('_',' ',$t['status'])) ?></span></td>
        <td><?= e($t['creator_name']) ?></td>
        <td><?= $t['agent_name'] ? e($t['agent_name']) : '<span style="color:#999;">Unassigned</span>' ?></td>
        <td><?= e($t['created_at']) ?></td>
        <td>
            <a class="btn small" href="<?= BASE_URL ?>/ticket_view.php?id=<?= $t['id'] ?>">View</a>
            <?php if ($canDelete): ?>
                <a class="btn small danger" href="<?= BASE_URL ?>/ticket_delete.php?id=<?= $t['id'] ?>"
                   onclick="return confirm('Delete this ticket permanently?');">Delete</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$tickets): ?><tr><td colspan="9">No tickets found.</td></tr><?php endif; ?>
</table>
<?php include __DIR__ . '/includes/footer.php'; ?>
