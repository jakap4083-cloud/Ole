<?php
// Secure PIN Transaction creation and ganti manager helper

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function setup_user_pin($user_id, $pin_input, $password_confirm) {
    if (strlen($pin_input) !== 6 || !is_numeric($pin_input)) {
         return ['success' => false, 'error' => 'PIN harus berupa 6 digit angka angka numeric.'];
    }
    
    $db = get_db_connection();
    
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch();
    
    if (!password_verify($password_confirm, $u['password'])) {
         return ['success' => false, 'error' => 'Sandi akun salah. Gagal membuat PIN.'];
    }
    
    try {
        $pin_hash = password_hash($pin_input, PASSWORD_BCRYPT);
        $stmt_pin = $db->prepare("INSERT INTO user_pins (user_id, pin_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE pin_hash = ?");
        $stmt_pin->execute([$user_id, $pin_hash, $pin_hash]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function verify_user_transaction_pin($user_id, $pin_input) {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT pin_hash FROM user_pins WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $p = $stmt->fetch();
    
    if (!$p) return false;
    return password_verify($pin_input, $p['pin_hash']);
}

function change_user_pin($user_id, $old_pin, $new_pin, $password_confirm) {
    if (strlen($new_pin) !== 6 || !is_numeric($new_pin)) {
         return ['success' => false, 'error' => 'PIN baru harus berupa 6 digit angka.'];
    }
    
    $db = get_db_connection();
    
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch();
    
    if (!password_verify($password_confirm, $u['password'])) {
         return ['success' => false, 'error' => 'Sandi akun salah.'];
    }
    
    // Verify old PIN
    $stmt_p = $db->prepare("SELECT pin_hash FROM user_pins WHERE user_id = ? LIMIT 1");
    $stmt_p->execute([$user_id]);
    $p = $stmt_p->fetch();
    
    if (!$p || !password_verify($old_pin, $p['pin_hash'])) {
         return ['success' => false, 'error' => 'PIN lama yang Anda masukkan tidak cocok.'];
    }
    
    try {
        $new_hash = password_hash($new_pin, PASSWORD_BCRYPT);
        $stmt_up = $db->prepare("UPDATE user_pins SET pin_hash = ? WHERE user_id = ?");
        $stmt_up->execute([$new_hash, $user_id]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}
