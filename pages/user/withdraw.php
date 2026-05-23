<?php
// User Withdrawal (Tarik Uang) Request processing screen
require_once __DIR__ . '/../../includes/header-helper.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/vip-helper.php';

require_login();
$user_id = $_SESSION['user_id'];
$csrf_token = generate_csrf_token();

$b = get_user_balances($user_id);
$vip = get_user_vip_details($user_id);

// Check linked bank details
$db = get_db_connection();
$stmt = $db->prepare("SELECT * FROM user_bank_accounts WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$bank = $stmt->fetch();

// Check if user has linked transaction pin
$stmt_p = $db->prepare("SELECT user_id FROM user_pins WHERE user_id = ? LIMIT 1");
$stmt_p->execute([$user_id]);
$has_pin = $stmt_p->fetch();
?>

<div class="space-y-4 fade-in">
     <!-- 1. Guide Informational panel -->
     <div class="bg-teal-900 border border-teal-850 rounded-2xl p-4 text-white text-left relative overflow-hidden">
          <div class="absolute -right-16 -bottom-16 w-32 h-32 rounded-full bg-teal-800 opacity-20"></div>
          <span class="block text-[9px] font-mono font-bold text-teal-300 tracking-wider uppercase">Pencairan Dana</span>
          <h2 class="font-display font-bold text-base mt-0.5">PENGAJUAN PENARIKAN SALDO</h2>
          <p class="text-[10px] text-teal-200 mt-1 leading-relaxed">Tarik Saldo Utama Anda ke rekening bank tujuan yang telah divalidasi. Sesuai skema Level Anda (<?php echo sanitize_output($vip['name']); ?>), dikenakan potongan biaya penarikan sebesar <?php echo $vip['withdrawn_fee_percent']; ?>%.</p>
     </div>

     <!-- 2. Balance summary & rules boxes -->
     <div class="bg-white rounded-2xl p-4 border border-teal-100 shadow-[0_4px_12px_rgba(15,118,110,0.01)] text-left grid grid-cols-2 gap-3 pb-3">
          <div>
               <span class="block text-[9px] text-[#5B7774] font-semibold uppercase tracking-wider">Saldo Utama Tersedia</span>
               <span class="block font-bold text-teal-905 font-mono text-base"><?php echo format_currency($b['main_balance']); ?></span>
          </div>
          <div>
               <span class="block text-[9px] text-[#5B7774] font-semibold uppercase tracking-wider">Tingkat Potongan Fee</span>
               <span class="block font-bold text-[#12302F] font-mono text-base"><?php echo $vip['withdrawn_fee_percent']; ?>%</span>
          </div>
          <div class="pt-2 border-t border-teal-50 col-span-2 text-[10px] text-[#5B7774]">
               <span>Syarat Batas Minimal Penarikan: </span>
               <strong class="text-teal-950 font-mono"><?php echo format_currency($vip['min_withdrawal']); ?></strong>
          </div>
     </div>

     <!-- Check if credentials has blocking gaps -->
     <?php if (!$bank): ?>
          <div class="bg-amber-50 border border-amber-200 text-amber-950 rounded-2xl p-5 text-center my-6 space-y-3.5">
               <svg class="w-12 h-12 text-amber-600 mx-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
               </svg>
               <h4 class="font-bold text-xs">Belum Menautkan Akun Bank</h4>
               <p class="text-xs leading-relaxed max-w-[280px] mx-auto">Anda wajib menyambungkan dan memverifikasi detail rekening akun bank pembayaran Anda sebelum melanjutkan.</p>
               <a href="/pages/user/bank.php" class="inline-flex h-9 px-5 bg-teal-850 hover:bg-teal-850 text-white rounded-lg text-xs font-bold items-center transition-transform active:scale-95 shadow-sm">Tautkan Bank Pembayaran Sekarang</a>
          </div>
     <?php elseif (!$has_pin): ?>
          <div class="bg-amber-50 border border-amber-200 text-amber-950 rounded-2xl p-5 text-center my-6 space-y-3.5">
               <svg class="w-12 h-12 text-amber-600 mx-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
               </svg>
               <h4 class="font-bold text-xs">Belum Mengaktifkan PIN Transaksi</h4>
               <p class="text-xs leading-relaxed max-w-[280px] mx-auto">Anda wajib membuat sandi PIN transaksi 6 digit terlebih dahulu demi alasan keamanan dana.</p>
               <a href="/pages/user/pin.php" class="inline-flex h-9 px-5 bg-teal-850 hover:bg-teal-850 text-white rounded-lg text-xs font-bold items-center transition-transform active:scale-95 shadow-sm">Setup PIN Transaksi Transaksi</a>
          </div>
     <?php else: ?>

          <!-- 3. Safe Form Withdraw -->
          <form id="withdraw-post-form" class="space-y-4 bg-white p-5 rounded-2xl border border-teal-100 shadow-[0_4px_16px_rgba(15,118,110,0.03)] text-left">
               <?php echo csrf_field(); ?>
               
               <!-- Destination bank info prefill as read-only card detail -->
               <div class="p-3.5 bg-teal-50/20 border border-teal-100 rounded-xl space-y-0.5">
                    <span class="block text-[9px] text-[#5B7774] uppercase tracking-wider font-semibold">Tujuan Rekening Terdaftar</span>
                    <strong class="block text-xs text-teal-900"><?php echo sanitize_output($bank['bank_name']); ?> — <?php echo sanitize_output($bank['account_number']); ?></strong>
                    <span class="block text-[10px] text-[#12302F]">Atas Nama: <strong><?php echo sanitize_output($bank['account_name']); ?></strong></span>
                    <a href="/pages/user/bank.php" class="block text-[9px] text-[#0F766E] font-semibold hover:underline pt-1">Ganti Rekening Bank Tujuan →</a>
               </div>

               <!-- Input amount -->
               <div>
                    <label class="block text-[10px] font-bold text-[#12302F] mb-1 uppercase tracking-wide">Nominal Penarikan Dana (Rp)</label>
                    <div class="relative">
                         <span class="absolute left-4 top-3 text-sm text-teal-800 font-bold font-mono">Rp</span>
                         <input id="wd-amount-input" type="number" required min="<?php echo (float)$vip['min_withdrawal']; ?>" class="w-full h-11 pl-12 pr-4 rounded-xl border border-teal-200 focus:outline-[#0F766E] text-xs font-mono font-bold text-[#0F766E]" placeholder="Minimal <?php echo number_format($vip['min_withdrawal']); ?>" oninput="calculateRealWithdrawnFee()">
                    </div>
               </div>

               <!-- Live dynamic fee calculations displays -->
               <div class="grid grid-cols-2 gap-3.5 p-3.5 bg-slate-50 border border-teal-50 rounded-xl text-left text-xs font-medium text-[#5B7774]">
                    <div>
                         <span>Potongan Biaya Admin:</span>
                         <span id="withdraw-fee-display" class="block font-bold text-rose-600 font-mono mt-0.5">Rp 0</span>
                    </div>
                    <div>
                         <span>Estimasi Bersih Diterima:</span>
                         <span id="withdraw-net-display" class="block font-bold text-emerald-700 font-mono mt-0.5">Rp 0</span>
                    </div>
               </div>

               <!-- PIN authentication -->
               <div>
                    <label class="block text-[10px] font-bold text-[#12302F] mb-1 uppercase tracking-wide">Masukkan PIN Transaksi (6 Digit)</label>
                    <input id="wd-pin" type="password" maxlength="6" required class="w-full h-11 text-center font-mono font-bold tracking-[1em] text-[#0F766E] text-base rounded-xl border border-teal-200 focus:outline-[#0F766E]" placeholder="xxxxxx">
               </div>

               <!-- Submits button -->
               <div class="pt-2">
                    <button id="wd-submit-btn" type="submit" class="w-full h-11 bg-[#0F766E] hover:bg-teal-800 text-white font-bold rounded-xl text-xs transition-transform active:scale-95 shadow-md flex items-center justify-center">
                         Kirim Pengajuan Penarikan
                    </button>
               </div>
          </form>
     <?php endif; ?>
</div>

<script>
const feePercent = <?php echo $vip ? (float)$vip['withdrawn_fee_percent'] : 0.00; ?>;

function calculateRealWithdrawnFee() {
     const input = parseFloat(document.getElementById('wd-amount-input').value);
     if (isNaN(input) || input <= 0) {
          document.getElementById('withdraw-fee-display').innerText = 'Rp 0';
          document.getElementById('withdraw-net-display').innerText = 'Rp 0';
          return;
     }
     
     const fee = Math.round((input * feePercent) / 100);
     const net = Math.max(0, input - fee);
     
     // Formatter
     const fmt = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });
     document.getElementById('withdraw-fee-display').innerText = fmt.format(fee);
     document.getElementById('withdraw-net-display').innerText = fmt.format(net);
}

