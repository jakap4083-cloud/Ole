<?php
// Strict Session Hardening
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    // In production force secure cookie if https
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

// Regenerate session id periodically or on privilege change to defend session hijacking
function regenerate_user_session() {
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Check authenticate
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['flash_error'] = 'Silakan login terlebih dahulu untuk mengakses halaman.';
        header('Location: /pages/auth/login.php');
        exit();
    }
    regenerate_user_session();
}

function require_admin() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: /pages/admin/login.php');
        exit();
    }
}
