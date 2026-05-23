<?php
// Secure AJAX handler for Login actions

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/captcha.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Safe rate limiting simulation using attempts table
function check_login_rate_limit($ip, $username) {
     $db = get_db_connection();
     $stmt = $db->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ? AND username = ? LIMIT 1");
     $stmt->execute([$ip, $username]);
     $row = $stmt->fetch();
     
     if ($row && $row['attempts'] >= 5 && (time() - strtotime($row['last_attempt'])) < 60) {
          return false; // locked for 1 minute
     }
     return true;
}

function update_login_rate_limit($ip, $username, $success) {
     $db = get_db_connection();
     if ($success) {
          $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND username = ?");
          $stmt->execute([$ip, $username]);
     } else {
          $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, username, attempts) VALUES (?, ?, 1)
                                ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()");
          $stmt->execute([$ip, $username]);
     }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
     send_json_response(['success' => false, 'error' => 'Metode request tidak diizinkan.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf = $input['csrf_token'] ?? '';
$captcha = $input['captcha_answer'] ?? '';
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';
$agreement = $input['terms_agree'] ?? false;

// 1. Verify CSRF
if (!verify_csrf_token($csrf)) {
     send_json_response(['success' => false, 'error' => 'Validasi keamanan CSRF gagal. Silakan muat ulang halaman.'], 400);
}

// 2. Verify Terms Agreement
if (!$agreement) {
     send_json_response(['success' => false, 'error' => 'Anda harus menyetujui Syarat & Ketentuan dan Kebijakan Privasi.'], 400);
}

// 3. Verify Captcha
if (!verify_captcha($captcha)) {
     send_json_response(['success' => false, 'error' => 'Jawaban Captcha matematika salah.'], 400);
}

// 4. Rate limiting check
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
if (!check_login_rate_limit($ip, $username)) {
     send_json_response(['success' => false, 'error' => 'Batas percobaan login terlampaui. Akun terkunci sementara selama 1 menit.'], 429);
}

// 5. Auth action
$res = authenticate_user($username, $password);
update_login_rate_limit($ip, $username, $res['success']);

if ($res['success']) {
     send_json_response(['success' => true]);
} else {
     send_json_response(['success' => false, 'error' => $res['error']]);
}