const wdForm = document.getElementById('withdraw-post-form');
if (wdForm) {
     wdForm.addEventListener('submit', async function(e) {
         e.preventDefault();
         
         const amount = parseFloat(document.getElementById('wd-amount-input').value);
         const pinVal = document.getElementById('wd-pin').value.trim();
         const csrf = document.querySelector('input[name="csrf_token"]').value;
         
         if (isNaN(amount) || amount <= 0) {
              showNotification('Nominal penarikan tidak valid.', 'error');
              return;
         }

         if (pinVal.length !== 6 || isNaN(parseInt(pinVal))) {
              showNotification('PIN transaksi harus tepat 6 digit angka numerik.', 'error');
              return;
         }

         const btn = document.getElementById('wd-submit-btn');
         btn.disabled = true;
         btn.innerText = 'Memvalidasi Buku Ledger Kontrol...';

         try {
              const res = await fetch('/actions/user/index.php', {
                   method: 'POST',
                   headers: {'Content-Type': 'application/json'},
                   body: JSON.stringify({
                        action: 'create_withdrawal',
                        csrf_token: csrf,
                        amount: amount,
                        pin: pinVal
                   })
              });
              const r = await res.json();
              if (r.success) {
                   showNotification(r.message || 'Pengajuan penarikan sukses!', 'success');
                   setTimeout(() => {
                        window.location.href = '/pages/user/transactions.php';
                   }, 1500);
              } else {
                   showNotification(r.error || 'Gagal menyimpan.', 'error');
                   if (r.redirect) {
                        setTimeout(() => { window.location.href = r.redirect; }, 1500);
                   }
                   btn.disabled = false;
                   btn.innerText = 'Kirim Pengajuan Penarikan';
              }
         } catch (e) {
              showNotification('VPS connection timeout.', 'error');
              btn.disabled = false;
              btn.innerText = 'Kirim Pengajuan Penarikan';
         }
     });
}
</script>

<?php
render_footer(true, 'profile');
?>
