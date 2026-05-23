<?php
// Administrator Central Control Center and approving page dashboard
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Verify is admin session authenticated
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
     header('Location: /pages/admin/login.php');
     exit();
}

$csrf_token = generate_csrf_token();
$db = get_db_connection();

// 1. STATS: Total active users, balances, deposit tickets, and pending withdrawals count
$total_users = $db->query("SELECT COUNT(id) FROM users")->fetchColumn();
$total_deposits = $db->query("SELECT SUM(total_amount) FROM topups WHERE status = 'success'")->fetchColumn() ?: 0;
$total_withdrawals = $db->query("SELECT SUM(amount) FROM withdrawals WHERE status = 'approved'")->fetchColumn() ?: 0;

// 2. LISTS: User listings
$users_list = $db->query("SELECT u.id, u.username, u.email, u.phone_number, u.status, ub.main_balance, ub.locked_balance, ub.profit_balance FROM users u JOIN user_balances ub ON u.id = ub.user_id ORDER BY u.id DESC LIMIT 15")->fetchAll();

// 3. LISTS: Pending Withdrawals requests list awaiting manual admin validation approval / reject
$pending_wds = $db->query("SELECT w.*, u.username FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.status = 'pending' ORDER BY w.id ASC")->fetchAll();

// 4. LISTS: User Bank details linked
$bank_accounts = $db->query("SELECT ba.*, u.username FROM user_bank_accounts ba JOIN users u ON ba.user_id = u.id ORDER BY ba.id DESC LIMIT 10")->fetchAll();

// 5. LISTS: Support aduan chats threads
$support_msgs = $db->query("SELECT sm.*, u.username FROM support_messages sm JOIN users u ON sm.user_id = u.id ORDER BY sm.id DESC LIMIT 15")->fetchAll();

