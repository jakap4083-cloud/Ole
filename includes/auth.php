<?php
// Secure user registration and authentication backend engine

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';

function authenticate_user($login_input, $password) {
    $db = get_db_connection();
    
    // Check if input is username, email, or telephone
    $stmt = $db->prepare("SELECT id, username, email, phone, password, status FROM users WHERE username = ? OR email = ? OR phone = ? LIMIT 1");
    $stmt->execute([$login_input, $login_input, $login_input]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'error' => 'Akun tidak ditemukan. Periksa kembali username/email/no HP Anda.'];
    }
    
    if ($user['status'] === 'blocked') {
        return ['success' => false, 'error' => 'Akun Anda telah dinonaktifkan oleh administrator.'];
    }
    
    if ($user['status'] === 'suspended') {
        return ['success' => false, 'error' => 'Akun Anda sedang ditangguhkan sementara.'];
    }
    
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Kata sandi tidak sesuai.'];
    }
    
    // Create Session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    
    // Sync VIP and insert default profiles or levels if empty
    require_once __DIR__ . '/vip-helper.php';
    sync_user_vip_status($user['id']);
    
    // Log user status activity hook
    log_user_activity($user['id'], 'login', 'User successfully authenticated into device UI');
    
    return ['success' => true, 'user' => $user];
}

function register_user($username, $email, $phone, $password, $referrer_code) {
    $db = get_db_connection();
    
    // Check Unique Constraints
    // 1. Username
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Username sudah digunakan oleh orang lain.'];
    }
    
    // 2. Email
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Alamat email sudah terdaftar.'];
    }
    
    // 3. Phone
    $stmt = $db->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Nomor HP sudah terdaftar.'];
    }
    
    $referred_by_id = null;
    if (!empty($referrer_code)) {
        $stmt = $db->prepare("SELECT user_id FROM user_profiles WHERE referral_code = ? LIMIT 1");
        $stmt->execute([$referrer_code]);
        $profile = $stmt->fetch();
        if ($profile) {
            $referred_by_id = $profile['user_id'];
        } else {
            return ['success' => false, 'error' => 'Kode referral tidak valid.'];
        }
    }
    
    // Write Transact Safely
    $db->beginTransaction();
    try {
        // Create User account
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (username, email, phone, password, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$username, $email, $phone, $password_hash]);
        $new_user_id = $db->lastInsertId();
        
        // Generate new user custom referral code
        $new_ref = 'NOX' . strtoupper(substr(md5(uniqid($username, true)), 0, 7));
        
        // Create Profile
        $stmt = $db->prepare("INSERT INTO user_profiles (user_id, full_name, referral_code, referred_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$new_user_id, $username, $new_ref, $referred_by_id]);
        
        // Create Balance Accounts with Default Bonus Rp 15,000 as configured
        require_once __DIR__ . '/settings-helper.php';
        $pendaftaraan_bonus = get_setting('signup_bonus', 15000.00);
        
        $stmt = $db->prepare("INSERT INTO balance_accounts (user_id, main_balance, bonus_balance, profit_balance, commission_balance, locked_balance, total_profit) VALUES (?, 0.00, ?, 0.00, 0.00, 0.00, 0.00)");
        $stmt->execute([$new_user_id, $pendaftaraan_bonus]);
        
        // Write Initial Ledger for signup balance tracker
        require_once __DIR__ . '/ledger-helper.php';
        if ($pendaftaraan_bonus > 0) {
            write_ledger_transaction(
                $new_user_id,
                'signup_bonus',
                $pendaftaraan_bonus,
                'bonus_balance',
                'in',
                'Hadiah saldo pendaftaran akun baru NOXARA',
                'signup_' . $new_user_id
            );
        }
        
        // Create VIP Level mapping VIP 0
        $stmt = $db->prepare("INSERT INTO user_vip_status (user_id, vip_level) VALUES (?, 0)");
        $stmt->execute([$new_user_id]);
        
        // Setup Referral Tree link
        if ($referred_by_id) {
            // Find parent height
            $stmt = $db->prepare("SELECT level FROM referral_tree WHERE user_id = ? LIMIT 1");
            $stmt->execute([$referred_by_id]);
            $parent_level_data = $stmt->fetch();
            $parent_height = $parent_level_data ? (int)$parent_level_data['level'] : 0;
            $my_level = $parent_height + 1;
            
            $stmt = $db->prepare("INSERT INTO referral_tree (user_id, parent_id, level) VALUES (?, ?, ?)");
            $stmt->execute([$new_user_id, $referred_by_id, $my_level]);
        } else {
            $stmt = $db->prepare("INSERT INTO referral_tree (user_id, parent_id, level) VALUES (?, NULL, 1)");
            $stmt->execute([$new_user_id]);
        }
        
        $db->commit();
        return ['success' => true];
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Registration transact failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Terjadi kesalahan internal pendaftaran: ' . $e->getMessage()];
    }
}

function log_user_activity($user_id, $action, $notes = null) {
    try {
        $db = get_db_connection();
        $stmt = $db->prepare("INSERT INTO user_status_logs (user_id, action, notes) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $action, $notes]);
    } catch (Exception $e) {
        // silent
    }
}
