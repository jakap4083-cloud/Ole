<?php
// Auto process completed mining Sessions periodically. Target runs hourly/daily.
// Run: php /www/wwwroot/noxara.page/cron/mining-cron.php

$start_time = microtime(true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ledger-helper.php';

try {
    $db = get_db_connection();
    
    // 1. Fetch running mining sessions whose ends_at is reached
    $stmt = $db->prepare("SELECT ms.*, up.user_id FROM mining_sessions ms 
                          JOIN user_products up ON ms.user_product_id = up.id 
                          WHERE ms.status = 'running' AND ms.ends_at <= NOW()");
    $stmt->execute();
    $sessions = $stmt->fetchAll();
    
    $success_count = 0;
    foreach ($sessions as $session) {
         $session_id = (int)$session['id'];
         $user_id = (int)$session['user_id'];
         $profit_amount = (float)$session['profit_amount'];
         
         // Idempotency check matching payment criteria
         $ledger_key = "mining_profit_cron_{$session_id}";
         
         $db->beginTransaction();
         try {
             // Mark completed/claimed
             $stmt_up = $db->prepare("UPDATE mining_sessions SET status = 'claimed' WHERE id = ?");
             $stmt_up->execute([$session_id]);
             
             // Ledger Transaction
             $success = write_ledger_transaction(
                 $user_id,
                 'mining_profit',
                 $profit_amount,
                 'profit_balance',
                 'in',
                 "Hasil cloud mining harian otomatis dari sesi tambang #" . $session_id,
                 $ledger_key
             );
             
             if ($success) {
                  // Log detailed record
                  $stmt_log = $db->prepare("INSERT INTO mining_profit_logs (user_id, session_id, amount) VALUES (?, ?, ?)");
                  $stmt_log->execute([$user_id, $session_id, $profit_amount]);
                  
                  // Check team transaction referral bonus allocations
                  // Retrieve purchase data
                  $stmt_pur = $db->prepare("SELECT id FROM product_purchases WHERE user_product_id = ? LIMIT 1");
                  $stmt_pur->execute([$session['user_product_id']]);
                  $pur = $stmt_pur->fetch();
                  if ($pur) {
                       require_once __DIR__ . '/../includes/referral-helper.php';
                       process_product_referral_commission($pur['id']);
                  }
                  
                  $success_count++;
             }
             $db->commit();
         } catch (Exception $e) {
             $db->rollBack();
             error_log("Cron mining single-item failed on session $session_id: " . $e->getMessage());
         }
    }
    
    $elapsed = microtime(true) - $start_time;
    require_once __DIR__ . '/../includes/helpers.php';
    write_system_log('mining-cron', 'success', "Cron penambangan harian sukses memproses $success_count sesi mining.", $elapsed);
    echo "Successfully updated $success_count mining sessions.\n";
    
} catch (Exception $e) {
    $elapsed = microtime(true) - $start_time;
    require_once __DIR__ . '/../includes/helpers.php';
    write_system_log('mining-cron', 'failed', "Kegagalan eksekusi cron: " . $e->getMessage(), $elapsed);
    echo "Mining cron failed: " . $e->getMessage() . "\n";
}
