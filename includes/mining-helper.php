<?php
// Secure Cloud Mining Engines & Countdown Checkers

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ledger-helper.php';

function get_user_mining_stats($user_id) {
    $db = get_db_connection();
    
    // Total profit earned from mining
    $balances = get_user_balances($user_id);
    
    // Profit harian ini (claimed today)
    $today = date('Y-m-d');
    $stmt_today = $db->prepare("SELECT SUM(amount) as total_today FROM mining_profit_logs WHERE user_id = ? AND DATE(created_at) = ?");
    $stmt_today->execute([$user_id, $today]);
    $res_today = $stmt_today->fetch();
    $profit_today = $res_today['total_today'] ? (float)$res_today['total_today'] : 0.00;
    
    return [
        'profit_total' => (float)$balances['total_profit'],
        'profit_today' => $profit_today,
        'profit_balance' => (float)$balances['profit_balance']
    ];
}

function start_mining_session($user_id, $user_product_id) {
    $db = get_db_connection();
    
    // 1. Verify user owning this subscription
    $stmt = $db->prepare("SELECT up.*, p.name as product_name FROM user_products up 
                          JOIN products p ON up.product_id = p.id 
                          WHERE up.id = ? AND up.user_id = ? AND up.status = 'active' LIMIT 1");
    $stmt->execute([$user_product_id, $user_id]);
    $subscription = $stmt->fetch();
    
    if (!$subscription) {
         return ['success' => false, 'error' => 'Mesin tambang tidak valid atau telah kedaluwarsa.'];
    }
    
    // Checks if subscription has expired chronologically
    if (strtotime($subscription['active_until']) < time()) {
        // flag state to expire
        $stmt_close = $db->prepare("UPDATE user_products SET status = 'expired' WHERE id = ?");
        $stmt_close->execute([$user_product_id]);
        return ['success' => false, 'error' => 'Mesin tambang ini sudah kedaluwarsa secara batas waktu durasi.'];
    }
    
    // 2. Prevent Double Mining within 24 Hours VPS Time
    // A user can only execute 1 mining session per active purchase everyday
    $today = date('Y-m-d');
    $stmt_check = $db->prepare("SELECT id FROM mining_sessions WHERE user_product_id = ? AND DATE(started_at) = ? AND user_id = ? LIMIT 1");
    $stmt_check->execute([$user_product_id, $today, $user_id]);
    if ($stmt_check->fetch()) {
        return ['success' => false, 'error' => 'Mesin ini sudah digunakan menambang hari ini. Tiap mesin hanya diperbolehkan menambang 1 kali per hari.'];
    }
    
    // Default countdown 2 hours configured
    $session_duration_seconds = 7200; // 2 Hours
    $started_at = date('Y-m-d H:i:s');
    $ends_at = date('Y-m-d H:i:s', time() + $session_duration_seconds);
    $profit = (float)$subscription['profit_per_day'];
    $idempotency = "mining_session_{$user_product_id}_" . str_replace('-', '_', $today);
    
    try {
        $stmt_insert = $db->prepare("INSERT INTO mining_sessions (user_id, user_product_id, started_at, ends_at, status, profit_amount, idempotency_key) VALUES (?, ?, ?, ?, 'running', ?, ?)");
        $stmt_insert->execute([$user_id, $user_product_id, $started_at, $ends_at, $profit, $idempotency]);
        return ['success' => true, 'ends_at' => $ends_at];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Gagal meluncurkan program penambangan: ' . $e->getMessage()];
    }
}

/**
 * Fallback Manual Claim if cron is late
 */
function claim_session_individual($session_id, $user_id) {
    $db = get_db_connection();
    
    $stmt = $db->prepare("SELECT * FROM mining_sessions WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$session_id, $user_id]);
    $session = $stmt->fetch();
    
    if (!$session) {
        return ['success' => false, 'error' => 'Sesi penambangan tidak ditemukan.'];
    }
    
    if ($session['status'] !== 'running') {
        return ['success' => false, 'error' => 'Sesi ini sudah diproses atau terbayarkan sebelumnya.'];
    }
    
    if (strtotime($session['ends_at']) > time()) {
        $remain = strtotime($session['ends_at']) - time();
        $min = ceil($remain / 60);
        return ['success' => false, 'error' => "Sektor tambang sedang berputar menyelesaikan pending block. Sisa waktu penambangan harian: $min menit."];
    }
    
    // Process payment safely
    $db->beginTransaction();
    try {
        // Lock and update state to claimed / completed
        $stmt_up = $db->prepare("UPDATE mining_sessions SET status = 'claimed' WHERE id = ?");
        $stmt_up->execute([$session_id]);
        
        // Ledger Transaction safe
        $ledger_key = "mining_profit_claim_{$session['id']}";
        $success = write_ledger_transaction(
            $user_id,
            'mining_profit',
            (float)$session['profit_amount'],
            'profit_balance',
            'in',
            "Hasil cloud mining harian dari sesi tambang #" . $session['id'],
            $ledger_key
        );
        
        if ($success) {
            // Write profit log
            $stmt_log = $db->prepare("INSERT INTO mining_profit_logs (user_id, session_id, amount) VALUES (?, ?, ?)");
            $stmt_log->execute([$user_id, $session['id'], (float)$session['profit_amount']]);
            
            // Check team transaction referral bonus allocations
            require_once __DIR__ . '/referral-helper.php';
            // Find parent user product purchase context or session context
            $stmt_up_prod = $db->prepare("SELECT up.product_id, up.user_id FROM user_products up 
                                          JOIN mining_sessions ms ON ms.user_product_id = up.id 
                                          WHERE ms.id = ? LIMIT 1");
            $stmt_up_prod->execute([$session['id']]);
            $sub = $stmt_up_prod->fetch();
            
            if ($sub) {
                // Look up in purchases
                $stmt_pur = $db->prepare("SELECT id FROM product_purchases WHERE user_product_id = ? LIMIT 1");
                $stmt_pur->execute([$session['user_product_id']]);
                $pur = $stmt_pur->fetch();
                if ($pur) {
                    process_product_referral_commission($pur['id']);
                }
            }
            
            $db->commit();
            return ['success' => true, 'amount' => (float)$session['profit_amount']];
        } else {
            $db->rollBack();
            return ['success' => false, 'error' => 'Keamanan ledger menolak transaksi ganda.'];
        }
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'error' => 'Kesalahan sistem penarikan mining: ' . $e->getMessage()];
    }
}
