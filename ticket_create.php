<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
requirePermission($conn, 'tickets_create');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject  = trim($_POST['subject'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'General';
    $priority = $_POST['priority'] ?? 'Medium';

    $validCategories = ['Technical', 'Billing', 'General', 'Other'];
    $validPriorities = ['Low', 'Medium', 'High', 'Urgent'];

    if ($subject === '' || $desc === '') {
        $error = 'Subject and description are required.';
    } elseif (!in_array($category, $validCategories, true) || !in_array($priority, $validPriorities, true)) {
        $error = 'Invalid category or priority.';
    } else {
        $sql = "INSERT INTO tickets (subject, description, category, priority, status, created_by)
                VALUES (?, ?, ?, ?, 'open', ?)";
        $stmt = mysqli_prepare($conn, $sql);
        $uid = (int)$_SESSION['user_id'];
        mysqli_stmt_bind_param($stmt, 'ssssi', $subject, $desc, $category, $priority, $uid);
        mysqli_stmt_execute($stmt);
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        flash('success', 'Ticket #' . $newId . ' raised successfully.');
        header('Location: ' . BASE_URL . '/tickets.php');
        exit;
    }
}

include __DIR__ . '/includes/header.php';
?>
<h2 class="page-title">Raise a New Ticket</h2>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<div class="card" style="max-width:550px;">
    <form method="POST">
        <div class="form-group">
            <label>Subject</label>
            <input type="text" name="subject" required value="<?= e($_POST['subject'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="5" required><?= e($_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Category</label>
            <select name="category">
                <option value="Technical">Technical</option>
                <option value="Billing">Billing</option>
                <option value="General" selected>General</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="form-group">
            <label>Priority</label>
            <select name="priority">
                <option value="Low">Low</option>
                <option value="Medium" selected>Medium</option>
                <option value="High">High</option>
                <option value="Urgent">Urgent</option>
            </select>
        </div>
        <button class="btn" type="submit">Submit Ticket</button>
        <a class="btn secondary" href="<?= BASE_URL ?>/tickets.php">Cancel</a>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
