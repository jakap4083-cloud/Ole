<?php
// Global Helper Functions (sanitization, formatting, and standard JSON output)

function sanitize_output($data) {
    if ($data === null) return '';
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function format_currency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}

function redirect_with_flash($url, $flash_type, $flash_message) {
    require_once __DIR__ . '/flash.php';
    set_flash_message($flash_type, $flash_message);
    header("Location: $url");
    exit();
}

function is_maintenance_mode() {
    require_once __DIR__ . '/db.php';
    $db = get_db_connection();
    try {
        $stmt = $db->query("SELECT is_active, message, whitelist_ips FROM maintenance_settings LIMIT 1");
        $setting = $stmt->fetch();
        if ($setting && $setting['is_active']) {
            $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $whitelist = array_map('trim', explode(',', $setting['whitelist_ips']));
            if (!in_array($user_ip, $whitelist)) {
                return $setting['message'];
            }
        }
    } catch (Exception $e) {
        // silent fail on setup phase
    }
    return false;
}

function write_system_log($cron_name, $status, $message, $run_time_seconds = 0.00) {
    require_once __DIR__ . '/db.php';
    $db = get_db_connection();
    try {
        $stmt = $db->prepare("INSERT INTO cron_logs (cron_name, status, message, run_time_seconds) VALUES (?, ?, ?, ?)");
        $stmt->execute([$cron_name, $status, $message, $run_time_seconds]);
    } catch (Exception $e) {
        // Fallback to local file logging
        $log_dir = __DIR__ . '/../storage/logs/';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $log_file = $log_dir . 'cron_fallback_errors.log';
        file_put_contents($log_file, date('[Y-m-d H:i:s]') . " [$cron_name] $status - $message ($run_time_seconds s)" . PHP_EOL, FILE_APPEND);
    }
}
