<?php
// Secure Administrator Login action handler

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
     send_json_response(['success' => false, 'error' => 'Metode tidak diperbolehkan.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$csrf = $input['csrf_token'] ?? '';
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

// 1. CSRF
if (!verify_csrf_token($csrf)) {
     send_json_response(['success' => false, 'error' => 'Validasi CSRF gagal.'], 400);
}

if (empty($username) || empty($password)) {
     send_json_response(['success' => false, 'error' => 'Username dan sandi wajib diisi.'], 400);
}

$db = get_db_connection();
$stmt = $db->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password'])) {
     send_json_response(['success' => false, 'error' => 'Kredensial login administrator tidak valid.']);
}

// Session allocation
$_SESSION['admin_id'] = $admin['id'];
$_SESSION['admin_username'] = $admin['username'];
$_SESSION['admin_role'] = $admin['role'];

send_json_response(['success' => true]);
