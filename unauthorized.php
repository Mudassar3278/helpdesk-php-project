<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
include __DIR__ . '/includes/header.php';
?>
<div class="card" style="text-align:center;">
    <h2 class="page-title">🚫 Access Denied</h2>
    <p>Your role (<strong><?= e($_SESSION['user_role']) ?></strong>) does not have permission to view that page.</p>
    <a class="btn" href="<?= BASE_URL ?>/dashboard.php">Go to my dashboard</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
