<?php
// Secure vouchers authentication and claims engines

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ledger-helper.php';

function validate_and_apply_voucher($code, $user_id, $context = 'balance_claim', $transaction_amount = 0.00) {
    if (empty($code)) {
        return ['success' => false, 'error' => 'Kode voucher kosong.'];
    }
    
    $db = get_db_connection();
    
    // Check code in db
    $stmt = $db->prepare("SELECT * FROM vouchers WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    $voucher = $stmt->fetch();
    
    if (!$voucher) {
        return ['success' => false, 'error' => 'Kode voucher tidak valid.'];
    }
    
    if (!$voucher['is_active']) {
        return ['success' => false, 'error' => 'Voucher sudah dinonaktifkan oleh sistem.'];
    }
    
    if (strtotime($voucher['valid_until']) < time()) {
        return ['success' => false, 'error' => 'Masa berlaku voucher ini telah habis (expired).'];
    }
    
    if ($voucher['quota'] !== null && $voucher['used_count'] >= $voucher['quota']) {
        return ['success' => false, 'error' => 'Kuota penukaran voucher ini telah habis terpake.'];
    }
    
    // Check VIP requirement
    require_once __DIR__ . '/vip-helper.php';
    $vip_status = get_user_vip_details($user_id);
    if ((int)$vip_status['level_num'] < (int)$voucher['vip_level']) {
        return ['success' => false, 'error' => "Voucher tidak berlaku untuk level VIP kamu ({$vip_status['name']}). Perlu minimal VIP " . $voucher['vip_level']];
    }
    
    // Check Single Usage restrict
    $stmt = $db->prepare("SELECT id FROM voucher_usages WHERE voucher_id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$voucher['id'], $user_id]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Anda telah menukarkan voucher unik ini sebelumnya.'];
    }
    
    // Context mapping matching validation
    if ($context === 'product_purchase' && $voucher['type'] !== 'product_discount') {
        return ['success' => false, 'error' => 'Jenis voucher tidak cocok untuk pembelian produk.'];
    }
    
    if ($context === 'topup' && $voucher['type'] !== 'topup_bonus') {
        return ['success' => false, 'error' => 'Jenis voucher tidak cocok untuk bonus topup.'];
    }
    
    if ($context === 'balance_claim' && $voucher['type'] !== 'balance_claim') {
        return ['success' => false, 'error' => 'Voucher ini hanya berlaku untuk dipake pada diskon produk atau topup.'];
    }
    
    // Checks Minimum Transaction rule
    if ($transaction_amount < (float)$voucher['min_transaction']) {
        return ['success' => false, 'error' => 'Kombinasi transaksi belum memenuhi batas minimum klaim voucher ini.'];
    }
    
    // If it is simple balance claim, we process directly inside this thread
    if ($voucher['type'] === 'balance_claim') {
        $db->beginTransaction();
        try {
            // Apply ledger
            $claim_amount = (float)$voucher['flat_value'];
            $idempotency_key = "voucher_claim_{$voucher['id']}_{$user_id}";
            
            $success = write_ledger_transaction(
                $user_id,
                'voucher_claim',
                $claim_amount,
                'main_balance',
                'in',
                "Klaim voucher bonus tunai bebas pakai: " . $voucher['code'],
                $idempotency_key
            );
            
            if ($success) {
                // Record Usage
                $stmt = $db->prepare("INSERT INTO voucher_usages (voucher_id, user_id) VALUES (?, ?)");
                $stmt->execute([$voucher['id'], $user_id]);
                
                // Update counter
                $stmt_up = $db->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?");
                $stmt_up->execute([$voucher['id']]);
                
                $db->commit();
                return ['success' => true, 'amount' => $claim_amount, 'message' => 'Voucher berhasil diklaim! Saldo sebesar ' . format_currency($claim_amount) . ' telah ditambahkan ke Saldo Utama Anda.'];
            } else {
                $db->rollBack();
                return ['success' => false, 'error' => 'Gagal mencatat transaksi voucher.'];
            }
        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'error' => 'Kesalahan sistem penulisan: ' . $e->getMessage()];
        }
    }
    
    // For products or topups, return validation context so caller can subtract or add bonuses accordingly
    return ['success' => true, 'voucher' => $voucher];
}
