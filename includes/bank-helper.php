<?php
// Secure bank account linking and verification helpers

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function link_user_bank_account($user_id, $bank_name, $account_number, $account_name, $password_confirm) {
    $db = get_db_connection();
    
    // Verify password first as requested for security
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch();
    
    if (!password_verify($password_confirm, $u['password'])) {
         return ['success' => false, 'error' => 'Sandi konfirmasi akun Anda salah. Gagal menautkan bank.'];
    }
    
    try {
        $stmt_up = $db->prepare("INSERT INTO user_bank_accounts (user_id, bank_name, account_number, account_name) VALUES (?, ?, ?, ?)
                                 ON DUPLICATE KEY UPDATE bank_name = ?, account_number = ?, account_name = ?");
        $stmt_up->execute([$user_id, $bank_name, $account_number, $account_name, $bank_name, $account_number, $account_name]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

function get_user_bank_details($user_id) {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT bank_name, account_number, account_name FROM user_bank_accounts WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}
