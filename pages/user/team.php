<?php
// User Referral Team Statistics view file
require_once __DIR__ . '/../../includes/header-helper.php';
require_once __DIR__ . '/../../includes/referral-helper.php';

require_login();
$user_id = $_SESSION['user_id'];

// Get team specs
$team = get_team_stats($user_id);

// Generate custom referral link matching the registered domain requirements
$ref_link = "https://noxara.page/pages/auth/register.php?ref=" . $user_id;

render_header('Mitra Tim Saya', true, 'team');
?>

<div class="space-y-4 fade-in">
     <!-- 1. Top Card Information Stats -->
     <div class="bg-gradient-to-br from-[#0F766E] to-[#2DD4BF] text-white rounded-2xl p-5 border border-[#B7D6D2]/20 relative overflow-hidden">
          <div class="absolute -right-16 -bottom-16 w-32 h-32 rounded-full bg-white opacity-10"></div>
          
          <span class="block text-[10px] text-teal-300 font-bold uppercase tracking-wider">Mitra & Skema Rabat</span>
          <h2 class="font-display font-bold text-lg text-white mb-4">PENDAPATAN TIM JARINGAN</h2>
          
          <div class="grid grid-cols-2 gap-4 border-b border-teal-800 pb-4 mb-4 text-left">
               <div>
                    <span class="block text-[10px] text-teal-300 font-medium uppercase tracking-wider">Total Komisi Rabat Tim</span>
                    <span class="block font-display font-bold text-xl text-teal-100 font-mono"><?php echo format_currency($team['total_earnings']); ?></span>
               </div>
               <div>
                    <span class="block text-[10px] text-teal-300 font-medium uppercase tracking-wider">Komisi Hari Ini</span>
                    <span class="block font-display font-bold text-xl text-teal-100 font-mono"><?php echo format_currency($team['today_earnings']); ?></span>
               </div>
          </div>

          <div class="grid grid-cols-3 gap-2 text-left">
               <div>
                    <span class="block text-[9px] text-teal-400 font-medium uppercase">Total Anggota</span>
                    <span class="block font-bold text-sm text-teal-100"><?php echo $team['subordinates_count']; ?> Orang</span>
               </div>
               <div>
                    <span class="block text-[9px] text-teal-400 font-medium uppercase">Mitra Aktif</span>
                    <span class="block font-bold text-sm text-emerald-400"><?php echo $team['subordinates_active']; ?> Member</span>
               </div>
               <div>
                    <span class="block text-[9px] text-teal-400 font-medium uppercase">Belum Aktif</span>
                    <span class="block font-bold text-sm text-rose-400"><?php echo $team['subordinates_inactive']; ?> Member</span>
               </div>
          </div>
     </div>

     <!-- 2. Transfer Commission Section -->
     <?php
          $b = get_user_balances($user_id);
     ?>
     <div class="bg-white border border-teal-100 rounded-2xl p-4 flex justify-between items-center shadow-[0_4px_12px_rgba(15,118,110,0.01)]">
          <div class="text-left">
               <span class="block text-[9px] text-[#5B7774] font-semibold uppercase tracking-wider">Saldo Komisi Dapat Dipindahkan</span>
               <span class="block font-display font-bold text-base text-[#0F766E] font-mono mt-0.5"><?php echo format_currency($b['commission_balance']); ?></span>
          </div>
          <?php if ((float)$b['commission_balance'] > 0): ?>
               <button onclick="transferCommissionWallet()" class="h-9 px-4 bg-teal-800 hover:bg-teal-700 text-white rounded-lg text-xs font-bold transition-transform active:scale-95 shadow-sm">
                    Pindahkan ke Utama
               </button>
          <?php else: ?>
               <button disabled class="h-9 px-4 bg-teal-100 text-teal-400 rounded-lg text-xs font-semibold cursor-not-allowed">
                    Kosong
               </button>
          <?php endif; ?>
     </div>

     <!-- 3. Copy Link Invite Section -->
     <div class="bg-white border border-teal-100 rounded-2xl p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] text-left space-y-2.5">
          <h3 class="font-display font-bold text-xs text-[#12302F]">Sistem Tautan Pendaftaran Anda</h3>
          <p class="text-[11px] text-[#5B7774] leading-relaxed">Berbagi kode rujukan unik Anda dengan rekan kerja atau teman dekat. Setiap kali bawahan melakukan pengisian dana atau menyewa mesin tambang, rabat tim rujukan didistribusikan instan ke akun Anda.</p>
          
          <div class="flex gap-2">
               <input id="ref-link-input" type="text" readonly class="flex-1 bg-teal-50/50 border border-teal-200 h-10 px-3 rounded-lg text-[10px] font-mono font-bold text-[#0F766E] focus:outline-none" value="<?php echo $ref_link; ?>">
               <button onclick="copyRefLink()" class="h-10 px-4 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-bold transition-transform active:scale-95">
                    Salin
               </button>
          </div>
     </div>

     <!-- 4. Tabular multi-level layout statistics of subordinates -->
     <div class="bg-white border border-teal-100 rounded-2xl p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] text-left space-y-3">
          <div class="border-b border-teal-50 pb-2">
               <h3 class="font-display font-bold text-xs text-[#12302F]">Daftar Jenjang Rujukan Tingkat</h3>
          </div>

          <div class="space-y-2">
               <!-- Level 1 -->
               <div class="p-3 bg-teal-50/30 rounded-xl border border-teal-100/50 flex justify-between items-center">
                    <div class="space-y-0.5">
                         <span class="block text-xs font-bold text-[#12302F]">Rujukan Tingkat 1 (Langsung)</span>
                         <span class="block text-[10px] text-[#5B7774]">Komisi Rabat Massk: <strong class="text-[#0F766E]">10%</strong></span>
                    </div>
                    <div>
                         <span class="bg-[#0F766E] text-white text-[10px] font-bold px-2.5 py-1 rounded-full"><?php echo count($team['subordinates_by_level'][1]); ?> Anggota</span>
                    </div>
               </div>

               <!-- Level 2 -->
               <div class="p-3 bg-teal-50/30 rounded-xl border border-teal-100/50 flex justify-between items-center">
                    <div class="space-y-0.5">
                         <span class="block text-xs font-bold text-[#12302F]">Rujukan Tingkat 2</span>
                         <span class="block text-[10px] text-[#5B7774]">Komisi Rabat Masuk: <strong class="text-[#0F766E]">5% (Topup) | 4% (Beli)</strong></span>
                    </div>
                    <div>
                         <span class="bg-[#14B8A6] text-white text-[10px] font-bold px-2.5 py-1 rounded-full"><?php echo count($team['subordinates_by_level'][2]); ?> Anggota</span>
                    </div>
               </div>

               <!-- Level 3 -->
               <div class="p-3 bg-teal-50/30 rounded-xl border border-teal-100/50 flex justify-between items-center">
                    <div class="space-y-0.5">
                         <span class="block text-xs font-bold text-[#12302F]">Rujukan Tingkat 3</span>
                         <span class="block text-[10px] text-[#5B7774]">Komisi Rabat Masuk: <strong class="text-[#0F766E]">2% (Topup) | 1% (Beli)</strong></span>
                    </div>
                    <div>
                         <span class="bg-[#2DD4BF] text-teal-900 text-[10px] font-bold px-2.5 py-1 rounded-full"><?php echo count($team['subordinates_by_level'][3]); ?> Anggota</span>
                    </div>
               </div>
          </div>
     </div>
</div>

<script>
function copyRefLink() {
     const input = document.getElementById('ref-link-input');
     input.select();
     input.setSelectionRange(0, 99999);
     navigator.clipboard.writeText(input.value);
     showNotification('Tautan rujukan unik berhasil disalin!', 'success');
}

async function transferCommissionWallet() {
     if (!confirm('Pindahkan seluruh saldo komisi rabat tim Anda ke Saldo Utama?')) return;
     
     const csrf_token = '<?php echo generate_csrf_token(); ?>';
     try {
          const res = await fetch('/actions/user/helpers-ajax.php', {
               method: 'POST',
               headers: {'Content-Type': 'application/json'},
               body: JSON.stringify({
                    action: 'move_commission_to_main',
                    csrf_token: csrf_token
               })
          });
          const r = await res.json();
          if (r.success) {
               showNotification(r.message || 'Sukses dipindahkan.', 'success');
               setTimeout(() => { window.location.reload(); }, 1500);
          } else {
               showNotification(r.error || 'Terjadi kesalahan rute.', 'error');
          }
     } catch (e) {
          showNotification('VPS Timeout.', 'error');
     }
}
</script>

<?php
render_footer(true, 'team');
?>
