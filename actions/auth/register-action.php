<?php
// Secure AJAX handler for register actions

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/captcha.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/settings-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
     send_json_response(['success' => false, 'error' => 'Metode request tidak diizinkan.'], 405);
}

// Check if registration feature is enabled in Admin settings
if (!is_feature_enabled('register')) {
     send_json_response(['success' => false, 'error' => 'Fitur pendaftaran user baru sedang ditutup sementara oleh administrator.'], 403);
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf = $input['csrf_token'] ?? '';
$captcha = $input['captcha_answer'] ?? '';
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$password = $input['password'] ?? '';
$password_confirmation = $input['password_confirmation'] ?? '';
$referrer_code = trim($input['referrer_code'] ?? '');
$agreement = $input['terms_agree'] ?? false;

// 1. CSRF
if (!verify_csrf_token($csrf)) {
     send_json_response(['success' => false, 'error' => 'Validasi keamanan CSRF gagal. Silakan muat ulang halaman.'], 400);
}

// 2. Terms Agreement
if (!$agreement) {
     send_json_response(['success' => false, 'error' => 'Anda harus menyetujui Syarat & Ketentuan dan Kebijakan Privasi.'], 400);
}

// 3. Math Captcha Validation
if (!verify_captcha($captcha)) {
     send_json_response(['success' => false, 'error' => 'Jawaban Captcha matematika salah.'], 400);
}

// 4. Form Rules
if (strlen($username) < 4 || strlen($username) > 20 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
     send_json_response(['success' => false, 'error' => 'Username harus berupa 4-20 karakter alfanumerik (huruf, angka, garis bawah).'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
     send_json_response(['success' => false, 'error' => 'Format alamat email tidak valid.'], 400);
}

if (strlen($phone) < 9 || strlen($phone) > 15 || !preg_match('/^[0-9]+$/', $phone)) {
     send_json_response(['success' => false, 'error' => 'Format nomor HP tidak valid. Hanya menerima angka 9-15 digit.'], 400);
}

if (strlen($password) < 8) {
     send_json_response(['success' => false, 'error' => 'Kata sandi minimal harus terdiri dari 8 karakter.'], 400);
}

if ($password !== $password_confirmation) {
     send_json_response(['success' => false, 'error' => 'Kata sandi konfirmasi tidak sesuai.'], 400);
}

// 5. Fire core processor
$res = register_user($username, $email, $phone, $password, $referrer_code);

if ($res['success']) {
     send_json_response(['success' => true]);
} else {
     send_json_response(['success' => false, 'error' => $res['error']]);
}
