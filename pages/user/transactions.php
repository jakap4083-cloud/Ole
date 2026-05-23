<?php
// User LEDGER historical transactions & deposit status lists
require_once __DIR__ . '/../../includes/header-helper.php';
require_once __DIR__ . '/../../includes/db.php';

require_login();
$user_id = $_SESSION['user_id'];

$db = get_db_connection();

// Select all ledger logs linked directly to this account
$stmt = $db->prepare("SELECT * FROM ledger_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 50");
$stmt->execute([$user_id]);
$ledgers = $stmt->fetchAll();

// Select pending deposits or active cashify tickets
$stmt_dep = $db->prepare("SELECT * FROM topups WHERE user_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 5");
$stmt_dep->execute([$user_id]);
$pending_deposits = $stmt_dep->fetchAll();

// Select pending bank withdrawals
$stmt_wd = $db->prepare("SELECT * FROM withdrawals WHERE user_id = ? ORDER BY id DESC LIMIT 15");
$stmt_wd->execute([$user_id]);
$withdrawals = $stmt_wd->fetchAll();
?>

<div class="space-y-4 fade-in">
     <!-- 1. Top Pending Deposit QRIS Tickets -->
     <?php if (!empty($pending_deposits)): ?>
          <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-4 text-left space-y-3.5">
               <div class="flex items-center gap-2 text-amber-900">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    <h3 class="font-display font-bold text-xs">Misi Isi Ulang Tertunda! (Harus Dibayar)</h3>
               </div>
               
               <div class="space-y-2.5">
                    <?php foreach ($pending_deposits as $p): ?>
                         <div class="bg-white rounded-xl p-3 border border-amber-200">
                              <div class="flex justify-between items-start">
                                   <div>
                                        <span class="block text-[9px] text-[#5B7774] font-medium tracking-wide">ID Tiket: #<?php echo $p['id']; ?></span>
                                        <span class="block text-xs font-bold text-teal-900 font-mono mt-0.5"><?php echo format_currency($p['total_amount']); ?></span>
                                        <span class="block text-[9px] text-amber-700 font-medium font-mono">Unik Kode Termasuk: Rp <?php echo $p['unique_nominal']; ?></span>
                                   </div>
                                   <div>
                                        <a href="/pages/user/deposit-pay.php?id=<?php echo $p['id']; ?>" class="inline-flex h-7 px-3.5 bg-amber-600 hover:bg-amber-700 text-white rounded-md text-[10px] font-bold items-center transition-transform active:scale-95 shadow-sm">
                                             Bayar QRIS
                                        </a>
                                   </div>
                              </div>
                              <span class="block text-[9px] text-[#5B7774] border-t border-teal-50 pt-1.5 mt-1.5">Kedaluwarsa: <?php echo date('d-m-Y H:i:s', strtotime($p['expired_at'])); ?></span>
                         </div>
                    <?php endforeach; ?>
               </div>
          </div>
     <?php endif; ?>

     <!-- 2. Ledger historical logs tabber -->
     <div class="bg-white border border-teal-100 rounded-2xl p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] text-left space-y-4">
          <div class="border-b border-teal-50 pb-2 flex justify-between items-center">
               <h3 class="font-display font-bold text-xs text-[#12302F]">Buku Besar Ledger Keuangan</h3>
               <span class="text-[9px] text-teal-800 font-bold bg-teal-50 px-2 py-0.5 rounded-lg">Imunitas Enkripsi</span>
          </div>

          <?php if (empty($ledgers)): ?>
               <div class="text-center py-10">
                    <p class="text-xs text-[#5B7774]">Belum ada mutasi pembukuan di mutasi buku besar Anda.</p>
               </div>
          <?php else: ?>
               <div class="space-y-2.5 max-h-[280px] overflow-y-auto pr-1">
                    <?php foreach ($ledgers as $l): ?>
                         <div class="p-3 bg-teal-50/10 rounded-xl border border-teal-50 flex justify-between items-center text-left text-xs">
                              <div class="space-y-0.5 pr-4">
                                   <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="bg-teal-50 border border-teal-200 text-teal-800 text-[8px] font-bold px-1.5 py-0.5 rounded uppercase font-mono tracking-wider"><?php echo sanitize_output($l['action_type']); ?></span>
                                        <span class="text-[9px] text-[#5B7774] font-medium"><?php echo sanitize_output($l['balance_type']); ?></span>
                                   </div>
                                   <p class="text-[10px] text-[#12302F] leading-relaxed"><?php echo sanitize_output($l['description']); ?></p>
                                   <span class="block text-[8px] text-[#5B7774] font-mono"><?php echo date('Y-m-d H:i:s', strtotime($l['created_at'])); ?></span>
                              </div>
                              <div class="text-right whitespace-nowrap">
                                   <span class="font-mono font-bold text-xs <?php echo ($l['direction'] === 'in') ? 'text-emerald-600' : 'text-rose-600'; ?>">
                                        <?php echo ($l['direction'] === 'in') ? '+' : '-'; ?> <?php echo format_currency($l['amount']); ?>
                                   </span>
                              </div>
                         </div>
                    <?php endforeach; ?>
               </div>
          <?php endif; ?>
     </div>

     <!-- 3. Withdrawals ticket logs -->
     <div class="bg-white border border-teal-100 rounded-2xl p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] text-left space-y-4">
          <div class="border-b border-teal-50 pb-2">
               <h3 class="font-display font-bold text-xs text-[#12302F]">Riwayat Penarikan Rekening</h3>
          </div>

          <?php if (empty($withdrawals)): ?>
               <div class="text-center py-6">
                    <p class="text-xs text-[#5B7774]">Belum ada penelusuran keluar dana denda rujukan.</p>
               </div>
          <?php else: ?>
               <div class="space-y-2.5 max-h-[220px] overflow-y-auto pr-1">
                    <?php foreach ($withdrawals as $w): ?>
                         <div class="p-3 bg-teal-50/10 rounded-xl border border-teal-50 flex flex-col gap-1 text-left text-xs">
                              <div class="flex justify-between items-center">
                                   <div>
                                        <span class="block text-[9px] text-[#5B7774] font-medium font-mono">Kode Tiket WD: #<?php echo $w['id']; ?></span>
                                        <span class="block font-bold text-teal-900 font-mono leading-tight mt-0.5"><?php echo format_currency($w['net_amount']); ?></span>
                                        <span class="block text-[9px] text-[#5B7774] font-mono">Biaya Admin: <?php echo format_currency($w['fee_amount']); ?></span>
                                   </div>
                                   <div>
                                        <!-- Render custom badge values of pending, success, reject -->
                                        <?php if ($w['status'] === 'pending'): ?>
                                             <span class="bg-amber-100 text-amber-800 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase">Diproses</span>
                                        <?php elseif ($w['status'] === 'approved'): ?>
                                             <span class="bg-emerald-100 text-emerald-800 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase">Berhasil</span>
                                        <?php else: ?>
                                             <span class="bg-rose-100 text-rose-800 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase">Ditolak</span>
                                        <?php endif; ?>
                                   </div>
                              </div>

                              <div class="flex justify-between text-[9px] text-[#5B7774] border-t border-teal-50 pt-1 mt-1 flex-wrap gap-2">
                                   <span>Bank: <?php echo sanitize_output($w['bank_name']); ?> (<?php echo sanitize_output($w['account_number']); ?>)</span>
                                   <span><?php echo date('d-m-Y H:i:s', strtotime($w['created_at'])); ?></span>
                              </div>
                              
                              <?php if ($w['status'] === 'rejected' && $w['rejection_reason']): ?>
                                   <div class="mt-1 p-1.5 bg-rose-50 border border-rose-100 rounded text-[9px] text-rose-800 leading-relaxed">
                                        <strong>Alasan Tolak Admin:</strong> <?php echo sanitize_output($w['rejection_reason']); ?>
                                   </div>
                              <?php endif; ?>
                         </div>
                    <?php endforeach; ?>
               </div>
          <?php endif; ?>
     </div>
</div>

<?php
render_footer(true, 'transactions');
?>
