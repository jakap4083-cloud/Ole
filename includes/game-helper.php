<?php
// Secure VIP Games mechanics runner & reward validation engines

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ledger-helper.php';

function check_game_limits($user_id, $game_key_name) {
    $db = get_db_connection();
    
    // Fetch game parameters
    $stmt = $db->prepare("SELECT * FROM vip_games WHERE key_name = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$game_key_name]);
    $game = $stmt->fetch();
    
    if (!$game) {
        return ['allowed' => false, 'error' => 'Permainan tidak aktif atau sedang dinonaktifkan admin.'];
    }
    
    // Check level eligibility
    require_once __DIR__ . '/vip-helper.php';
    $vip = get_user_vip_details($user_id);
    if ((int)$vip['level_num'] < (int)$game['vip_level']) {
        return ['allowed' => false, 'error' => "Permainan ini hanya bisa diakses oleh level VIP " . $game['vip_level'] . " ke atas."];
    }
    
    // Check Daily Play threshold log
    $today = date('Y-m-d');
    $stmt_claims = $db->prepare("SELECT COUNT(*) as play_count FROM vip_game_plays WHERE user_id = ? AND game_id = ? AND played_date = ?");
    $stmt_claims->execute([$user_id, $game['id'], $today]);
    $res = $stmt_claims->fetch();
    
    if ((int)$res['play_count'] >= (int)$game['play_limit_per_day']) {
        return ['allowed' => false, 'error' => 'Batas maksimal harian bermain game ini adalah ' . $game['play_limit_per_day'] . ' kali per hari. Silakan coba kembali besok.'];
    }
    
    return ['allowed' => true, 'game' => $game, 'plays_today' => (int)$res['play_count']];
}

/**
 * Executes a roll server-side. High-integrity: reward amounts and zonk state are decided strictly here.
 */
function process_game_claim($user_id, $game_key_name, $client_score = 0) {
    $limits = check_game_limits($user_id, $game_key_name);
    if (!$limits['allowed']) {
        return ['success' => false, 'error' => $limits['error']];
    }
    
    $game = $limits['game'];
    $db = get_db_connection();
    $today = date('Y-m-d');
    
    // Roll win/lose block
    $roll = rand(1, 100);
    $is_win = ($roll <= (int)$game['probability_percent']);
    
    $reward = 0.00;
    $status = 'zonk';
    
    if ($is_win) {
        $min = (float)$game['min_reward'];
        $max = (float)$game['max_reward'];
        
        // Puzzle score or Coin taps can scale up the rewards within safe parameters set by admin
        if ($game_key_name === 'tap_coin' && $client_score > 0) {
            // coin values scaled safely
            $coin_factor = min(30, $client_score); // limit visual exploits
            $reward = $min + (($max - $min) * ($coin_factor / 30));
        } else {
            $reward = rand($min * 100, $max * 100) / 100;
        }
        $reward = round($reward, 2);
        $status = 'win';
    }
    
    // Secure inside a transaction
    $db->beginTransaction();
    try {
        // Record game log
        $stmt_log = $db->prepare("INSERT INTO vip_game_plays (user_id, game_id, played_date, reward_amount, status) VALUES (?, ?, ?, ?, ?)");
        $stmt_log->execute([$user_id, $game['id'], $today, $reward, $status]);
        $play_id = $db->lastInsertId();
        
        if ($reward > 0) {
            $idempotency = "game_play_reward_{$game['id']}_{$play_id}_{$user_id}";
            write_ledger_transaction(
                $user_id,
                'game_reward',
                $reward,
                'main_balance',
                'in',
                "Bonus memenangkan mini game: " . $game['display_name'],
                $idempotency
            );
        }
        
        $db->commit();
        
        return [
            'success' => true,
            'status' => $status,
            'reward' => $reward,
            'message' => $is_win ? 'Selamat! Anda memenangkan saldo bonus sebesar ' . format_currency($reward) : 'Waduh! Keberuntungan sedang tidak berpihak. Anda mendapatkan Zonk kali ini.'
        ];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'error' => 'Gagal mencatat hadiah penambangan mini game: ' . $e->getMessage()];
    }
}
