<?php
// Secure AJAX Handler for User operations (Bank, PIN, Buy, Mining, Claim)

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/settings-helper.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
     send_json_response(['success' => false, 'error' => 'Metode request tidak diizinkan.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$csrf = $input['csrf_token'] ?? '';

// Check CSRF
if (!verify_csrf_token($csrf)) {
     send_json_response(['success' => false, 'error' => 'Token CSRF tidak valid. Silakan menyegarkan halaman.'], 400);
}

$user_id = $_SESSION['user_id'];

// Feature level global checks
if ($action === 'buy_product' && !is_feature_enabled('products')) {
     send_json_response(['success' => false, 'error' => 'Fitur pembelian produk penambangan sedang dinonaktifkan sementara.'], 403);
}
if ($action === 'start_mining' && !is_feature_enabled('mining')) {
     send_json_response(['success' => false, 'error' => 'Fitur aktivitas mining sedang dinonaktifkan sementara.'], 403);
}
if ($action === 'claim_mining' && !is_feature_enabled('mining')) {
     send_json_response(['success' => false, 'error' => 'Fitur klaim profit sedang dinonaktifkan sementara.'], 403);
}

switch ($action) {
     case 'setup_bank':
          require_once __DIR__ . '/../../includes/bank-helper.php';
          $bank_name = trim($input['bank_name'] ?? '');
          $acc_num = trim($input['account_number'] ?? '');
          $acc_name = trim($input['account_name'] ?? '');
          $password = $input['password'] ?? '';
          
          if (empty($bank_name) || empty($acc_num) || empty($acc_name) || empty($password)) {
               send_json_response(['success' => false, 'error' => 'Semua kolom wajib diisi lengkap.']);
          }
          
          $res = link_user_bank_account($user_id, $bank_name, $acc_num, $acc_name, $password);
          if ($res['success']) {
               send_json_response(['success' => true, 'message' => 'Akun bank pembayaran Anda berhasil ditautkan secara aman.']);
          } else {
               send_json_response(['success' => false, 'error' => $res['error']]);
          }
          break;

     case 'setup_pin':
          require_once __DIR__ . '/../../includes/pin-helper.php';
          $pin = $input['pin'] ?? '';
          $password = $input['password'] ?? '';
          
          $res = setup_user_pin($user_id, $pin, $password);
          if ($res['success']) {
               send_json_response(['success' => true, 'message' => 'PIN transaksi 6 digit Anda berhasil dibuat. Simpan baik-baik.']);
          } else {
               send_json_response(['success' => false, 'error' => $res['error']]);
          }
          break;

     case 'change_pin':
          require_once __DIR__ . '/../../includes/pin-helper.php';
          $old_pin = $input['old_pin'] ?? '';
          $new_pin = $input['new_pin'] ?? '';
          $password = $input['password'] ?? '';
          
          $res = change_user_pin($user_id, $old_pin, $new_pin, $password);
          if ($res['success']) {
               send_json_response(['success' => true, 'message' => 'PIN transaksi berhasil diperbarui.']);
          } else {
               send_json_response(['success' => false, 'error' => $res['error']]);
          }
          break;

     case 'buy_product':
          require_once __DIR__ . '/../../includes/transaction-helper.php';
          $prod_id = (int)($input['product_id'] ?? 0);
          $v_code = trim($input['voucher_code'] ?? '');
          
          $res = purchase_cloud_miner_product($user_id, $prod_id, $v_code);
          if ($res['success']) {
               send_json_response(['success' => true, 'message' => 'Selamat! Pembelian Cloud Miner berhasil diproses.']);
          } else {
               send_json_response(['success' => false, 'error' => $res['error']]);
          }
          break;

     case 'start_mining':
          require_once __DIR__ . '/../../includes/mining-helper.php';
          $up_id = (int)($input['user_product_id'] ?? 0);
          
          $res = start_mining_session($user_id, $up_id);
          if ($res['success']) {
               send_json_response(['success' => true, 'ends_at' => $res['ends_at'], 'message' => 'Mesin penambang berhasil diputar harian.']);
          } else {
               send_json_response(['success' => false, 'error' => $res['error']]);
          }
          break;

     case 'claim_mining':
          require_once __DIR__ . '/../../includes/mining-helper.php';
          $sess_id = (int)($input['session_id'] ?? 0);
          
          $res = claim_session_individual($sess_id, $user_id);
          if ($res['success']) {
               send_json_response(['success' => true, 'message' => 'Profit harian sebesar ' . format_currency($res['amount']) . ' berhasil diklaim ke Saldo Profit Anda.']);
          } else {
               send_json_response(['success' => false, 'error' => $res['error']]);
          }
          break;

     case 'claim_daily_bonus':
          if (!is_feature_enabled('daily_bonus')) {
               send_json_response(['success' => false, 'error' => 'Fitur bonus harian dinonaktifkan sementara.']);
          }
          require_once __DIR__ . '/../../includes/db.php';
          require_once __DIR__ . '/../../includes/ledger-helper.php';
          
          $day_num = (int)($input['day_num'] ?? 0);
          if ($day_num < 1 || $day_num > 7) {
               send_json_response(['success' => false, 'error' => 'Parameter harian tidak valid.']);
          }
          
          $db = get_db_connection();
          
          // Verify day rate reward
          $stmt_day = $db->prepare("SELECT reward_amount FROM daily_bonus_settings WHERE day_num = ? AND is_active = 1 LIMIT 1");
          $stmt_day->execute([$day_num]);
          $bonus = $stmt_day->fetch();
          if (!$bonus) {
               send_json_response(['success' => false, 'error' => 'Hadiah untuk hari ke-' . $day_num . ' belum diatur oleh admin.']);
          }
          
          $reward = (float)$bonus['reward_amount'];
          $today = date('Y-m-d');
          
          // Verify user has not claimed *any* bonus today
          $stmt_check = $db->prepare("SELECT id FROM daily_bonus_claims WHERE user_id = ? AND claimed_date = ? LIMIT 1");
          $stmt_check->execute([$user_id, $today]);
          if ($stmt_check->fetch()) {
               send_json_response(['success' => false, 'error' => 'Anda sudah mengklaim bonus harian hari ini. Silakan kembali besok pagi.']);
          }
          
          // Verify sequencing: can claim day X only if day X-1 has been done within the current cycle
          // For simplicity and fluid UX, we permit sequential check-ins:
          // User must claim previous days sequentially or we reset sequence to Day 1
          $stmt_last = $db->prepare("SELECT day_num FROM daily_bonus_claims WHERE user_id = ? ORDER BY claimed_date DESC LIMIT 1");
          $stmt_last->execute([$user_id]);
          $last = $stmt_last->fetch();
          
          $expected_day = 1;
          if ($last) {
               $last_day = (int)$last['day_num'];
               $expected_day = ($last_day >= 7) ? 1 : ($last_day + 1);
          }
          
          if ($day_num !== $expected_day) {
               send_json_response(['success' => false, 'error' => "Urutan absen salah. Anda harus absen untuk hari ke-$expected_day hari ini."]);
          }
          
          // Double-entry ledger within SQL transaction (High protection)
          $db->beginTransaction();
          try {
               $stmt_ins = $db->prepare("INSERT INTO daily_bonus_claims (user_id, day_num, claimed_date, reward_amount) VALUES (?, ?, ?, ?)");
               $stmt_ins->execute([$user_id, $day_num, $today, $reward]);
               $claim_id = $db->lastInsertId();
               
               $idempotency = "daily_bonus_{$user_id}_{$today}";
               write_ledger_transaction(
                    $user_id,
                    'daily_bonus',
                    $reward,
                    'main_balance', // goes directly to spendable main balance
                    'in',
                    "Klaim absen harian Day $day_num",
                    $idempotency
               );
               
               $db->commit();
               send_json_response(['success' => true, 'message' => "Absensi berhasil! Hadiah Day $day_num sebesar " . format_currency($reward) . " ditambahkan ke Saldo Utama Anda."]);
          } catch (Exception $e) {
               $db->rollBack();
               send_json_response(['success' => false, 'error' => 'Terjadi kesalahan absen harian: ' . $e->getMessage()]);
          }
          break;

     case 'claim_voucher_wallet':
          if (!is_feature_enabled('voucher')) {
               send_json_response(['success' => false, 'error' => 'Fitur klaim voucher dinonaktifkan sementara.']);
          }
          require_once __DIR__ . '/../../includes/voucher-helper.php';
          $code = trim($input['voucher_code'] ?? '');
          
          $res = validate_and_apply_voucher($code, $user_id, 'balance_claim');
          if ($res['success']) {
                send_json_response(['success' => true, 'message' => $res['message']]);
          } else {
                send_json_response(['success' => false, 'error' => $res['error']]);
          }
          break;

     case 'play_game':
          if (!is_feature_enabled('game')) {
               send_json_response(['success' => false, 'error' => 'Fitur VIP Games dinonaktifkan sementara oleh admin.']);
          }
          require_once __DIR__ . '/../../includes/game-helper.php';
          $game_key = trim($input['game_key'] ?? '');
          $score = (int)($input['score'] ?? 0);
          
          $res = process_game_claim($user_id, $game_key, $score);
          if ($res['success']) {
                send_json_response(['success' => true, 'status' => $res['status'], 'reward' => $res['reward'], 'message' => $res['message']]);
          } else {
                send_json_response(['success' => false, 'error' => $res['error']]);
          }
          break;

     case 'create_deposit':
          if (!is_feature_enabled('deposit')) {
               send_json_response(['success' => false, 'error' => 'Fitur isi ulang saldo sedang ditutup sementara oleh admin.']);
          }
          require_once __DIR__ . '/../../includes/cashify-helper.php';
          $amount = (float)($input['amount'] ?? 0);
          $display_method = trim($input['display_method'] ?? 'QRIS');
          $v_code = trim($input['voucher_code'] ?? '');
          
          if ($amount < 15000) {
               send_json_response(['success' => false, 'error' => 'Minimal pengisian ulang adalah Rp 15.000.']);
          }
          
          // Verify voucher linked if any
          $v_id = null;
          if (!empty($v_code)) {
               require_once __DIR__ . '/../../includes/voucher-helper.php';
               $val = validate_and_apply_voucher($v_code, $user_id, 'topup', $amount);
               if (!$val['success']) {
                    send_json_response(['success' => false, 'error' => $val['error']]);
               }
               $v_id = $val['voucher']['id'];
          }
          
          // Request gateway Cashify V2 REST API
          $cashify = request_cashify_qris($amount);
          
          if (!$cashify['success']) {
               send_json_response(['success' => false, 'error' => $cashify['error']]);
          }
          
          $db = get_db_connection();
          $db->beginTransaction();
          try {
               $ex_at = $cashify['expired_at'];
               $original = (float)$cashify['originalAmount'];
               $uniq = (int)$cashify['uniqueNominal'];
               $total = (float)$cashify['totalAmount'];
               $cashify_tx = $cashify['transactionId'];
               $qr_string = $cashify['qr_string'];
               
               $today_format = date('YmdHis');
               $idempotency = "cashify_init_{$user_id}_{$amount}_{$today_format}";
               
               $stmt_ins = $db->prepare("INSERT INTO topups (user_id, original_amount, unique_nominal, total_amount, transaction_id_cashify, qr_string, status, voucher_id, method_display, idempotency_key, expired_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)");
               $stmt_ins->execute([$user_id, $original, $uniq, $total, $cashify_tx, $qr_string, $v_id, $display_method, $idempotency, $ex_at]);
               $topup_id = $db->lastInsertId();
               
               $db->commit();
               
               send_json_response([
                    'success' => true,
                    'topup_id' => $topup_id,
                    'transaction_id' => $topup_id,
                    'message' => 'Transaksi Cashify QRIS dibentuk!'
               ]);
          } catch (Exception $e) {
               $db->rollBack();
               send_json_response(['success' => false, 'error' => 'Gagal mencatat transaksi isi ulang: ' . $e->getMessage()]);
          }
          break;

     case 'check_deposit_status':
          require_once __DIR__ . '/../../includes/cashify-helper.php';
          $topup_id = (int)($input['topup_id'] ?? 0);
          
          $db = get_db_connection();
          $stmt = $db->prepare("SELECT * FROM topups WHERE id = ? AND user_id = ? LIMIT 1");
          $stmt->execute([$topup_id, $user_id]);
          $topup = $stmt->fetch();
          
          if (!$topup) {
               send_json_response(['success' => false, 'error' => 'Topup tidak ditemukan.']);
          }
          
          if ($topup['status'] === 'success') {
                send_json_response(['success' => true, 'status' => 'success', 'message' => 'Pembayaran Anda telah sukses diverifikasi! Saldo Utama ditambahkan.']);
          }
          
          if (strtotime($topup['expired_at']) < time() && $topup['status'] === 'pending') {
                $stmt_up = $db->prepare("UPDATE topups SET status = 'expired' WHERE id = ?");
                $stmt_up->execute([$topup_id]);
                send_json_response(['success' => true, 'status' => 'expired', 'message' => 'Masa tenggang pembayaran telah habis (expired).']);
          }
          
          // Poll Cashify server directly
          $chk = check_cashify_payment_status($topup['transaction_id_cashify']);
          if ($chk['status'] === 'paid' || $chk['status'] === 'success') {
                $res = complete_user_topup($topup_id, $chk['status'], $topup['transaction_id_cashify']);
                if ($res['success']) {
                     send_json_response(['success' => true, 'status' => 'success', 'message' => 'pembayaran lunas!']);
                } else {
                     send_json_response(['success' => false, 'error' => $res['error']]);
                }
          } else if ($chk['status'] === 'cancel') {
                $stmt_cancel = $db->prepare("UPDATE topups SET status = 'cancel' WHERE id = ?");
                $stmt_cancel->execute([$topup_id]);
                send_json_response(['success' => true, 'status' => 'cancel', 'message' => 'Pembayaran dibatalkan.']);
          } else {
                send_json_response(['success' => true, 'status' => 'pending']);
          }
          break;

     case 'cancel_deposit':
          require_once __DIR__ . '/../../includes/db.php';
          $topup_id = (int)($input['topup_id'] ?? 0);
          
          $db = get_db_connection();
          
          // Request gateway status update if possible
          $stmt = $db->prepare("SELECT transaction_id_cashify FROM topups WHERE id = ? AND user_id = ? AND status = 'pending' LIMIT 1");
          $stmt->execute([$topup_id, $user_id]);
          $topup = $stmt->fetch();
          
          if ($topup) {
               $config = get_cashify_config();
               $url = rtrim($config['base_url'], '/') . '/api/generate/cancel-status';
               $ch = curl_init($url);
               curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
               curl_setopt($ch, CURLOPT_POST, true);
               curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['transactionId' => $topup['transaction_id_cashify']]));
               curl_setopt($ch, CURLOPT_HTTPHEADER, [
                   'x-license-key: ' . $config['license_key'],
                   'Content-Type: application/json'
               ]);
               curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
               curl_setopt($ch, CURLOPT_TIMEOUT, 10);
               curl_exec($ch);
               curl_close($ch);
               
               $stmt_up = $db->prepare("UPDATE topups SET status = 'cancel' WHERE id = ?");
               $stmt_up->execute([$topup_id]);
               send_json_response(['success' => true, 'message' => 'Pengajuan pengisian saldo berhasil dibatalkan.']);
          } else {
               send_json_response(['success' => false, 'error' => 'Pembatalan ditolak. Transaksi tidak pending atau bukan milik Anda.']);
          }
          break;

     case 'create_withdrawal':
          if (!is_feature_enabled('withdraw')) {
               send_json_response(['success' => false, 'error' => 'Fitur penarikan dana sedang ditutup sementara oleh admin.']);
          }
          require_once __DIR__ . '/../../includes/withdraw-helper.php';
          $amount = (float)($input['amount'] ?? 0);
          $pin = $input['pin'] ?? '';
          
          $res = process_withdrawal_request($user_id, $amount, $pin);
          if ($res['success']) {
               send_json_response(['success' => true, 'withdraw_id' => $res['withdraw_id'], 'message' => 'Sukses! Pengajuan penarikan dana Anda sebesar ' . format_currency($res['net']) . ' (setelah dipotong biaya administrasi) telah kami terima dan masuk antrean persetujuan finansial admin.']);
          } else {
               if (isset($res['redirect'])) {
                    send_json_response(['success' => false, 'redirect' => $res['redirect'], 'error' => $res['error']]);
               }
               send_json_response(['success' => false, 'error' => $res['error']]);
          }
          break;

     case 'send_chat_message':
          if (!is_feature_enabled('live_chat')) {
               send_json_response(['success' => false, 'error' => 'Layanan live chat sedang tidak aktif.']);
          }
          require_once __DIR__ . '/../../includes/db.php';
          $message = trim($input['message'] ?? '');
          $attach = trim($input['attachment_url'] ?? '');
          
          if (empty($message) && empty($attach)) {
               send_json_response(['success' => false, 'error' => 'Pesan tidak boleh kosong.']);
          }
          
          $db = get_db_connection();
          $db->beginTransaction();
          try {
               // Find active or open thread for this user
               $stmt_thr = $db->prepare("SELECT id FROM chat_threads WHERE user_id = ? AND status = 'open' LIMIT 1");
               $stmt_thr->execute([$user_id]);
               $thread = $stmt_thr->fetch();
               
               if (!$thread) {
                    $stmt_ct = $db->prepare("INSERT INTO chat_threads (user_id, status) VALUES (?, 'open')");
                    $stmt_ct->execute([$user_id]);
                    $thread_id = $db->lastInsertId();
               } else {
                    $thread_id = $thread['id'];
               }
               
               // Write message log
               $stmt_m = $db->prepare("INSERT INTO chat_messages (thread_id, sender_type, sender_id, message, attachment_path) VALUES (?, 'user', ?, ?, ?)");
               $stmt_m->execute([$thread_id, $user_id, $message, $attach ?: null]);
               
               // Touch thread updated time
               $stmt_touch = $db->prepare("UPDATE chat_threads SET updated_at = NOW() WHERE id = ?");
               $stmt_touch->execute([$thread_id]);
               
               $db->commit();
               send_json_response(['success' => true]);
          } catch (Exception $e) {
               $db->rollBack();
               send_json_response(['success' => false, 'error' => 'Kesalahan chat: ' . $e->getMessage()]);
          }
          break;

     default:
          send_json_response(['success' => false, 'error' => 'Aksi tidak dikenal.'], 400);
}
