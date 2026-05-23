<?php
// Secure Double-entry Ledger Management Core
// This is the absolute source of truth for user balances

require_once __DIR__ . '/db.php';

/**
 * Inserts transactional ledger entries and automatically updates balance_accounts safely.
 * MUST run inside DB transactions to enforce integrity!
 */
function write_ledger_transaction($user_id, $type, $amount, $balance_type, $direction, $description, $idempotency_key) {
    if ($amount <= 0) return false;
    
    $db = get_db_connection();
    
    // Check idempotency first
    $stmt = $db->prepare("SELECT id FROM ledger_transactions WHERE idempotency_key = ? LIMIT 1");
    $stmt->execute([$idempotency_key]);
    if ($stmt->fetch()) {
        // Already processed, exit silently (idempotency safety)
        return true;
    }
    
    // 1. Log ledger entry
    $stmt = $db->prepare("INSERT INTO ledger_transactions (user_id, type, amount, balance_type, direction, description, idempotency_key) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $type, $amount, $balance_type, $direction, $description, $idempotency_key]);
    
    // 2. Adjust Balance Account directly depending on type
    // We fetch current or build a safety default if record is missing
    $stmt = $db->prepare("SELECT user_id FROM balance_accounts WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        $stmt_init = $db->prepare("INSERT INTO balance_accounts (user_id) VALUES (?)");
        $stmt_init->execute([$user_id]);
    }
    
    $operator = ($direction === 'in') ? '+' : '-';
    
    // Run core arithmetic update
    $allowed_balances = ['main_balance', 'bonus_balance', 'profit_balance', 'commission_balance', 'locked_balance'];
    if (!in_array($balance_type, $allowed_balances)) {
        throw new InvalidArgumentException("Invalid target currency type");
    }
    
    // Update target balance column
    $query = "UPDATE balance_accounts SET {$balance_type} = {$balance_type} {$operator} ? WHERE user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$amount, $user_id]);
    
    // Hook: If mining profit is rolling, we also increase total_profit column (statistic counters)
    if ($type === 'mining_profit' && $balance_type === 'profit_balance' && $direction === 'in') {
        $stmt_stat = $db->prepare("UPDATE balance_accounts SET total_profit = total_profit + ? WHERE user_id = ?");
        $stmt_stat->execute([$amount, $user_id]);
    }
    
    return true;
}

function get_user_balances($user_id) {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT main_balance, bonus_balance, profit_balance, commission_balance, locked_balance, total_profit FROM balance_accounts WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $balances = $stmt->fetch();
    
    if (!$balances) {
        return [
            'main_balance' => 0.00,
            'bonus_balance' => 0.00,
            'profit_balance' => 0.00,
            'commission_balance' => 0.00,
            'locked_balance' => 0.00,
            'total_profit' => 0.00
        ];
    }
    return $balances;
}
