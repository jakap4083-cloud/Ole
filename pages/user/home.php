<?php
// User Homepage Dashboard view file
require_once __DIR__ . '/../../includes/header-helper.php';
require_once __DIR__ . '/../../includes/ledger-helper.php';
require_once __DIR__ . '/../../includes/settings-helper.php';
require_once __DIR__ . '/../../includes/vip-helper.php';

require_login();
$user_id = $_SESSION['user_id'];

// Get wallet balance calculations
$b = get_user_balances($user_id);
$vip = get_user_vip_details($user_id);

// System promo dynamic sliders or messages
$banners = [];
try {
     $db = get_db_connection();
     $stmt = $db->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 3");
     $banners = $stmt->fetchAll();
} catch (Exception $e) {}

if (empty($banners)) {
     $banners = [
         ['title' => 'Hadiah Sambutan Pendaftaran', 'details' => 'Dapatkan bonus pendaftaran gratis Rp 10.000 terintegrasi ke saldo bonus Anda sekarang.'],
         ['title' => 'Perlindungan Buku Besar Ganda', 'details' => 'Platform komersial dengan keamanan anti manipulasi mutlak. Aman nyaman menguntungkan.']
     ];
}

render_header('Beranda Utama', true, 'home');
?>

<!-- 1. Header Branded Bar -->
<div class="bg-gradient-to-br from-[#0F766E] to-[#2DD4BF] text-white px-5 pt-8 pb-14 rounded-b-[40px] relative overflow-hidden border-b border-[#B7D6D2]/20">
    <!-- Bubble background decoration -->
    <div class="absolute -right-20 -top-20 w-44 h-44 rounded-full bg-white opacity-10"></div>
    <div class="absolute -left-16 -bottom-16 w-36 h-36 rounded-full bg-white opacity-10"></div>

    <div class="flex justify-between items-start z-10 relative">
        <div>
             <span class="block text-[10px] text-teal-300 font-bold uppercase tracking-wider">Investasi Berintegritas</span>
             <h2 class="font-display font-bold text-xl text-white tracking-tight">NOXARA PLATFORM</h2>
        </div>
        <div class="flex items-center gap-2">
             <div class="bg-teal-800/80 px-2.5 py-1 rounded-full border border-teal-700 flex items-center gap-1.5 backdrop-blur-sm">
                  <!-- Star indicator VIP -->
                  <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                  <span class="text-[10px] font-bold text-teal-100 uppercase tracking-widest"><?php echo sanitize_output($vip['name']); ?></span>
             </div>
        </div>
    </div>

    <!-- VIP progress stats bar if any -->
    <div class="mt-4 text-[10px] text-teal-200 z-10 relative font-medium">
         <span>Total Pengisian Dana Sukses Anda: </span>
         <span class="font-bold underline text-white font-mono"><?php echo format_currency($vip['approved_topup_total']); ?></span>
    </div>
</div>

