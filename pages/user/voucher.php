<?php
// User Direct Voucher Balance claim visual screen page
require_once __DIR__ . '/../../includes/header-helper.php';
require_once __DIR__ . '/../../includes/settings-helper.php';

require_login();
$user_id = $_SESSION['user_id'];
$csrf_token = generate_csrf_token();
?>

<div class="space-y-4 fade-in">
     <!-- 1. Intro guide panel -->
     <div class="bg-teal-900 border border-teal-850 rounded-2xl p-4 text-white text-left relative overflow-hidden">
          <div class="absolute -right-16 -bottom-16 w-32 h-32 rounded-full bg-teal-800 opacity-20"></div>
          <span class="block text-[9px] font-mono font-bold text-teal-300 tracking-wider uppercase">Enkripsi Kode Promo</span>
          <h2 class="font-display font-bold text-base mt-0.5">KLAIM VOUCHER TUNAI DANA</h2>
          <p class="text-[10px] text-teal-200 mt-1 leading-relaxed">Mendapatkan kode promo voucher event khusus dari program rujukan Anda? Masukan kode voucher unik Anda di bawah ini untuk mencairkan saldo tunai cuma-cuma instan berkredit ke Saldo Utama / Saldo Bonus Anda.</p>
     </div>

     <!-- Check voucher toggle settings features -->
     <?php if (!is_feature_enabled('voucher')): ?>
          <div class="bg-rose-50 border border-rose-200 rounded-xl p-6 text-center py-12">
               <svg class="w-10 h-10 text-rose-500 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
               </svg>
               <h4 class="font-bold text-xs text-[#12302F]">Program Voucher Ditutup</h4>
               <p class="text-[11px] text-[#5B7774]">Administrator sedang menutup pembagian kuota voucher bulanan sementara waktu.</p>
          </div>
     <?php else: ?>

          <!-- 2. Claims Form ticket config page -->
          <form id="voucher-post-claim-form" class="space-y-4 bg-white p-5 rounded-2xl border border-teal-100 shadow-[0_4px_16px_rgba(15,118,110,0.03)] text-left">
               <?php echo csrf_field(); ?>
               
               <div>
                    <label class="block text-[10px] font-bold text-[#12302F] mb-1.5 uppercase tracking-wide">Masukkan Kode Kupon Voucher</label>
                    <input id="voucher-code-input" type="text" required class="w-full h-11 px-4 text-center font-mono font-bold uppercase text-teal-900 tracking-wider text-sm rounded-xl border border-teal-200 focus:outline-[#0F766E]" placeholder="MISAL: EXCLUSIVE_nox">
               </div>

               <div class="bg-amber-50 rounded-xl p-3 border border-amber-200 text-[10px] text-amber-900 leading-relaxed font-sans">
                    <strong>Penting:</strong> Kode voucher bersifat sekali pakai (*single redeemable*), memiliki batas masa daluarsa periodik, dan kuota penerima terbatas ditentukan admin.
               </div>

               <div class="pt-2">
                    <button id="voucher-submit-btn" type="submit" class="w-full h-11 bg-[#0F766E] hover:bg-teal-800 text-white font-bold rounded-xl text-xs shadow-md transition-transform active:scale-95 flex items-center justify-center">
                         Cairkan Kode Kupon Tunjangan
                    </button>
               </div>
          </form>
     <?php endif; ?>
</div>

<script>
const vForm = document.getElementById('voucher-post-claim-form');
if (vForm) {
     vForm.addEventListener('submit', async function(e) {
          e.preventDefault();
          
          const codeVal = document.getElementById('voucher-code-input').value.trim();
          const csrf = document.querySelector('input[name="csrf_token"]').value;
          
          if (codeVal.length === 0) return;

          const btn = document.getElementById('voucher-submit-btn');
          btn.disabled = true;
          btn.innerText = 'Mengautentifikasi Kode Kriptografis Voucher...';

          try {
               const res = await fetch('/actions/user/index.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                         action: 'claim_voucher_wallet',
                         csrf_token: csrf,
                         voucher_code: codeVal
                    })
               });
               const r = await res.json();
               if (r.success) {
                    showNotification(r.message || 'Sukses mencairkan saldo voucher!', 'success');
                    document.getElementById('voucher-code-input').value = '';
                    setTimeout(() => { window.location.href = '/pages/user/home.php'; }, 1500);
               } else {
                    showNotification(r.error || 'Voucher tidak valid.', 'error');
                    btn.disabled = false;
                    btn.innerText = 'Cairkan Kode Kupon Tunjangan';
               }
          } catch (e) {
               showNotification('Connection Fail.', 'error');
               btn.disabled = false;
               btn.innerText = 'Cairkan Kode Kupon Tunjangan';
          }
     });
}
</script>

<?php
render_footer(true, 'home');
?>
