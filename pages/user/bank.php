<?php
// User Bank Accounts Linkage layout page
require_once __DIR__ . '/../../includes/header-helper.php';
require_once __DIR__ . '/../../includes/bank-helper.php';

require_login();
$user_id = $_SESSION['user_id'];
$csrf_token = generate_csrf_token();
$bank = get_user_bank_details($user_id);
?>

<div class="space-y-4 fade-in">
     <!-- 1. Top visual card -->
     <div class="bg-teal-900 border border-teal-850 rounded-2xl p-4 text-white text-left relative overflow-hidden">
          <div class="absolute -right-16 -bottom-16 w-32 h-32 rounded-full bg-teal-800 opacity-20"></div>
          <span class="block text-[9px] font-mono font-bold text-teal-300 tracking-wider uppercase">Penerimaan Rekening</span>
          <h2 class="font-display font-bold text-base mt-0.5">TUTUTKAN AKUN REKENING BANK</h2>
          <p class="text-[10px] text-teal-200 mt-1 leading-relaxed">Tautkan tujuan mutasi penarikan dana Anda secara benar. PERIKSA KEMBALI KESANTUNAN NAMA DAN NOMOR AKUN. Kesalahan pengetikan data bank di luar jaminan kompensasi platform.</p>
     </div>

     <!-- 2. Display of Current Bank Account Linked if any -->
     <?php if ($bank): ?>
          <div class="bg-emerald-950 text-white rounded-xl p-4 border border-emerald-800 text-left font-mono relative overflow-hidden">
               <div class="absolute right-3 top-3">
                    <span class="bg-emerald-800 text-emerald-250 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase">AKTIF</span>
               </div>
               
               <span class="block text-[9px] text-emerald-300 uppercase tracking-wider font-semibold font-sans mb-2">Tersambung di Database</span>
               <div class="space-y-1 text-xs">
                    <div>
                         <span class="text-emerald-400 font-sans block text-[9px] uppercase font-semibold">Nama Bank Pilihan:</span>
                         <span class="font-bold text-sm"><?php echo sanitize_output($bank['bank_name']); ?></span>
                    </div>
                    <div class="pt-1">
                         <span class="text-emerald-400 font-sans block text-[9px] uppercase font-semibold">Nomor Rekening:</span>
                         <span class="font-bold text-sm tracking-wider"><?php echo sanitize_output($bank['account_number']); ?></span>
                    </div>
                    <div class="pt-1">
                         <span class="text-emerald-400 font-sans block text-[9px] uppercase font-semibold">Nama Pemegang Rekening:</span>
                         <span class="font-bold text-sm"><?php echo sanitize_output($bank['account_name']); ?></span>
                    </div>
               </div>
          </div>
     <?php endif; ?>

     <!-- 3. Dynamic setup form -->
     <form id="bank-link-form" class="space-y-3.5 bg-white p-5 rounded-2xl border border-teal-100 shadow-[0_4px_16px_rgba(15,118,110,0.03)] text-left">
          <?php echo csrf_field(); ?>
          
          <!-- Select bank name options list containing indonesia major banks -->
          <div>
               <label class="block text-[10px] font-bold text-[#12302F] mb-1 uppercase tracking-wide">Pilih Bank Pembayaran / E-Wallet</label>
               <select id="bank-name" required class="w-full h-11 px-3 bg-teal-50/20 border border-teal-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-[#0F766E]">
                    <option value="" disabled <?php echo !$bank ? 'selected' : ''; ?>>-- Pilih Bank / Dompet Digital --</option>
                    <option value="BCA" <?php echo ($bank && $bank['bank_name'] === 'BCA') ? 'selected' : ''; ?>>BCA (Bank Central Asia)</option>
                    <option value="MANDIRI" <?php echo ($bank && $bank['bank_name'] === 'MANDIRI') ? 'selected' : ''; ?>>MANDIRI (Bank Mandiri)</option>
                    <option value="BNI" <?php echo ($bank && $bank['bank_name'] === 'BNI') ? 'selected' : ''; ?>>BNI (Bank Negara Indonesia)</option>
                    <option value="BRI" <?php echo ($bank && $bank['bank_name'] === 'BRI') ? 'selected' : ''; ?>>BRI (Bank Rakyat Indonesia)</option>
                    <option value="CIMB" <?php echo ($bank && $bank['bank_name'] === 'CIMB') ? 'selected' : ''; ?>>CIMB NIAGA / CIMB Phone</option>
                    <option value="OVO" <?php echo ($bank && $bank['bank_name'] === 'OVO') ? 'selected' : ''; ?>>OVO (Dompet Digital)</option>
                    <option value="DANA" <?php echo ($bank && $bank['bank_name'] === 'DANA') ? 'selected' : ''; ?>>DANA (Dompet Digital)</option>
                    <option value="GOPAY" <?php echo ($bank && $bank['bank_name'] === 'GOPAY') ? 'selected' : ''; ?>>GOPAY / Gojek Pay</option>
                    <option value="SHOPEEPAY" <?php echo ($bank && $bank['bank_name'] === 'SHOPEEPAY') ? 'selected' : ''; ?>>SHOPEEPAY (Shopee Wallet)</option>
               </select>
          </div>

          <!-- Account number -->
          <div>
               <label class="block text-[10px] font-bold text-[#12302F] mb-1 uppercase tracking-wide">Nomor Rekening / No Telepon Akun</label>
               <input id="bank-acc-num" type="text" required class="w-full h-11 px-4 rounded-xl border border-teal-200 bg-teal-50/10 focus:outline-[#0F766E] text-xs font-mono font-bold text-[#0F766E]" placeholder="Contoh: 812122119" value="<?php echo $bank ? sanitize_output($bank['account_number']) : ''; ?>">
          </div>

          <!-- Account Name -->
          <div>
               <label class="block text-[10px] font-bold text-[#12302F] mb-1 uppercase tracking-wide">Nama Pemilik Rekening (Sesuai Buku Tabungan)</label>
               <input id="bank-acc-name" type="text" required class="w-full h-11 px-4 rounded-xl border border-teal-200 bg-teal-50/10 focus:outline-[#0F766E] text-xs font-semibold" placeholder="CONTOH: JAKSON GUSTI" value="<?php echo $bank ? sanitize_output($bank['account_name']) : ''; ?>">
          </div>

          <!-- Confirm Login Password for Security validation -->
          <div class="border-t border-teal-50 pt-2">
               <label class="block text-[10px] font-bold text-rose-800 mb-1 uppercase tracking-wide">Konfirmasi Sandi Login Akun</label>
               <input id="bank-password" type="password" required class="w-full h-11 px-4 rounded-xl border border-teal-200 bg-teal-50/10 focus:outline-none focus:border-rose-600 text-xs" placeholder="Ketik kata sandi Anda">
          </div>

          <!-- Action Button -->
          <div class="pt-2">
               <button type="submit" id="bank-submit-btn" class="w-full h-11 bg-[#0F766E] hover:bg-teal-800 text-white font-bold rounded-xl text-xs shadow-md transition-transform active:scale-95 flex items-center justify-center">
                    Simpan / Perbarui Data Rekening
               </button>
          </div>
     </form>
