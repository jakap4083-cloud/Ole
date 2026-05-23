<?php
// User Mining Sessions management viewport view file
require_once __DIR__ . '/../../includes/header-helper.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/mining-helper.php';
require_once __DIR__ . '/../../includes/settings-helper.php';

require_login();
$user_id = $_SESSION['user_id'];
$csrf_token = generate_csrf_token();

// Fetch rented active miner machines belonging strictly to this user
$db = get_db_connection();
$stmt = $db->prepare("SELECT up.*, p.name as product_name, p.category as product_category 
                      FROM user_products up 
                      JOIN products p ON up.product_id = p.id 
                      WHERE up.user_id = ? AND up.status = 'active'
                      ORDER BY up.id DESC");
$stmt->execute([$user_id]);
$my_miners = $stmt->fetchAll();

// Fetch latest mining sessions (running or claimable claim types)
$sessions = get_active_mining_sessions($user_id);
?>

<div class="space-y-4 fade-in">
     <!-- 1. Top visual guide header panel -->
     <div class="bg-gradient-to-br from-[#0F766E] to-[#2DD4BF] text-white rounded-2xl p-4 border border-[#B7D6D2]/20 text-left relative overflow-hidden">
          <div class="absolute -right-16 -bottom-16 w-32 h-32 rounded-full bg-white opacity-10"></div>
          <span class="block text-[9px] font-mono font-bold text-teal-300 tracking-widest block uppercase">Hardware Control Room</span>
          <h2 class="font-display font-bold text-base mt-0.5">AKTIVITAS & KLAIM MINING HARIAN</h2>
          <p class="text-[11px] text-teal-200 mt-1 pb-1 leading-relaxed">Putar mesin server mining sewaan Anda setiap harinya di halaman ini untuk memulai penambangan komputasi 24 jam. Jika durasi penambangan selesai, tombol klaim profit harian akan berganti warna menjadi aktif. Klaim segera untuk memasukannya ke Saldo Profit.</p>
     </div>

     <!-- 2. Section: Sewaan Aktif (Belum Dijalankan Sesi Hari Ini) -->
     <div class="bg-white border border-teal-100 rounded-2xl p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] text-left space-y-3.5">
          <div class="border-b border-teal-50 pb-2 flex justify-between items-center">
               <h3 class="font-display font-bold text-xs text-[#12302F]">Sewa Miner Aktif Anda</h3>
               <span class="bg-teal-550/10 text-teal-800 text-[9px] font-bold px-2 py-0.5 rounded-lg"><?php echo count($my_miners); ?> Perangkat</span>
          </div>

          <?php if (empty($my_miners)): ?>
               <div class="text-center py-6">
                    <p class="text-xs text-[#5B7774] leading-relaxed">Anda belum memiliki atau menyewa unit mesin tambang cloud apapun saat ini.</p>
                    <a href="/pages/user/products.php" class="inline-flex mt-3 text-xs font-bold text-[#0F766E] underline hover:text-teal-800">Sewa Unit Hardware Pertama Anda →</a>
               </div>
          <?php else: ?>
               <div class="space-y-3">
                    <?php foreach ($my_miners as $m): ?>
                         <div class="p-3 bg-teal-50/20 rounded-xl border border-teal-100 flex flex-col gap-2">
                              <div class="flex justify-between items-start">
                                   <div>
                                        <h4 class="font-display font-bold text-xs text-[#12302F]"><?php echo sanitize_output($m['product_name']); ?></h4>
                                        <span class="text-[9px] font-mono font-bold text-teal-800 block uppercase">ID Unit: #<?php echo $m['id']; ?></span>
                                   </div>
                                   <div>
                                        <!-- Verify if this user_product_id has a RUNNING mining session right now to avoid duplicate spin attempts -->
                                        <?php
                                             $stmt_spin = $db->prepare("SELECT id FROM mining_sessions WHERE user_product_id = ? AND status = 'running' LIMIT 1");
                                             $stmt_spin->execute([$m['id']]);
                                             $has_running = $stmt_spin->fetch();
                                        ?>
                                        <?php if ($has_running): ?>
                                             <button disabled class="h-8 px-3.5 bg-teal-100 text-teal-500 rounded-lg text-[10px] font-bold cursor-not-allowed">
                                                  Sedang Bekerja
                                             </button>
                                        <?php else: ?>
                                             <button onclick="spinMinerPerangkat(<?php echo $m['id']; ?>)" class="h-8 px-3.5 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-[10px] font-bold transition-transform active:scale-95 shadow-sm">
                                                  Putar Sesi 24 Jam
                                             </button>
                                        <?php endif; ?>
                                   </div>
                              </div>

                              <div class="flex justify-between text-[10px] text-[#5B7774] border-t border-teal-50 pt-1.5 font-medium">
                                   <span>Profit / Hari: <strong class="text-emerald-700 font-mono font-bold"><?php echo format_currency($m['profit_per_day']); ?></strong></span>
                                   <span>Sisa Kontrak: <strong class="text-[#12302F]">s.d <?php echo date('d-m-Y', strtotime($m['active_until'])); ?></strong></span>
                              </div>
                         </div>
                    <?php endforeach; ?>
               </div>
          <?php endif; ?>
     </div>

     <!-- 3. Section: Sesi Penambangan Berjalan (Claimables) -->
     <div class="bg-white border border-teal-100 rounded-2xl p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] text-left space-y-3.5">
          <div class="border-b border-teal-50 pb-1.5">
               <h3 class="font-display font-bold text-xs text-[#12302F]">Sesi Komputasi Cloud Saat Ini</h3>
          </div>

          <?php if (empty($sessions)): ?>
               <div class="text-center py-6">
                    <p class="text-xs text-[#5B7774] leading-relaxed">Tidak ada sesi penambangan yang sedang berjalan atau siap diklaim profitnya hari ini.</p>
               </div>
          <?php else: ?>
               <div class="space-y-3">
                    <?php foreach ($sessions as $s): ?>
                         <div class="p-3 bg-emerald-50/40 border border-emerald-100 rounded-xl relative">
                              <h4 class="font-display font-bold text-xs text-emerald-950"><?php echo sanitize_output($s['product_name'] ?? 'Miner Unit'); ?></h4>
                              
                              <div class="grid grid-cols-2 gap-2 text-[10px] text-emerald-900 border-b border-emerald-100 border-dotted pb-2 mb-2 mt-1">
                                    <div>
                                         <span>Hasil Pendapatan Harian:</span>
                                         <span class="block font-mono font-bold text-emerald-700 text-xs mt-0.5"><?php echo format_currency($s['profit_amount']); ?></span>
                                    </div>
                                    <div>
                                         <span>Status Pemutaran:</span>
                                         <span class="block font-bold mt-0.5 uppercase tracking-wide <?php echo ($s['status'] === 'running') ? 'text-teal-600' : 'text-emerald-600'; ?>">
                                              <?php echo ($s['status'] === 'running') ? 'Berlangsung' : 'Klaim Siap'; ?>
                                         </span>
                                    </div>
                              </div>

                              <div class="flex items-center justify-between">
                                    <div class="text-[9px] text-[#5B7774]">
                                         <?php if ($s['status'] === 'running'): ?>
                                              <span>Selesai pada: <strong class="font-mono"><?php echo date('Y-m-d H:i:s', strtotime($s['ends_at'])); ?></strong></span>
                                         <?php else: ?>
                                              <span class="text-emerald-700 font-bold">Durasi komputasi harian selesai!</span>
                                         <?php endif; ?>
                                    </div>
                                    
                                    <div>
                                         <?php if ($s['status'] === 'running'): ?>
                                              <!-- Countdown simulator or inert button -->
                                              <button disabled class="h-7 px-3 bg-amber-500/20 text-amber-700 border border-amber-300 rounded-md text-[9px] font-bold cursor-not-allowed">
                                                   Menambang...
                                              </button>
                                         <?php else: ?>
                                              <button onclick="claimMinerProfitSesi(<?php echo $s['id']; ?>)" class="h-7 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-[9px] font-bold transition-transform active:scale-95 shadow-sm">
                                                   Klaim Profit
                                              </button>
                                         <?php endif; ?>
                                    </div>
                              </div>
                         </div>
                    <?php endforeach; ?>
               </div>
          <?php endif; ?>
     </div>
</div>

<script>
async function spinMinerPerangkat(user_product_id) {
     const csrf_token = '<?php echo $csrf_token; ?>';
     try {
          const res = await fetch('/actions/user/index.php', {
               method: 'POST',
               headers: {'Content-Type': 'application/json'},
               body: JSON.stringify({
                    action: 'start_mining',
                    csrf_token: csrf_token,
                    user_product_id: user_product_id
               })
          });
          const r = await res.json();
          if (r.success) {
               showNotification(r.message || 'Sukses menyalakan unit!', 'success');
               setTimeout(() => { window.location.reload(); }, 1500);
          } else {
               showNotification(r.error || 'Gagal memulai pemutaran harian.', 'error');
          }
     } catch (e) {
          showNotification('VPS Timeout.', 'error');
     }
}

async function claimMinerProfitSesi(session_id) {
     const csrf_token = '<?php echo $csrf_token; ?>';
     try {
          const res = await fetch('/actions/user/index.php', {
               method: 'POST',
               headers: {'Content-Type': 'application/json'},
               body: JSON.stringify({
                    action: 'claim_mining',
                    csrf_token: csrf_token,
                    session_id: session_id
               })
          });
          const r = await res.json();
          if (r.success) {
               showNotification(r.message || 'Profit berhasil dipindahkan ke dompet!', 'success');
               setTimeout(() => { window.location.reload(); }, 1500);
          } else {
               showNotification(r.error || 'Gagal mengklaim profit harian.', 'error');
          }
     } catch (e) {
          showNotification('VPS Connection drop.', 'error');
     }
}
</script>

<?php
render_footer(true, 'mining');
?>
