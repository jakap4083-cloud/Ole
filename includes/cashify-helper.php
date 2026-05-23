<?php
// Cashify QRIS Automated V2 Payment API Engine

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings-helper.php';

function get_cashify_config() {
    $db = get_db_connection();
    $stmt = $db->query("SELECT * FROM cashify_settings LIMIT 1");
    $settings = $stmt->fetch();
    
    if (!$settings) {
         // return defaults if db is blank
         return [
             'base_url' => 'https://cashify.my.id',
             'api_version' => 'v2',
             'qr_id' => '1b935c41-bf43-4075-8f57-56b6cbfa2d07',
             'license_key' => 'cashify_261885e5c5f830e68f929de05e3bfdf72e118d859edc5419472f79a813eed3ea',
             'package_ids' => '["com.orderkuota.app"]',
             'qr_type' => 'static',
             'payment_method' => 'qris',
             'use_qris' => 1,
             'use_unique_code' => 1,
             'expired_minutes' => 15,
             'polling_interval' => 5,
             'max_polling_attempts' => 180
         ];
    }
    return $settings;
}

function request_cashify_qris($amount) {
    if ($amount <= 0) {
        return ['success' => false, 'error' => 'Jumlah topup tidak valid.'];
    }
    
    $config = get_cashify_config();
    $url = rtrim($config['base_url'], '/') . '/api/generate/v2/qris';
    
    $package_ids = json_decode($config['package_ids'] ?? '["com.orderkuota.app"]', true);
    if (!is_array($package_ids)) {
         $package_ids = ["com.orderkuota.app"];
    }
    
    $req_body = [
        'qr_id' => $config['qr_id'],
        'amount' => (int)$amount,
        'useUniqueCode' => (bool)$config['use_unique_code'],
        'packageIds' => $package_ids,
        'expiredInMinutes' => (int)$config['expired_minutes'],
        'qrType' => $config['qr_type'],
        'paymentMethod' => $config['payment_method'],
        'useQris' => (bool)$config['use_qris']
    ];
    
    // Fire REST request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($req_body));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-license-key: ' . $config['license_key'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // avoid SSL VPS cert trust issues
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $curl_err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false) {
        return ['success' => false, 'error' => 'Kesalahan koneksi ke server gateway Cashify: ' . $curl_err];
    }
    
    $result = json_decode($response, true);
    
    if ($http_code !== 200 && $http_code !== 201) {
        $msg = $result['message'] ?? $result['error'] ?? 'API Error code: ' . $http_code;
        return ['success' => false, 'error' => 'Gateway Cashify menolak permintaan: ' . $msg];
    }
    
    if (!isset($result['status']) || ($result['status'] !== 'success' && $result['status'] !== 'pending')) {
        return ['success' => false, 'error' => $result['message'] ?? 'Status transaksi tidak berhasil dibentuk.'];
    }
    
    // Success parse
    // Cashify v2 standard response structure maps data blocks
    $data = $result['data'] ?? [];
    
    return [
        'success' => true,
        'transactionId' => $data['transactionId'] ?? $result['transactionId'] ?? null,
        'qr_string' => $data['qr_string'] ?? $result['qr_string'] ?? '',
        'originalAmount' => $data['amount'] ?? $data['originalAmount'] ?? $amount,
        'uniqueNominal' => $data['unique_nominal'] ?? $data['uniqueNominal'] ?? 0,
        'totalAmount' => $data['total_amount'] ?? $data['totalAmount'] ?? $amount,
        'expired_at' => date('Y-m-d H:i:s', time() + ((int)$config['expired_minutes'] * 60))
    ];
}

function check_cashify_payment_status($provider_transaction_id) {
    $config = get_cashify_config();
    $url = rtrim($config['base_url'], '/') . '/api/generate/check-status';
    
    $req_body = [
        'transactionId' => $provider_transaction_id
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($req_body));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-license-key: ' . $config['license_key'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false) {
        return ['status' => 'pending', 'error' => 'Gagal terhubung status server Cashify.'];
    }
    
    $result = json_decode($response, true);
    
    // Standard response yields status: 'paid', 'success', 'cancel', 'expired', 'pending'
    $status = $result['status'] ?? $result['data']['status'] ?? 'pending';
    return ['status' => $status, 'raw' => $result];
}

