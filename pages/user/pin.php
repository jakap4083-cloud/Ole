<?php
// User Transactions PIN Creation and updating managers page
require_once __DIR__ . '/../../includes/header-helper.php';
require_once __DIR__ . '/../../includes/db.php';

require_login();
$user_id = $_SESSION['user_id'];
$csrf_token = generate_csrf_token();

// Check if user has linked a PIN currently in database
$db = get_db_connection();
$stmt = $db->prepare("SELECT user_id FROM user_pins WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$has_pin = $stmt->fetch();
?>

<div class="space-y-4 fade-in">
     <!-- 1. Top Informer panel -->
     <div class="bg-teal-900 border border-teal-850 rounded-2xl p-4 text-white text-left relative overflow-hidden">
          <div class="absolute -right-16 -bottom-16 w-32 h-32 rounded-full bg-teal-800 opacity-20"></div>
          <span class="block text-[9px] font-mono font-bold text-teal-300 tracking-wider uppercase">Keamanan Transaksi</span>
          <h2 class="font-display font-bold text-base mt-0.5">PIN TRANSAKSI 6 DIGIT</h2>
          <p class="text-[10px] text-teal-200 mt-1 leading-relaxed">PIN Transaksi dibutuhkan sebagai verifikasi autentikasi mutlak setiap kali Anda mengajukan penarikan saldo keluar dari platform. Buat PIN yang unik dan mudah diingat.</p>
     </div>

     <!-- 2. Display of Current PIN setup Status -->
     <div class="bg-white border border-teal-100 rounded-2xl p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] text-left flex items-center justify-between">
          <div class="space-y-0.5">
               <span class="block text-[9px] text-[#5B7774] font-semibold uppercase tracking-wider">Status Keamanan Akun Anda</span>
               <span class="block font-bold text-xs <?php echo $has_pin ? 'text-emerald-700' : 'text-rose-600'; ?>" id="pin-status-lbl">
                    <?php echo $has_pin ? '● TERKUNCI PIN TRANSAKSI (AMAN)' : '● BELUM MEMILIKI PIN TRANSAKSI (SENSITIF)'; ?>
               </span>
          </div>
          <div>
               <span class="bg-teal-50 border border-teal-100 p-2.5 rounded-xl font-mono font-bold text-[#0F766E] text-xs">Security: OK</span>
          </div>
     </div>

     <!-- 3. Setup or Change Form Card -->
     <form id="pin-setup-form" class="space-y-3.5 bg-white p-5 rounded-2xl border border-teal-100 shadow-[0_4px_16px_rgba(15,118,110,0.03)] text-left">
          <?php echo csrf_field(); ?>
          
          <?php if (!$has_pin): ?>
               <!-- MODE: CREATE PIN -->
               <input type="hidden" id="pin-mode" value="setup_pin">
               
               <div>
                    <label class="block text-[10px] font-bold text-[#12302F] mb-1.5 uppercase tracking-wide">Buat Kode PIN Transaksi (6 Digit Angka)</label>
                    <input id="new-pin" type="tel" maxlength="6" required class="w-full h-11 text-center font-mono font-bold tracking-[1em] text-[#0F766E] text-base rounded-xl border border-teal-200 focus:outline-[#0F766E]" placeholder="xxxxxx">
                    <span class="block text-[9px] text-[#5B7774] mt-1">Harus berupa angka numeric bulat, tidak menerima aksen karakter khusus.</span>
               </div>
          <?php else: ?>
               <!-- MODE: CHANGE PIN -->
               <input type="hidden" id="pin-mode" value="change_pin">
               
               <div>
                    <label class="block text-[10px] font-bold text-[#12302F] mb-1.5 uppercase tracking-wide">PIN Transaksi Lama Anda</label>
                    <input id="old-pin" type="tel" maxlength="6" required class="w-full h-11 text-center font-mono font-bold tracking-[1em] text-[#5B7774] text-base rounded-xl border border-teal-100 bg-teal-50/20 focus:outline-[#0F766E]" placeholder="xxxxxx">
               </div>
               
               <div>
                    <label class="block text-[10px] font-bold text-[#12302F] mb-1.5 uppercase tracking-wide">Masukkan PIN Transaksi Baru (6 Digit)</label>
                    <input id="new-pin" type="tel" maxlength="6" required class="w-full h-11 text-center font-mono font-bold tracking-[1em] text-[#0F766E] text-base rounded-xl border border-teal-200 focus:outline-[#0F766E]" placeholder="xxxxxx">
               </div>
          <?php endif; ?>

          <!-- Core verification with account password -->
          <div class="border-t border-teal-50 pt-2">
               <label class="block text-[10px] font-bold text-rose-800 mb-1 uppercase tracking-wide">Kata Sandi Login Untuk Otoritas</label>
               <input id="pin-password" type="password" required class="w-full h-11 px-4 rounded-xl border border-teal-200 bg-teal-50/10 focus:outline-none focus:border-rose-600 text-xs" placeholder="Ketik kata sandi Anda">
          </div>

          <div class="pt-2">
               <button id="pin-submit-btn" type="submit" class="w-full h-11 bg-[#0F766E] hover:bg-teal-800 text-white font-bold rounded-xl text-xs transition-transform active:scale-95 shadow-md flex items-center justify-center">
                    <?php echo $has_pin ? 'Ubah PIN Transaksi Akun' : 'Aktifkan PIN Transaksi'; ?>
               </button>
          </div>
     </form>
</div>

<script>
const pinForm = document.getElementById('pin-setup-form');
if (pinForm) {
     pinForm.addEventListener('submit', async function(e) {
          e.preventDefault();
          
          const mode = document.getElementById('pin-mode').value;
          const csrf = document.querySelector('input[name="csrf_token"]').value;
          
          const payload = {
               action: mode,
               csrf_token: csrf,
               password: document.getElementById('pin-password').value,
               // Extract inputs depending on layout modes
               pin: document.getElementById('new-pin').value.trim(),
               new_pin: document.getElementById('new-pin').value.trim()
          };
          
          if (mode === 'change_pin') {
               payload.old_pin = document.getElementById('old-pin').value.trim();
          }

          if (payload.new_pin.length !== 6 || isNaN(parseInt(payload.new_pin))) {
               showNotification('PIN harus tepat terdiri dari 6 digit angka numerik.', 'error');
               return;
          }

          const btn = document.getElementById('pin-submit-btn');
          btn.disabled = true;
          btn.innerText = 'Mengenkripsikan Kode PIN...';

          try {
               const res = await fetch('/actions/user/index.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
               });
               const r = await res.json();
               if (r.success) {
                    showNotification(r.message || 'Konfigurasi PIN sukses diperbarui!', 'success');
                    setTimeout(() => { window.location.reload(); }, 1200);
               } else {
                    showNotification(r.error || 'Gagal merubah PIN.', 'error');
                    btn.disabled = false;
                    btn.innerText = mode === 'setup_pin' ? 'Aktifkan PIN Transaksi' : 'Ubah PIN Transaksi Akun';
               }
          } catch (err) {
               showNotification('VPS connection fails.', 'error');
               btn.disabled = false;
               btn.innerText = mode === 'setup_pin' ? 'Aktifkan PIN Transaksi' : 'Ubah PIN Transaksi Akun';
          }
     });
}
</script>

<?php
render_footer(true, 'profile');
?>
