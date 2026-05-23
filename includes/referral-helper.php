<?php
// Secure Multi-Level Referral Calculation and distribution engines

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ledger-helper.php';

/**
 * Distribute commission derived from deposit approved.
 * Level 1 = 10%, Level 2 = 5%, Level 3 = 2% default (db rates configurable)
 */
function process_topup_referral_commission($topup_id) {
    $db = get_db_connection();
    
    // Fetch Topup Detail
    $stmt = $db->prepare("SELECT * FROM topups WHERE id = ? AND status = 'success' LIMIT 1");
    $stmt->execute([$topup_id]);
    $topup = $stmt->fetch();
    
    if (!$topup) return false;
    
    $user_id = $topup['user_id'];
    $source_amount = (float)$topup['original_amount']; // exclude gift card added unique cents
    
    // Look up parent chain sequence up to 3 height level
    $parents = get_referral_parent_upline_chain($user_id);
    
    // Fetch rates config
    $rates = get_referral_commission_rates('topup');
    
    foreach ($parents as $level => $parent_id) {
        if (!isset($rates[$level])) continue;
        
        $rate_percent = $rates[$level];
        $commission_amount = round(($source_amount * $rate_percent) / 100, 2);
        
        if ($commission_amount <= 0) continue;
        
        $idempotency = "referral_topup_{$topup_id}_{$parent_id}_{$level}";
        
        $db->beginTransaction();
        try {
            // Apply Ledger Transaction (to commission balance column as rule specifies)
            $success = write_ledger_transaction(
                $parent_id,
                'referral_commission',
                $commission_amount,
                'commission_balance',
                'in',
                "Rabat komisi isi ulang Level $level dari bawahan ID #" . $user_id . " (" . format_currency($source_amount) . ")",
                $idempotency
            );
            
            if ($success) {
                // Record log
                $stmt_comm = $db->prepare("INSERT INTO referral_commissions (earner_id, source_id, source_type, reference_id, level, amount, status, idempotency_key) VALUES (?, ?, 'topup', ?, ?, ?, 'processed', ?)");
                $stmt_comm->execute([$parent_id, $user_id, $topup_id, $level, $commission_amount, $idempotency]);
                
                // Direct notification
                $stmt_not = $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Komisi Isi Ulang Tim Masuk', ?)");
                $msg = "Anda mendapatkan bonus komisi tim Level $level sebesar " . format_currency($commission_amount) . " karena bawahan Anda melakukan pengisian ulang.";
                $stmt_not->execute([$parent_id, $msg]);
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Referral calculation crash for topup ID $topup_id level $level: " . $e->getMessage());
        }
    }
    return true;
}

/**
 * Distribute commission derived from Product Purchases.
 * Level 1 = 10%, Level 2 = 4%, Level 3 = 1% default (db rates configurable)
 */
function process_product_referral_commission($purchase_id) {
    $db = get_db_connection();
    
    // Fetch purchase detail
    $stmt = $db->prepare("SELECT * FROM product_purchases WHERE id = ? LIMIT 1");
    $stmt->execute([$purchase_id]);
    $purchase = $stmt->fetch();
    
    if (!$purchase) return false;
    
    $user_id = $purchase['user_id'];
    $source_amount = (float)$purchase['amount_paid'];
    
    $parents = get_referral_parent_upline_chain($user_id);
    $rates = get_referral_commission_rates('purchase');
    
    foreach ($parents as $level => $parent_id) {
        if (!isset($rates[$level])) continue;
        
        $rate_percent = $rates[$level];
        $commission_amount = round(($source_amount * $rate_percent) / 100, 2);
        
        if ($commission_amount <= 0) continue;
        
        $idempotency = "referral_product_{$purchase_id}_{$parent_id}_{$level}";
        
        $db->beginTransaction();
        try {
            // Apply Ledger Transaction safely
            $success = write_ledger_transaction(
                $parent_id,
                'referral_commission',
                $commission_amount,
                'commission_balance',
                'in',
                "Rabat komisi pembelian mesin tambang Level $level dari bawahan ID #" . $user_id . " (" . format_currency($source_amount) . ")",
                $idempotency
            );
            
            if ($success) {
                // Record Log
                $stmt_comm = $db->prepare("INSERT INTO referral_commissions (earner_id, source_id, source_type, reference_id, level, amount, status, idempotency_key) VALUES (?, ?, 'purchase', ?, ?, ?, 'processed', ?)");
                $stmt_comm->execute([$parent_id, $user_id, $purchase_id, $level, $commission_amount, $idempotency]);
                
                // Notify
                $stmt_not = $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Komisi Pembelian Tim Masuk', ?)");
                $msg = "Anda mendapatkan bonus komisi tim Level $level sebesar " . format_currency($commission_amount) . " karena bawahan Anda melakukan investasi.";
                $stmt_not->execute([$parent_id, $msg]);
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Referral product commission error: " . $e->getMessage());
        }
    }
    return true;
}

/**
 * Returns parent upline up to maximum level 3
 * Level 1 = Direct Inviter
 * Level 2 = Grand-inviter
 * Level 3 = Great-grand-inviter
 */
function get_referral_parent_upline_chain($user_id) {
    $db = get_db_connection();
    $sequence = [];
    
    // Fetch direct inviter (level 1)
    $stmt = $db->prepare("SELECT referred_by FROM user_profiles WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $prof_1 = $stmt->fetch();
    
    if ($prof_1 && $prof_1['referred_by']) {
        $sequence[1] = (int)$prof_1['referred_by'];
        
        // Level 2 parent lookup
        $stmt_2 = $db->prepare("SELECT referred_by FROM user_profiles WHERE user_id = ? LIMIT 1");
        $stmt_2->execute([$sequence[1]]);
        $prof_2 = $stmt_2->fetch();
        if ($prof_2 && $prof_2['referred_by']) {
            $sequence[2] = (int)$prof_2['referred_by'];
            
            // Level 3 parent lookup
            $stmt_3 = $db->prepare("SELECT referred_by FROM user_profiles WHERE user_id = ? LIMIT 1");
            $stmt_3->execute([$sequence[2]]);
            $prof_3 = $stmt_3->fetch();
            if ($prof_3 && $prof_3['referred_by']) {
                $sequence[3] = (int)$prof_3['referred_by'];
            }
        }
    }
    return $sequence;
}

function get_referral_commission_rates($type) {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT level, percent FROM referral_commission_rates WHERE type = ?");
    $stmt->execute([$type]);
    $rows = $stmt->fetchAll();
    
    $rates = [];
    foreach ($rows as $row) {
        $rates[(int)$row['level']] = (float)$row['percent'];
    }
    
    // Safely enforce default fallback values if config rows are missing
    if (empty($rates)) {
        if ($type === 'topup') {
            $rates = [1 => 10.00, 2 => 5.00, 3 => 2.00];
        } else {
            $rates = [1 => 10.00, 2 => 4.00, 3 => 1.00];
        }
    }
    return $rates;
}

function get_team_stats($user_id) {
    $db = get_db_connection();
    
    // Total komisi rabat tim earned in history
    $stmt = $db->prepare("SELECT SUM(amount) as earned_total FROM referral_commissions WHERE earner_id = ?");
    $stmt->execute([$user_id]);
    $tot = $stmt->fetch();
    $total_earned = $tot['earned_total'] ? (float)$tot['earned_total'] : 0.00;
    
    // Komisi tim hari ini
    $today = date('Y-m-d');
    $stmt_today = $db->prepare("SELECT SUM(amount) as earned_today FROM referral_commissions WHERE earner_id = ? AND DATE(created_at) = ?");
    $stmt_today->execute([$user_id, $today]);
    $tdy = $stmt_today->fetch();
    $today_earned = $tdy['earned_today'] ? (float)$tdy['earned_today'] : 0.00;
    
    // Bawahan details
    $parents = get_referral_parent_upline_chain($user_id);
    
    // Total count at levels
    $levels_children = [1 => [], 2 => [], 3 => []];
    
    // Level 1 children
    $stmt_lvl1 = $db->prepare("SELECT user_id FROM user_profiles WHERE referred_by = ?");
    $stmt_lvl1->execute([$user_id]);
    $l1_ids = $stmt_lvl1->fetchAll(PDO::FETCH_COLUMN);
    $levels_children[1] = $l1_ids;
    
    // Level 2 children
    if (!empty($l1_ids)) {
        $placeholders = implode(',', array_fill(0, count($l1_ids), '?'));
        $stmt_lvl2 = $db->prepare("SELECT user_id FROM user_profiles WHERE referred_by IN ($placeholders)");
        $stmt_lvl2->execute($l1_ids);
        $l2_ids = $stmt_lvl2->fetchAll(PDO::FETCH_COLUMN);
        $levels_children[2] = $l2_ids;
        
        // Level 3 children
        if (!empty($l2_ids)) {
            $placeholders_2 = implode(',', array_fill(0, count($l2_ids), '?'));
            $stmt_lvl3 = $db->prepare("SELECT user_id FROM user_profiles WHERE referred_by IN ($placeholders_2)");
            $stmt_lvl3->execute($l2_ids);
            $l3_ids = $stmt_lvl3->fetchAll(PDO::FETCH_COLUMN);
            $levels_children[3] = $l3_ids;
        }
    }
    
    // Count ON/OFF Status
    // ON = user has approved topup
    // OFF = user doesn't have approved topup
    $total_children = count($levels_children[1]) + count($levels_children[2]) + count($levels_children[3]);
    
    $total_active = 0;
    $total_inactive = 0;
    
    $all_referred_ids = array_merge($levels_children[1], $levels_children[2], $levels_children[3]);
    
    if (!empty($all_referred_ids)) {
        $placeholders_all = implode(',', array_fill(0, count($all_referred_ids), '?'));
        $stmt_active_check = $db->prepare("SELECT DISTINCT user_id FROM topups WHERE user_id IN ($placeholders_all) AND status = 'success'");
        $stmt_active_check->execute($all_referred_ids);
        $active_user_ids = $stmt_active_check->fetchAll(PDO::FETCH_COLUMN);
        
        $total_active = count($active_user_ids);
        $total_inactive = $total_children - $total_active;
    }
    
    return [
        'total_earnings' => $total_earned,
        'today_earnings' => $today_earned,
        'subordinates_count' => $total_children,
        'subordinates_active' => $total_active,
        'subordinates_inactive' => $total_inactive,
        'subordinates_by_level' => $levels_children
    ];
}
