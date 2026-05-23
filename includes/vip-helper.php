<?php
// Automatic VIP upgrades check hook linked with approved topups

require_once __DIR__ . '/db.php';

function sync_user_vip_status($user_id) {
    $db = get_db_connection();
    
    // Calculate total Approved topup for this user (referral status, gifts, games etc are safely excluded as rules requested)
    $stmt = $db->prepare("SELECT SUM(original_amount) as total_approved FROM topups WHERE user_id = ? AND status = 'success'");
    $stmt->execute([$user_id]);
    $res = $stmt->fetch();
    $total_topup = $res['total_approved'] ? (float)$res['total_approved'] : 0.00;
    
    // Map corresponding VIP level based on tiers rules
    // VIP 0: default
    // VIP 1: min topup APPROVED >= 50,000
    // VIP 2: min topup APPROVED >= 100,000
    // VIP 3: min topup APPROVED >= 1,000,000
    
    // Check master ranges config in DB
    $stmt_levels = $db->query("SELECT level_num, min_deposit FROM vip_levels WHERE is_active = 1 ORDER BY min_deposit DESC");
    $levels = $stmt_levels->fetchAll();
    
    $earned_vip = 0;
    foreach ($levels as $lvl) {
        if ($total_topup >= (float)$lvl['min_deposit']) {
            $earned_vip = (int)$lvl['level_num'];
            break;
        }
    }
    
    // Check current mapping in db
    $stmt_curr = $db->prepare("SELECT vip_level FROM user_vip_status WHERE user_id = ? LIMIT 1");
    $stmt_curr->execute([$user_id]);
    $curr = $stmt_curr->fetch();
    
    if (!$curr) {
        // Init
        $stmt_ins = $db->prepare("INSERT INTO user_vip_status (user_id, vip_level) VALUES (?, ?)");
        $stmt_ins->execute([$user_id, $earned_vip]);
    } else {
        $current_vip = (int)$curr['vip_level'];
        if ($earned_vip > $current_vip) {
            // Upgrade! VIP never downgrades as explicit requirement
            $stmt_up = $db->prepare("UPDATE user_vip_status SET vip_level = ? WHERE user_id = ?");
            $stmt_up->execute([$earned_vip, $user_id]);
            
            // Send user notification of rank up
            $stmt_notify = $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Selamat! Level VIP Naik', ?)");
            $msg = "Akun Anda telah otomatis ditingkatkan menjadi VIP " . $earned_vip . " karena total topup Anda telah mencapai krateria. Nikmati pengurangan biaya admin penarikan dan akses fitur mini-game eksklusif.";
            $stmt_notify->execute([$user_id, $msg]);
        }
    }
    return $earned_vip;
}

function get_user_vip_details($user_id) {
    $db = get_db_connection();
    
    // Force compute/sync first
    $vip_level = sync_user_vip_status($user_id);
    
    // Get stats
    $stmt = $db->prepare("SELECT l.level_num, l.name, l.min_deposit, l.min_withdrawal, l.withdrawn_fee_percent, l.game_enabled, l.voucher_enabled, l.badge_image 
                          FROM vip_levels l 
                          WHERE l.level_num = ? LIMIT 1");
    $stmt->execute([$vip_level]);
    $details = $stmt->fetch();
    
    if (!$details) {
        // Fallback default structure
        return [
            'level_num' => 0,
            'name' => 'VIP 0',
            'min_deposit' => 0.00,
            'min_withdrawal' => 100000.00,
            'withdrawn_fee_percent' => 10.00,
            'game_enabled' => 0,
            'voucher_enabled' => 0,
            'badge_image' => '/assets/icons/vip0.svg'
        ];
    }
    return $details;
}
