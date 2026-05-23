<?php
// Secure financial purchases database transaction logs

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ledger-helper.php';
require_once __DIR__ . '/voucher-helper.php';

function purchase_cloud_miner_product($user_id, $product_id, $voucher_code = '') {
    $db = get_db_connection();
    
    // Fetch product specs
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        return ['success' => false, 'error' => 'Produk mesin penambang tidak ditemukan.'];
    }
    
    if (!$product['is_active']) {
        return ['success' => false, 'error' => 'Mesin tambang ini sedang dinonaktifkan sementara.'];
    }
    
    if ($product['stock'] <= 0) {
        return ['success' => false, 'error' => 'Stok produk ini sedang habis. Silakan hubungi CS.'];
    }
    
    $price = (float)$product['price'];
    $final_price = $price;
    $voucher_id = null;
    
    // Process voucher discount if any
    if (!empty($voucher_code)) {
         $val = validate_and_apply_voucher($voucher_code, $user_id, 'product_purchase', $price);
         if (!$val['success']) {
              return ['success' => false, 'error' => $val['error']];
         }
         
         $voucher = $val['voucher'];
         $voucher_id = $voucher['id'];
         
         // Apply percent discount with max Cap ceiling limits
         $disc_percent = (float)$voucher['value_percent'];
         $disc_val = ($price * $disc_percent) / 100;
         
         $max_disc = (float)$voucher['max_discount'];
         if ($max_disc > 0 && $disc_val > $max_disc) {
              $disc_val = $max_disc;
         }
         
         $final_price = max(0.00, $price - $disc_val);
    }
    
    // Acquire user balances info
    $balances = get_user_balances($user_id);
    $main = (float)$balances['main_balance'];
    $bonus = (float)$balances['bonus_balance'];
    
    if (($main + $bonus) < $final_price) {
        return ['success' => false, 'error' => 'Saldo gabungan Anda tidak mencukupi untuk melakukan pembelian mesin seharga ini. Tekan tombol Isi Ulang untuk melanjutkan.'];
    }
    
    // Calculate balanced extraction
    // System uses bonus balance first, then main balance as requested
    $bonus_deduction = 0.00;
    $main_deduction = 0.00;
    
    if ($bonus >= $final_price) {
         $bonus_deduction = $final_price;
    } else {
         $bonus_deduction = $bonus;
         $main_deduction = $final_price - $bonus;
    }
    
    $today_format = date('YmdHis');
    $idempotency = "product_purchase_{$product_id}_{$user_id}_{$today_format}";
    
    // Wrap database inside SQL transaction blocks (atomic and fail-safe)
    $db->beginTransaction();
    try {
        // Double check stock first inside lock if possible (omitted for multi-environment aaPanel simple compatibility)
        // 1. Create active user_product subscription
        $duration = (int)$product['duration_days'];
        $active_until = date('Y-m-d H:i:s', time() + ($duration * 24 * 3600));
        
        $stmt_up = $db->prepare("INSERT INTO user_products (user_id, product_id, price_paid, profit_per_day, active_until, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt_up->execute([$user_id, $product_id, $final_price, $product['profit_per_day'], $active_until]);
        $user_product_id = $db->lastInsertId();
        
        // 2. Log purchase detail reference
        $stmt_pur = $db->prepare("INSERT INTO product_purchases (user_id, product_id, user_product_id, amount_paid, bonus_amount_used, main_amount_used, voucher_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_pur->execute([$user_id, $product_id, $user_product_id, $final_price, $bonus_deduction, $main_deduction, $voucher_id]);
        $purchase_id = $db->lastInsertId();
        
        // 3. Write Ledger Deductions
        if ($bonus_deduction > 0) {
             write_ledger_transaction(
                 $user_id,
                 'purchase',
                 $bonus_deduction,
                 'bonus_balance',
                 'out',
                 "Pembelian mesin tambang #" . $user_product_id . " seharga " . format_currency($final_price) . " (porsi Saldo Bonus)",
                 "purchase_bonus_" . $purchase_id
             );
        }
        
        if ($main_deduction > 0) {
             write_ledger_transaction(
                 $user_id,
                 'purchase',
                 $main_deduction,
                 'main_balance',
                 'out',
                 "Pembelian mesin tambang #" . $user_product_id . " seharga " . format_currency($final_price) . " (porsi Saldo Utama)",
                 "purchase_main_" . $purchase_id
             );
        }
        
        // 4. Decrement Stock
        $stmt_st = $db->prepare("UPDATE products SET stock = stock - 1 WHERE id = ?");
        $stmt_st->execute([$product_id]);
        
        // 5. Record voucher usage in tables if applicable
        if ($voucher_id) {
             $stmt_us = $db->prepare("INSERT INTO voucher_usages (voucher_id, user_id) VALUES (?, ?)");
             $stmt_us->execute([$voucher_id, $user_id]);
             
             $stmt_vc = $db->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?");
             $stmt_vc->execute([$voucher_id]);
        }
        
        // 6. Direct notification broadcast
        $stmt_not = $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Pembelian Miner Berhasil!', ?)");
        $msg = "Mesin Cloud Miner " . $product['name'] . " berhasil diaktifkan dengan profit harian " . format_currency($product['profit_per_day']) . " selama " . $duration . " hari. Ayo masuk halaman mining dan kumpulkan profit harian Anda.";
        $stmt_not->execute([$user_id, $msg]);
        
        // 7. Fire referral commission logic instantly (idempotency key inside functions prevents double allocations)
        require_once __DIR__ . '/referral-helper.php';
        process_product_referral_commission($purchase_id);
        
        $db->commit();
        
        return ['success' => true, 'user_product_id' => $user_product_id];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'error' => 'Kesalahan database selama pemrosesan transaksi: ' . $e->getMessage()];
    }
}
