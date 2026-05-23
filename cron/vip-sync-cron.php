<?php
// Synchronizes any out of sync VIP users based on topup approved.
// Runs daily.
// Run: php /www/wwwroot/noxara.page/cron/vip-sync-cron.php

$start_time = microtime(true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/vip-helper.php';

try {
    $db = get_db_connection();
    
    // Select all user IDs
    $stmt = $db->query("SELECT id FROM users WHERE status = 'active'");
    $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $upgrades = 0;
    foreach ($user_ids as $uid) {
         $uid = (int)$uid;
         
         // Look up current rank mapping
         $stmt_cc = $db->prepare("SELECT vip_level FROM user_vip_status WHERE user_id = ? LIMIT 1");
         $stmt_cc->execute([$uid]);
         $row = $stmt_cc->fetch();
         $current = $row ? (int)$row['vip_level'] : 0;
         
         // Force calculate newly earned
         $earned = sync_user_vip_status($uid);
         
         if ($earned > $current) {
              $upgrades++;
         }
    }
    
    $elapsed = microtime(true) - $start_time;
    require_once __DIR__ . '/../includes/helpers.php';
    write_system_log('vip-sync-cron', 'success', "Cron sinkronisasi tingkat VIP berhasil menyelesaikan pengauditan. $upgrades user diupgrade.", $elapsed);
    echo "VIP Status audit synchronization complete. Upgraded: $upgrades\n";
    
} catch (Exception $e) {
     $elapsed = microtime(true) - $start_time;
     require_once __DIR__ . '/../includes/helpers.php';
     write_system_log('vip-sync-cron', 'failed', "Cron VIP sync failed: " . $e->getMessage(), $elapsed);
     echo "VIP sync cron failed: " . $e->getMessage() . "\n";
}