// 6. Settings items
$settings = $db->query("SELECT * FROM settings_features")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Kantor Pusat | NOXARA Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        noxara: {
                            bg: '#EAF5F4',
                            surface: '#F3FAF9',
                            soft: '#D9EEEC',
                            primary: '#0F766E',
                            light: '#14B8A6',
                            accent: '#2DD4BF',
                            text: '#12302F',
                            muted: '#5B7774',
                            border: '#B7D6D2',
                            success: '#16A34A',
                            danger: '#DC2626',
                            warning: '#EA580C'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Space Grotesk', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace']
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #EAF5F4; color: #12302F; font-family: 'Inter', sans-serif; }
        .app-container { max-width: 480px; margin: 0 auto; background-color: #F3FAF9; min-height: 100vh; }
        .toast-notification {
             position: fixed;
             top: 24px;
             left: 50%;
             transform: translateX(-50%) translateY(-100px);
             z-index: 100;
             transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .toast-notification.show {
             transform: translateX(-50%) translateY(0);
        }
        .custom-scroller::-webkit-scrollbar {
             height: 5px;
             width: 4px;
        }
        .custom-scroller::-webkit-scrollbar-thumb {
             background: #0F766E;
             border-radius: 9px;
        }
    </style>
</head>
<body class="bg-slate-900 md:py-4">
    
    <!-- Toast -->
    <div id="toast-wrapper" class="toast-notification flex items-center gap-3 p-4 bg-[#12302F] text-white border border-teal-200 shadow-xl rounded-xl max-w-[340px] w-full text-xs font-semibold">
         <span id="toast-icon" class="inline-block w-2.5 h-2.5 rounded-full bg-amber-400"></span>
         <p id="toast-message" class="flex-1 leading-normal text-left">Sinyal Enkripsi Admin.</p>
    </div>

    <div class="app-container shadow-2xl border-x border-teal-100 min-h-screen p-5 flex flex-col justify-between">
         <div class="space-y-4">
              
              <!-- 1. HEADER SECTION -->
              <div class="flex justify-between items-center border-b border-teal-100 pb-3">
                   <div>
                        <span class="text-[9px] font-mono font-bold text-teal-800 tracking-wider block uppercase">KONSOL VPS SUPERUSER</span>
                        <h2 class="font-display font-bold text-base text-[#0F766E]">Noxara Admin Desk</h2>
                   </div>
                   <div class="flex items-center gap-2">
                        <a href="/actions/admin/logout.php" onclick="return confirm('Keluar dari Dasbor Superuser?')" class="h-8 px-3 bg-rose-50 border border-rose-200 hover:bg-rose-100 text-rose-700 text-[10px] font-bold rounded-lg flex items-center justify-center transition-transform active:scale-95">
                             Lock Root Out
                        </a>
                   </div>
              </div>

              <!-- 2. TOTAL COUNTER BOARD STATS GRID -->
              <div class="grid grid-cols-3 gap-2 text-left">
                   <div class="bg-white border border-teal-100 p-3 rounded-xl shadow-[0_4px_12px_rgba(15,118,110,0.01)] col-span-1">
                        <span class="block text-[8px] text-[#5B7774] font-bold uppercase">Total Mitra</span>
                        <span class="block font-display font-bold text-[#0F766E] text-sm mt-0.5"><?php echo $total_users; ?> Member</span>
                   </div>
                   <div class="bg-white border border-teal-100 p-3 rounded-xl shadow-[0_4px_12px_rgba(15,118,110,0.01)] col-span-1">
                        <span class="block text-[8px] text-[#5B7774] font-bold uppercase">Dana Masuk</span>
                        <span class="block font-display font-mono font-bold text-emerald-700 text-xs mt-0.5"><?php echo format_currency($total_deposits); ?></span>
                   </div>
                   <div class="bg-white border border-teal-100 p-3 rounded-xl shadow-[0_4px_12px_rgba(15,118,110,0.01)] col-span-1">
                        <span class="block text-[8px] text-[#5B7774] font-bold uppercase">Dana Keluar</span>
                        <span class="block font-display font-mono font-bold text-rose-600 text-xs mt-0.5"><?php echo format_currency($total_withdrawals); ?></span>
                   </div>
              </div>

              <!-- 3. APPROVAL SECTION TICKET (MANUAL PENDING WITHDRAWALS) -->
              <div class="bg-white border border-teal-100 rounded-2xl p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] text-left space-y-3">
                   <div class="border-b border-teal-50 pb-1.5 flex justify-between items-center">
                        <h3 class="font-display font-bold text-xs text-rose-800 uppercase tracking-wider">Tiket Withdraw Pending (Menunggu Persetujuan)</h3>
                        <span class="bg-rose-50 border border-rose-200 text-rose-800 text-[9px] font-bold px-2 py-0.5 rounded-lg"><?php echo count($pending_wds); ?> Tiket</span>
                   </div>

                   <?php if (empty($pending_wds)): ?>
                        <div class="text-center py-6">
                             <p class="text-[11px] text-[#5B7774]">Seluruh pengajuan dana keluar telah diselesaikan aman sentosa.</p>
                        </div>
                   <?php else: ?>
                        <div class="space-y-3 max-h-[220px] overflow-y-auto custom-scroller pr-1">
                             <?php foreach ($pending_wds as $w): ?>
                                  <div class="p-3 bg-rose-50/20 border border-rose-100 rounded-xl flex flex-col gap-2">
                                       <div class="flex justify-between items-start text-xs">
                                            <div>
                                                 <span class="block text-[9px] text-rose-950 font-bold">MITRA: <strong class="underline"><?php echo sanitize_output($w['username']); ?></strong> (ID: #<?php echo $w['user_id']; ?>)</span>
                                                 <span class="block font-mono font-bold text-[#0F766E] text-xs mt-0.5"><?php echo format_currency($w['net_amount']); ?></span>
                                                 <span class="block text-[9px] text-[#5B7774] font-mono leading-relaxed mt-0.5">Biaya Admin Potong: <?php echo format_currency($w['fee_amount']); ?> | Bruto: <?php echo format_currency($w['amount']); ?></span>
                                            </div>
                                            <span class="block text-[8px] text-[#5B7774] font-mono"><?php echo date('d-m-Y H:i', strtotime($w['created_at'])); ?></span>
                                       </div>

                                       <div class="bg-white p-2 border border-teal-100 rounded-lg text-[10px] text-teal-900 font-mono">
                                            BANK: <strong><?php echo sanitize_output($w['bank_name']); ?></strong><br>
                                            REK: <strong><?php echo sanitize_output($w['account_number']); ?></strong><br>
                                            NAMA: <strong><?php echo sanitize_output($w['account_name']); ?></strong>
                                       </div>

                                       <!-- Approve / Reject inputs form fields interactive calls -->
                                       <div class="grid grid-cols-2 gap-2 pt-1">
                                            <button onclick="approveWithdrawTicket(<?php echo $w['id']; ?>)" class="h-8 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold shadow transition-transform active:scale-95">Setujui WD</button>
                                            <button onclick="promptRejectWithdrawTicket(<?php echo $w['id']; ?>)" class="h-8 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-[10px] font-bold shadow transition-transform active:scale-95">Tolak WD</button>
                                       </div>
                                  </div>
                             <?php endforeach; ?>
                        </div>
                   <?php endif; ?>
              </div>

              <!-- 4. ACCOUNT CONTROL (FREEZING & ADD BALANCE CONTROLLERS) -->
              <div class="bg-white border border-teal-100 rounded-2xl p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] text-left space-y-4">
                   <div class="border-b border-teal-50 pb-1.5 flex justify-between items-center">
                        <h3 class="font-display font-bold text-xs text-[#12302F] uppercase tracking-wider">Manajemen Rekening Anggota</h3>
                        <span class="text-[9px] bg-teal-50 text-teal-800 font-bold px-2 py-0.5 rounded-lg">Pembekuan Akun</span>
                   </div>

                   <div class="space-y-3 max-h-[250px] overflow-y-auto custom-scroller pr-1">
                        <?php foreach ($users_list as $ul): ?>
                             <div class="p-3 bg-teal-50/10 border border-teal-50 rounded-xl relative">
                                  <div class="absolute right-3 top-3">
                                       <?php if ($ul['status'] === 'active'): ?>
                                            <span class="bg-emerald-100 text-emerald-800 text-[8px] font-bold px-1.5 py-0.5 rounded uppercase">AKTIF</span>
                                       <?php else: ?>
                                            <span class="bg-rose-150 text-rose-800 text-[8px] font-bold px-1.5 py-0.5 rounded uppercase font-black animate-pulse">BEKU</span>
                                       <?php endif; ?>
                                  </div>

                                  <div class="text-xs space-y-0.5 pr-14 select-none">
                                       <strong class="block text-teal-950 font-bold font-sans"><?php echo sanitize_output($ul['username']); ?></strong>
                                       <span class="block text-[9px] text-[#5B7774] font-mono">User ID: #<?php echo $ul['id']; ?> | Phone: <?php echo sanitize_output($ul['phone_number']); ?></span>
                                       <div class="pt-1 text-[10px] text-[#12302F]">
                                            S.Utama: <strong class="font-mono text-[#0F766E]"><?php echo format_currency($ul['main_balance']); ?></strong><br>
                                            S.Tertahan: <strong class="font-mono text-rose-600"><?php echo format_currency($ul['locked_balance']); ?></strong><br>
                                            S.Profit: <strong class="font-mono text-emerald-700"><?php echo format_currency($ul['profit_balance']); ?></strong>
                                       </div>
                                  </div>

                                  <!-- Freeze toggler / Ledger Injection controllers -->
                                  <div class="grid grid-cols-2 gap-2 pt-2 border-t border-teal-50 mt-2">
                                       <button onclick="injectBalanceToUserLedgerModal(<?php echo $ul['id']; ?>, '<?php echo sanitize_output($ul['username']); ?>')" class="h-7 bg-teal-800 hover:bg-teal-700 text-white rounded text-[9px] font-bold transition-transform active:scale-95 shadow-sm">Suntik Saldo</button>
                                       
                                       <?php if ($ul['status'] === 'active'): ?>
                                            <button onclick="toggleUserFreezeStatus(<?php echo $ul['id']; ?>, 'freeze')" class="h-7 bg-rose-50 border border-rose-200 text-rose-700 rounded text-[9px] font-bold transition-transform active:scale-95">Bekukan Akun</button>
                                       <?php else: ?>
                                            <button onclick="toggleUserFreezeStatus(<?php echo $ul['id']; ?>, 'unfreeze')" class="h-7 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded text-[9px] font-bold transition-transform active:scale-95 animate-pulse">Pulihkan Akun</button>
                                       <?php endif; ?>
                                  </div>
                             </div>
                        <?php endforeach; ?>
                   </div>
              </div>

              <!-- 5. CENTRAL TOGGLE SETTINGS PANEL -->
              <div class="bg-white border border-teal-100 rounded-2xl p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] text-left space-y-3">
                   <div class="border-b border-teal-50 pb-1.5 flex justify-between items-center">
                        <h3 class="font-display font-bold text-xs text-[#12302F] uppercase tracking-wider">Pengaturan Saklar Fitur Utama</h3>
                   </div>

                   <div class="space-y-2.5 text-xs">
                        <?php foreach ($settings as $set): ?>
                             <div class="flex justify-between items-center p-2.5 bg-teal-50/25 rounded-lg border border-teal-50">
                                  <div class="space-y-0.5">
                                       <strong class="block text-[#12302F]"><?php echo sanitize_output($set['feature_name']); ?></strong>
                                       <span class="block text-[10px] text-[#5B7774]"><?php echo sanitize_output($set['description_helper']); ?></span>
                                  </div>
                                  <div>
                                       <?php if ($set['is_enabled'] == 1): ?>
                                            <button onclick="toggleFeatureSetting(<?php echo $set['id']; ?>, 0)" class="h-7 px-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-[9px] uppercase transition-transform active:scale-95 shadow">Aktif</button>
                                       <?php else: ?>
                                            <button onclick="toggleFeatureSetting(<?php echo $set['id']; ?>, 1)" class="h-7 px-3.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-[9px] uppercase transition-transform active:scale-95 shadow">Mati</button>
                                       <?php endif; ?>
                                  </div>
                             </div>
                        <?php endforeach; ?>
                   </div>
              </div>

              <!-- 6. LIVE ADUAN CHATS MODULES SCREEN -->
              <div class="bg-white border border-teal-100 rounded-2xl p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] text-left space-y-3.5">
                   <div class="border-b border-teal-50 pb-1 flex justify-between items-center">
                        <h3 class="font-display font-bold text-xs text-rose-800 uppercase tracking-widest">Pantauan Diskusi / Tiket Aduan Support</h3>
                        <span class="text-[9px] font-bold text-[#5B7774]">50 Threads Terbaru</span>
                   </div>

                   <div class="space-y-3 max-h-[220px] overflow-y-auto custom-scroller pr-1">
                        <?php if (empty($support_msgs)): ?>
                             <p class="text-[10px] text-[#5B7774] text-center">Belum ada aduan masuk.</p>
                        <?php else: ?>
                             <?php foreach ($support_msgs as $sm): ?>
                                  <div class="p-2.5 bg-slate-50 border border-teal-50 rounded-lg space-y-1.5 text-xs">
                                       <div class="flex justify-between items-center">
                                            <strong class="font-bold font-sans text-teal-900"><?php echo sanitize_output($sm['username']); ?> (ID: #<?php echo $sm['user_id']; ?>)</strong>
                                            <span class="text-[8px] text-[#5B7774] font-mono"><?php echo date('H:i', strtotime($sm['created_at'])); ?></span>
                                       </div>
                                       <p class="text-[10px] text-[#12302F] leading-normal font-sans"><?php echo sanitize_output($sm['message_text']); ?></p>
                                       
                                       <?php if (!empty($sm['attachment_path'])): ?>
                                            <div class="p-1 rounded bg-teal-50 border border-teal-150 inline-block">
                                                 <a href="<?php echo sanitize_output($sm['attachment_path']); ?>" target="_blank" class="text-[8px] text-[#0F766E] font-mono font-bold hover:underline">Download / Lihat Gambar Lampiran</a>
                                            </div>
                                       <?php endif; ?>

                                       <!-- Reply interface direct submit -->
                                       <div class="flex gap-1 pt-1.5">
                                            <input type="text" id="reply-input-<?php echo $sm['id']; ?>" class="flex-1 h-7 border border-teal-200 bg-white px-2 rounded text-[10px] focus:outline-none" placeholder="Tulis balasan agen resmi...">
                                            <button onclick="submitAdminSupportReply(<?php echo $sm['id']; ?>, <?php echo $sm['user_id']; ?>)" class="h-7 px-3 bg-teal-900 text-white rounded text-[9px] font-bold">Reply</button>
                                       </div>
                                  </div>
                             <?php endforeach; ?>
                        <?php endif; ?>
                   </div>
              </div>

         </div>

         <!-- App Footer Admin Info space spacer -->
         <div class="text-center text-[9px] text-[#5B7774] font-mono border-t border-teal-50 pt-2.5 mt-5 uppercase tracking-wide">
              <span>ROOT CONSOLE AUTHENTICATED SUCCESSFUL</span>
         </div>
    </div>

    <!-- MODAL: ADD EXTRA INJECT LEDGER TO USER MODALS SCREEN -->
    <div id="ledger-inject-modal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm z-50 flex items-end justify-center hidden">
         <div class="bg-white rounded-t-[24px] max-w-[480px] w-full p-6 text-left space-y-4">
              <div class="flex justify-between items-center border-b border-teal-50 pb-2.5">
                   <h3 class="font-display font-bold text-sm text-[#12302F]">Suntik Kredit Saldo Buku Ledger</h3>
                   <button onclick="closeLedgerModal()" class="text-[#5B7774]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                             <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                   </button>
              </div>

              <div>
                   <label class="block text-[9px] text-[#5B7774] font-semibold uppercase">Penerima Sasaran:</label>
                   <strong id="modal-target-username" class="block text-xs text-teal-900 font-bold">Username Sasar</strong>
              </div>

              <div>
                   <label class="block text-[10px] font-bold text-[#12302F] mb-1 uppercase">Pilih Saluran Saldo (Balance Type)</label>
                   <select id="modal-bal-type" class="w-full h-10 px-3 bg-teal-55/10 border border-teal-200 rounded-xl text-xs font-semibold focus:outline-none">
                        <option value="main_balance">SALDO UTAMA (Tersedia)</option>
                        <option value="bonus_balance">SALDO BONUS (Event)</option>
                        <option value="profit_balance">SALDO PROFIT (Mining)</option>
                        <option value="commission_balance">SALDO KOMISI RABAT</option>
                        <option value="locked_balance">LOCK BALANCE (Tertahan)</option>
                   </select>
              </div>

              <div>
                   <label class="block text-[10px] font-bold text-[#12302F] mb-1 uppercase">Masukkan Nominal Kredit (Rp)</label>
                   <input id="modal-amount-input" type="number" class="w-full h-11 px-3 bg-teal-50/10 border border-teal-200 rounded-xl text-xs font-bold font-mono text-[#0F766E]" placeholder="Contoh: 100000">
              </div>

              <div>
                   <label class="block text-[10px] font-bold text-[#12302F] mb-1 uppercase">Ulasan Audit Transaksi (Memo)</label>
                   <input id="modal-memo-input" type="text" class="w-full h-10 px-3 bg-teal-50/10 border border-teal-200 rounded-xl text-xs font-medium" placeholder="Memo: Bonus event pendaftaran / Ganti rugi VPS">
              </div>

              <div class="pt-2">
                   <button onclick="submitDirectSuntikBalance()" class="w-full h-11 bg-teal-950 hover:bg-[#0F766E] text-white font-bold rounded-xl text-xs shadow transition-transform active:scale-95">Setujui & Perbarui Saldo Ledger</button>
              </div>
         </div>
    </div>

    <!-- CORE INTERACTION AJAX FUNCTIONS FOR ADMIN PANEL -->
    <script>
    const csrf = '<?php echo $csrf_token; ?>';
    let currentModalTargetUserId = null;

    function showNotification(msg, type = 'info') {
         const t = document.getElementById('toast-wrapper');
         const tlbl = document.getElementById('toast-message');
         const tbulb = document.getElementById('toast-icon');
         
         tlbl.innerText = msg;
         if (type === 'success') {
              tbulb.className = 'inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-sm';
         } else if (type === 'error') {
              tbulb.className = 'inline-block w-2.5 h-2.5 rounded-full bg-rose-600 shadow-sm';
         } else {
              tbulb.className = 'inline-block w-2.5 h-2.5 rounded-full bg-amber-400 shadow-sm';
         }
         
         t.classList.add('show');
         setTimeout(() => {
              t.classList.remove('show');
         }, 3500);
    }

    // 1. Approve Withdraw ticket
    async function approveWithdrawTicket(id) {
         if (!confirm('Apakah Anda setuju mencairkan keluar dana penarikan ini ke pemilik bank?')) return;
         
         try {
              const res = await fetch('/actions/admin/index.php', {
                   method: 'POST',
                   headers: {'Content-Type': 'application/json'},
                   body: JSON.stringify({
                        action: 'approve_withdraw',
                        csrf_token: csrf,
                        withdrawal_id: id
                   })
              });
              const r = await res.json();
              if (r.success) {
                   showNotification('Withdraw sukses disetujui, dana dikonfirmasi keluar.', 'success');
                   setTimeout(() => { window.location.reload(); }, 1200);
              } else {
                   showNotification(r.error || 'Gagal menyetujui tiket.', 'error');
              }
         } catch(e) {
              showNotification('VPS Timeout.', 'error');
         }
    }

    // 2. Reject Withdraw ticket prompt rules reasons
    async function promptRejectWithdrawTicket(id) {
         const reason = prompt('Masbukkan alasan formal penolakan tiket dana keluar ini:');
         if (reason === null) return;
         if (reason.trim().length === 0) {
              alert('Alasan tolak wajib diisi!');
              return;
         }

         try {
              const res = await fetch('/actions/admin/index.php', {
                   method: 'POST',
                   headers: {'Content-Type': 'application/json'},
                   body: JSON.stringify({
                        action: 'reject_withdraw',
                        csrf_token: csrf,
                        withdrawal_id: id,
                        reason: reason.trim()
                   })
              });
              const r = await res.json();
              if (r.success) {
                   showNotification('WD Sukses Ditolak. Saldo Utama dikembalikan ke akun mitra.', 'success');
                   setTimeout(() => { window.location.reload(); }, 1200);
              } else {
                   showNotification(r.error || 'Gagal menolak.', 'error');
              }
         } catch (e) {
              showNotification('Failure connection.', 'error');
         }
    }

    // 3. User freezes / unfreezes account
    async function toggleUserFreezeStatus(userId, goal) {
         const label = goal === 'freeze' ? 'Membekukan' : 'Memulihkan';
         if (!confirm('Apakah Anda mau melanjutkan prospek ' + label + ' Akun Member ID #' + userId + '?')) return;

         try {
              const res = await fetch('/actions/admin/index.php', {
                   method: 'POST',
                   headers: {'Content-Type': 'application/json'},
                   body: JSON.stringify({
                        action: goal === 'freeze' ? 'freeze_user' : 'unfreeze_user',
                        csrf_token: csrf,
                        user_id: userId
                   })
              });
              const r = await res.json();
              if (r.success) {
                   showNotification('Status akun berhasil diubah!', 'success');
                   setTimeout(() => { window.location.reload(); }, 1200);
              } else {
                   showNotification(r.error || 'Gagal merubah status.', 'error');
              }
         } catch(e) {
              showNotification('Connection problem.', 'error');
         }
    }

    // 4. Suntik injection bal logic Modal
    function injectBalanceToUserLedgerModal(id, username) {
         currentModalTargetUserId = id;
         document.getElementById('modal-target-username').innerText = username + " (User ID: #" + id + ")";
         document.getElementById('modal-amount-input').value = '';
         document.getElementById('modal-memo-input').value = '';
         document.getElementById('ledger-inject-modal').classList.remove('hidden');
    }

    function closeLedgerModal() {
         document.getElementById('ledger-inject-modal').classList.add('hidden');
    }

    async function submitDirectSuntikBalance() {
         const balType = document.getElementById('modal-bal-type').value;
         const amount = parseFloat(document.getElementById('modal-amount-input').value);
         const memo = document.getElementById('modal-memo-input').value.trim();

         if (isNaN(amount) || amount <= 0) {
              alert('Masukkan nominal suntikan dana secara valid.');
              return;
         }

         try {
              const res = await fetch('/actions/admin/index.php', {
                   method: 'POST',
                   headers: {'Content-Type': 'application/json'},
                   body: JSON.stringify({
                        action: 'add_balance',
                        csrf_token: csrf,
                        user_id: currentModalTargetUserId,
                        balance_type: balType,
                        amount: amount,
                        description: memo || "Penambahan Manual khusus oleh administrator"
                   })
              });
              const r = await res.json();
              if (r.success) {
                   showNotification('Suntikan dana terpasang aman di ledger!', 'success');
                   closeLedgerModal();
                   setTimeout(() => { window.location.reload(); }, 1200);
              } else {
                   showNotification(r.error || 'Penolakan suntikan saldo.', 'error');
              }
         } catch(e) {
              showNotification('VPS Timeout.', 'error');
         }
    }

    // 5. Toggle setting features
    async function toggleFeatureSetting(id, is_active) {
         try {
              const res = await fetch('/actions/admin/index.php', {
                   method: 'POST',
                   headers: {'Content-Type': 'application/json'},
                   body: JSON.stringify({
                        action: 'toggle_setting',
                        csrf_token: csrf,
                        setting_id: id,
                        is_enabled: is_active
                   })
              });
              const r = await res.json();
              if (r.success) {
                   showNotification('Saklar sistem diubah!', 'success');
                   setTimeout(() => { window.location.reload(); }, 1200);
              } else {
                   showNotification(r.error || 'Gagal mengubah saklar info.', 'error');
              }
         } catch(err) {
              showNotification('Conn loss.', 'error');
         }
    }

    // 6. Submit admin support chat reply
    async function submitAdminSupportReply(smId, targetUserId) {
         const replyBox = document.getElementById('reply-input-' + smId);
         const valStr = replyBox.value.trim();
         if (valStr.length === 0) return;

         try {
              const res = await fetch('/actions/admin/index.php', {
                   method: 'POST',
                   headers: {'Content-Type': 'application/json'},
                   body: JSON.stringify({
                        action: 'reply_message',
                        csrf_token: csrf,
                        user_id: targetUserId,
                        message_text: valStr
                   })
              });
              const r = await res.json();
              if (r.success) {
                   showNotification('Balasan keluhan terkirim!', 'success');
                   replyBox.value = '';
                   setTimeout(() => { window.location.reload(); }, 1200);
              } else {
                   showNotification(r.error || 'Gagal membalas aduan.', 'error');
              }
         } catch(e) {
              showNotification('VPS socket disconnect.', 'error');
         }
    }
    </script>
</body>
</html>
