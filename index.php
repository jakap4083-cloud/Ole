<?php
// Main landing point router & security checkpoint filters

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';

// Safe redirector if maintenance mode is active
$maintenance_msg = is_maintenance_mode();
if ($maintenance_msg && !isset($_SESSION['admin_id'])) {
    header('Location: /pages/public/maintenance.php');
    exit();
}

// Redirect logged in user immediately to feed, or guest to login
if (isset($_SESSION['user_id'])) {
    header('Location: /pages/user/home.php');
} else {
    header('Location: /pages/auth/login.php');
}
exit();
