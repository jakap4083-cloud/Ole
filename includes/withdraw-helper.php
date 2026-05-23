<?php
// Secure withdrawal request processing engine

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ledger-helper.php';
require_once __DIR__ . '/vip-helper.php';

function check_withdrawal_eligibility($user_id, $amount) {
    if ($amount <= 0) {
        return ['allowed' => false, 'error' => 'Jumlah nominal pengajuan tidak valid.'];
    }
    
    // Check if user is blocked or frozen
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch();
    if ($u['status'] !== 'active') {
        return ['allowed' => false, 'error' => 'Akun Anda sedang ditangguhkan. Tidak diizinkan melakukan penarikan.'];
    }
    
    // Check User Freeze Status setting
    $stmt = $db->prepare("SELECT freeze_type FROM user_freezes WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $freeze = $stmt->fetch();
    if ($freeze) {
        if (in_array($freeze['freeze_type'], ['all', 'main_balance', 'withdraw_only'])) {
            return ['allowed' => false, 'error' => 'Fitur penarikan dana akun Anda sedang dibekukan oleh administrator. Hubungi Customer Service.'];
        }
    }
    
    // Check linked Bank Accounts
    $stmt = $db->prepare("SELECT * FROM user_bank_accounts WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $bank = $stmt->fetch();
    if (!$bank) {
        return ['allowed' => false, 'redirect' => '/pages/user/bank.php', 'error' => 'Tambahkan akun bank terlebih dahulu sebelum melakukan penarikan.'];
    }
    
    // Check linked PIN numbers
    $stmt = $db->prepare("SELECT * FROM user_pins WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $pin = $stmt->fetch();
    if (!$pin) {
        return ['allowed' => false, 'redirect' => '/pages/user/pin.php', 'error' => 'Buat PIN transaksi terlebih dahulu sebelum melakukan penarikan.'];
    }
    
    // Check VIP requirement details (minimum requirements and custom fee)
    $vip = get_user_vip_details($user_id);
    
    if ($amount < (float)$vip['min_withdrawal']) {
        return ['allowed' => false, 'error' => 'Batas minimal penarikan dana untuk tingkatan Level Anda (' . $vip['name'] . ') adalah ' . format_currency($vip['min_withdrawal'])];
    }
    
    // Check user main balances
    $balances = get_user_balances($user_id);
    if ((float)$balances['main_balance'] < $amount) {
        return ['allowed' => false, 'error' => 'Saldo Utama Anda saat ini tidak mencukupi untuk melakukan penarikan sebesar ini. Saldo bonus dan komisi harus dipindahkan/diinvestasikan ke utama terlebih dahulu.'];
    }
    
    return [
        'allowed' => true,
        'vip' => $vip,
        'bank' => $bank,
        'balances' => $balances
    ];
}

function process_withdrawal_request($user_id, $amount, $pin_input) {
    if (strlen($pin_input) !== 6) {
        return ['success' => false, 'error' => 'Format PIN transaksi salah. PIN wajib 6 digit angka angka.'];
    }
    
    $check = check_withdrawal_eligibility($user_id, $amount);
    if (!$check['allowed']) {
         if (isset($check['redirect'])) {
             return ['success' => false, 'redirect' => $check['redirect'], 'error' => $check['error']];
         }
         return ['success' => false, 'error' => $check['error']];
    }
    
    $db = get_db_connection();
    
    // Verify PIN entry
    $stmt = $db->prepare("SELECT pin_hash FROM user_pins WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $p = $stmt->fetch();
    
    if (!password_verify($pin_input, $p['pin_hash'])) {
        return ['success' => false, 'error' => 'PIN transaksi yang Anda masukkan salah.'];
    }
    
    $vip = $check['vip'];
    $bank = $check['bank'];
    
    // Calculate fee
    $fee_percent = (float)$vip['withdrawn_fee_percent'];
    $fee = round(($amount * $fee_percent) / 100, 2);
    $net = $amount - $fee;
    
    $today_format = date('YmdHis');
    $idempotency = "withdraw_req_{$user_id}_{$amount}_{$today_format}";
    
    // Wrap safely inside database SQL transaction (High protection)
    $db->beginTransaction();
    try {
        // Create withdrawal transaction
        $stmt_w = $db->prepare("INSERT INTO withdrawals (user_id, amount, fee_amount, net_amount, bank_name, account_number, account_name, status, idempotency_key) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt_w->execute([$user_id, $amount, $fee, $net, $bank['bank_name'], $bank['account_number'], $bank['account_name'], $idempotency]);
        $withdraw_id = $db->lastInsertId();
        
        // Ledger entry: Move main balance to locked balance
        write_ledger_transaction(
            $user_id,
            'withdrawal_request',
            $amount,
            'main_balance',
            'out',
            "Pengajuan penarikan dana penarikan harian #" . $withdraw_id . " dikunci sementara",
            "wd_main_out_" . $withdraw_id
        );
        
        write_ledger_transaction(
            $user_id,
            'withdrawal_request',
            $amount,
            'locked_balance',
            'in',
            "Saldo dikunci penarikan #" . $withdraw_id,
            "wd_locked_in_" . $withdraw_id
        );
        
        $db->commit();
        return ['success' => true, 'withdraw_id' => $withdraw_id, 'net' => $net];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'error' => 'Gagal memproses penarikan: ' . $e->getMessage()];
    }
}
