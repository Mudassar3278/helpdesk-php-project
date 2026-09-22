<?php
// ================================================================
// Global configuration file - included at the top of every page
// Uses mysqli (core PHP), not PDO.
// ================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// IMPORTANT: Change this to match your project folder name inside htdocs/www
// Example: if the folder is C:/xampp/htdocs/helpdesk-php-project -> keep as below
// If you put it directly in htdocs (no subfolder) -> set to ''
define('BASE_URL', '/helpdesk-php-project');

// ---------- Database settings ----------
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'helpdesk_system');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
