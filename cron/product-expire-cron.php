<?php
// Checks for active purchased user_products that expired.
// Runs daily.
// Run: php /www/wwwroot/noxara.page/cron/product-expire-cron.php

$start_time = microtime(true);
require_once __DIR__ . '/../includes/db.php';

try {
    $db = get_db_connection();
    
    // Select open/active items where chronological expiry date is reached
    $stmt = $db->prepare("SELECT id, user_id, product_id FROM user_products WHERE status = 'active' AND active_until <= NOW()");
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    $count = 0;
    foreach ($rows as $row) {
        $up_id = (int)$row['id'];
        $user_id = (int)$row['user_id'];
        
        $db->beginTransaction();
        try {
            $stmt_up = $db->prepare("UPDATE user_products SET status = 'expired' WHERE id = ?");
            $stmt_up->execute([$up_id]);
            
            // Create user notification of expiry
            $stmt_no = $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Masa Aktif Miner Kedaluwarsa', 'Mesin penambangan cloud miner Anda #$up_id telah melewati batas masa kontrak 30 hari dan sekarang nonaktif (Expired).')");
            $stmt_no->execute([$user_id]);
            
            $db->commit();
            $count++;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Failed to expire user product $up_id: " . $e->getMessage());
        }
    }
    
    $elapsed = microtime(true) - $start_time;
    require_once __DIR__ . '/../includes/helpers.php';
    write_system_log('product-expire-cron', 'success', "Cron kedaluwarsa miner berhasil menonaktifkan $count produk expired.", $elapsed);
    echo "Product expire cron complete. $count miners deactivated.\n";
    
} catch (Exception $e) {
    $elapsed = microtime(true) - $start_time;
    require_once __DIR__ . '/../includes/helpers.php';
    write_system_log('product-expire-cron', 'failed', "Cron product expiry failed: " . $e->getMessage(), $elapsed);
    echo "Expiry cron failed: " . $e->getMessage() . "\n";
}