/**
 * Executes a payment complete hook (High protection and idempotent)
 */
function complete_user_topup($topup_id, $provider_status, $provider_transaction_id) {
    if (!in_array($provider_status, ['paid', 'success'])) {
        return ['success' => false, 'error' => "Status topup bukan pelunasan."];
    }
    
    $db = get_db_connection();
    
    $stmt = $db->prepare("SELECT * FROM topups WHERE id = ? LIMIT 1");
    $stmt->execute([$topup_id]);
    $topup = $stmt->fetch();
    
    if (!$topup) {
        return ['success' => false, 'error' => "Data transaksi ID #$topup_id tidak ditemukan."];
    }
    
    if ($topup['status'] === 'success' || $topup['status'] === 'paid') {
        // Already processed
        return ['success' => true, 'message' => "Topup sudah diproses sebelumnya."];
    }
    
    $user_id = $topup['user_id'];
    $original_amount = (float)$topup['original_amount'];
    
    // Idempotency key signature matching requirements
    $idempotency_key = "cashify_topup_paid_{$topup_id}_{$provider_transaction_id}";
    
    $db->beginTransaction();
    try {
        // Update topup record status
        $stmt_up = $db->prepare("UPDATE topups SET status = 'success', transaction_id_cashify = ?, updated_at = NOW() WHERE id = ?");
        $stmt_up->execute([$provider_transaction_id, $topup_id]);
        
        // Write ledger for the main balance credit
        $ledger_success = write_ledger_transaction(
            $user_id,
            'deposit',
            $original_amount,
            'main_balance',
            'in',
            "Setoran dana / deposit otomatis via Cashify QRIS sebesar " . format_currency($original_amount),
            $idempotency_key
        );
        
        if ($ledger_success) {
            // Check if voucher topup was linked
            if ($topup['voucher_id']) {
                 $stmt_v = $db->prepare("SELECT * FROM vouchers WHERE id = ? LIMIT 1");
                 $stmt_v->execute([$topup['voucher_id']]);
                 $voucher = $stmt_v->fetch();
                 if ($voucher && $voucher['type'] === 'topup_bonus') {
                      $bonus_percent = (float)$voucher['value_percent'];
                      $bonus_amount = round(($original_amount * $bonus_percent) / 100, 2);
                      
                      if ($bonus_amount > 0) {
                          $idempotency_v = "voucher_topup_bonus_{$topup_id}_{$voucher['id']}";
                          write_ledger_transaction(
                              $user_id,
                              'deposit',
                              $bonus_amount,
                              'main_balance', // voucher topup_bonus adds extra directly to main balance
                              'in',
                              "Bonus topup deposit voucher [{$voucher['code']}] sebesar " . $bonus_percent . "%",
                              $idempotency_v
                          );
                      }
                      
                      // Increment usage
                      $stmt_record = $db->prepare("INSERT INTO voucher_usages (voucher_id, user_id) VALUES (?, ?)");
                      $stmt_record->execute([$voucher['id'], $user_id]);
                      
                      $stmt_count = $db->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?");
                      $stmt_count->execute([$voucher['id']]);
                 }
            }
            
            // Sync VIP ranks immediately depending on total APPROVED topups
            require_once __DIR__ . '/vip-helper.php';
            sync_user_vip_status($user_id);
            
            // Sync referral rewards commission flow
            require_once __DIR__ . '/referral-helper.php';
            process_topup_referral_commission($topup_id);
            
            $db->commit();
            return ['success' => true];
        } else {
            $db->rollBack();
            return ['success' => false, 'error' => "Idempotency trigger block."];
        }
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'error' => 'Database error during topup completion: ' . $e->getMessage()];
    }
}
