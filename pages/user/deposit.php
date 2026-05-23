<?php
// User Deposit (Topup) request page view
require_once __DIR__ . '/../../includes/header-helper.php';
require_once __DIR__ . '/../../includes/settings-helper.php';

require_login();
$user_id = $_SESSION['user_id'];
$csrf_token = generate_csrf_token();
?>

<div class="space-y-4 fade-in">
     <!-- 1. Intro panel -->
     <div class="bg-teal-900 text-white rounded-2xl p-4 border border-teal-850 text-left relative overflow-hidden">
          <div class="absolute -right-16 -bottom-16 w-32 h-32 rounded-full bg-teal-800 opacity-20"></div>
          <span class="block text-[9px] font-mono font-bold text-teal-300 tracking-widest block uppercase">Dana Otomatis</span>
          <h2 class="font-display font-bold text-base mt-0.5">PENGISIAN SALDO UTAMA</h2>
          <p class="text-[11px] text-teal-200 mt-1 pb-1 leading-relaxed">Isi saldo Anda instan menggunakan platform pembayaran multipihak Cashify QRIS asli. Silakan masukkan nominal pengisian (Minimal: Rp 15.000) dan klik Generate Tiket Baru di bawah ini.</p>
     </div>

     <!-- Check deposit feature enabled -->
     <?php if (!is_feature_enabled('deposit')): ?>
          <div class="bg-rose-50 border border-rose-200 rounded-xl p-8 text-center py-12">
               <svg class="w-10 h-10 text-rose-500 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
               </svg>
               <h4 class="font-bold text-xs text-[#12302F]">Sistem Ditutup Sementara</h4>
               <p class="text-[11px] text-[#5B7774]">Administrator VPS sedang melakukan migrasi gateway pembayaran manual s.d otomatis.</p>
          </div>
     <?php else: ?>

          <!-- 2. Form topup -->
          <form id="deposit-init-form" class="space-y-4 bg-white p-5 rounded-2xl border border-teal-100 shadow-[0_4px_16px_rgba(15,118,110,0.03)] text-left">
               <?php echo csrf_field(); ?>
               
               <!-- Custom quick presets grid numbers -->
               <div>
                    <label class="block text-[10px] font-bold text-[#12302F] mb-1.5 uppercase tracking-wide">Pilih Preset Nominal Cepat</label>
                    <div class="grid grid-cols-3 gap-2 text-xs font-bold font-mono">
                         <button type="button" onclick="setNominalPreset(20000)" class="h-10 bg-teal-50 hover:bg-teal-100 border border-teal-200 text-[#0F766E] rounded-lg">Rp 20.000</button>
                         <button type="button" onclick="setNominalPreset(50000)" class="h-10 bg-teal-50 hover:bg-teal-100 border border-teal-200 text-[#0F766E] rounded-lg">Rp 50.000</button>
                         <button type="button" onclick="setNominalPreset(100000)" class="h-10 bg-teal-50 hover:bg-teal-100 border border-teal-200 text-[#0F766E] rounded-lg">Rp 100.000</button>
                         <button type="button" onclick="setNominalPreset(250000)" class="h-10 bg-teal-50 hover:bg-teal-100 border border-teal-200 text-[#0F766E] rounded-lg">Rp 250.000</button>
                         <button type="button" onclick="setNominalPreset(500000)" class="h-10 bg-teal-50 hover:bg-teal-100 border border-teal-200 text-[#0F766E] rounded-lg">Rp 500.000</button>
                         <button type="button" onclick="setNominalPreset(1000000)" class="h-10 bg-teal-50 hover:bg-teal-100 border border-teal-200 text-[#0F766E] rounded-lg">Rp 1.000.000</button>
                    </div>
               </div>

               <!-- Manual Amount Input -->
               <div>
                    <label class="block text-[10px] font-bold text-[#12302F] mb-1.5 uppercase tracking-wide">Nominal Isi Ulang Lainnya</label>
                    <div class="relative">
                         <span class="absolute left-4 top-3 text-sm text-teal-800 font-bold">Rp</span>
                         <input id="deposit-amount-input" type="number" required min="15000" class="w-full h-12 pl-12 pr-4 rounded-xl border border-teal-200 focus:outline-[#0F766E] text-sm font-mono font-bold text-[#0F766E]" placeholder="Minimal Rp 15.000">
                    </div>
               </div>

               <!-- Option Promo Voucher for Topup Bonus percent -->
               <div class="bg-teal-50/30 p-3.5 rounded-xl border border-teal-100 space-y-1">
                    <label class="block text-[10px] font-bold text-[#12302F] uppercase tracking-wide">Gunakan Voucher Pengisian Sembari (Optional)</label>
                    <input id="deposit-voucher" type="text" class="w-full h-9 px-3 bg-white border border-teal-200 rounded-lg text-xs font-mono font-bold uppercase placeholder:font-sans placeholder-shown:font-normal" placeholder="nox_topupxx / EVENT">
               </div>

               <!-- Submits button -->
               <div class="pt-2">
                    <button id="deposit-submit-btn" type="submit" class="w-full h-11 bg-[#0F766E] hover:bg-teal-800 text-white font-bold rounded-xl text-xs shadow-md transition-transform active:scale-95 flex items-center justify-center gap-2">
                         Buat Tiket QRIS Cashify
                    </button>
               </div>
          </form>
     <?php endif; ?>
</div>

<script>
function setNominalPreset(val) {
     document.getElementById('deposit-amount-input').value = val;
}

// Handler post topup
const depForm = document.getElementById('deposit-init-form');
if (depForm) {
     depForm.addEventListener('submit', async function(e) {
          e.preventDefault();
          
          const amount = parseFloat(document.getElementById('deposit-amount-input').value);
          const voucher = document.getElementById('deposit-voucher').value.trim();
          const csrf = document.querySelector('input[name="csrf_token"]').value;
          
          if (isNaN(amount) || amount < 15000) {
               showNotification('Nominal minimal adalah Rp 15.000.', 'error');
               return;
          }
          
          const btn = document.getElementById('deposit-submit-btn');
          btn.disabled = true;
          btn.innerText = 'Menghubungkan Server Cashify V2 API...';
          
          try {
               const r = await fetch('/actions/user/index.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                         action: 'create_deposit',
                         csrf_token: csrf,
                         amount: amount,
                         voucher_code: voucher
                    })
               });
               const data = await r.json();
               if (data.success) {
                    showNotification('Tiket pembayaran QRIS sukses dibentuk!', 'success');
                    setTimeout(() => {
                         window.location.href = '/pages/user/deposit-pay.php?id=' + data.topup_id;
                    }, 1000);
               } else {
                    showNotification(data.error || 'Gagal membentuk tiket.', 'error');
                    btn.disabled = false;
                    btn.innerText = 'Buat Tiket QRIS Cashify';
               }
          } catch (err) {
               showNotification('VPS Timeout.', 'error');
               btn.disabled = false;
               btn.innerText = 'Buat Tiket QRIS Cashify';
          }
     });
}
</script>

<?php
render_footer(true, 'transactions');
?>