</div>

<script>
const bankForm = document.getElementById('bank-link-form');
if (bankForm) {
     bankForm.addEventListener('submit', async function(e) {
         e.preventDefault();
         
         const payload = {
              action: 'setup_bank',
              csrf_token: document.querySelector('input[name="csrf_token"]').value,
              bank_name: document.getElementById('bank-name').value,
              account_number: document.getElementById('bank-acc-num').value.trim(),
              account_name: document.getElementById('bank-acc-name').value.trim(),
              password: document.getElementById('bank-password').value
         };

         const btn = document.getElementById('bank-submit-btn');
         btn.disabled = true;
         btn.innerText = 'Mengamankan Handshake...';

         try {
              const res = await fetch('/actions/user/index.php', {
                  method: 'POST',
                  headers: {'Content-Type': 'application/json'},
                  body: JSON.stringify(payload)
              });
              const r = await res.json();
              if (r.success) {
                   showNotification(r.message || 'Rekening terhubung!', 'success');
                   setTimeout(() => { window.location.reload(); }, 1200);
              } else {
                   showNotification(r.error || 'Gagal menyimpan.', 'error');
                   btn.disabled = false;
                   btn.innerText = 'Simpan / Perbarui Data Rekening';
              }
         } catch (err) {
              showNotification('VPS Timeout.', 'error');
              btn.disabled = false;
              btn.innerText = 'Simpan / Perbarui Data Rekening';
         }
     });
}
</script>

<?php
render_footer(true, 'profile');
?>
