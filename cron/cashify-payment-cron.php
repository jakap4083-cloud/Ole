<?php
// Poll status of all pending Cashify payments. Correct delays.
// Runs every 5 minutes in aaPanel cron.
// Run: php /www/wwwroot/noxara.page/cron/cashify-payment-cron.php

$start_time = microtime(true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cashify-helper.php';

try {
    $db = get_db_connection();
    
    // Select all pending topups that hasn't expired yet
    $stmt = $db->prepare("SELECT * FROM topups WHERE status = 'pending' AND expired_at > NOW() LIMIT 20");
    $stmt->execute();
    $pending_tx = $stmt->fetchAll();
    
    $paid_count = 0;
    $cancel_count = 0;
    
    foreach ($pending_tx as $tx) {
         $topup_id = (int)$tx['id'];
         $cashify_tx_id = $tx['transaction_id_cashify'];
         
         if (empty($cashify_tx_id)) continue;
         
         // Direct lookup
         $chk = check_cashify_payment_status($cashify_tx_id);
         
         if ($chk['status'] === 'paid' || $chk['status'] === 'success') {
              $res = complete_user_topup($topup_id, $chk['status'], $cashify_tx_id);
              if ($res['success']) {
                   $paid_count++;
              }
         } else if ($chk['status'] === 'cancel') {
              $stmt_up = $db->prepare("UPDATE topups SET status = 'cancel', updated_at = NOW() WHERE id = ?");
              $stmt_up->execute([$topup_id]);
              $cancel_count++;
         }
    }
    
    // Auto flag expired items past threshold
    $stmt_expire = $db->prepare("UPDATE topups SET status = 'expired' WHERE status = 'pending' AND expired_at <= NOW()");
    $stmt_expire->execute();
    $expired_count = $stmt_expire->rowCount();
    
    $elapsed = microtime(true) - $start_time;
    require_once __DIR__ . '/../includes/helpers.php';
    write_system_log('cashify-payment-cron', 'success', "Cron Cashify selesai: $paid_count sukses dibayar, $cancel_count dibatalkan, $expired_count kedaluwarsa.", $elapsed);
    echo "Cashify status sync complete. Approved: $paid_count, Cancelled: $cancel_count, Expired: $expired_count.\n";
    
} catch (Exception $e) {
    $elapsed = microtime(true) - $start_time;
    require_once __DIR__ . '/../includes/helpers.php';
    write_system_log('cashify-payment-cron', 'failed', "Cron Cashify gagal: " . $e->getMessage(), $elapsed);
    echo "Cashify cron crash: " . $e->getMessage() . "\n";
}
