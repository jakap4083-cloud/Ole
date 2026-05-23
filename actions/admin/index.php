<?php
// Secure AJAX handler for Admin panel controls

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
     send_json_response(['success' => false, 'error' => 'Metode request tidak diizinkan.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$csrf = $input['csrf_token'] ?? '';

if (!verify_csrf_token($csrf)) {
     send_json_response(['success' => false, 'error' => 'Token CSRF tidak valid. Gagal memvalidasi keamanan admin.'], 400);
}

$admin_id = $_SESSION['admin_id'];
$admin_role = $_SESSION['admin_role'] ?? 'admin';
$db = get_db_connection();

function write_audit_log($admin_id, $action, $details) {
    global $db;
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt_aud = $db->prepare("INSERT INTO audit_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt_aud->execute([$admin_id, $action, $details, $ip]);
    } catch (Exception $e) {}
}

const FINANCE_ACTIONS = ['approve_deposit', 'reject_deposit', 'approve_withdrawal', 'reject_withdrawal'];
const SUPPORT_ACTIONS = ['send_admin_chat', 'resolve_chat'];

// Role Based Authorization Checks
if ($admin_role === 'finance' && !in_array($action, FINANCE_ACTIONS)) {
     send_json_response(['success' => false, 'error' => 'Akses ditolak. Peran finansial Anda hanya diizinkan untuk mengelola transaksi deposit/penarikan denda.'], 403);
}
if ($admin_role === 'support' && !in_array($action, SUPPORT_ACTIONS)) {
     send_json_response(['success' => false, 'error' => 'Akses ditolak. Peran support Anda hanya diizinkan untuk mengelola obrolan pelanggan (chat support).'], 403);
}

switch ($action) {
     case 'approve_deposit':
          $topup_id = (int)($input['topup_id'] ?? 0);
          
          $stmt_top = $db->prepare("SELECT * FROM topups WHERE id = ? AND status = 'pending' LIMIT 1");
          $stmt_top->execute([$topup_id]);
          $topup = $stmt_top->fetch();
          
          if (!$topup) {
               send_json_response(['success' => false, 'error' => 'Isi ulang tidak ditemukan atau sudah tidak pending.']);
          }
          
          require_once __DIR__ . '/../../includes/cashify-helper.php';
          // Manual force override lunas approved by admin
          $res = complete_user_topup($topup_id, 'paid', $topup['transaction_id_cashify'] ?: 'manual_adm_' . $topup_id);
          
          if ($res['success']) {
                write_audit_log($admin_id, 'APPROVE_DEPOSIT', "Manual disetujui topup ID #$topup_id sebesar " . format_currency($topup['original_amount']) . " milik User #" . $topup['user_id']);
                send_json_response(['success' => true, 'message' => 'Topup berhasil disetujui secara manual. Saldo ditambahkan ke user.']);
          } else {
                send_json_response(['success' => false, 'error' => $res['error']]);
          }
          break;

     case 'reject_deposit':
          $topup_id = (int)($input['topup_id'] ?? 0);
          
          $stmt_up = $db->prepare("UPDATE topups SET status = 'cancel', updated_at = NOW() WHERE id = ? AND status = 'pending'");
          if ($stmt_up->execute([$topup_id])) {
                write_audit_log($admin_id, 'REJECT_DEPOSIT', "Manual menolak (Cancel) topup ID #$topup_id");
                send_json_response(['success' => true, 'message' => 'Status pengisian ulang berhasil diubah menjadi pembatalan/ditolak.']);
          } else {
                send_json_response(['success' => false, 'error' => 'Gagal mengubah status topup. Mungkin transaksi sudah terbayarkan.']);
          }
          break;

     case 'approve_withdrawal':
          $wd_id = (int)($input['withdrawal_id'] ?? 0);
          
          $stmt_wd = $db->prepare("SELECT * FROM withdrawals WHERE id = ? AND status = 'pending' LIMIT 1");
          $stmt_wd->execute([$wd_id]);
          $wd = $stmt_wd->fetch();
          
          if (!$wd) {
                send_json_response(['success' => false, 'error' => 'Item penarikan tidak ditemukan atau sudah diproses.']);
          }
          
          $user_id = $wd['user_id'];
          $amount = (float)$wd['amount'];
          
          $db->beginTransaction();
          try {
               // Update status APPROVED
               $stmt_up = $db->prepare("UPDATE withdrawals SET status = 'approved', updated_at = NOW() WHERE id = ?");
               $stmt_up->execute([$wd_id]);
               
               // Ledger Deduction: decrease locked_balance permanently
               require_once __DIR__ . '/../../includes/ledger-helper.php';
               write_ledger_transaction(
                   $user_id,
                   'withdrawal_approve',
                   $amount,
                   'locked_balance',
                   'out',
                   "Penarikan dana #" . $wd_id . " berhasil ditransfer ke rekening: " . $wd['bank_name'] . " (" . $wd['account_number'] . ")",
                   "wd_approved_locked_out_" . $wd_id
               );
               
               // Create notify
               $stmt_not = $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Penarikan Dana Berhasil Dipercaya', ?)");
               $msg = "Penarikan dana Anda sebesar " . format_currency($wd['net_amount']) . " telah kami transfer ke rekening Anda. Silakan periksa mutasi bank Anda.";
               $stmt_not->execute([$user_id, $msg]);
               
               $db->commit();
               
               write_audit_log($admin_id, 'APPROVE_WITHDRAWAL', "Persetujuan penarikan ID #$wd_id sebesar " . format_currency($amount) . " milik User #$user_id");
               send_json_response(['success' => true, 'message' => 'Penarikan dana berhasil disetujui. Saldo dikunci permanen dikurangi.']);
          } catch (Exception $e) {
               $db->rollBack();
               send_json_response(['success' => false, 'error' => 'Gagal menyetujui transaksi: ' . $e->getMessage()]);
          }
          break;

     case 'reject_withdrawal':
          $wd_id = (int)($input['withdrawal_id'] ?? 0);
          $reason = trim($input['reason'] ?? 'Persyaratan rekening tidak sesuai.');
          
          $stmt_wd = $db->prepare("SELECT * FROM withdrawals WHERE id = ? AND status = 'pending' LIMIT 1");
          $stmt_wd->execute([$wd_id]);
          $wd = $stmt_wd->fetch();
          
          if (!$wd) {
               send_json_response(['success' => false, 'error' => 'Item penarikan tidak ditemukan atau sudah diproses.']);
          }
          
          $user_id = $wd['user_id'];
          $amount = (float)$wd['amount'];
          
          $db->beginTransaction();
          try {
               // Update status REJECTED
               $stmt_up = $db->prepare("UPDATE withdrawals SET status = 'rejected', rejection_reason = ?, updated_at = NOW() WHERE id = ?");
               $stmt_up->execute([$reason, $wd_id]);
               
               // Ledger Rollback: Locked balance moves back to Main dynamic balance
               require_once __DIR__ . '/../../includes/ledger-helper.php';
               
               write_ledger_transaction(
                   $user_id,
                   'withdrawal_reject',
                   $amount,
                   'locked_balance',
                   'out',
                   "Batal penguncian saldo karena penarikan #" . $wd_id . " ditolak",
                   "wd_rejected_locked_out_" . $wd_id
               );
               
               write_ledger_transaction(
                   $user_id,
                   'withdrawal_reject',
                   $amount,
                   'main_balance',
                   'in',
                   "Pengembalian saldo penarikan #" . $wd_id . " karena ditolak dengan alasan: " . $reason,
                   "wd_rejected_main_in_" . $wd_id
               );
               
               // Notify
               $stmt_not = $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Penarikan Dana Ditolak Admin', ?)");
               $msg = "Penarikan dana Anda sebesar " . format_currency($amount) . " ditolak oleh admin keuangan kami dengan alasan: " . $reason . ". Saldo Anda telah dikembalikan seutuhnya ke Saldo Utama.";
               $stmt_not->execute([$user_id, $msg]);
               
               $db->commit();
               
               write_audit_log($admin_id, 'REJECT_WITHDRAWAL', "Tolak penarikan ID #$wd_id sebesar " . format_currency($amount) . " dengan alasan: $reason");
               send_json_response(['success' => true, 'message' => 'Penarikan berhasil ditolak. Saldo dikembalikan seutuhnya ke user.']);
          } catch (Exception $e) {
               $db->rollBack();
               send_json_response(['success' => false, 'error' => 'Gagal menolak transaksi: ' . $e->getMessage()]);
          }
          break;

     case 'update_settings':
          if ($admin_role !== 'super_admin') {
                send_json_response(['success' => false, 'error' => 'Akses ditolak. Fitur ini hanya bagi Super Admin.']);
          }
          $key = trim($input['key'] ?? '');
          $value = trim($input['value'] ?? '');
          
          if (empty($key)) send_json_response(['success' => false, 'error' => 'Key tidak boleh kosong.']);
          
          require_once __DIR__ . '/../../includes/settings-helper.php';
          if (set_setting($key, $value)) {
                write_audit_log($admin_id, 'UPDATE_SITE_SETTING', "Setelan '$key' diubah menjadi '$value'");
                send_json_response(['success' => true, 'message' => "Setelan '$key' berhasil diperbarui."]);
          } else {
                send_json_response(['success' => false, 'error' => 'Gagal menulis setelan ke database.']);
          }
          break;

     case 'update_feature_toggle':
          if ($admin_role !== 'super_admin') {
                send_json_response(['success' => false, 'error' => 'Akses ditolak. Fitur ini hanya bagi Super Admin.']);
          }
          $key = trim($input['key'] ?? '');
          $enabled = (int)($input['enabled'] ?? 1);
          
          $stmt_t = $db->prepare("UPDATE feature_settings SET is_enabled = ? WHERE `key` = ?");
          if ($stmt_t->execute([$enabled, $key])) {
                write_audit_log($admin_id, 'TOGGLE_FEATURE', "Fitur '$key' diubah statusnya menjadi: " . ($enabled ? 'ON' : 'OFF'));
                send_json_response(['success' => true, 'message' => "Fitur berhasil dialihkan ke " . ($enabled ? 'ON' : 'OFF')]);
          } else {
                send_json_response(['success' => false, 'error' => 'Gagal mengubah status fitur di database.']);
          }
          break;

     case 'freeze_user':
          if ($admin_role !== 'super_admin' && $admin_role !== 'admin') {
                send_json_response(['success' => false, 'error' => 'Akses ditolak. Perlu setidaknya status Admin/SuperAdmin.']);
          }
          $target_userid = (int)($input['user_id'] ?? 0);
          $freeze_type = trim($input['freeze_type'] ?? 'all');
          $reason = trim($input['reason'] ?? 'Pelanggaran keamanan terdeteksi.');
          
          $allowed_freezes = ['all', 'main_balance', 'bonus_balance', 'profit_balance', 'commission_balance', 'withdraw_only', 'purchase_only'];
          if (!in_array($freeze_type, $allowed_freezes)) {
               send_json_response(['success' => false, 'error' => 'Jenis pembekuan akun tidak valid.']);
          }
          
          $stmt_ch = $db->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
          $stmt_ch->execute([$target_userid]);
          if (!$stmt_ch->fetch()) {
               send_json_response(['success' => false, 'error' => 'User target tidak ditemukan.']);
          }
          
          $db->beginTransaction();
          try {
               $stmt_fz = $db->prepare("INSERT INTO user_freezes (user_id, freeze_type, reason) VALUES (?, ?, ?)
                                        ON DUPLICATE KEY UPDATE freeze_type = ?, reason = ?");
               $stmt_fz->execute([$target_userid, $freeze_type, $reason, $freeze_type, $reason]);
               
               // log user status
               $stmt_l = $db->prepare("INSERT INTO user_status_logs (user_id, action, notes) VALUES (?, 'frozen', ?)");
               $stmt_l->execute([$target_userid, "Pembekuan tipe: $freeze_type. Alasan: $reason"]);
               
               $db->commit();
               
               write_audit_log($admin_id, 'FREEZE_USER', "Membekukan user #$target_userid dengan aturan: $freeze_type. Alasan: $reason");
               send_json_response(['success' => true, 'message' => 'Saldo/akun user target berhasil dibekukan secara efektif.']);
          } catch (Exception $e) {
               $db->rollBack();
               send_json_response(['success' => false, 'error' => 'Gagal memproses pembekuan: ' . $e->getMessage()]);
          }
          break;

     case 'unfreeze_user':
          if ($admin_role !== 'super_admin' && $admin_role !== 'admin') {
                send_json_response(['success' => false, 'error' => 'Akses ditolak.']);
          }
          $target_userid = (int)($input['user_id'] ?? 0);
          
          $stmt_un = $db->prepare("DELETE FROM user_freezes WHERE user_id = ?");
          if ($stmt_un->execute([$target_userid])) {
                $stmt_l = $db->prepare("INSERT INTO user_status_logs (user_id, action, notes) VALUES (?, 'unfrozen', NULL)");
                $stmt_l->execute([$target_userid]);
                
                write_audit_log($admin_id, 'UNFREEZE_USER', "Mencairkan pembekuan user #$target_userid");
                send_json_response(['success' => true, 'message' => 'Status pembekuan berhasil dihapuskan seutuhnya dari user.']);
          } else {
                send_json_response(['success' => false, 'error' => 'Gagal mencairkan akun user.']);
          }
          break;

     case 'send_admin_chat':
          $thread_id = (int)($input['thread_id'] ?? 0);
          $message = trim($input['message'] ?? '');
          
          if (empty($message)) {
               send_json_response(['success' => false, 'error' => 'Pesan tidak boleh kosong.']);
          }
          
          $stmt_t = $db->prepare("SELECT id FROM chat_threads WHERE id = ? LIMIT 1");
          $stmt_t->execute([$thread_id]);
          if (!$stmt_t->fetch()) {
               send_json_response(['success' => false, 'error' => 'Thread obrolan tidak ditemukan.']);
          }
          
          $db->beginTransaction();
          try {
               $stmt_m = $db->prepare("INSERT INTO chat_messages (thread_id, sender_type, sender_id, message) VALUES (?, 'admin', ?, ?)");
               $stmt_m->execute([$thread_id, $admin_id, $message]);
               
               // Assign thread to this admin if empty
               $stmt_assign = $db->prepare("UPDATE chat_threads SET assigned_admin_id = ?, status = 'open', updated_at = NOW() WHERE id = ? AND assigned_admin_id IS NULL");
               $stmt_assign->execute([$admin_id, $thread_id]);
               
               // Touch updated time anyways
               $stmt_touch = $db->prepare("UPDATE chat_threads SET updated_at = NOW() WHERE id = ?");
               $stmt_touch->execute([$thread_id]);
               
               $db->commit();
               send_json_response(['success' => true]);
          } catch (Exception $e) {
               $db->rollBack();
               send_json_response(['success' => false, 'error' => 'Gagal mengirim balasan chat: ' . $e->getMessage()]);
          }
          break;

     case 'resolve_chat':
          $thread_id = (int)($input['thread_id'] ?? 0);
          
          $stmt_res = $db->prepare("UPDATE chat_threads SET status = 'resolved', updated_at = NOW() WHERE id = ?");
          if ($stmt_res->execute([$thread_id])) {
                write_audit_log($admin_id, 'RESOLVED_CHAT_THREAD', "Menyelesaikan obrolan chat thread #$thread_id");
                send_json_response(['success' => true, 'message' => 'Sesi chat ditandai selesai.']);
          } else {
                send_json_response(['success' => false, 'error' => 'Gagal menutup obrolan.']);
          }
          break;

     default:
          send_json_response(['success' => false, 'error' => 'Aksi admin tidak dikenal.'], 400);
}
