<?php
// Session-based Flash Messaging wrapper helper

function set_flash_message($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION["flash_{$type}"] = $message;
}

function get_flash_message($type) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $key = "flash_{$type}";
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

function display_flash_alerts() {
    $success = get_flash_message('success');
    $error = get_flash_message('error');
    $warning = get_flash_message('warning');
    
    $html = '';
    if ($success) {
        $html .= '<div class="alert-box success bg-emerald-500/10 border border-emerald-500 text-emerald-400 p-3 rounded-lg text-sm mb-4">' . htmlspecialchars($success) . '</div>';
    }
    if ($error) {
        $html .= '<div class="alert-box error bg-rose-500/10 border border-rose-500 text-rose-400 p-3 rounded-lg text-sm mb-4">' . htmlspecialchars($error) . '</div>';
    }
    if ($warning) {
        $html .= '<div class="alert-box warning bg-amber-500/10 border border-amber-500 text-amber-400 p-3 rounded-lg text-sm mb-4">' . htmlspecialchars($warning) . '</div>';
    }
    return $html;
}