<!-- 2. Elevated Interactive Wallet Card Panel floating -->
<div class="px-5 -mt-8 relative z-20">
    <div class="bg-white rounded-2xl p-5 border border-teal-100 shadow-[0_12px_24px_rgba(15,118,110,0.06)] space-y-4">
         <div class="flex justify-between items-center border-b border-teal-50 pb-3">
              <div>
                   <span class="block text-[10px] text-[#5B7774] font-semibold uppercase tracking-wider">Saldo Utama Tersedia</span>
                   <span class="block font-display font-bold text-2xl text-[#0F766E] font-mono leading-tight mt-0.5"><?php echo format_currency($b['main_balance']); ?></span>
              </div>
              <div class="text-right">
                   <span class="block text-[10px] text-[#5B7774] font-semibold uppercase tracking-wider">locked / tertahan</span>
                   <span class="block text-xs font-semibold text-rose-600 font-mono mt-0.5"><?php echo format_currency($b['locked_balance']); ?></span>
              </div>
         </div>

         <!-- Display multi category wallet channels for transparent layout -->
         <div class="grid grid-cols-2 gap-3 pb-1 text-left">
              <div class="bg-teal-50/40 p-2.5 rounded-xl border border-teal-100/50">
                   <span class="block text-[9px] text-[#5B7774] font-semibold uppercase tracking-wider">Saldo Bonus (Event)</span>
                   <span class="block text-sm font-bold text-[#12302F] font-mono mt-0.5"><?php echo format_currency($b['bonus_balance']); ?></span>
              </div>
              <div class="bg-emerald-50/40 p-2.5 rounded-xl border border-emerald-100/50">
                   <span class="block text-[9px] text-emerald-800 font-semibold uppercase tracking-wider">Saldo Profit (Tambang)</span>
                   <span class="block text-sm font-bold text-emerald-700 font-mono mt-0.5"><?php echo format_currency($b['profit_balance']); ?></span>
              </div>
         </div>

         <!-- Action Financial Buttons Grid Grid -->
         <div class="grid grid-cols-2 gap-3 pt-1">
              <?php if (is_feature_enabled('deposit')): ?>
              <a href="/pages/user/deposit.php" class="h-11 bg-[#0F766E] hover:bg-teal-800 text-white rounded-xl text-xs font-bold shadow-md shadow-teal-700/10 flex items-center justify-center gap-2 transform active:scale-95 transition-transform">
                   <!-- Topup Icon inline -->
                   <svg class="w-4 h-4 text-teal-100" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                   </svg>
                   Isi Ulang Saldo
              </a>
              <?php endif; ?>

              <?php if (is_feature_enabled('withdraw')): ?>
              <a href="/pages/user/withdraw.php" class="h-11 bg-[#14B8A6] hover:bg-[#0F766E] text-white rounded-xl text-xs font-bold shadow-md flex items-center justify-center gap-2 transform active:scale-95 transition-transform">
                   <!-- Withdraw Icon inline -->
                   <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                   </svg>
                   Tarik Uang
              </a>
              <?php endif; ?>
         </div>
    </div>
</div>

<!-- Extra functional buttons list (Daily Checkin Absen, Claim Voucher, VIP Games) -->
<div class="px-5 mt-5 grid grid-cols-3 gap-2.5 text-center">
     <?php if (is_feature_enabled('daily_bonus')): ?>
     <a href="/pages/user/daily-checkin.php" class="bg-white rounded-xl p-3 border border-teal-100 shadow-[0_4px_12px_rgba(15,118,110,0.02)] block hover:bg-teal-50/50">
          <div class="bg-teal-50 w-9 h-9 rounded-lg flex items-center justify-center mx-auto mb-1.5 border border-teal-100 text-[#0F766E]">
               <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
               </svg>
          </div>
          <span class="text-[10px] font-bold text-[#12302F]">Absen Absensi</span>
     </a>
     <?php endif; ?>

     <?php if (is_feature_enabled('voucher')): ?>
     <a href="/pages/user/voucher.php" class="bg-white rounded-xl p-3 border border-teal-100 shadow-[0_4px_12px_rgba(15,118,110,0.02)] block hover:bg-teal-50/50">
          <div class="bg-teal-50 w-9 h-9 rounded-lg flex items-center justify-center mx-auto mb-1.5 border border-teal-100 text-[#0F766E]">
               <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
               </svg>
          </div>
          <span class="text-[10px] font-bold text-[#12302F]">Klaim Voucher</span>
     </a>
     <?php endif; ?>

     <?php if (is_feature_enabled('game')): ?>
     <a href="/pages/user/games.php" class="bg-white rounded-xl p-3 border border-teal-100 shadow-[0_4px_12px_rgba(15,118,110,0.02)] block hover:bg-teal-50/50">
          <div class="bg-teal-50 w-9 h-9 rounded-lg flex items-center justify-center mx-auto mb-1.5 border border-teal-100 text-[#0F766E]">
               <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
               </svg>
          </div>
          <span class="text-[10px] font-bold text-[#12302F]">VIP Games</span>
     </a>
     <?php endif; ?>
</div>

