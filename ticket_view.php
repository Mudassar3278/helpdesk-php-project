<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$uid = (int)$_SESSION['user_id'];
$id  = (int)($_GET['id'] ?? 0);

$canViewAll      = hasPermission($conn, 'tickets_view_all');
$canViewAssigned = hasPermission($conn, 'tickets_view_assigned');
$canViewOwn      = hasPermission($conn, 'tickets_view_own');
$canReply        = hasPermission($conn, 'tickets_reply');
$canAssign       = hasPermission($conn, 'tickets_assign');
$canChangeStatus = hasPermission($conn, 'tickets_change_status');
$canDelete       = hasPermission($conn, 'tickets_delete');

function fetchTicket(mysqli $conn, $id) {
    $sql = "SELECT t.*, c.full_name AS creator_name, c.email AS creator_email,
                   a.full_name AS agent_name
            FROM tickets t
            JOIN users c ON t.created_by = c.id
            LEFT JOIN users a ON t.assigned_to = a.id
            WHERE t.id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row;
}

$ticket = fetchTicket($conn, $id);

if (!$ticket) {
    flash('error', 'Ticket not found.');
    header('Location: ' . BASE_URL . '/tickets.php');
    exit;
}

$isOwner    = ((int)$ticket['created_by'] === $uid);
$isAssignee = ((int)$ticket['assigned_to'] === $uid);

// A user may open this specific ticket only if their granted scope covers it
$canOpen = $canViewAll
    || ($canViewAssigned && $isAssignee)
    || ($canViewOwn && $isOwner);

if (!$canOpen) {
    header('Location: ' . BASE_URL . '/unauthorized.php');
    exit;
}

$error = '';

// ---------- Handle POST actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'reply' && $canReply) {
        $message = trim($_POST['message'] ?? '');
        if ($message !== '') {
            $sql = "INSERT INTO ticket_replies (ticket_id, user_id, message) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'iis', $id, $uid, $message);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            flash('success', 'Reply posted.');
        }
    }

    if ($formAction === 'status' && $canChangeStatus) {
        $status = $_POST['status'] ?? '';
        $allowed = ['open', 'in_progress', 'resolved', 'closed'];
        if (in_array($status, $allowed, true)) {
            $stmt = mysqli_prepare($conn, "UPDATE tickets SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            flash('success', 'Ticket status updated.');
        }
    }

    if ($formAction === 'assign' && $canAssign) {
        $agentId = (int)($_POST['assigned_to'] ?? 0);
        if ($agentId > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE tickets SET assigned_to = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'ii', $agentId, $id);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE tickets SET assigned_to = NULL WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        flash('success', 'Ticket assignment updated.');
    }

    header('Location: ' . BASE_URL . '/ticket_view.php?id=' . $id);
    exit;
}

// Refresh ticket data (in case GET request after redirect)
$ticket = fetchTicket($conn, $id);

// Fetch conversation thread
$sql = "SELECT r.*, u.full_name, u.email
        FROM ticket_replies r
        JOIN users u ON r.user_id = u.id
        WHERE r.ticket_id = ?
        ORDER BY r.id ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$replies = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Agents list for the assign dropdown
$agents = [];
if ($canAssign) {
    $res = mysqli_query($conn,
        "SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id=r.id
         WHERE r.name='agent' AND u.status='active' ORDER BY u.full_name"
    );
    $agents = mysqli_fetch_all($res, MYSQLI_ASSOC);
}

include __DIR__ . '/includes/header.php';
?>
<div class="actions-bar">
    <h2 class="page-title">Ticket #<?= $ticket['id'] ?>: <?= e($ticket['subject']) ?></h2>
    <?php if ($canDelete): ?>
        <a class="btn small danger" href="<?= BASE_URL ?>/ticket_delete.php?id=<?= $ticket['id'] ?>"
           onclick="return confirm('Delete this ticket permanently?');">Delete Ticket</a>
    <?php endif; ?>
</div>

<?php if ($msg = flash('success')): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>

<div class="card">
    <div class="ticket-meta-grid">
        <div class="item"><div class="k">Status</div><div class="v"><span class="badge <?= e($ticket['status']) ?>"><?= e(str_replace('_',' ',$ticket['status'])) ?></span></div></div>
        <div class="item"><div class="k">Priority</div><div class="v"><span class="badge <?= e($ticket['priority']) ?>"><?= e($ticket['priority']) ?></span></div></div>
        <div class="item"><div class="k">Category</div><div class="v"><span class="cat-pill"><?= e($ticket['category']) ?></span></div></div>
        <div class="item"><div class="k">Raised By</div><div class="v"><?= e($ticket['creator_name']) ?></div></div>
        <div class="item"><div class="k">Assigned Agent</div><div class="v"><?= $ticket['agent_name'] ? e($ticket['agent_name']) : 'Unassigned' ?></div></div>
        <div class="item"><div class="k">Created</div><div class="v"><?= e($ticket['created_at']) ?></div></div>
    </div>
    <p><?= nl2br(e($ticket['description'])) ?></p>
</div>

<?php if ($canChangeStatus || $canAssign): ?>
<div class="card">
    <h3 style="margin-top:0;">Manage Ticket</h3>
    <div style="display:flex; gap:30px; flex-wrap:wrap;">
        <?php if ($canChangeStatus): ?>
        <form method="POST" style="display:flex; gap:8px; align-items:center;">
            <input type="hidden" name="form_action" value="status">
            <label style="margin:0;">Change status:</label>
            <select name="status">
                <option value="open" <?= $ticket['status']=='open'?'selected':'' ?>>Open</option>
                <option value="in_progress" <?= $ticket['status']=='in_progress'?'selected':'' ?>>In Progress</option>
                <option value="resolved" <?= $ticket['status']=='resolved'?'selected':'' ?>>Resolved</option>
                <option value="closed" <?= $ticket['status']=='closed'?'selected':'' ?>>Closed</option>
            </select>
            <button class="btn small" type="submit">Update</button>
        </form>
        <?php endif; ?>

        <?php if ($canAssign): ?>
        <form method="POST" style="display:flex; gap:8px; align-items:center;">
            <input type="hidden" name="form_action" value="assign">
            <label style="margin:0;">Assign to:</label>
            <select name="assigned_to">
                <option value="">-- Unassigned --</option>
                <?php foreach ($agents as $ag): ?>
                    <option value="<?= $ag['id'] ?>" <?= $ag['id']==$ticket['assigned_to']?'selected':'' ?>><?= e($ag['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn small" type="submit">Assign</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <h3 style="margin-top:0;">Conversation</h3>
    <div class="thread">
        <?php foreach ($replies as $r): ?>
            <div class="reply-item">
                <div class="meta"><strong><?= e($r['full_name']) ?></strong> &middot; <?= e($r['created_at']) ?></div>
                <div><?= nl2br(e($r['message'])) ?></div>
            </div>
        <?php endforeach; ?>
        <?php if (!$replies): ?><p style="color:#999;">No replies yet.</p><?php endif; ?>
    </div>

    <?php if ($canReply): ?>
        <form method="POST" style="margin-top:15px;">
            <input type="hidden" name="form_action" value="reply">
            <div class="form-group">
                <textarea name="message" rows="3" placeholder="Write a reply..." required></textarea>
            </div>
            <button class="btn" type="submit">Post Reply</button>
        </form>
    <?php endif; ?>
</div>

<a class="btn secondary" href="<?= BASE_URL ?>/tickets.php">&larr; Back to Tickets</a>
<?php include __DIR__ . '/includes/footer.php'; ?>
