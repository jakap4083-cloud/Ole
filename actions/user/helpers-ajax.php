<?php
// Secure AJAX Handler for internal wallet balance claims that are too simple for ledger endpoints

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/ledger-helper.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
     send_json_response(['success' => false, 'error' => 'Metode dilarang.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$csrf = $input['csrf_token'] ?? '';

if (!verify_csrf_token($csrf)) {
     send_json_response(['success' => false, 'error' => 'Validasi token CSRF kedaluwarsa. Silakan menyegarkan jendela.'], 400);
}

$user_id = $_SESSION['user_id'];

switch ($action) {
     case 'move_profit_to_main':
          $db = get_db_connection();
          $balances = get_user_balances($user_id);
          
          $profit_amount = (float)$balances['profit_balance'];
          if ($profit_amount <= 0.00) {
               send_json_response(['success' => false, 'error' => 'Tidak ada saldo profit penambangan yang dapat dipindahkan saat ini.']);
          }
          
          $today_format = date('YmdHis');
          $idempotency_1 = "move_profit_out_{$user_id}_{$today_format}";
          $idempotency_2 = "move_profit_in_{$user_id}_{$today_format}";
          
          $db->beginTransaction();
          try {
               // Safe write double entry deduct profit
               write_ledger_transaction(
                   $user_id,
                   'move_balance',
                   $profit_amount,
                   'profit_balance',
                   'out',
                   "Konversi pemindahan saldo hasil tambang harian ke Saldo Utama",
                   $idempotency_1
               );
               
               // Safe write credit main balance
               write_ledger_transaction(
                   $user_id,
                   'move_balance',
                   $profit_amount,
                   'main_balance',
                   'in',
                   "Penerimaan konversi hasil tambang harian",
                   $idempotency_2
               );
               
               $db->commit();
               send_json_response(['success' => true, 'message' => "Sukses! Saldo profit sebesar " . format_currency($profit_amount) . " berhasil dikonversikan dan ditransfer seutuhnya ke Saldo Utama Anda."]);
          } catch (Exception $e) {
               $db->rollBack();
               send_json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
          }
          break;

     case 'move_commission_to_main':
          $db = get_db_connection();
          $balances = get_user_balances($user_id);
          
          $comm_amount = (float)$balances['commission_balance'];
          if ($comm_amount <= 0.00) {
               send_json_response(['success' => false, 'error' => 'Tidak ada saldo komisi rujukan yang dapat dipindahkan saat ini.']);
          }
          
          $today_format = date('YmdHis');
          $idempotency_1 = "move_comm_out_{$user_id}_{$today_format}";
          $idempotency_2 = "move_comm_in_{$user_id}_{$today_format}";
          
          $db->beginTransaction();
          try {
               // Out
               write_ledger_transaction(
                   $user_id,
                   'move_balance',
                   $comm_amount,
                   'commission_balance',
                   'out',
                   "Konversi pemindahan saldo rujukan rabat tim ke Saldo Utama",
                   $idempotency_1
               );
               
               // In
               write_ledger_transaction(
                   $user_id,
                   'move_balance',
                   $comm_amount,
                   'main_balance',
                   'in',
                   "Penerimaan konversi rujukan rabat tim",
                   $idempotency_2
               );
               
               $db->commit();
               send_json_response(['success' => true, 'message' => "Sukses! Saldo rujukan rabat tim sebesar " . format_currency($comm_amount) . " berhasil dipindahkan ke Saldo Utama Anda."]);
          } catch (Exception $e) {
               $db->rollBack();
               send_json_response(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
          }
          break;

     default:
          send_json_response(['success' => false, 'error' => 'Metode helpers-ajax tidak dikenal.'], 400);
}