<!-- 3. Dynamic Promo Slideshow -->
<div class="px-5 mt-5">
     <div class="bg-teal-900 border border-teal-800 rounded-2xl p-4 text-white relative overflow-hidden">
          <span class="text-[9px] font-mono font-bold text-teal-300 tracking-widest block uppercase mb-1">Promo Terhangat</span>
          
          <!-- Loop show single banner on home -->
          <?php foreach ($banners as $idx => $ban): ?>
               <div class="space-y-1 block <?php echo ($idx === 0) ? '' : 'hidden'; ?>">
                    <h4 class="font-bold text-xs text-white leading-tight"><?php echo sanitize_output($ban['title']); ?></h4>
                    <p class="text-[10px] text-teal-200 leading-relaxed"><?php echo sanitize_output($ban['details'] ?? 'Klik selengkapnya untuk syarat klaim bonus rujukan VIP.'); ?></p>
               </div>
          <?php endforeach; ?>
     </div>
</div>

<!-- 4. Quick stats summary dashboard (Transparency highlight) -->
<div class="px-5 mt-5">
     <div class="bg-white border border-teal-100 rounded-2xl p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] space-y-3">
          <div class="flex justify-between items-center border-b border-teal-50 pb-2">
               <h3 class="font-display font-bold text-xs text-[#12302F]">Statistik Tim & Saldo Anda</h3>
               <span class="text-[10px] text-[#5B7774] font-medium font-mono">ID: #<?php echo $user_id; ?></span>
          </div>

          <?php
               require_once __DIR__ . '/../../includes/referral-helper.php';
               $team = get_team_stats($user_id);
          ?>
          <div class="grid grid-cols-2 gap-3.5 text-left text-xs">
               <div class="space-y-0.5">
                    <span class="block text-[9px] text-[#5B7774] font-semibold uppercase tracking-wider">Bonus Komisi Tim</span>
                    <span class="block font-bold text-teal-900 font-mono"><?php echo format_currency($team['total_earnings']); ?></span>
               </div>
               <div class="space-y-0.5">
                    <span class="block text-[9px] text-[#5B7774] font-semibold uppercase tracking-wider">Bawahan Terdaftar</span>
                    <span class="block font-bold text-[#12302F]"><?php echo $team['subordinates_count']; ?> Mitra</span>
               </div>
          </div>
     </div>
</div>

<!-- 5. Interactive Profit Transferred Module Box -->
<div class="px-5 mt-5">
     <div class="bg-emerald-850/10 text-emerald-950 border border-emerald-200/60 rounded-2xl p-4 flex justify-between items-center bg-teal-50/50">
          <div class="space-y-0.5">
               <span class="block text-[10px] text-emerald-800 font-semibold uppercase tracking-wider">Hasil Profit Tambang (Claimable)</span>
               <span class="block font-display font-bold text-sm text-emerald-900 font-mono"><?php echo format_currency($b['profit_balance']); ?></span>
          </div>
          
          <!-- Transfer Button to convert profit to main spending balance -->
          <?php if ((float)$b['profit_balance'] > 0): ?>
               <button onclick="transferProfitToMainWallet()" class="h-9 px-4 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-xs font-bold transition-all transition-transform active:scale-95 shadow-sm">
                    Pindahkan ke Utama
               </button>
          <?php else: ?>
               <button disabled class="h-9 px-4 bg-emerald-200 text-emerald-500 rounded-lg text-xs font-bold cursor-not-allowed">
                    Kosong
               </button>
          <?php endif; ?>
</div>

<script>
async function transferProfitToMainWallet() {
     if (!confirm('Pindahkan seluruh saldo profit tambang Anda ke Saldo Utama? Saldo Utama dapat ditarik ataupun diinvestasikan kembali.')) return;
     
     const csrf = '<?php echo $csrf_token; ?>';
     
     // Trigger custom claim ledger action
     try {
          const res = await fetch('/actions/user/helpers-ajax.php', {
               method: 'POST',
               headers: {'Content-Type': 'application/json'},
               body: JSON.stringify({
                    action: 'move_profit_to_main',
                    csrf_token: csrf
               })
          });
          const data = await res.json();
          if (data.success) {
               showNotification(data.message || 'Saldo profit sukses dipindahkan.', 'success');
               setTimeout(() => { window.location.reload(); }, 1500);
          } else {
               showNotification(data.error || 'Gagal memindahkan.', 'error');
          }
     } catch (e) {
          showNotification(' VPS Connection timeout.', 'error');
     }
}
</script>

<?php
render_footer(true, 'home');
?>
